<?php

namespace App\Plugins\Donations\Support;

use App\Plugins\Donations\Models\Donation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Donations narrowed by the dashboard's dropdown filters. Always one
 * currency: amounts in different currencies cannot be added together.
 */
class DonationQuery
{
    public const PERIODS = [
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        '12m' => 'Last 12 months',
        'all' => 'All time',
    ];

    public static function currency(?array $filters): string
    {
        return ($filters['currency'] ?? null) ?: DonationSettings::defaultCurrency();
    }

    public static function filtered(?array $filters): Builder
    {
        $filters ??= [];
        $query = Donation::query()->where('currency', static::currency($filters));

        if (filled($filters['method'] ?? null)) {
            $query->where('method', $filters['method']);
        }
        if ($since = static::since($filters['period'] ?? '12m')) {
            $query->where('created_at', '>=', $since);
        }

        return $query;
    }

    public static function since(?string $period): ?CarbonInterface
    {
        return match ($period) {
            '30d' => now()->subDays(30)->startOfDay(),
            '90d' => now()->subDays(90)->startOfDay(),
            '12m' => now()->subMonths(11)->startOfMonth(),
            default => null,
        };
    }
}
