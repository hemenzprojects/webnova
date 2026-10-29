<?php

namespace App\Plugins\Membership\Http;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MemberDocument;
use App\Plugins\Membership\Models\MemberProfile;
use App\Plugins\Membership\Models\MemberResource;
use App\Plugins\Membership\Support\ApplicationFiles;
use App\Plugins\Membership\Support\MembershipSettings;
use App\Plugins\Membership\Support\PrivateFiles;
use App\Plugins\Membership\Support\MemberAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * The member portal's sign-in and account endpoints (Membership plugin,
 * "Member accounts" feature). Session-based on the "member" guard, so the
 * routes run with the "web" middleware and the website sends the XSRF token.
 */
class MemberAccountController extends Controller
{
    public function __construct(private MemberAccounts $accounts) {}

    /** The signed-in member, or null. Also hands the browser its XSRF cookie. */
    public function show(): JsonResponse
    {
        $member = $this->member();

        return response()->json(['member' => $member?->toPortalArray()])->header('Cache-Control', 'no-store');
    }

    public function login(Request $request): JsonResponse
    {
        // "email" is the member's email or their Member ID (e.g. APBA0042)
        $data = $request->validate([
            'email' => 'required|string|max:255',
            'password' => 'required|string',
            'remember' => 'boolean',
        ], ['email.required' => 'Enter your email or Member ID.']);

        $login = Str::lower(trim($data['email']));
        $throttleKey = 'member-login:' . $login . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Please try again in ' . ceil(RateLimiter::availableIn($throttleKey) / 60) . ' minute(s).',
            ]);
        }

        $credentials = str_contains($login, '@')
            ? ['email' => $login]
            : ['member_number' => strtoupper(preg_replace('/\s+/', '', $login))];
        if (! Auth::guard('member')->attempt($credentials + ['password' => $data['password']], (bool) ($data['remember'] ?? false))) {
            RateLimiter::hit($throttleKey, 300);
            throw ValidationException::withMessages(['email' => 'The email, Member ID or password is not correct.']);
        }

        RateLimiter::clear($throttleKey);
        // A new session id on sign-in; the admin login in the same session (if any) stays
        $request->session()->regenerate();

        /** @var MemberAccount $member */
        $member = Auth::guard('member')->user();
        $member->forceFill(['last_login_at' => now()])->save();

        return response()->json(['member' => $member->toPortalArray()]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('member')->logout();
        // Not invalidate(): that would also sign an admin out of /admin
        $request->session()->regenerateToken();

        return response()->json(['member' => null]);
    }

    /** Always answers the same, so the form cannot be used to find out who is a member */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => 'required|string|max:255'], ['email.required' => 'Enter your email or Member ID.']);

        $login = Str::lower(trim($data['email']));
        $account = str_contains($login, '@')
            ? MemberAccount::where('email', $login)->first()
            : MemberAccount::where('member_number', strtoupper(preg_replace('/\s+/', '', $login)))->first();
        if ($account) {
            $this->accounts->sendPasswordReset($account, $request->getSchemeAndHttpHost());
        }

        return response()->json(['message' => 'If this belongs to a member account, we have sent a link to choose a new password to the email on that account.']);
    }

    /** Set a password from the welcome email or a reset email, then sign in */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'consent' => 'boolean',
        ]);

        $account = MemberAccount::where('email', Str::lower(trim($data['email'])))->first();
        // First sign-in (or a changed notice): agree to the privacy notice while setting the password
        if ($account && ! $account->hasCurrentConsent() && ! ($data['consent'] ?? false)) {
            throw ValidationException::withMessages(['consent' => 'Please confirm that you have read and agree to the privacy notice.']);
        }
        $invalid = ValidationException::withMessages(['password' => 'This link has expired or was already used. Ask for a new one with "Forgot your password?".']);
        if (! $account) {
            throw $invalid;
        }

        $status = Password::broker($this->accounts->brokerFor($account))->reset(
            ['email' => $account->email, 'token' => $data['token'], 'password' => $data['password'], 'password_confirmation' => $data['password']],
            function (MemberAccount $member, string $password) {
                $member->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            }
        );
        if ($status !== Password::PASSWORD_RESET) {
            throw $invalid;
        }

        Auth::guard('member')->login($account->refresh());
        $request->session()->regenerate();
        $account->forceFill(['last_login_at' => now()])->save();
        if ($data['consent'] ?? false) {
            $this->recordConsent($account);
        }

        return response()->json(['member' => $account->toPortalArray()]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $member = $this->member();
        abort_unless($member, 401);

        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);
        if (! Auth::guard('member')->getProvider()->validateCredentials($member, ['password' => $data['current_password']])) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is not correct.']);
        }

        $member->forceFill(['password' => $data['password']])->save();

        return response()->json(['message' => 'Your password has been changed.']);
    }

    /** Members-only pages, for the portal's dashboard; only for active members */
    public function pages(): JsonResponse
    {
        $member = $this->member();
        abort_unless($member, 401);
        if (! $member->isActive()) {
            return response()->json(['pages' => []]);
        }

        $pages = Page::where('is_published', true)
            ->where('visibility', Page::VISIBILITY_MEMBERS)
            ->orderBy('order')
            ->orderBy('title')
            ->get(['title', 'slug', 'excerpt']);

        return response()->json(['pages' => $pages])->header('Cache-Control', 'no-store');
    }

    /** Agree to the site's current privacy notice (asked again when it changes) */
    public function consent(): JsonResponse
    {
        $member = $this->signedIn();
        $this->recordConsent($member);

        return response()->json(['member' => $member->refresh()->toPortalArray()]);
    }

    public function profile(): JsonResponse
    {
        $member = $this->signedIn();

        return response()->json([
            'profile' => ($member->profile ?? new MemberProfile())->toPortalArray(),
            'options' => [
                'titles' => MemberProfile::TITLES,
                'institution_types' => MemberProfile::INSTITUTION_TYPES,
                'crops' => MembershipSettings::cropOptions(),
                'expertise' => MembershipSettings::expertiseOptions(),
                'countries' => array_values(config('countries', [])),
            ],
        ])->header('Cache-Control', 'no-store');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $member = $this->signedIn();

        $data = $request->validate([
            'title' => ['nullable', Rule::in(MemberProfile::TITLES)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-.\s]{5,30}$/'],
            'country' => ['nullable', Rule::in(array_values(config('countries', [])))],
            'city' => 'nullable|string|max:100',
            'institution' => 'nullable|string|max:200',
            'institution_type' => ['nullable', Rule::in(array_keys(MemberProfile::INSTITUTION_TYPES))],
            'position' => 'nullable|string|max:150',
            'crops' => 'array|max:20',
            'crops.*' => [Rule::in(MembershipSettings::cropOptions())],
            'expertise' => 'array|max:20',
            'expertise.*' => [Rule::in(MembershipSettings::expertiseOptions())],
            'bio' => 'nullable|string|max:2000',
            'orcid' => ['nullable', 'regex:/^(https:\/\/orcid\.org\/)?\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/'],
            'website' => 'nullable|url:https,http|max:255',
        ], [
            'orcid.regex' => 'Enter your ORCID iD as 0000-0000-0000-0000.',
            'crops.*.in' => 'Choose crops from the list.',
            'expertise.*.in' => 'Choose areas of expertise from the list.',
            'phone.regex' => 'Enter a valid phone number.',
        ]);
        if (filled($data['orcid'] ?? null)) {
            $data['orcid'] = substr($data['orcid'], -19);
        }

        $profile = $member->profile()->updateOrCreate([], collect(MemberProfile::EDITABLE)->mapWithKeys(fn ($f) => [$f => $data[$f] ?? (in_array($f, ['crops', 'expertise'], true) ? [] : null)])->all());

        return response()->json(['profile' => $profile->toPortalArray(), 'message' => 'Your profile has been saved.']);
    }

    public function documents(): JsonResponse
    {
        $member = $this->signedIn();

        return response()->json([
            'documents' => $member->documents()->latest()->get()->map->toPortalArray(),
            'categories' => MemberDocument::CATEGORIES,
            'max' => MemberDocument::MAX_PER_MEMBER,
            'hint' => PrivateFiles::HINT,
        ])->header('Cache-Control', 'no-store');
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        $member = $this->signedIn();

        $data = $request->validate([
            'file' => ['required', ...PrivateFiles::rules()],
            'title' => 'required|string|max:150',
            'category' => ['required', Rule::in(array_keys(MemberDocument::CATEGORIES))],
        ], ['file.max' => 'The file must not be larger than 5 MB.']);

        if ($member->documents()->count() >= MemberDocument::MAX_PER_MEMBER) {
            throw ValidationException::withMessages(['file' => 'You have reached the limit of ' . MemberDocument::MAX_PER_MEMBER . ' documents. Remove one you no longer need first.']);
        }
        if (PrivateFiles::quotaError((int) $request->file('file')->getSize())) {
            throw ValidationException::withMessages(['file' => ApplicationFiles::QUOTA_MESSAGE]);
        }

        $document = $member->documents()->create(PrivateFiles::store($request->file('file'), "members/{$member->id}") + [
            'title' => $data['title'],
            'category' => $data['category'],
            'source' => 'member',
        ]);

        return response()->json(['document' => $document->toPortalArray(), 'message' => 'Your document has been uploaded.'], 201);
    }

    public function downloadDocument(int $id)
    {
        $document = $this->signedIn()->documents()->findOrFail($id);

        return PrivateFiles::download($document->file, $document->name);
    }

    public function deleteDocument(int $id): JsonResponse
    {
        $document = $this->signedIn()->documents()->findOrFail($id);
        abort_unless($document->deletableByMember(), 403, 'Documents from your application can only be removed by the secretariat.');
        $document->delete();

        return response()->json(['message' => 'The document has been removed.']);
    }

    /** The member's payment history, newest first */
    public function payments(): JsonResponse
    {
        $member = $this->signedIn();

        return response()->json([
            'payments' => $member->payments()->with(['memberAccount', 'membershipType', 'registration'])
                ->orderByDesc('paid_at')->get()->map->toReceiptArray(),
        ])->header('Cache-Control', 'no-store');
    }

    public function receipt(string $number): JsonResponse
    {
        $payment = $this->signedIn()->payments()->where('receipt_number', $number)->firstOrFail();

        return response()->json(['receipt' => $payment->toReceiptArray()])->header('Cache-Control', 'no-store');
    }

    public function receiptPdf(string $number, \App\Plugins\Membership\Support\ReceiptPdf $pdf)
    {
        return $pdf->download($this->signedIn()->payments()->where('receipt_number', $number)->firstOrFail());
    }

    /** Files shared with members (Membership → Member resources): active members only */
    public function resources(): JsonResponse
    {
        $member = $this->signedIn();

        return response()->json([
            'resources' => $member->isActive()
                ? MemberResource::where('is_published', true)->orderBy('order')->orderByDesc('updated_at')->get()->map->toPortalArray()
                : [],
        ])->header('Cache-Control', 'no-store');
    }

    public function downloadResource(int $id)
    {
        abort_unless($this->signedIn()->isActive(), 403, 'Your membership is not active.');
        $resource = MemberResource::where('is_published', true)->findOrFail($id);

        return PrivateFiles::download($resource->file, $resource->name);
    }

    private function recordConsent(MemberAccount $member): void
    {
        $version = MembershipSettings::privacyVersion();
        if ($version > 0) {
            $member->forceFill(['consented_at' => now(), 'consent_version' => $version])->save();
        }
    }

    private function signedIn(): MemberAccount
    {
        $member = $this->member();
        abort_unless($member, 401, 'Please sign in.');

        return $member;
    }

    private function member(): ?MemberAccount
    {
        return Auth::guard('member')->user();
    }
}
