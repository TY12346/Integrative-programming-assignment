<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Purpose: Transforms a FoodRequest model into the JSON representation
 *          published by the module's REST web service.
 */

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FoodRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        $progress = $this->progress();

        return [
            'request_id' => (int) $this->request_id,
            'charity_id' => (int) $this->charity_id,
            'category' => [
                'category_id' => (int) $this->category_id,
                'category_name' => $this->whenLoaded('category', fn () => $this->category->category_name),
            ],
            'requested_quantity' => (float) $this->requested_quantity,
            'reserved_quantity' => round($progress->reserved, 2),
            'fulfilled_quantity' => (float) $this->fulfilled_quantity,
            'outstanding_quantity' => round($progress->outstanding(), 2),
            'progress_percentage' => $progress->percentage(),
            'unit' => $this->unit,
            'notes' => $this->notes,
            'request_deadline' => $this->request_deadline?->toIso8601String(),
            'hours_to_deadline' => $this->hours_to_deadline,
            'urgency' => $this->urgency,
            'status' => [
                'code' => $this->state()->code(),
                'label' => $this->state()->label(),
                'can_edit' => $this->state()->canEdit(),
                'can_cancel' => $this->state()->canCancel(),
                'can_reserve' => $this->state()->canReserve(),
                'is_final' => $this->state()->isFinal(),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'reservations' => ReservationResource::collection($this->whenLoaded('reservations')),
        ];
    }
}
