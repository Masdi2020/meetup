<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMeetingMinutesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'meeting_minutes' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }
}
