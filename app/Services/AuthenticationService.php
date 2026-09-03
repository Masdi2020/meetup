<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticationService
{
    public function __construct(private AuditService $audits) {}

    /** @param array<string, mixed> $credentials */
    public function attempt(array $credentials): ?User
    {
        $username = (string) $credentials['username'];
        $password = (string) $credentials['password'];
        $user = User::where('username', $username)->first();

        if ($user && Hash::check($password, $user->password)) {
            Auth::login($user, $user->role === 'display');

            $this->audits->record(
                'User',
                $user->id,
                'logged_in',
                null,
                ['role' => $user->role],
                $user->id,
                'pengguna masuk ke sistem',
            );

            return $user;
        }

        $this->recordFailedLogin($user, $username);

        return null;
    }

    public function logout(User $user): void
    {
        $this->audits->record(
            'User',
            $user->id,
            'logged_out',
            null,
            null,
            $user->id,
            'pengguna keluar dari sistem',
        );

        Auth::logout();
    }

    private function recordFailedLogin(?User $user, string $username): void
    {
        if ($user) {
            $this->audits->record(
                'User',
                $user->id,
                'login_failed',
                null,
                null,
                null,
                'percobaan masuk gagal',
            );

            return;
        }

        $this->audits->record(
            'Authentication',
            0,
            'login_failed',
            null,
            ['username' => $username],
            null,
            'percobaan masuk gagal untuk username yang tidak dikenal',
        );
    }
}
