<?php
/* Lau Ke Xin */

namespace App\Filters\Donation;

use Illuminate\Database\Eloquent\Builder;

final class DonationStatusFilter implements DonationFilter
{
    /** Existing food_donations.donation_status enum values. */
    public const STATUSES = ['AVAILABLE', 'RESERVED', 'COMPLETED', 'CANCELLED', 'EXPIRED'];

    public function key(): string
    {
        return 'donation_status';
    }

    public function apply(Builder $query, mixed $value): void
    {
        $status = trim((string) $value);

        if ($status === '' || ! in_array($status, self::STATUSES, true)) {
            return;
        }

        $query->where('donation_status', $status);
    }

    /**
     * Statuses shown on the Available Donations filter dropdown.
     * CANCELLED and EXPIRED remain valid lifecycle values but are not browse filters.
     */
    public static function options(): array
    {
        return ['AVAILABLE', 'RESERVED', 'COMPLETED'];
    }
}
