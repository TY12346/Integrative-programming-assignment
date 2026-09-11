<?php
/**

 */

namespace App\Filters\Donation;

use Illuminate\Database\Eloquent\Builder;

interface DonationFilter
{
    /** Name of the request input this strategy reacts to. */
    public function key(): string;

    /** Apply the strategy to the donation query for the supplied user input. */
    public function apply(Builder $query, mixed $value): void;
}
