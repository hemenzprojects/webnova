<?php

namespace App\Plugins\Membership\Http;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Support\MemberAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);

        $throttleKey = 'member-login:' . Str::lower($data['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Please try again in ' . ceil(RateLimiter::availableIn($throttleKey) / 60) . ' minute(s).',
            ]);
        }

        $credentials = ['email' => Str::lower(trim($data['email'])), 'password' => $data['password']];
        if (! Auth::guard('member')->attempt($credentials, (bool) ($data['remember'] ?? false))) {
            RateLimiter::hit($throttleKey, 300);
            throw ValidationException::withMessages(['email' => 'The email or password is not correct.']);
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
        $data = $request->validate(['email' => 'required|email']);

        $account = MemberAccount::where('email', Str::lower(trim($data['email'])))->first();
        if ($account) {
            $this->accounts->sendPasswordReset($account, $request->getSchemeAndHttpHost());
        }

        return response()->json(['message' => 'If this email belongs to a member account, we have sent a link to choose a new password.']);
    }

    /** Set a password from the welcome email or a reset email, then sign in */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $account = MemberAccount::where('email', Str::lower(trim($data['email'])))->first();
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

    private function member(): ?MemberAccount
    {
        return Auth::guard('member')->user();
    }
}
