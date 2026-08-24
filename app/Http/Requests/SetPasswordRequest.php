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
        return [
            'password' => ['required', 'string', 'confirmed', 'min:8', 'regex:/^\S+$/u'],
            'password_confirmation' => ['required', 'string', 'regex:/^\S+$/u'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Password baru tidak boleh mengandung spasi.',
            'password_confirmation.regex' => 'Konfirmasi password tidak boleh mengandung spasi.',
        ];
    }
}
