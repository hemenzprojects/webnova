<?php

namespace App\Plugins\Membership\Http;

use App\Http\Controllers\Controller;
use App\Plugins\Donations\Support\Donor;
use App\Plugins\Membership\Models\MembershipForm;
use App\Plugins\Membership\Models\MembershipRegistration;
use App\Plugins\Membership\Models\MembershipType;
use App\Plugins\Membership\Support\MembershipSettings;
use App\Plugins\Membership\Support\Registrar;
use App\Plugins\PluginManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Public API for the "Membership form" page block.
 */
class MembershipController extends Controller
{
    public function __construct(private Registrar $registrar) {}

    /**
     * The form to render: sections and fields, membership types and prices.
     */
    public function form(): JsonResponse
    {
        $schema = MembershipForm::current()->schema;

        return response()->json([
            'sections' => $schema['sections'] ?? [],
            'submit_text' => $schema['submit_text'] ?? 'Submit',
            'show_amount' => $schema['show_amount'] ?? true,
            'currency' => MembershipSettings::get('currency'),
            'online_payment' => MembershipSettings::paystack()->isConfigured(),
            'types' => MembershipType::where('is_active', true)->orderBy('order')->orderBy('name')->get()
                ->map(fn (MembershipType $type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                    'price' => (float) $type->price,
                    'period' => $type->period,
                    'period_suffix' => MembershipType::PERIOD_SUFFIX[$type->period] ?? '',
                ]),
            'countries' => array_values(config('countries', [])),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        // JSON, or multipart when the form has documents: answers as a JSON string, files as files[{key}]
        $answers = $request->input('answers');
        if (is_string($answers)) {
            $answers = json_decode($answers, true);
        }
        abort_unless(is_array($answers), 422, 'No answers were sent.');
        foreach ((array) $request->file('files', []) as $key => $file) {
            if (is_string($key) && $file instanceof \Illuminate\Http\UploadedFile) {
                $answers[$key] = $file;
            }
        }

        $result = $this->registrar->submit($answers, $this->siteUrl($request));
        $registration = $result['registration'];

        return response()->json([
            'reference' => $registration->reference,
            'payment_url' => $result['payment_url'],
            'payment_status' => $registration->payment_status,
            'message' => $result['payment_failed']
                ? 'Your registration has been received, but online payment could not be started. We will contact you about how to pay. Please keep your reference.'
                : MembershipSettings::get('success_message'),
        ], 201);
    }

    /**
     * A document attached to an application, for admins who may view
     * applications (Membership → Applications). Never public.
     */
    public function file(string $reference, string $key)
    {
        $registration = MembershipRegistration::where('reference', $reference)->firstOrFail();
        $answer = ($registration->answers ?? [])[$key] ?? null;
        $files = app(\App\Plugins\Membership\Support\ApplicationFiles::class);

        abort_unless($files::isFile($answer) && $files->belongsToSite($answer['file']), 404);
        abort_unless(\Illuminate\Support\Facades\Storage::disk($files::DISK)->exists($answer['file']), 404);

        return \Illuminate\Support\Facades\Storage::disk($files::DISK)
            ->download($answer['file'], $answer['name'], ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    /**
     * Where Paystack sends the payer back to: confirm the payment and report.
     */
    public function status(string $reference): JsonResponse
    {
        $registration = MembershipRegistration::where('reference', $reference)->firstOrFail();

        try {
            $registration = $this->registrar->confirmPayment($registration);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'reference' => $registration->reference,
            'name' => $registration->name,
            'membership_type' => $registration->membershipType?->name,
            'amount' => (float) $registration->amount,
            'currency' => $registration->currency,
            'payment_status' => $registration->payment_status,
            'status' => $registration->status,
            'message' => MembershipSettings::get('success_message'),
        ]);
    }

    /**
     * Start a new payment attempt after a failed or abandoned one.
     */
    public function retryPayment(Request $request, string $reference): JsonResponse
    {
        $registration = MembershipRegistration::where('reference', $reference)->firstOrFail();

        // The last attempt may have gone through since the applicant last looked
        try {
            $registration = $this->registrar->confirmPayment($registration);
        } catch (Throwable $e) {
            report($e);
        }
        abort_if($registration->payment_status === 'paid' || $registration->payment_status === 'not_required', 409, 'Nothing to pay.');

        return response()->json(['payment_url' => $this->registrar->startPayment($registration, $this->siteUrl($request))]);
    }

    /**
     * Paystack webhook (set the URL in the Paystack dashboard). Confirms
     * payments even when the payer closes the browser before returning.
     */
    public function paystackWebhook(Request $request): JsonResponse
    {
        abort_unless(MembershipSettings::paystack()->validSignature($request->getContent(), $request->header('x-paystack-signature')), 401);

        if ($request->input('event') === 'charge.success') {
            $paymentReference = (string) $request->input('data.reference');
            // A Paystack account has a single webhook URL, shared with the Donations plugin
            if (! $this->registrar->confirmByPaymentReference($paymentReference) && app(PluginManager::class)->isActive('donations')) {
                app(Donor::class)->confirmByPaymentReference($paymentReference);
            }
        }

        return response()->json(['received' => true]);
    }

    /**
     * The public site's address, for Paystack's return link.
     */
    private function siteUrl(Request $request): string
    {
        return $request->getSchemeAndHttpHost();
    }
}
