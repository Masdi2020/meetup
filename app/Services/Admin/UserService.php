<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(private AuditService $audits) {}

    /** @return Collection<int, User> */
    public function search(string $search, string $role): Collection
    {
        return User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', fn ($query) => $query->where('role', $role))
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'role']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, generated_password: string}
     */
    public function create(array $data, int $actorId): array
    {
        $generatedPassword = Str::password(8);

        $user = DB::transaction(function () use ($data, $generatedPassword, $actorId) {
            $user = User::create([
                ...$data,
                'password' => Hash::make($generatedPassword),
                'force_change_password' => true,
            ]);

            $this->audits->record(
                'User',
                $user->id,
                'created',
                null,
                $user->only(['name', 'username', 'role', 'force_change_password']),
                $actorId,
                'pengguna dibuat oleh admin',
            );

            return $user;
        });

        return ['user' => $user, 'generated_password' => $generatedPassword];
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data, int $actorId): void
    {
        DB::transaction(function () use ($user, $data, $actorId) {
            $oldValues = $user->only(['name', 'username', 'role']);
            $user->update($data);

            $this->audits->record(
                'User',
                $user->id,
                'updated',
                $oldValues,
                $user->only(['name', 'username', 'role']),
                $actorId,
                'data pengguna diperbarui oleh admin',
            );
        });
    }

    public function resetPassword(User $user, int $actorId): string
    {
        $password = Str::password(length: 8, symbols: false);

        DB::transaction(function () use ($user, $password, $actorId) {
            $oldForceChangePassword = (bool) $user->force_change_password;
            $user->update([
                'password' => Hash::make($password),
                'force_change_password' => true,
            ]);

            $this->audits->record(
                'User',
                $user->id,
                'password_reset',
                ['force_change_password' => $oldForceChangePassword],
                ['force_change_password' => true],
                $actorId,
                'password pengguna direset oleh admin',
            );
        });

        return $password;
    }

    public function delete(User $user, int $actorId): void
    {
        DB::transaction(function () use ($user, $actorId) {
            $oldValues = $user->only(['name', 'username', 'role']);
            $user->delete();

            $this->audits->record(
                'User',
                $user->id,
                'deleted',
                $oldValues,
                null,
                $actorId,
                'pengguna dihapus oleh admin',
            );
        });
    }
}
