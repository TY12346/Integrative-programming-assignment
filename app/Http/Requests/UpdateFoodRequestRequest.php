<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Purpose: Validation and authorisation for "Edit Request Details". 
 */

namespace App\Http\Requests;

class UpdateFoodRequestRequest extends StoreFoodRequestRequest
{
    public function authorize(): bool
    {
        $foodRequest = $this->route('foodRequest');

        return $foodRequest !== null && ($this->user()?->can('update', $foodRequest) ?? false);
    }
}
