<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\Models\MembershipRegistration;
use Illuminate\Database\Eloquent\Builder;

/**
 * Registrations narrowed by the dashboard's dropdown filters.
 */
class RegistrationQuery
{
    public const PERIODS = [
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        '12m' => 'Last 12 months',
        'all' => 'All time',
    ];

    public static function filtered(?array $filters): Builder
    {
        $filters ??= [];
        $query = MembershipRegistration::query();

        if (filled($filters['membership_type_id'] ?? null)) {
            $query->where('membership_type_id', $filters['membership_type_id']);
        }
        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (filled($filters['payment_status'] ?? null)) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if ($since = static::since($filters['period'] ?? '12m')) {
            $query->where('created_at', '>=', $since);
        }

        return $query;
    }

    public static function since(?string $period): ?\Carbon\CarbonInterface
    {
        return match ($period) {
            '30d' => now()->subDays(30)->startOfDay(),
            '90d' => now()->subDays(90)->startOfDay(),
            '12m' => now()->subMonths(11)->startOfMonth(),
            default => null,
        };
    }
}
