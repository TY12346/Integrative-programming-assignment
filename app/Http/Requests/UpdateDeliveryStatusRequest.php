<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Http/Requests/UpdateDeliveryStatusRequest.php
 */

namespace App\Http\Requests;

use App\Models\DeliveryTask;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $delivery = $this->route('delivery');
        $user = $this->user();

        if (! $delivery instanceof DeliveryTask || $user === null) {
            return false;
        }

        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        return $user->role === User::ROLE_VOLUNTEER
            && $user->partnerProfile !== null
            && (int) $delivery->volunteer_id === (int) $user->partnerProfile->profile_id;
    }

    public function rules(): array
    {
        return [
            'delivery_status' => ['required', Rule::in(DeliveryTask::STATUSES)],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
