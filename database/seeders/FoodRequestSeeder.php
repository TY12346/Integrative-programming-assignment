<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : database/seeders/FoodRequestSeeder.php
 * Purpose: Demo data for the Food Request Management module. It creates one
 *          request in each lifecycle state so the dashboard, the status badges,
 *          the deadline monitor and the reserved quantity tracking can all be
 *          shown during the tutor demo without clicking through the whole flow.
 *
 *          Only entities that appear on the analysis class diagram are seeded:
 *          FoodRequest, Reservation and FoodDonation.
 *
 *          It also issues a demo API token for the seeded charity so the REST
 *          web service can be tested straight away.
 */

namespace Database\Seeders;

use App\Domain\RequestStatus\RequestState;
use App\Models\FoodCategory;
use App\Models\FoodDonation;
use App\Models\FoodRequest;
use App\Models\PartnerProfile;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class FoodRequestSeeder extends Seeder
{
    /** Plain token printed for the demo; only its hash is stored. */
    public const DEMO_API_TOKEN = 'foodlink-charity-demo-token';

    public function run(): void
    {
        $charityUser = User::where('email', 'charity@foodlink.test')->first();
        $charity = $charityUser?->partnerProfile;
        $donor = PartnerProfile::whereHas('user', fn ($q) => $q->where('role', 'FOOD_DONOR'))->first();

        if ($charity === null || $donor === null) {
            $this->command?->warn('FoodRequestSeeder skipped: run DatabaseSeeder first.');

            return;
        }

        // Secure coding: the database keeps the SHA-256 hash, never the token.
        $charityUser->api_token = hash('sha256', self::DEMO_API_TOKEN);
        $charityUser->save();

        $categories = FoodCategory::orderBy('category_id')->get();
        $bakery = $categories->firstWhere('category_name', 'Bakery') ?? $categories->first();
        $vegetables = $categories->firstWhere('category_name', 'Vegetables') ?? $categories->first();
        $canned = $categories->firstWhere('category_name', 'Canned Food') ?? $categories->first();

        // 1. Pending request, deadline far away.
        $this->makeRequest($charity->profile_id, $canned->category_id, 80, 'boxes', now()->addDays(6),
            RequestState::PENDING, 'Monthly food bank top-up for 60 families.');

        // 2. Urgent pending request, deadline within the next few hours.
        $this->makeRequest($charity->profile_id, $bakery->category_id, 40, 'packs', now()->addHours(8),
            RequestState::PENDING, 'Bread for tomorrow morning breakfast programme.');

        // 3. Partially fulfilled request with a live reservation against a donation.
        $partial = $this->makeRequest($charity->profile_id, $vegetables->category_id, 60, 'kg', now()->addDays(2),
            RequestState::PARTIALLY_FULFILLED, 'Fresh vegetables for the community kitchen.');

        $donation = FoodDonation::create([
            'donor_id' => $donor->profile_id,
            'category_id' => $vegetables->category_id,
            'food_name' => 'Surplus Mixed Vegetables',
            'description' => 'Assorted vegetables from the evening market, still fresh.',
            'donation_quantity' => 50,
            'current_quantity' => 25,
            'measurement_unit' => 'kg',
            'expiry_datetime' => now()->addDays(2),
            'pickup_address' => '12 Donor Street',
            'storage_type' => 'Chilled',
            'halal_status' => 'Halal',
            'donation_status' => 'AVAILABLE',
        ]);

        // withoutEvents keeps the seeded status exactly as written; in the running
        // application the ReservationObserver would recalculate it instead.
        Reservation::withoutEvents(fn () => Reservation::create([
            'request_id' => $partial->request_id,
            'donation_id' => $donation->donation_id,
            'reserved_quantity' => 25,
            'reservation_status' => Reservation::CONFIRMED,
            'pickup_deadline' => now()->addDay(),
        ]));

        // 4. Completed request, kept for the history tab.
        $this->makeRequest($charity->profile_id, $bakery->category_id, 20, 'trays', now()->subDay(),
            RequestState::COMPLETED, 'Pastries for the weekend soup kitchen.', fulfilled: 20);

        // 5. Expired request, deadline passed with nothing committed.
        $this->makeRequest($charity->profile_id, $canned->category_id, 30, 'boxes', now()->subDays(2),
            RequestState::EXPIRED, 'Canned food drive that no donor could cover in time.');

        $this->command?->info('Module 3.3 demo API token for charity@foodlink.test: '.self::DEMO_API_TOKEN);
    }

    private function makeRequest(
        int $charityId,
        int $categoryId,
        float $quantity,
        string $unit,
        $deadline,
        string $status,
        string $notes,
        float $fulfilled = 0,
    ): FoodRequest {
        $request = new FoodRequest();
        $request->charity_id = $charityId;
        $request->category_id = $categoryId;
        $request->requested_quantity = $quantity;
        $request->fulfilled_quantity = $fulfilled;
        $request->unit = $unit;
        $request->notes = $notes;
        $request->request_deadline = $deadline;
        $request->request_status = $status;
        $request->save();

        return $request;
    }
}
