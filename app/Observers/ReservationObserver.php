<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * File   : app/Observers/ReservationObserver.php
 * Purpose: OBSERVER design pattern. The reserved and fulfilled quantities of a
 *          food request must stay correct no matter which module changed a
 *          reservation - my own reserve screen, or the Delivery and Impact
 *          Tracking module (3.4) marking a delivery as completed.
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
