<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrivacyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'is_profile_public' => ['required', 'boolean'],
            'show_budget_publicly' => ['required', 'boolean'],
        ];
    }
}
