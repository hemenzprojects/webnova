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
    public function submit(array $answers, string $siteUrl, bool $consent = false): array
    {
        $form = MembershipForm::current();
        $schema = $form->schema;

        // With a privacy notice (Membership settings), applying means agreeing to it
        $privacyVersion = MembershipSettings::privacyVersion();
        if ($privacyVersion && ! $consent) {
            throw ValidationException::withMessages(['consent' => 'Please confirm that you have read and agree to the privacy notice.']);
        }

        $typeField = FormSchema::membershipTypeField($schema);
        $typeId = $typeField && is_numeric($answers[$typeField['key']] ?? null) ? (int) $answers[$typeField['key']] : null;

        Validator::make(['answers' => $answers], FormSchema::rules($schema, $typeId), [], FormSchema::attributes($schema))->validate();

        $type = $typeId ? MembershipType::find($typeId) : null;
        $clean = FormSchema::clean($schema, $answers, $typeId);

        $this->refuseExistingMember($schema, $clean);

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
            'consented_at' => $privacyVersion ? now() : null,
            'consent_version' => $privacyVersion ?: null,
        ]);

        // "Received, we will review it" only when applications are reviewed; otherwise the
        // receipt (on payment) or the acceptance (free membership) is the applicant's first email
        if (MembershipSettings::get('require_approval')) {
            app(MembershipMailer::class)->applicationReceived($registration);
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
     * Someone who is already a member renews from the member portal, not by
     * applying again (which would give them a second Member ID).
     *
     * @throws ValidationException
     */
    private function refuseExistingMember(array $schema, array $answers): void
    {
        $emailField = FormSchema::fieldByRole($schema, 'email') ?? collect(FormSchema::fields($schema))->firstWhere('type', 'email');
        $email = strtolower(trim((string) ($emailField ? ($answers[$emailField['key']] ?? '') : '')));
        if ($email === '') {
            return;
        }

        $account = \App\Plugins\Membership\Models\MemberAccount::where('email', $email)->first();
        if ($account) {
            $message = app(MemberAccounts::class)->enabled()
                ? "This email already belongs to a member ({$account->member_number}). Please log in to the member portal to renew your membership or update your details."
                : "This email already belongs to a member ({$account->member_number}). If you need help, please contact us.";
        } else {
            $earlier = MembershipRegistration::whereRaw('lower(email) = ?', [$email])
                ->where('status', 'approved')
                ->latest()
                ->first();
            if (! $earlier) {
                return;
            }
            $message = "This email is already registered (reference {$earlier->reference}). If you need help, please contact us.";
        }

        throw ValidationException::withMessages(['answers.' . $emailField['key'] => $message]);
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
