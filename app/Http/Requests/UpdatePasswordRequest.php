<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|object>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password', 'regex:/^\S+$/u'],
            ...NewPasswordRules::RULES,
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'Password lama tidak sesuai.',
            'current_password.regex' => 'Password lama tidak boleh mengandung spasi.',
            ...NewPasswordRules::MESSAGES,
        ];
    }
}
