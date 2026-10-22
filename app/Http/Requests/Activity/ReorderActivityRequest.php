<?php

namespace App\Http\Requests\Activity;

use App\Models\Trip;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $trip = $this->route('trip');

        if (! $trip instanceof Trip) {
            $trip = Trip::find($trip);
        }

        return $trip && $this->user()?->can('update', $trip);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:activities,id'],
            'items.*.day' => ['required', 'integer', 'min:1'],
            'items.*.time_slot' => ['required', Rule::in(['morning', 'midday', 'afternoon', 'evening'])],
            'items.*.position' => ['required', 'integer', 'min:0'],
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
            'items.required' => __('The reorder items list is required.'),
            'items.array' => __('The reorder items must be an array.'),
            'items.min' => __('At least one item must be provided for reordering.'),
            'items.*.id.required' => __('Each item must include a valid activity ID.'),
            'items.*.id.exists' => __('One or more specified activities do not exist.'),
            'items.*.day.required' => __('Each item must specify a day.'),
            'items.*.day.integer' => __('The day must be an integer.'),
            'items.*.day.min' => __('The day must be at least 1.'),
            'items.*.time_slot.required' => __('Each item must specify a time slot.'),
            'items.*.time_slot.in' => __('The selected time slot is invalid.'),
            'items.*.position.required' => __('Each item must specify a position.'),
            'items.*.position.integer' => __('The position must be an integer.'),
            'items.*.position.min' => __('The position must be at least 0.'),
        ];
    }
}
