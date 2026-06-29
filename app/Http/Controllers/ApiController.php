<?php

namespace App\Http\Controllers;

use App\Models\DeliveryTask;
use App\Models\FoodDonation;
use App\Models\FoodRequest;
use App\Models\PartnerProfile;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    private function wrap($data)
    {
        return response()->json([
            'status' => 'success',
            'timestamp' => now()->toISOString(),
            'data' => $data,
        ]);
    }

    public function partnerStatus(int $id)
    {
        return $this->wrap(
            PartnerProfile::findOrFail($id)->only('profile_id', 'verification_status')
        );
    }

    public function availableDonations(Request $request)
    {
        $donations = FoodDonation::query()
            ->where('donation_status', 'AVAILABLE')
            ->where('expiry_datetime', '>', now())
            ->when($request->timestamp, fn ($query) => $query->where('donation_datetime', '>=', $request->timestamp))
            ->get();

        return $this->wrap($donations);
    }

    public function requestReservations(Request $request, int $id)
    {
        $reservations = FoodRequest::findOrFail($request->requestID ?? $id)
            ->reservations()
            ->with('donation')
            ->when($request->timestamp, fn ($query) => $query->where('created_at', '>=', $request->timestamp))
            ->get();

        return $this->wrap($reservations);
    }

    public function deliveryStatus(int $id)
    {
        return $this->wrap(
            DeliveryTask::findOrFail($id)->only('delivery_id', 'delivery_status', 'picked_up_at', 'delivered_at')
        );
    }
}
