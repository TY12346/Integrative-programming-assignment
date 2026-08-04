<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Http/Resources/DonationResource.php
 * Purpose: JSON representation of an active donation as consumed by the
 *          charity-facing endpoints of this module ("Display Active
 *          Donations"). Only the fields a charity needs in order to decide are
 *          exposed; donor contact details stay out of the payload.
 */

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DonationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'donation_id' => (int) $this->donation_id,
            'food_name' => $this->food_name,
            'description' => $this->description,
            'category_id' => (int) $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->category_name),
            'current_quantity' => (float) $this->current_quantity,
            'measurement_unit' => $this->measurement_unit,
            'expiry_datetime' => $this->expiry_datetime?->toIso8601String(),
            'pickup_address' => $this->pickup_address,
            'storage_type' => $this->storage_type,
            'halal_status' => $this->halal_status,
            'donation_status' => $this->donation_status,
        ];
    }
}
