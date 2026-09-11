<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Purpose: INTEGRATION DRAFT between the Delivery and Impact Tracking module
 *          (3.4, Khoo Sheng Hao) and  Food Request Management module.
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
