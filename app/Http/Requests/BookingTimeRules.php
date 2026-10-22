<?php

namespace App\Http\Requests;

final class BookingTimeRules
{
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
