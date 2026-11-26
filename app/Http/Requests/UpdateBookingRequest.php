<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBookingRequest extends FormRequest
{
    /** @return array{title: string, date: string, start_time: string, end_time: string, participants_count?: int} */
    public function bookingData(): array
    {
        return [
            'title' => $this->string('title')->toString(),
            'date' => $this->string('date')->toString(),
            'start_time' => $this->string('start_time')->toString(),
            'end_time' => $this->string('end_time')->toString(),
            ...($this->has('participants') ? ['participants_count' => $this->integer('participants')] : []),
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
        $dateRules = ['required', 'date'];

        if (! $this->user()?->isAdmin()) {
            $dateRules[] = 'after_or_equal:today';
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'date' => $dateRules,
            ...BookingTimeRules::RULES,
            'participants' => ['sometimes', 'required', 'integer', 'min:1'],
        ];
    }

    /** @return list<\Closure(Validator): void> */
    public function after(): array
    {
        return $this->user()?->isAdmin() ? [] : [
            fn (Validator $validator) => BookingTimeRules::validateFutureStart($validator, $this->route('booking')),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return BookingTimeRules::MESSAGES;
    }
}
