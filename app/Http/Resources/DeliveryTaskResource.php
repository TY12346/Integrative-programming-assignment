<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Http/Resources/DeliveryTaskResource.php
 * Purpose: Consistent JSON representation for the Delivery REST web service.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'delivery_id' => (int) $this->delivery_id,
            'reservation_id' => (int) $this->reservation_id,
            'volunteer_id' => (int) $this->volunteer_id,
            'volunteer_name' => $this->volunteer?->user?->full_name,
            'pickup_address' => $this->pickup_address,
            'delivery_address' => $this->delivery_address,
            'delivery_status' => $this->delivery_status,
            'delivery_notes' => $this->delivery_notes,
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'picked_up_at' => $this->picked_up_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'impact' => $this->whenLoaded('impacts', fn () => $this->impacts->map(fn ($impact) => [
                'impact_type' => $impact->impact_type,
                'quantity' => $impact->quantity === null ? null : (float) $impact->quantity,
                'measurement_unit' => $impact->measurement_unit,
                'description' => $impact->description,
                'recorded_at' => $impact->recorded_at?->toIso8601String(),
            ])->values()),
        ];
    }
}
