<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
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
            'appName' => ['required', 'string', 'max:255'],
            'sessionTimeout' => ['required', 'integer', 'min:1', 'max:43200'],
        ];
    }
}
