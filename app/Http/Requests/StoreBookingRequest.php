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
            'start_time' => ['required'],
            'end_time' => ['required', 'after:start_time'],
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
}
