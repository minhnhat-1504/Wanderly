<?php

namespace App\Http\Requests\Activity;

use App\Models\Trip;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'day' => ['required', 'integer', 'min:1'],
            'time_slot' => ['required', Rule::in(['morning', 'midday', 'afternoon', 'evening'])],
            'cost' => ['required', 'numeric', 'min:0'],
            'category' => ['required', Rule::in(['sightseeing', 'dining', 'cafe', 'shopping', 'entertainment', 'other'])],
            'location' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'type' => ['nullable', Rule::in(['activity', 'room', 'booking'])],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'position' => ['nullable', 'integer', 'min:0'],
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
            'title.required' => __('The activity title field is required.'),
            'title.string' => __('The activity title must be a valid string.'),
            'title.max' => __('The activity title must not exceed 255 characters.'),
            'day.required' => __('The day field is required.'),
            'day.integer' => __('The day must be an integer.'),
            'day.min' => __('The day must be at least 1.'),
            'time_slot.required' => __('The time slot is required.'),
            'time_slot.in' => __('The selected time slot is invalid.'),
            'cost.required' => __('The cost field is required.'),
            'cost.numeric' => __('The cost must be a valid number.'),
            'cost.min' => __('The cost must be at least 0.'),
            'category.required' => __('The category is required.'),
            'category.in' => __('The selected category is invalid.'),
            'location.required' => __('The location field is required.'),
            'location.string' => __('The location must be a valid string.'),
            'location.max' => __('The location must not exceed 255 characters.'),
            'address.string' => __('The address must be a valid string.'),
            'address.max' => __('The address must not exceed 255 characters.'),
            'type.in' => __('The selected activity type is invalid.'),
            'start_time.date_format' => __('The start time must follow the HH:MM format.'),
            'end_time.date_format' => __('The end time must follow the HH:MM format.'),
            'position.integer' => __('The position must be an integer.'),
            'position.min' => __('The position must be at least 0.'),
        ];
    }
}
