<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Services/FoodRequestService.php
 * Purpose: Business rules of the Food Request Management module. Controllers
 *          only validate input and choose a view; every rule about who may do
 *          what, how quantities move and when the status changes lives here, so
 *          the web controller and the REST web service behave identically.
 *
 * Secure coding notes:
 *   - charity_id always comes from the authenticated partner profile, never
 *     from the request payload.
 *   - Reserving runs inside a database transaction with SELECT ... FOR UPDATE
 *     on both the request and the donation row, so two charities reserving the
 *     same donation at the same moment cannot oversubscribe it.
 *   - Every rule is re-checked here even though the form already validated it,
 *     because the REST API and the browser form are two different entry points.
 *   - Every create, cancel, reserve, release and status transition is written to
 *     the application log with the acting user id, which is the module's audit
 *     trail. The analysis class diagram has no history entity for food requests,
 *     so no extra table is introduced for this.
 */

namespace App\Services;

use App\Domain\RequestStatus\CancelledState;
use App\Domain\RequestStatus\PendingState;
use App\Domain\RequestStatus\RequestProgress;
use App\Models\FoodDonation;
use App\Models\FoodRequest;
use App\Models\PartnerProfile;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FoodRequestService
{
    /* ------------------------------------------------------------------ */
    /* 1. Create Food Request                                              */
    /* ------------------------------------------------------------------ */

    public function create(PartnerProfile $charity, array $data): FoodRequest
    {
        return DB::transaction(function () use ($charity, $data) {
            $request = new FoodRequest();
            $request->fill($this->onlyEditable($data));
            $request->charity_id = $charity->profile_id;          // never from input
            $request->fulfilled_quantity = 0;
            $request->request_status = (new PendingState())->code();
            $request->save();

            Log::info('Food request created.', [
                'request_id' => $request->request_id,
                'charity_id' => $charity->profile_id,
                'user_id' => Auth::id(),
            ]);

            return $request;
        });
    }

    /* ------------------------------------------------------------------ */
    /* 3. Edit Request Details                                             */
    /* ------------------------------------------------------------------ */

    public function update(FoodRequest $request, array $data): FoodRequest
    {
        // "before the processing phase begins" - the state object owns this rule.
        if (! $request->state()->canEdit()) {
            throw ValidationException::withMessages([
                'request' => 'This request can no longer be edited because donors have already started fulfilling it.',
            ]);
        }

        $newQuantity = (float) ($data['requested_quantity'] ?? $request->requested_quantity);
        $committed = $request->progress()->committed();

        if ($newQuantity < $committed) {
            throw ValidationException::withMessages([
                'requested_quantity' => 'The requested quantity cannot be lower than the quantity already committed ('.$committed.').',
            ]);
        }

        $request->fill($this->onlyEditable($data));
        $request->save();

        Log::info('Food request updated.', ['request_id' => $request->request_id, 'user_id' => Auth::id()]);

        return $this->refreshStatus($request);
    }

    /* ------------------------------------------------------------------ */
    /* 4. Cancel Food Request                                              */
    /* ------------------------------------------------------------------ */

    public function cancel(FoodRequest $request, ?string $reason = null): FoodRequest
    {
        if (! $request->state()->canCancel()) {
            throw ValidationException::withMessages([
                'request' => 'A '.strtolower($request->state()->label()).' request can no longer be cancelled.',
            ]);
        }

        return DB::transaction(function () use ($request, $reason) {
            $locked = FoodRequest::query()->whereKey($request->request_id)->lockForUpdate()->firstOrFail();

            if ($locked->reservations()->completed()->exists()) {
                throw ValidationException::withMessages([
                    'request' => 'Part of this request has already been delivered, so it cannot be cancelled.',
                ]);
            }

            // Give the still reserved quantity back to the donors.
            foreach ($locked->reservations()->active()->get() as $reservation) {
                $this->releaseReservation($reservation, 'Parent food request cancelled.');
            }

            $old = $locked->request_status;
            $locked->request_status = (new CancelledState())->code();
            $locked->save();

            Log::info('Food request cancelled.', [
                'request_id' => $locked->request_id,
                'from_status' => $old,
                'reason' => $reason ?: 'Cancelled by charity.',
                'user_id' => Auth::id(),
            ]);

            return $locked;
        });
    }

    /* ------------------------------------------------------------------ */
    /* 5. Track Reserved Quantity - reserve a donation for a request       */
    /* ------------------------------------------------------------------ */

    public function reserve(FoodRequest $request, int $donationId, float $quantity, string $pickupDeadline): Reservation
    {
        return DB::transaction(function () use ($request, $donationId, $quantity, $pickupDeadline) {
            /** @var FoodRequest $lockedRequest */
            $lockedRequest = FoodRequest::query()->whereKey($request->request_id)->lockForUpdate()->firstOrFail();

            if (! $lockedRequest->state()->canReserve()) {
                throw ValidationException::withMessages([
                    'request' => 'Donations can no longer be reserved for a '.strtolower($lockedRequest->state()->label()).' request.',
                ]);
            }

            /** @var FoodDonation|null $donation */
            $donation = FoodDonation::query()->whereKey($donationId)->lockForUpdate()->first();

            if ($donation === null || $donation->donation_status !== 'AVAILABLE' || $donation->expiry_datetime->isPast()) {
                throw ValidationException::withMessages([
                    'donation_id' => 'That donation is no longer available.',
                ]);
            }

            if ($quantity > (float) $donation->current_quantity) {
                throw ValidationException::withMessages([
                    'reserved_quantity' => 'Only '.(float) $donation->current_quantity.' '.$donation->measurement_unit.' are still available in this donation.',
                ]);
            }

            $outstanding = $lockedRequest->progress()->outstanding();

            if ($quantity > $outstanding) {
                throw ValidationException::withMessages([
                    'reserved_quantity' => 'Your request only needs '.$outstanding.' '.$lockedRequest->unit.' more.',
                ]);
            }

            $deadline = Carbon::parse($pickupDeadline);

            if ($deadline->greaterThan($donation->expiry_datetime)) {
                throw ValidationException::withMessages([
                    'pickup_deadline' => 'The pickup deadline must be before the donation expires on '.$donation->expiry_datetime->format('d M Y H:i').'.',
                ]);
            }

            // Take the quantity out of the donor's stock (module 3.2 data).
            $donation->current_quantity = (float) $donation->current_quantity - $quantity;

            if ((float) $donation->current_quantity <= 0) {
                $donation->donation_status = 'RESERVED';
            }

            $donation->save();

            $reservation = Reservation::create([
                'request_id' => $lockedRequest->request_id,
                'donation_id' => $donation->donation_id,
                'reserved_quantity' => $quantity,
                'reservation_status' => Reservation::CONFIRMED,
                'pickup_deadline' => $deadline,
            ]);

            Log::info('Donation reserved for a food request.', [
                'request_id' => $lockedRequest->request_id,
                'donation_id' => $donation->donation_id,
                'quantity' => $quantity,
                'user_id' => Auth::id(),
            ]);

            // The ReservationObserver recalculates the request status from here.
            return $reservation;
        });
    }

    /** Withdraw a single reservation and hand the quantity back to the donor. */
    public function cancelReservation(Reservation $reservation, ?string $reason = null): void
    {
        if (! $reservation->isCancellable()) {
            throw ValidationException::withMessages([
                'reservation' => 'This reservation has already been completed and cannot be withdrawn.',
            ]);
        }

        DB::transaction(function () use ($reservation, $reason) {
            $this->releaseReservation($reservation, $reason ?: 'Reservation withdrawn by charity.');
        });
    }

    /* ------------------------------------------------------------------ */
    /* 6 + 7. Monitor deadline / Check request status                      */
    /* ------------------------------------------------------------------ */

    /**
     * Recalculate the tracked quantities and move the request to the state the
     * numbers imply. Called by the ReservationObserver, by the API and by the
     * request:refresh-statuses command, so there is exactly one place where the
     * status of a request is decided.
     */
    public function refreshStatus(FoodRequest $request): FoodRequest
    {
        $fulfilled = (float) $request->reservations()->completed()->sum('reserved_quantity');
        $reserved = (float) $request->reservations()->active()->sum('reserved_quantity');

        $progress = new RequestProgress(
            requested: (float) $request->requested_quantity,
            fulfilled: $fulfilled,
            reserved: $reserved,
            deadlinePassed: $request->isPastDeadline(),
        );

        $old = $request->request_status;
        $next = $request->state()->next($progress)->code();

        $request->fulfilled_quantity = $fulfilled;
        $request->request_status = $next;
        $request->save();

        if ($old !== $next) {
            Log::info('Food request status changed.', [
                'request_id' => $request->request_id,
                'from_status' => $old,
                'to_status' => $next,
                'requested' => $progress->requested,
                'reserved' => $progress->reserved,
                'fulfilled' => $progress->fulfilled,
                'user_id' => Auth::id(),      // null when the system triggered it
            ]);
        }

        // Keep the derived accessor in step with what was just written.
        // syncOriginalAttribute stops this virtual column from being treated as
        // a dirty database column on a later save().
        $request->setAttribute('reserved_quantity', $reserved);
        $request->syncOriginalAttribute('reserved_quantity');

        return $request;
    }

    /**
     * Sweep used by the scheduled command: flag active requests whose
     * fulfilment deadline has passed. Returns the number of requests changed.
     */
    public function expireOverdueRequests(): int
    {
        $changed = 0;

        FoodRequest::query()
            ->active()
            ->where('request_deadline', '<', now())
            ->chunkById(100, function ($requests) use (&$changed) {
                foreach ($requests as $request) {
                    $before = $request->request_status;
                    $this->refreshStatus($request);
                    $changed += $request->request_status !== $before ? 1 : 0;
                }
            }, 'request_id');

        return $changed;
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    /** Return the reserved quantity to the donation and close the reservation. */
    private function releaseReservation(Reservation $reservation, string $reason): void
    {
        /** @var FoodDonation|null $donation */
        $donation = FoodDonation::query()->whereKey($reservation->donation_id)->lockForUpdate()->first();

        if ($donation !== null) {
            $donation->current_quantity = (float) $donation->current_quantity + (float) $reservation->reserved_quantity;

            // Put the donation back on the board if it is still usable.
            if ($donation->donation_status === 'RESERVED' && $donation->expiry_datetime->isFuture()) {
                $donation->donation_status = 'AVAILABLE';
            }

            $donation->save();
        }

        $reservation->reservation_status = Reservation::CANCELLED;
        $reservation->save();

        Log::info('Reservation released.', [
            'reservation_id' => $reservation->reservation_id,
            'reason' => $reason,
            'user_id' => Auth::id(),
        ]);
    }

    /** Only the columns a charity is allowed to set are passed to the model. */
    private function onlyEditable(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'category_id', 'requested_quantity', 'unit', 'notes', 'request_deadline',
        ]));
    }

}
