<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Filters/Donation/KeywordFilter.php
 * Purpose: Strategy implementing "Search Specific Donations". Matches the free
 *          text keyword against the food name and description.
 *
 * Secure coding: the keyword is bound as a parameter (never concatenated into
 * SQL) and the LIKE wildcards % _ \ are escaped, so a user cannot turn the
 * search box into a wildcard scan of the whole table.
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
