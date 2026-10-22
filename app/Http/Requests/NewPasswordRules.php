<?php

namespace App\Http\Requests;

final class NewPasswordRules
{
    /** @var array<string, list<string>> */
    public const RULES = [
        'password' => ['required', 'string', 'confirmed', 'min:8', 'regex:/^\S+$/u'],
        'password_confirmation' => ['required', 'string', 'regex:/^\S+$/u'],
    ];

    /** @var array<string, string> */
    public const MESSAGES = [
        'password.regex' => 'Password baru tidak boleh mengandung spasi.',
        'password_confirmation.regex' => 'Konfirmasi password tidak boleh mengandung spasi.',
    ];
}
