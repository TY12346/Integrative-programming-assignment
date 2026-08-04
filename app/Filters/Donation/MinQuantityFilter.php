<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Filters/Donation/MinQuantityFilter.php
 * Purpose: Strategy implementing "Filter Donation Options" by remaining
 *          quantity, so a charity only sees donations large enough to cover the
 *          outstanding part of its request.
 */

namespace App\Filters\Donation;

use Illuminate\Database\Eloquent\Builder;

final class MinQuantityFilter implements DonationFilter
{
    public function key(): string
    {
        return 'min_quantity';
    }

    public function apply(Builder $query, mixed $value): void
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            return;
        }

        $query->where('current_quantity', '>=', (float) $value);
    }
}
