<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Http/Requests/StoreFoodRequestRequest.php
 * Purpose: Validation and authorisation for "Create Food Request".
 *
 * Secure coding: a Form Request rejects the payload before it ever reaches the
 * controller. Only the listed keys are returned by validated(), the unit is
 * checked against a whitelist rather than accepted as free text, the category
 * must exist, and the length limits stop oversized input from reaching the
 * database.
 */

namespace App\Http\Requests;

use App\Models\FoodRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFoodRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FoodRequest::class) ?? false;
    }

    public function rules(): array
    {
        $config = config('foodlink.request');

        return [
            'category_id' => ['required', 'integer', 'exists:food_categories,category_id'],
            'requested_quantity' => ['required', 'numeric', 'gt:0', 'max:'.$config['max_quantity']],
            'unit' => ['required', 'string', Rule::in($config['units'])],
            'notes' => ['nullable', 'string', 'max:500'],
            'request_deadline' => [
                'required',
                'date',
                'after:'.now()->addHours($config['min_deadline_hours'])->toDateTimeString(),
                'before:'.now()->addDays($config['max_deadline_days'])->toDateTimeString(),
            ],
        ];
    }

    public function messages(): array
    {
        $config = config('foodlink.request');

        return [
            'request_deadline.after' => 'The fulfilment deadline must be at least '.$config['min_deadline_hours'].' hour(s) from now.',
            'request_deadline.before' => 'The fulfilment deadline cannot be more than '.$config['max_deadline_days'].' days away.',
            'requested_quantity.gt' => 'Please request a quantity greater than zero.',
            'unit.in' => 'Please choose one of the supported measurement units.',
        ];
    }

    /** Trim the free text before it is validated and stored. */
    protected function prepareForValidation(): void
    {
        if ($this->has('notes')) {
            $this->merge(['notes' => trim((string) $this->input('notes'))]);
        }
    }
}
