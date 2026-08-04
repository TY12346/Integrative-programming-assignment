<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Filters/Donation/DonationFilterPipeline.php
 * Purpose: Context class of the STRATEGY design pattern. It holds the set of
 *          registered filter strategies and applies the ones the user actually
 *          supplied. The concrete strategies are injected by
 *          FoodRequestServiceProvider, so adding a new filter is a one line
 *          change in the provider.
 */

namespace App\Filters\Donation;

use Illuminate\Database\Eloquent\Builder;

final class DonationFilterPipeline
{
    /** @var DonationFilter[] */
    private array $filters;

    public function __construct(DonationFilter ...$filters)
    {
        $this->filters = $filters;
    }

    /**
     * Apply every registered strategy whose input key is present.
     *
     * Secure coding: only the keys owned by a registered strategy are read from
     * the criteria array, so unexpected query string parameters can never reach
     * the query builder.
     */
    public function apply(Builder $query, array $criteria): Builder
    {
        foreach ($this->filters as $filter) {
            $value = $criteria[$filter->key()] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $filter->apply($query, $value);
        }

        return $query;
    }
}
