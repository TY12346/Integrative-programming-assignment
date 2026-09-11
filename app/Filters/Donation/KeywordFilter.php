<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Purpose: Strategy implementing "Search Specific Donations". Matches the free
 *          text keyword against the food name and description.
 
 */

namespace App\Filters\Donation;

use Illuminate\Database\Eloquent\Builder;

final class KeywordFilter implements DonationFilter
{
    public function key(): string
    {
        return 'keyword';
    }

    public function apply(Builder $query, mixed $value): void
    {
        $keyword = trim((string) $value);

        if ($keyword === '') {
            return;
        }

        $term = '%'.self::escapeLike($keyword).'%';

        $query->where(function (Builder $inner) use ($term) {
            $inner->where('food_name', 'like', $term)
                ->orWhere('description', 'like', $term);
        });
    }

    /** Neutralise the LIKE metacharacters before the value is bound. */
    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
