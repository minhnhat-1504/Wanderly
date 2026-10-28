<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendBroadcastRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'target_audience' => ['required', 'string', 'in:all,travelers,providers'],
            'action_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('Please enter a broadcast announcement title.'),
            'title.min' => __('The title must be at least 3 characters.'),
            'message.required' => __('Please compose the announcement message.'),
            'message.min' => __('The message must be at least 10 characters.'),
            'target_audience.required' => __('Please select a target recipient audience.'),
            'target_audience.in' => __('The selected audience is invalid.'),
            'action_url.url' => __('The action URL must be a valid URL.'),
        ];
    }
}
