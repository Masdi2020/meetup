<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'username' => ['required', 'string', 'regex:/^\S+$/u'],
            'password' => ['required', 'string', 'regex:/^\S+$/u'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username tidak boleh mengandung spasi.',
            'password.regex' => 'Password tidak boleh mengandung spasi.',
        ];
    }
}
