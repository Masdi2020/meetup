<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->user()?->role === 'admin' && $this->filled('status')) {
            $this->merge(['status' => strtoupper($this->string('status')->toString())]);
        }
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
        $isAdmin = $this->user()?->role === 'admin';
        $dateRules = ['required', 'date'];

        if (! $isAdmin) {
            $dateRules[] = 'after_or_equal:today';
        }

        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'date' => $dateRules,
            'start_time' => ['required', 'date_format:H:i', 'regex:/^\d{2}:(00|15|30|45)$/'],
            'end_time' => ['required', 'date_format:H:i', 'regex:/^\d{2}:(00|15|30|45)$/', 'after:start_time'],
            'title' => ['required', 'string', 'max:255'],
            'participants' => ['required', 'integer', 'min:1'],
            'request' => ['nullable', 'string'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'user_id' => $isAdmin
                ? [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where(fn ($query) => $query
                        ->whereIn('role', ['user', 'admin'])
                        ->whereNull('deleted_at')),
                ]
                : ['prohibited'],
            'status' => $isAdmin
                ? ['required', 'string', Rule::exists('booking_statuses', 'code')]
                : ['prohibited'],
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
