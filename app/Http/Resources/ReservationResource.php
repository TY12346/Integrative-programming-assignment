<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Purpose: JSON representation of a reservation. 
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
