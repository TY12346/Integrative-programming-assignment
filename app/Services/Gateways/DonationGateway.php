<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Services/Gateways/DonationGateway.php
 * Purpose: Boundary between the Food Request Management module and the Food
 *          Donation Management module (3.2, Lau Ke Xin). Functions 8-10 of my
 *          module need donation data that my module does not own, so the access
 *          goes through this interface instead of through the donation tables
 *          directly.
 *
 *          Two implementations are provided (ADAPTER / STRATEGY at the
 *          integration boundary), selected by config('foodlink.donation_gateway'):
 *            - LocalDonationGateway : same-database access through Eloquent.
 *            - HttpDonationGateway  : consumes the donation REST web service.
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
