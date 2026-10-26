<?php

namespace App\Http\Requests\Provider;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterProviderRequest extends FormRequest
{
    /**
     * Any authenticated non-provider user may apply; duplicate checks happen in ProviderService.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:150'],
            'service_type' => ['required', 'in:accommodation,transport,tour,restaurant,photo,guide'],
            'destination_id' => ['required', 'integer', 'exists:destinations,id'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_name.required' => __('Please enter your business name.'),
            'service_type.required' => __('Please choose the type of service you provide.'),
            'service_type.in' => __('The selected service type is invalid.'),
            'destination_id.required' => __('Please choose the destination you operate in.'),
            'destination_id.exists' => __('The selected destination does not exist.'),
            'phone.required' => __('Please enter a contact phone number.'),
            'address.required' => __('Please enter your business address.'),
        ];
    }
}
