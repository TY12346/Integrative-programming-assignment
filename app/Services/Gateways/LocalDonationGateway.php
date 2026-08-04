<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Services/Gateways/LocalDonationGateway.php
 * Purpose: Default gateway implementation. Reads the donation data owned by
 *          module 3.2 through Eloquent ORM and narrows it with the injected
 *          strategy pipeline. Used when the whole system runs as one Laravel
 *          application (the normal case for the tutor demo).
 */

namespace App\Services\Gateways;

use App\Filters\Donation\DonationFilterPipeline;
use App\Models\FoodDonation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class LocalDonationGateway implements DonationGateway
{
    /** Hard cap so a charity can never pull the entire donation table. */
    private const MAX_ROWS = 100;

    public function __construct(private readonly DonationFilterPipeline $filters)
    {
    }

    public function activeDonations(array $criteria = []): Collection
    {
        $query = $this->baseQuery();

        $this->filters->apply($query, $criteria);

        return $query->orderBy('expiry_datetime')->limit(self::MAX_ROWS)->get();
    }

    public function find(int $donationId): ?FoodDonation
    {
        return $this->baseQuery()->where('donation_id', $donationId)->first();
    }

    /**
     * The definition of "active" agreed with module 3.2: still marked
     * AVAILABLE, not past its expiry date, and with quantity left to reserve.
     */
    private function baseQuery(): Builder
    {
        return FoodDonation::query()
            ->with(['category', 'donor.user'])
            ->where('donation_status', 'AVAILABLE')
            ->where('expiry_datetime', '>', now())
            ->where('current_quantity', '>', 0);
    }
}
