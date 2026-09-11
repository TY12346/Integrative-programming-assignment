<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Http/Requests/StoreDeliveryTaskRequest.php
 */

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [User::ROLE_VOLUNTEER, User::ROLE_ADMIN], true);
    }

    public function rules(): array
    {
        return [
            'reservation_id' => ['required', 'integer', 'exists:reservations,reservation_id'],
            'volunteer_id' => ['nullable', 'integer', 'exists:partner_profiles,profile_id'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
