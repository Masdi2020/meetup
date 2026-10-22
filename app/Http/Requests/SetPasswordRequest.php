<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->force_change_password;
    }

    /**
     * @return array<string, array<int, string|object>|string>
     */
    public function rules(): array
    {
        return NewPasswordRules::RULES;
    }

    public function messages(): array
    {
        return NewPasswordRules::MESSAGES;
    }
}
