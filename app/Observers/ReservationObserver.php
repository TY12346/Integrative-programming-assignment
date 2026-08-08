<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Observers/ReservationObserver.php
 * Purpose: OBSERVER design pattern. The reserved and fulfilled quantities of a
 *          food request must stay correct no matter which module changed a
 *          reservation - my own reserve screen, or the Delivery and Impact
 *          Tracking module (3.4) marking a delivery as completed.
 *
 *          Instead of asking every module to remember to call my service, this
 *          observer listens to the Reservation model events and recalculates
 *          the parent request itself. That is the integration contract for
 *          module 3.4: set reservation_status = 'COMPLETED' and my module
 *          updates the request status and history automatically.
 */

namespace App\Observers;

use App\Models\Reservation;
use App\Services\FoodRequestService;

class ReservationObserver
{
    public function __construct(private readonly FoodRequestService $service)
    {
    }

    public function created(Reservation $reservation): void
    {
        $this->sync($reservation);
    }

    public function updated(Reservation $reservation): void
    {
        // Only quantity or status changes can move the request forward.
        if (! $reservation->wasChanged(['reservation_status', 'reserved_quantity'])) {
            return;
        }

        $this->sync($reservation);
    }

    public function deleted(Reservation $reservation): void
    {
        $this->sync($reservation);
    }

    private function sync(Reservation $reservation): void
    {
        $request = $reservation->request()->first();

        if ($request !== null) {
            $this->service->refreshStatus($request);
        }
    }
}
