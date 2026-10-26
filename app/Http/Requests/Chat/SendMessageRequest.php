<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Channel-level authorization is enforced in ChatController via ChatService.
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
            'content' => ['nullable', 'string', 'max:2000', 'required_without:attachment'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required_without' => __('Please type a message or attach an image.'),
            'content.max' => __('Messages may not be longer than 2000 characters.'),
            'attachment.image' => __('Only image attachments are supported.'),
            'attachment.max' => __('Images may not be larger than 2 MB.'),
        ];
    }
}
