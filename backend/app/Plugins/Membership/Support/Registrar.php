<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\Models\MembershipForm;
use App\Plugins\Membership\Models\MembershipRegistration;
use App\Plugins\Membership\Models\MembershipType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
     * @return array{registration: MembershipRegistration, payment_url: ?string}
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

        if ($amount <= 0) {
            $this->autoApprove($registration);
        }

        $this->notifyAdmin($registration);

        return [
            'registration' => $registration,
            'payment_url' => $amount > 0 ? $this->startPayment($registration, $siteUrl) : null,
        ];
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
     * Ask Paystack about the latest payment attempt and record the result.
     */
    public function confirmPayment(MembershipRegistration $registration): MembershipRegistration
    {
        if ($registration->payment_status === 'paid' || ! $registration->payment_reference) {
            return $registration;
        }

        $result = MembershipSettings::paystack()->verify($registration->payment_reference);
        if (! $result) {
            return $registration;
        }

        $paidInFull = $result['status'] === 'success'
            && strtoupper($result['currency']) === strtoupper($registration->currency)
            && $result['amount'] + 0.001 >= (float) $registration->amount;

        if ($paidInFull) {
            $registration->update(['payment_status' => 'paid', 'paid_at' => now()]);
            $this->autoApprove($registration);
        } elseif (in_array($result['status'], ['failed', 'abandoned', 'reversed'], true)) {
            $registration->update(['payment_status' => 'failed']);
        }

        return $registration->refresh();
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
