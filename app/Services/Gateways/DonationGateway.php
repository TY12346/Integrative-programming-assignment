<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 */

namespace App\Services\Gateways;

use App\Models\FoodDonation;
use Illuminate\Support\Collection;

interface DonationGateway
{
    /**
     * "Display Active Donations" - donations that are still available, not
     * expired and still have quantity left, narrowed by the supplied criteria.
     *
     * @param  array<string, mixed>  $criteria  keys understood by DonationFilterPipeline
     * @return Collection<int, FoodDonation>
     */
    public function activeDonations(array $criteria = []): Collection;

    /** A single active donation, or null when it is gone or unavailable. */
    public function find(int $donationId): ?FoodDonation;
}
