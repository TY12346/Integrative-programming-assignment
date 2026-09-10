<?php
/* Lau Ke Xin */

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
