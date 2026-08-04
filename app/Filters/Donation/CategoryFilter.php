<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Filters/Donation/CategoryFilter.php
 * Purpose: Strategy implementing "Filter Donation Options" by food category /
 *          food type, which is how a charity narrows the donation list down to
 *          what its request actually needs.
 */

namespace App\Filters\Donation;

use Illuminate\Database\Eloquent\Builder;

final class CategoryFilter implements DonationFilter
{
    public function key(): string
    {
        return 'category_id';
    }

    public function apply(Builder $query, mixed $value): void
    {
        if (! is_numeric($value)) {
            return;
        }

        $query->where('category_id', (int) $value);
    }
}
