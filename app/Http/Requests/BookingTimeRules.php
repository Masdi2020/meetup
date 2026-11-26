<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

final class BookingTimeRules
{
    public static function validateFutureStart(Validator $validator, ?Booking $booking = null): void
    {
        if ($validator->errors()->hasAny(['date', 'start_time'])) {
            return;
        }

        $data = $validator->getData();
        $start = Carbon::parse($data['date'])->setTimeFromTimeString($data['start_time']);

        if ($booking && $start->equalTo($booking->date->setTimeFromTimeString($booking->start_time->format('H:i')))) {
            return;
        }

        if ($start->lt(now()->startOfMinute())) {
            $validator->errors()->add('start_time', 'Waktu mulai tidak boleh sudah lewat.');
        }
    }

    /** @var array<string, list<string>> */
    public const RULES = [
        'start_time' => ['required', 'date_format:H:i', 'regex:/^\d{2}:(00|15|30|45)$/'],
        'end_time' => ['required', 'date_format:H:i', 'regex:/^\d{2}:(00|15|30|45)$/', 'after:start_time'],
    ];

    /** @var array<string, string> */
    public const MESSAGES = [
        'start_time.regex' => 'Waktu mulai harus menggunakan interval 15 menit.',
        'end_time.regex' => 'Waktu selesai harus menggunakan interval 15 menit.',
    ];
}
