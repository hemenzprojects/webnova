<?php

namespace App\Plugins\Donations\Http;

use App\Http\Controllers\Controller;
use App\Plugins\Donations\Models\Donation;
use App\Plugins\Donations\Support\DonationSettings;
use App\Plugins\Donations\Support\Donor;
use App\Plugins\Membership\Support\Registrar;
use App\Plugins\PluginManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Public API for the "Donation form" page block.
 */
class DonationController extends Controller
{
    public function __construct(private Donor $donor) {}

    /**
     * What the form needs: currencies, titles and the smallest amount.
     */
    public function form(): JsonResponse
    {
        return response()->json([
            'currencies' => collect(DonationSettings::currencies())
                ->map(fn (string $code) => ['code' => $code, 'label' => DonationSettings::CURRENCY_LABELS[$code] ?? $code])
                ->values(),
            'titles' => array_values((array) DonationSettings::get('titles')),
            'minimum_amount' => (float) DonationSettings::get('minimum_amount'),
            'online_payment' => DonationSettings::paystack()->isConfigured(),
        ]);
    }

    public function donate(Request $request): JsonResponse
    {
        $result = $this->donor->submit($request->all(), $request->getSchemeAndHttpHost());
        $donation = $result['donation'];

        return response()->json([
            'reference' => $donation->reference,
            'payment_url' => $result['payment_url'],
            'message' => DonationSettings::get('pledge_message'),
        ], 201);
    }

    /**
     * Where Paystack sends the donor back to: confirm the payment and report.
     */
    public function status(string $reference): JsonResponse
    {
        $donation = Donation::where('reference', $reference)->firstOrFail();

        try {
            $donation = $this->donor->confirmPayment($donation);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'reference' => $donation->reference,
            'first_name' => $donation->first_name,
            'amount' => (float) $donation->amount,
            'currency' => $donation->currency,
            'payment_status' => $donation->payment_status,
            'message' => DonationSettings::get('success_message'),
        ]);
    }

    /**
     * Start a new payment attempt after a failed or abandoned one.
     */
    public function retryPayment(Request $request, string $reference): JsonResponse
    {
        // Only donations made through the form; a gift recorded by an admin has nothing to pay
        $donation = Donation::where('reference', $reference)->where('method', 'online')->firstOrFail();

        // The last attempt may have gone through since the donor last looked
        try {
            $donation = $this->donor->confirmPayment($donation);
        } catch (Throwable $e) {
            report($e);
        }
        abort_if($donation->payment_status === 'paid', 409, 'This donation has already been received.');

        return response()->json(['payment_url' => $this->donor->startPayment($donation, $request->getSchemeAndHttpHost())]);
    }

    /**
     * Paystack webhook. Confirms donations even when the donor closes the
     * browser before returning. A Paystack account has a single webhook URL,
     * so a payment that is not a donation is passed on to Membership.
     */
    public function paystackWebhook(Request $request, PluginManager $plugins): JsonResponse
    {
        abort_unless(DonationSettings::paystack()->validSignature($request->getContent(), $request->header('x-paystack-signature')), 401);

        if ($request->input('event') === 'charge.success') {
            $paymentReference = (string) $request->input('data.reference');

            if (! $this->donor->confirmByPaymentReference($paymentReference) && $plugins->isActive('membership')) {
                app(Registrar::class)->confirmByPaymentReference($paymentReference);
            }
        }

        return response()->json(['received' => true]);
    }
}
