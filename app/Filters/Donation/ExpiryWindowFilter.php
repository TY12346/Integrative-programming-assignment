<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Filters/Donation/ExpiryWindowFilter.php
 * Purpose: Strategy implementing "Filter Donation Options" by expiry window.
 *          A charity can list only the donations expiring within N hours, which
 *          is how the most at-risk food gets rescued first.
 */

namespace App\Filters\Donation;

use Illuminate\Database\Eloquent\Builder;

final class ExpiryWindowFilter implements DonationFilter
{
    /** Whitelisted windows, in hours. Anything else is ignored. */
    private const ALLOWED_HOURS = [6, 12, 24, 48, 72, 168];

    public function key(): string
    {
        return 'expires_within_hours';
    }

    public function apply(Builder $query, mixed $value): void
    {
        if (! is_numeric($value) || ! in_array((int) $value, self::ALLOWED_HOURS, true)) {
            return;
        }

        $query->where('expiry_datetime', '<=', now()->addHours((int) $value));
    }

    public static function options(): array
    {
        return self::ALLOWED_HOURS;
    }
}
