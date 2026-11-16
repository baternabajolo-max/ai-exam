<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $claimedBadgeIcons = $this->user()?->role === 'student'
            ? $this->user()->achievements()->wherePivotNotNull('claimed_at')->pluck('achievements.icon')->all()
            : [];

        return [
            'first_name' => ['nullable', 'string', 'max:100', 'required_with:last_name'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'current_password' => [
                'nullable',
                'current_password',
                Rule::requiredIf(fn (): bool => in_array($this->user()?->role, ['admin', 'super-admin'], true)
                    && ($this->filled('first_name') || $this->filled('last_name'))),
            ],
            'badge_icon' => ['nullable', 'string', 'max:100', Rule::in($claimedBadgeIcons)],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'background_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'badge_icon.in' => 'You can only feature an achievement badge you have claimed.',
        ];
    }
}
