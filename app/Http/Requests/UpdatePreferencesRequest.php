<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'pace' => ['nullable', 'string', 'in:relaxed,moderate,packed,fast'],
            'budget' => ['nullable', 'string', 'in:budget,moderate,luxury'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['string', 'max:50'],
            'locale' => ['nullable', 'string', 'in:en,vi'],
            'currency' => ['nullable', 'string', 'in:VND,USD'],
        ];
    }
}
