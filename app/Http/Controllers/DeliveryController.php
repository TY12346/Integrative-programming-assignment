<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Http/Controllers/DeliveryController.php
 * Purpose: MVC controller for Create, View and Update Delivery Task features.
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryTaskRequest;
use App\Http\Requests\UpdateDeliveryStatusRequest;
use App\Models\DeliveryImpact;
use App\Models\DeliveryTask;
use App\Models\PartnerProfile;
use App\Models\Reservation;
use App\Models\User;
use App\Services\DeliveryService;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function __construct(private readonly DeliveryService $deliveries)
    {
    }

    public function index(Request $request)
    {
        $query = DeliveryTask::query()
            ->with(['reservation.donation', 'reservation.request.charity', 'volunteer.user'])
            ->orderByDesc('assigned_at');

        if ($request->user()->role === User::ROLE_VOLUNTEER) {
            $profileId = $request->user()->partnerProfile?->profile_id;
            $query->where('volunteer_id', $profileId ?? 0);
        }

        $tasks = $query->get();

        $visibleIds = $tasks->pluck('delivery_id');
        $impacts = DeliveryImpact::query()->whereIn('delivery_id', $visibleIds)->get();

        return view('deliveries.index', [
            'deliveries' => $tasks,
            'completedDeliveries' => $impacts->where('impact_type', 'FOOD_DELIVERED')->count(),
            'impactByUnit' => $impacts
                ->where('impact_type', 'FOOD_DELIVERED')
                ->groupBy('measurement_unit')
                ->map(fn ($rows) => $rows->sum(fn ($row) => (float) $row->quantity)),
        ]);
    }

    public function create(Request $request)
    {
        $reservations = Reservation::query()
            ->active()
            ->whereDoesntHave('deliveryTask')
            ->with(['donation', 'request.charity'])
            ->orderBy('pickup_deadline')
            ->get();

        $volunteers = collect();
        if ($request->user()->role === User::ROLE_ADMIN) {
            $volunteers = PartnerProfile::query()
                ->where('verification_status', 'APPROVED')
                ->whereHas('user', fn ($q) => $q
                    ->where('role', User::ROLE_VOLUNTEER)
                    ->where('account_status', User::STATUS_ACTIVE))
                ->with('user')
                ->get();
        }

        return view('deliveries.create', compact('reservations', 'volunteers'));
    }

    public function store(StoreDeliveryTaskRequest $request)
    {
        $task = $this->deliveries->create($request->user(), $request->validated());

        return redirect()->route('deliveries.show', $task)
            ->with('message', 'Delivery task created successfully.');
    }

    /** Backward-compatible endpoint used by the existing reservation screen. */
    public function createFromReservation(Request $request, Reservation $reservation)
    {
        // A volunteer can claim it immediately. An administrator must first
        // choose which approved volunteer will receive the task.
        if ($request->user()->role === User::ROLE_ADMIN) {
            return redirect()->route('deliveries.create', ['reservation_id' => $reservation->reservation_id]);
        }

        $data = ['reservation_id' => $reservation->reservation_id];
        $task = $this->deliveries->create($request->user(), $data);

        return redirect()->route('deliveries.show', $task)
            ->with('message', 'Delivery task created successfully.');
    }

    public function show(Request $request, DeliveryTask $delivery)
    {
        $this->ensureVisibleTo($request, $delivery);

        $delivery->load([
            'reservation.donation.donor.user',
            'reservation.request.charity.user',
            'volunteer.user',
            'statusHistories.changedBy',
            'impacts',
        ]);

        return view('deliveries.show', compact('delivery'));
    }

    public function edit(Request $request, DeliveryTask $delivery)
    {
        $this->ensureVisibleTo($request, $delivery);
        return view('deliveries.edit', compact('delivery'));
    }

    public function update(
    UpdateDeliveryStatusRequest $request,
    DeliveryTask $delivery
        ) {
    $this->ensureVisibleTo($request, $delivery);

    $this->deliveries->updateStatus(
        $delivery,
        $request->user(),
        $request->validated()
        );

    return redirect()
        ->route('deliveries.show', $delivery)
        ->with(
            'message',
            'Delivery status updated successfully.'
            );
        }

    private function ensureVisibleTo(Request $request, DeliveryTask $delivery): void
    {
        if ($request->user()->role === User::ROLE_ADMIN) {
            return;
        }

        abort_unless(
            $request->user()->role === User::ROLE_VOLUNTEER
            && $request->user()->partnerProfile !== null
            && (int) $delivery->volunteer_id === (int) $request->user()->partnerProfile->profile_id,
            403
        );
    }
}
