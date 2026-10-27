<?php

namespace App\Http\Requests\Provider;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BlockDateRequest extends FormRequest
{
    /**
     * Only users with a provider profile may manage the availability calendar.
     */
    public function authorize(): bool
    {
        return $this->user()?->provider !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => __('Please choose a date.'),
            'date.date_format' => __('The date must use the YYYY-MM-DD format.'),
        ];
    }
}
