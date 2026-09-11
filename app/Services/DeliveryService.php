<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Services/DeliveryService.php
 * Purpose: Business rules for creating delivery tasks and changing status.
 */

namespace App\Services;

use App\Models\DeliveryStatusHistory;
use App\Models\DeliveryTask;
use App\Models\PartnerProfile;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    public function __construct(private readonly DeliveryNoteSanitizer $notes)
    {
    }

    public function create(User $actor, array $data): DeliveryTask
    {
        return DB::transaction(function () use ($actor, $data) {
            /** @var Reservation $reservation */
            $reservation = Reservation::query()
                ->with(['donation', 'request.charity'])
                ->lockForUpdate()
                ->findOrFail((int) $data['reservation_id']);

            if (! $reservation->isActive()) {
                throw ValidationException::withMessages([
                    'reservation_id' => 'Only an active reservation can be turned into a delivery task.',
                ]);
            }

            if ($reservation->deliveryTask()->exists()) {
                throw ValidationException::withMessages([
                    'reservation_id' => 'This reservation already has a delivery task.',
                ]);
            }

            $volunteer = $this->resolveVolunteer($actor, $data['volunteer_id'] ?? null);

            $task = DeliveryTask::create([
                'reservation_id' => $reservation->reservation_id,
                'volunteer_id' => $volunteer->profile_id,
                'pickup_address' => $reservation->donation->pickup_address,
                'delivery_address' => $reservation->request->charity?->address ?? 'Charity address unavailable',
                'delivery_status' => DeliveryTask::ASSIGNED,
                'delivery_notes' => $this->notes->sanitize($data['delivery_notes'] ?? null),
            ]);

            DeliveryStatusHistory::create([
                'delivery_id' => $task->delivery_id,
                'old_status' => null,
                'new_status' => DeliveryTask::ASSIGNED,
                'changed_by' => $actor->user_id,
                'remarks' => $this->notes->sanitize($data['delivery_notes'] ?? 'Delivery task created.'),
            ]);

            return $task->load(['reservation.donation', 'reservation.request.charity', 'volunteer.user']);
        });
    }

    public function updateStatus(DeliveryTask $delivery, User $actor, array $data): DeliveryTask
    {
        return DB::transaction(function () use ($delivery, $actor, $data) {
            /** @var DeliveryTask $locked */
            $locked = DeliveryTask::query()->lockForUpdate()->findOrFail($delivery->delivery_id);
            $newStatus = (string) $data['delivery_status'];

            if ($newStatus === $locked->delivery_status) {
                throw ValidationException::withMessages([
                    'delivery_status' => 'Choose a different status from the current status.',
                ]);
            }

            if (! in_array($newStatus, $locked->allowedNextStatuses(), true)) {
                throw ValidationException::withMessages([
                    'delivery_status' => "Invalid delivery transition from {$locked->delivery_status} to {$newStatus}.",
                ]);
            }

            $oldStatus = $locked->delivery_status;
            $remarks = $this->notes->sanitize($data['remarks'] ?? null);

            $attributes = [
                'delivery_status' => $newStatus,
                'delivery_notes' => $remarks ?? $locked->delivery_notes,
            ];

            if ($newStatus === DeliveryTask::PICKED_UP) {
                $attributes['picked_up_at'] = now();
            }

            if ($newStatus === DeliveryTask::DELIVERED) {
                $attributes['delivered_at'] = now();
            }

            // Eloquent update emits the model "updated" event. DeliveryImpactObserver
            // observes that event and records impact when status becomes DELIVERED.
            $locked->update($attributes);

            DeliveryStatusHistory::create([
                'delivery_id' => $locked->delivery_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $actor->user_id,
                'remarks' => $remarks,
            ]);

            return $locked->fresh(['statusHistories.changedBy', 'impacts', 'reservation.donation', 'volunteer.user']);
        });
    }

    private function resolveVolunteer(User $actor, mixed $requestedVolunteerId): PartnerProfile
    {
        if ($actor->role === User::ROLE_VOLUNTEER) {
            $profile = $actor->partnerProfile;

            if ($profile === null) {
                throw ValidationException::withMessages(['volunteer_id' => 'Volunteer profile not found.']);
            }

            return $profile;
        }

        if ($actor->role !== User::ROLE_ADMIN || $requestedVolunteerId === null) {
            throw ValidationException::withMessages([
                'volunteer_id' => 'An administrator must select a volunteer.',
            ]);
        }

        $profile = PartnerProfile::query()->with('user')->find((int) $requestedVolunteerId);

        if ($profile === null || $profile->user?->role !== User::ROLE_VOLUNTEER
            || $profile->user?->account_status !== User::STATUS_ACTIVE
            || $profile->verification_status !== 'APPROVED') {
            throw ValidationException::withMessages([
                'volunteer_id' => 'Select an active, approved volunteer.',
            ]);
        }

        return $profile;
    }
}
