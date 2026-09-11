<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Observers/DeliveryImpactObserver.php
 * Design Pattern: Observer Pattern
 *
 * This observer subscribes to DeliveryTask model events. The controller/service
 * only changes delivery_status; when that status becomes DELIVERED, this class
 * automatically records the measurable food-delivery impact. The producer and
 * observer are therefore loosely coupled.
 */

namespace App\Observers;

use App\Models\DeliveryImpact;
use App\Models\DeliveryTask;

class DeliveryImpactObserver
{
    public function updated(DeliveryTask $delivery): void
    {
        if (! $delivery->wasChanged('delivery_status') || $delivery->delivery_status !== DeliveryTask::DELIVERED) {
            return;
        }

        $delivery->loadMissing('reservation.donation');
        $reservation = $delivery->reservation;

        if ($reservation === null) {
            return;
        }

        $unit = $reservation->donation?->measurement_unit ?? 'unit';
        $quantity = (float) $reservation->reserved_quantity;

        // Idempotent: the same delivered task cannot create duplicate impact
        // even if the event is accidentally triggered again.
        DeliveryImpact::firstOrCreate(
            [
                'delivery_id' => $delivery->delivery_id,
                'impact_type' => 'FOOD_DELIVERED',
            ],
            [
                'quantity' => $quantity,
                'measurement_unit' => $unit,
                'description' => "Completed delivery of {$quantity} {$unit} of donated food.",
            ]
        );
    }
}
