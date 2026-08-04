<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Filters/Donation/StorageTypeFilter.php
 * Purpose: Strategy implementing "Filter Donation Options" by storage
 *          requirement, so a charity without cold storage can hide donations it
 *          is unable to accept.
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
