<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Filters/Donation/DonationFilter.php
 * Purpose: Strategy interface of the STRATEGY design pattern. Each way of
 *          narrowing the list of active donations ("Filter Donation Options"
 *          and "Search Specific Donations") is a separate strategy object, so a
 *          new criterion can be added without editing the controller or the
 *          repository.
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
