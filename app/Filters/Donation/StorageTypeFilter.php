<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 */

namespace App\Filters\Donation;

use Illuminate\Database\Eloquent\Builder;

final class StorageTypeFilter implements DonationFilter
{
    public function key(): string
    {
        return 'storage_type';
    }

    public function apply(Builder $query, mixed $value): void
    {
        $storage = trim((string) $value);

        if ($storage === '') {
            return;
        }

        $query->where('storage_type', $storage);
    }
}
