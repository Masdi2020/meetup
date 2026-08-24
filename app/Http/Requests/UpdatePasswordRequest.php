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
            'password' => ['required', 'string', 'confirmed', 'min:8', 'regex:/^\S+$/u'],
            'password_confirmation' => ['required', 'string', 'regex:/^\S+$/u'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'Password lama tidak sesuai.',
            'current_password.regex' => 'Password lama tidak boleh mengandung spasi.',
            'password.regex' => 'Password baru tidak boleh mengandung spasi.',
            'password_confirmation.regex' => 'Konfirmasi password tidak boleh mengandung spasi.',
        ];
    }
}
