<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
                'required', 'string', 'max:50', 'regex:/^\S+$/u',
                Rule::unique('users', 'username')->withoutTrashed()->ignore($this->user()->id),
            ],
        ];
    }

    public function messages(): array
    {
        return ['username.regex' => 'Username tidak boleh mengandung spasi.'];
    }
}
