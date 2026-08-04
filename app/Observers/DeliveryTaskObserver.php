<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Observers/DeliveryTaskObserver.php
 * Purpose: INTEGRATION DRAFT between the Delivery and Impact Tracking module
 *          (3.4, Khoo Sheng Hao) and my Food Request Management module.
 *
 *          Module 3.4 only has to do what it already does - set
 *          delivery_status on a delivery task. This observer translates that
 *          into the vocabulary my module tracks:
 *
 *            delivery DELIVERED  -> reservation COMPLETED
 *                                -> ReservationObserver recalculates the
 *                                   request's fulfilled quantity and status
 *            delivery CANCELLED  -> reservation released, quantity returned to
 *                                   the donor (module 3.2)
 *
 *          Written as an observer rather than a change inside my team mate's
 *          controller so that neither module has to know about the other's
 *          internals, and so the rule also holds when a delivery is updated
 *          from the API or from a seeder.
 *
 *          The handler is idempotent: if module 3.4 later completes the
 *          reservation itself, this observer sees a non-active reservation and
 *          does nothing.
 */

namespace App\Observers;

use App\Models\DeliveryTask;
use App\Models\Reservation;
use App\Services\FoodRequestService;

class DeliveryTaskObserver
{
    public function __construct(private readonly FoodRequestService $service)
    {
    }

    public function updated(DeliveryTask $delivery): void
    {
        if (! $delivery->wasChanged('delivery_status')) {
            return;
        }

        $reservation = $delivery->reservation()->first();

        if ($reservation === null || ! $reservation->isActive()) {
            return;   // Already settled by module 3.4 or by the charity.
        }

        match ($delivery->delivery_status) {
            'DELIVERED' => $this->complete($reservation),
            'CANCELLED' => $this->service->cancelReservation($reservation, 'Delivery task cancelled by module 3.4.'),
            default => null,
        };
    }

    private function complete(Reservation $reservation): void
    {
        $reservation->reservation_status = Reservation::COMPLETED;
        $reservation->save();   // ReservationObserver picks it up from here.
    }
}
