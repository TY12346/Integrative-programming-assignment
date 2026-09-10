<?php
/**
 * FoodLink - Module 3.2 Food Donation Management
 * File   : app/Filters/Donation/LocationFilter.php
 * Purpose: Strategy for filtering donations by pickup location/address.
 *          Uses the existing food_donations.pickup_address column.
 */

namespace App\Filters\Donation;

use Illuminate\Database\Eloquent\Builder;

final class LocationFilter implements DonationFilter
{
    public function key(): string
    {
        return 'location';
    }

    public function apply(Builder $query, mixed $value): void
    {
        $location = trim((string) $value);

        if ($location === '') {
            return;
        }

        $term = '%'.KeywordFilter::escapeLike($location).'%';

        $query->where('pickup_address', 'like', $term);
    }
}
