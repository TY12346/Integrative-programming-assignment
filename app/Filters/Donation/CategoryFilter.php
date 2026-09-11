<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Purpose: "Filter Donation Options" 
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
