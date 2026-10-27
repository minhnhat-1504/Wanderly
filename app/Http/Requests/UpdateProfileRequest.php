<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Reserved keywords that cannot be used as username.
     */
    public const RESERVED_USERNAMES = [
        'admin',
        'settings',
        'login',
        'register',
        'logout',
        'trips',
        'provider',
        'marketplace',
        's',
        'u',
        'api',
        'ajax',
        'dashboard',
        'healthz',
        'social',
        'notifications',
        'profile',
        'user',
        'users',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:80'],
            'username' => [
                'required',
                'string',
                'regex:/^[a-z0-9_]{3,30}$/',
                Rule::unique('users', 'username')->ignore($userId),
                Rule::notIn(self::RESERVED_USERNAMES),
            ],
            'bio' => ['nullable', 'string', 'max:300'],
            'location' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'username.regex' => __('Username must be between 3 and 30 characters and contain only lowercase letters, numbers, and underscores.'),
            'username.not_in' => __('This username is reserved and cannot be used.'),
            'username.unique' => __('This username is already taken.'),
            'website.url' => __('Website must be a valid URL starting with http:// or https://.'),
            'avatar.max' => __('Avatar image must not exceed 2MB.'),
            'cover_image.max' => __('Cover image must not exceed 4MB.'),
        ];
    }
}
