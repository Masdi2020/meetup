<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    /** @return array{title: string, date: string, start_time: string, end_time: string} */
    public function bookingData(): array
    {
        return [
            'title' => $this->string('title')->toString(),
            'date' => $this->string('date')->toString(),
            'start_time' => $this->string('start_time')->toString(),
            'end_time' => $this->string('end_time')->toString(),
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', 'regex:/^\d{2}:(00|15|30|45)$/'],
            'end_time' => ['required', 'date_format:H:i', 'regex:/^\d{2}:(00|15|30|45)$/', 'after:start_time'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'start_time.regex' => 'Waktu mulai harus menggunakan interval 15 menit.',
            'end_time.regex' => 'Waktu selesai harus menggunakan interval 15 menit.',
        ];
    }
}
