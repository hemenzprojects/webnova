<?php

namespace App\Plugins\Membership\Support;

use Carbon\CarbonImmutable;

/**
 * Where a member stands with their yearly fee on a given day (Renewals).
 * Worked out by Renewals::standing() from their payments and the dates in
 * Membership → Settings; never stored as the truth (the standing column is a
 * copy for lists and filters).
 */
final class Standing
{
    public const EXEMPT = 'exempt';       // no fee for their category (e.g. Honorary)

    public const PAID = 'paid';           // the current year is paid

    public const DUE = 'due';             // not paid yet, before the due date

    public const OVERDUE = 'overdue';     // past the due date, not yet suspended

    public const SUSPENDED = 'suspended'; // members' area closed until they pay

    public const FORFEITED = 'forfeited'; // whole years unpaid: closed until they pay

    public const LABELS = [
        self::EXEMPT => 'No fee',
        self::PAID => 'Paid',
        self::DUE => 'Due',
        self::OVERDUE => 'Overdue',
        self::SUSPENDED => 'Suspended (not paid)',
        self::FORFEITED => 'Forfeited (not paid)',
    ];

    public function __construct(
        public readonly string $state,
        /** The latest year paid for, if any */
        public readonly ?int $paidYear,
        /** The first year not paid for (null when nothing is owed) */
        public readonly ?int $unpaidYear,
        /** The year a renewal now would pay for (null: nothing to pay yet) */
        public readonly ?int $renewYear,
        public readonly float $amount,
        public readonly string $currency,
        public readonly ?CarbonImmutable $dueDate = null,
        public readonly ?CarbonImmutable $noticeOn = null,
        public readonly ?CarbonImmutable $suspendOn = null,
        public readonly ?CarbonImmutable $forfeitOn = null,
        /** A seat: covered by this corporate member */
        public readonly ?string $coveredBy = null,
    ) {}

    public function label(): string
    {
        return self::LABELS[$this->state] ?? $this->state;
    }

    /** Members-only pages and files (and voting rights, when there is voting) */
    public function canUseMemberArea(): bool
    {
        return ! in_array($this->state, [self::SUSPENDED, self::FORFEITED], true);
    }

    public function inGoodStanding(): bool
    {
        return $this->canUseMemberArea();
    }

    public function canRenew(): bool
    {
        return $this->renewYear !== null && $this->amount > 0 && $this->coveredBy === null;
    }

    /** One sentence for the member */
    public function message(): string
    {
        $date = fn (?CarbonImmutable $d) => $d?->format('j F Y');

        return match (true) {
            $this->coveredBy !== null => "Your membership is covered by {$this->coveredBy}."
                . ($this->canUseMemberArea() ? '' : " {$this->coveredBy}'s membership fee has not been paid, so the members' area is closed for now."),
            $this->state === self::EXEMPT => 'Your membership category has no yearly fee.',
            $this->state === self::PAID => "Your membership is paid for {$this->paidYear}."
                . ($this->renewYear ? " You can already pay for {$this->renewYear}." : ''),
            $this->state === self::DUE => "Your {$this->unpaidYear} membership fee is due by {$date($this->dueDate)}.",
            $this->state === self::OVERDUE => "Your {$this->unpaidYear} membership fee was due on {$date($this->dueDate)}. Please pay before {$date($this->suspendOn)} to keep access to the members' area.",
            $this->state === self::SUSPENDED => "Your membership is suspended because the {$this->unpaidYear} fee has not been paid. Renew to open the members' area again.",
            $this->state === self::FORFEITED => 'Your membership has lapsed after ' . ($this->paidYear ? "no payment since {$this->paidYear}" : 'no payment') . '. Renew to become a member in good standing again.',
            default => '',
        };
    }

    public function toPortalArray(): array
    {
        return [
            'state' => $this->state,
            'label' => $this->label(),
            'message' => $this->message(),
            'paid_year' => $this->paidYear,
            'renew_year' => $this->canRenew() ? $this->renewYear : null,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'due_date' => $this->dueDate?->toDateString(),
            'suspend_on' => $this->suspendOn?->toDateString(),
            'covered_by' => $this->coveredBy,
            'good_standing' => $this->inGoodStanding(),
        ];
    }
}
