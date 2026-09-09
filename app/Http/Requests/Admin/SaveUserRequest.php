<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'max:255', 'regex:/^\S+$/u',
                Rule::unique('users', 'username')->withoutTrashed()->ignore($this->route('user')),
            ],
            'role' => ['required', 'in:admin,user,display'],
        ];
    }

    public function messages(): array
    {
        return ['username.regex' => 'Username tidak boleh mengandung spasi.'];
    }
}
