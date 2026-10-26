<?php

namespace App\Http\Requests\Provider;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    /**
     * Only users with a provider profile may add catalog services.
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
            'name' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'tags' => ['nullable', 'string', 'max:255'],
            'specs' => ['nullable', 'array', 'max:20'],
            'specs.*.key' => ['nullable', 'string', 'max:50', 'required_with:specs.*.value'],
            'specs.*.value' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Please enter the service name.'),
            'price.required' => __('Please enter the service price.'),
            'price.min' => __('The service price cannot be negative.'),
            'specs.max' => __('A service can have at most 20 specification fields.'),
            'specs.*.key.required_with' => __('Each specification value needs a name.'),
            'image.image' => __('The service photo must be an image.'),
            'image.max' => __('The service photo may not be larger than 2 MB.'),
        ];
    }
}
