<?php

namespace App\Http\Requests\Provider;

use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateServiceRequest extends StoreServiceRequest
{
    /**
     * Only the owning provider may update the service.
     */
    public function authorize(): bool
    {
        $service = $this->route('service');

        return $service instanceof Service && $this->user()->can('manage', $service);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }
}
