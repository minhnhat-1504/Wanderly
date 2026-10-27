<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'notify_in_app' => ['required', 'boolean'],
            'notify_email' => ['required', 'boolean'],
        ];
    }
}
