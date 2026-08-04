<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Http/Resources/ReservationResource.php
 * Purpose: JSON representation of a reservation. This is the payload the
 *          Delivery and Impact Tracking module (3.4) reads when it turns a
 *          confirmed reservation into a delivery task.
 */

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'reservation_id' => (int) $this->reservation_id,
            'request_id' => (int) $this->request_id,
            'donation_id' => (int) $this->donation_id,
            'reserved_quantity' => (float) $this->reserved_quantity,
            'reservation_status' => $this->reservation_status,
            'pickup_deadline' => $this->pickup_deadline?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'donation' => new DonationResource($this->whenLoaded('donation')),
            'delivery' => $this->whenLoaded('deliveryTask', fn () => $this->deliveryTask === null ? null : [
                'delivery_id' => (int) $this->deliveryTask->delivery_id,
                'delivery_status' => $this->deliveryTask->delivery_status,
            ]),
        ];
    }
}
