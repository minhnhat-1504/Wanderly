<?php

namespace App\Http\Requests\Marketplace;

use App\Models\TravelRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        // Must be a registered provider
        $provider = $user->provider;
        if (! $provider) {
            return false;
        }

        $travelRequest = $this->route('travelRequest');
        if (! $travelRequest instanceof TravelRequest) {
            $travelRequest = TravelRequest::findOrFail($travelRequest);
        }

        return in_array($travelRequest->status, ['open', 'bidding']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'valid_until' => ['required', 'date', 'after:now'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.service_id' => ['nullable', 'integer', 'exists:services,id'],
            'items.*.specs' => ['nullable', 'array'],
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
            'message.required' => __('Please include a proposal message for the traveler.'),
            'valid_until.required' => __('Please specify a validity date for the proposal.'),
            'valid_until.after' => __('The validity date must be in the future.'),
            'items.required' => __('Please include at least one item in your proposal package.'),
            'items.*.name.required' => __('Item name is required.'),
            'items.*.price.required' => __('Item price is required.'),
        ];
    }
}
