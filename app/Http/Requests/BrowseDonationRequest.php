<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Http/Requests/BrowseDonationRequest.php
 * Purpose: Validation for "Display Active Donations", "Filter Donation Options"
 *          and "Search Specific Donations". It converts the query string into
 *          the clean criteria array understood by DonationFilterPipeline.
 *
 * Secure coding: the search box is length limited and stripped of control
 * characters before it is used, and only known criteria keys are forwarded, so
 * arbitrary query parameters cannot influence the donation query.
 */

namespace App\Http\Requests;

use App\Filters\Donation\ExpiryWindowFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrowseDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:60'],
            'category_id' => ['nullable', 'integer', 'exists:food_categories,category_id'],
            'storage_type' => ['nullable', 'string', 'max:60'],
            'min_quantity' => ['nullable', 'numeric', 'gte:0', 'max:'.config('foodlink.request.max_quantity')],
            'expires_within_hours' => ['nullable', 'integer', Rule::in(ExpiryWindowFilter::options())],
            'request_id' => ['nullable', 'integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('keyword')) {
            // Drop control characters, then trim; XSS is additionally handled by
            // Blade escaping when the term is echoed back into the search box.
            $keyword = preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $this->input('keyword'));
            $this->merge(['keyword' => trim((string) $keyword)]);
        }
    }

    /** @return array<string, mixed> criteria for DonationFilterPipeline */
    public function criteria(): array
    {
        return array_filter(
            $this->safe()->only(['keyword', 'category_id', 'storage_type', 'min_quantity', 'expires_within_hours']),
            fn ($value) => $value !== null && $value !== ''
        );
    }
}
