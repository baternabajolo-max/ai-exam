<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParseAchievementRequirementsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'super-admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'requirements_text' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
