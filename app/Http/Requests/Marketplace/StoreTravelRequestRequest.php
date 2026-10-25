<?php

namespace App\Http\Requests\Marketplace;

use App\Models\Trip;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTravelRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $trip = $this->route('trip');

        if (! $trip instanceof Trip) {
            $trip = Trip::findOrFail($trip);
        }

        return $this->user()->can('manageMarketplace', $trip);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'needs' => ['required', 'array', 'min:1'],
            'pace' => ['nullable', 'string', 'in:relaxed,balanced,packed'],
            'max_budget' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'needs.required' => __('Please specify at least one service need.'),
            'needs.min' => __('Please specify at least one service need.'),
            'max_budget.min' => __('Maximum budget cannot be negative.'),
        ];
    }
}
