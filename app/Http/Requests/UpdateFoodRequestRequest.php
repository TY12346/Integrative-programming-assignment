<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Http/Requests/UpdateFoodRequestRequest.php
 * Purpose: Validation and authorisation for "Edit Request Details". The rules
 *          are the same as when creating, but authorisation additionally checks
 *          ownership and that the request has not entered processing yet.
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
