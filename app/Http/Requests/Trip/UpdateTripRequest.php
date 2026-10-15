<?php

namespace App\Http\Requests\Trip;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'budget' => ['required', 'numeric', 'min:0'],
            'privacy' => ['required', Rule::in(['private', 'group', 'public'])],
            'status' => ['sometimes', Rule::in(['planning', 'ongoing', 'completed'])],
            'cover' => ['nullable', 'image', 'max:3072'],
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
            'name.required' => __('The trip name field is required.'),
            'name.string' => __('The trip name must be a valid string.'),
            'name.max' => __('The trip name must not exceed 255 characters.'),
            'destination.required' => __('The destination field is required.'),
            'destination.string' => __('The destination must be a valid string.'),
            'destination.max' => __('The destination must not exceed 255 characters.'),
            'start_date.required' => __('The start date field is required.'),
            'start_date.date' => __('The start date is not a valid date.'),
            'end_date.required' => __('The end date field is required.'),
            'end_date.date' => __('The end date is not a valid date.'),
            'end_date.after_or_equal' => __('The end date must be a date after or equal to the start date.'),
            'budget.required' => __('The budget field is required.'),
            'budget.numeric' => __('The budget must be a number.'),
            'budget.min' => __('The budget must be at least 0.'),
            'privacy.required' => __('The privacy option is required.'),
            'privacy.in' => __('The selected privacy option is invalid.'),
            'status.in' => __('The selected status is invalid.'),
            'cover.image' => __('The cover must be an image.'),
            'cover.max' => __('The cover image must not exceed 3MB.'),
        ];
    }
}
