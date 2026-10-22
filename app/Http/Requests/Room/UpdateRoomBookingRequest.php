<?php

namespace App\Http\Requests\Room;

use App\Models\RoomBooking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $room = $this->route('room');

        if (! $room instanceof RoomBooking) {
            $room = RoomBooking::findOrFail($room);
        }

        return $this->user()->can('manageRooms', $room->trip);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hotel_name' => ['sometimes', 'required', 'string', 'max:255'],
            'room_type' => ['nullable', 'string', 'max:255'],
            'check_in' => ['sometimes', 'required', 'date'],
            'check_out' => ['sometimes', 'required', 'date', 'after_or_equal:check_in'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
            'is_budget_included' => ['sometimes', 'boolean'],
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
            'hotel_name.required' => __('The hotel name field is required.'),
            'check_in.required' => __('The check-in date is required.'),
            'check_out.required' => __('The check-out date is required.'),
            'check_out.after_or_equal' => __('The check-out date must be equal to or after the check-in date.'),
            'price.required' => __('The price is required.'),
            'price.min' => __('The price must be at least 0.'),
        ];
    }
}
