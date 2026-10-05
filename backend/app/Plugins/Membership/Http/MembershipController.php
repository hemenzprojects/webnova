<?php

namespace App\Plugins\Membership\Http;

use App\Http\Controllers\Controller;
use App\Plugins\Membership\Models\MembershipForm;
use App\Plugins\Membership\Models\MembershipRegistration;
use App\Plugins\Membership\Models\MembershipType;
use App\Plugins\Membership\Support\MembershipSettings;
use App\Plugins\Membership\Support\Registrar;
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
        $answers = $request->input('answers');
        abort_unless(is_array($answers), 422, 'No answers were sent.');

        $result = $this->registrar->submit($answers, $this->siteUrl($request));
        $registration = $result['registration'];

        return response()->json([
            'reference' => $registration->reference,
            'payment_url' => $result['payment_url'],
            'payment_status' => $registration->payment_status,
            'message' => MembershipSettings::get('success_message'),
        ], 201);
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
            $registration = MembershipRegistration::where('payment_reference', $paymentReference)->first();
            if ($registration) {
                $this->registrar->confirmPayment($registration);
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
