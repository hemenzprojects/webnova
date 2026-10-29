<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\Models\MembershipForm;
use App\Plugins\Membership\Models\MembershipRegistration;
use App\Plugins\Membership\Models\MembershipType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Takes a registration from submission through payment.
 */
class Registrar
{
    /**
     * Validate and store a submission, then start payment if one is due.
     *
     * @return array{registration: MembershipRegistration, payment_url: ?string, payment_failed: bool}
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function submit(array $answers, string $siteUrl): array
    {
        $form = MembershipForm::current();
        $schema = $form->schema;

        $typeField = FormSchema::membershipTypeField($schema);
        $typeId = $typeField && is_numeric($answers[$typeField['key']] ?? null) ? (int) $answers[$typeField['key']] : null;

        Validator::make(['answers' => $answers], FormSchema::rules($schema, $typeId), [], FormSchema::attributes($schema))->validate();

        $type = $typeId ? MembershipType::find($typeId) : null;
        $clean = FormSchema::clean($schema, $answers, $typeId);

        // Documents (fields of type "file"): stored privately, the answer keeps their details
        $files = app(ApplicationFiles::class);
        $uploads = array_filter($clean, fn ($answer) => $answer instanceof UploadedFile);
        if ($uploads && ($error = $files->quotaError($uploads))) {
            throw ValidationException::withMessages(['answers.' . array_key_first($uploads) => $error]);
        }
        foreach ($uploads as $key => $upload) {
            $clean[$key] = $files->store($upload);
        }
        [$name, $email] = FormSchema::nameAndEmail($schema, $clean);
        $amount = (float) ($type?->price ?? 0);

        $registration = MembershipRegistration::create([
            'membership_type_id' => $type?->id,
            'name' => $name,
            'email' => $email,
            'answers' => $clean,
            'form_snapshot' => $schema,
            'form_version' => $form->version,
            'amount' => $amount,
            'currency' => MembershipSettings::get('currency'),
            'status' => 'pending',
            'payment_status' => $amount > 0 ? 'unpaid' : 'not_required',
        ]);

        // Before any approval, so the applicant hears "received" first
        app(MembershipMailer::class)->applicationReceived($registration);

        if ($amount <= 0) {
            $this->autoApprove($registration);
        }

        $this->notifyAdmin($registration);

        $paymentUrl = null;
        $paymentFailed = false;
        if ($amount > 0) {
            try {
                $paymentUrl = $this->startPayment($registration, $siteUrl);
            } catch (Throwable $e) {
                // Paystack refused or could not be reached. The registration is
                // kept as unpaid, so the applicant need not fill the form in again.
                report($e);
                $paymentFailed = true;
            }
        }

        return ['registration' => $registration, 'payment_url' => $paymentUrl, 'payment_failed' => $paymentFailed];
    }

    /**
     * Send the applicant to Paystack. Null when Paystack is not set up: the
     * registration stays "unpaid" and the admin collects payment another way.
     */
    public function startPayment(MembershipRegistration $registration, string $siteUrl): ?string
    {
        $paystack = MembershipSettings::paystack();
        if (! $paystack->isConfigured() || ! $registration->email || $registration->payment_status === 'paid') {
            return null;
        }

        // A fresh reference per attempt, so a failed payment can be retried
        $attempt = $registration->payment_reference ? ((int) substr(strrchr($registration->payment_reference, '-'), 1)) + 1 : 1;
        $reference = "{$registration->reference}-{$attempt}";
        $registration->update(['payment_reference' => $reference]);

        return $paystack->initialize(
            $registration->email,
            (float) $registration->amount,
            $registration->currency,
            $reference,
            rtrim($siteUrl, '/') . '/membership/complete?reference=' . urlencode($registration->reference),
            ['registration' => $registration->reference, 'membership_type' => $registration->membershipType?->name],
        );
    }

    /**
     * Ask Paystack about a payment attempt and record the result. Checks the
     * latest attempt unless told which one: an earlier attempt can still
     * succeed after the applicant has started another.
     */
    public function confirmPayment(MembershipRegistration $registration, ?string $paymentReference = null): MembershipRegistration
    {
        $paymentReference ??= $registration->payment_reference;

        // Attempts are "{registration reference}-{n}"; anything else is a receipt number an admin typed
        if ($registration->payment_status === 'paid' || ! $paymentReference || ! str_starts_with($paymentReference, $registration->reference . '-')) {
            return $registration;
        }

        $result = MembershipSettings::paystack()->verify($paymentReference);
        if (! $result) {
            return $registration;
        }

        $paidInFull = $result['status'] === 'success'
            && strtoupper($result['currency']) === strtoupper($registration->currency)
            && $result['amount'] + 0.001 >= (float) $registration->amount;

        if ($paidInFull) {
            $registration->update(['payment_status' => 'paid', 'paid_at' => now(), 'payment_reference' => $paymentReference]);
            $this->autoApprove($registration);
        } elseif ($paymentReference === $registration->payment_reference && in_array($result['status'], ['failed', 'abandoned', 'reversed'], true)) {
            $registration->update(['payment_status' => 'failed']);
        }

        return $registration->refresh();
    }

    /**
     * For Paystack webhooks: confirm the registration a payment belongs to,
     * whichever attempt it was. False if the payment is not for a registration.
     */
    public function confirmByPaymentReference(string $paymentReference): bool
    {
        $registration = MembershipRegistration::where('payment_reference', $paymentReference)
            ->orWhere('reference', Str::beforeLast($paymentReference, '-'))
            ->first();
        if (! $registration) {
            return false;
        }

        $this->confirmPayment($registration, $paymentReference);

        return true;
    }

    /**
     * Without manual review, a registration is approved once nothing is owed.
     */
    private function autoApprove(MembershipRegistration $registration): void
    {
        if (! MembershipSettings::get('require_approval') && $registration->status === 'pending') {
            $registration->update(['status' => 'approved', 'reviewed_at' => now()]);
        }
    }

    private function notifyAdmin(MembershipRegistration $registration): void
    {
        $to = MembershipSettings::get('notification_email');
        if (! $to) {
            return;
        }

        $lines = collect($registration->readableAnswers())->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");
        $type = $registration->membershipType?->name ?? '—';

        try {
            Mail::raw(
                "A new membership registration was submitted.\n\nReference: {$registration->reference}\nMembership type: {$type}\n"
                . "Amount: {$registration->currency} {$registration->amount}\n\n{$lines}\n",
                fn ($message) => $message->to($to)->subject("New membership registration: {$registration->name}")
            );
        } catch (Throwable $e) {
            // A mail problem must not lose the registration
            Log::warning('Membership notification email failed', ['reference' => $registration->reference, 'error' => $e->getMessage()]);
        }
    }
}
