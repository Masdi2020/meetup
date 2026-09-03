<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountService
{
    public function __construct(private AuditService $audits) {}

    /** @param array<string, mixed> $data */
    public function updateProfile(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $oldValues = $user->only(['name', 'username']);
            $user->update($data);

            $this->audits->record(
                'User',
                $user->id,
                'profile_updated',
                $oldValues,
                $user->only(['name', 'username']),
                $user->id,
                'profil diperbarui oleh pengguna',
            );
        });
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password lama tidak sesuai.',
            ]);
        }

        $this->savePassword($user, $newPassword, false);
    }

    public function setInitialPassword(User $user, string $password): void
    {
        abort_unless($user->force_change_password, 403);

        $this->savePassword($user, $password, true);
    }

    private function savePassword(User $user, string $password, bool $wasForced): void
    {
        DB::transaction(function () use ($user, $password, $wasForced) {
            $user->update([
                'password' => Hash::make($password),
                'force_change_password' => false,
            ]);

            $this->audits->record(
                'User',
                $user->id,
                'password_changed',
                $wasForced ? ['force_change_password' => true] : null,
                ['force_change_password' => false],
                $user->id,
                $wasForced ? 'password awal dibuat oleh pengguna' : 'password diubah oleh pengguna',
            );
        });
    }
}
