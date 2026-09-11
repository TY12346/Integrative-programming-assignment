<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Purpose: Validation and authorisation for reserving a donation against a food
 *          request ("Track Reserved Quantity").
 *
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReserveDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $foodRequest = $this->route('foodRequest');

        return $foodRequest !== null && ($this->user()?->can('reserve', $foodRequest) ?? false);
    }

    public function rules(): array
    {
        return [
            'donation_id' => ['required', 'integer', 'exists:food_donations,donation_id'],
            'reserved_quantity' => ['required', 'numeric', 'gt:0', 'max:'.config('foodlink.request.max_quantity')],
            'pickup_deadline' => ['required', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_deadline.after' => 'The pickup deadline must be in the future.',
            'reserved_quantity.gt' => 'Please reserve a quantity greater than zero.',
        ];
    }
}
