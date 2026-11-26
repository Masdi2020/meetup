<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            ->when($role !== '', fn ($query) => $query->whereHas('assignedRole', fn ($query) => $query->where('code', $role)))
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'role_id']);
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
                $user->only(['name', 'username', 'role_id', 'role', 'force_change_password']),
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
            $this->protectLastSuperAdmin($user, (int) $data['role_id']);
            $oldValues = $user->only(['name', 'username', 'role_id', 'role']);
            $user->update($data);
            $user->load('assignedRole');

            $this->audits->record(
                'User',
                $user->id,
                'updated',
                $oldValues,
                $user->only(['name', 'username', 'role_id', 'role']),
                $actorId,
                'data pengguna diperbarui oleh admin',
            );
        });
    }

    public function resetPassword(User $user, int $actorId): string
    {
        $password = Str::password(length: 8, symbols: false);

        DB::transaction(function () use ($user, $password, $actorId) {
            // Lock in ID order so simultaneous resets cannot deadlock each other.
            $ids = array_unique([$user->id, $actorId]);
            sort($ids);

            foreach ($ids as $id) {
                DB::table('users')->where('id', $id)->update(['updated_at' => DB::raw('updated_at')]);
            }

            $user = User::findOrFail($user->id);
            $actor = User::findOrFail($actorId);
            abort_unless($actor->isAdmin(), 403);
            abort_if(
                $user->isSuperAdmin() && ! $actor->isSuperAdmin(),
                403,
                'Hanya Super Admin yang dapat mereset password Super Admin.',
            );

            $oldForceChangePassword = (bool) $user->force_change_password;
            $user->forceFill([
                'password' => Hash::make($password),
                'force_change_password' => true,
                'remember_token' => Str::random(60),
            ])->save();

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
            $this->protectLastSuperAdmin($user);
            $oldValues = $user->only(['name', 'username', 'role_id', 'role']);
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

    private function protectLastSuperAdmin(User $user, ?int $newRoleId = null): void
    {
        // Serialize role changes and deletions, including on SQLite where FOR UPDATE is ignored.
        DB::table('roles')->where('code', 'super_admin')->update(['code' => DB::raw('code')]);
        $user->refresh();

        if (! $user->isSuperAdmin() || $newRoleId === $user->role_id) {
            return;
        }

        $superAdmins = User::where('role_id', $user->role_id)->lockForUpdate()->get(['id']);

        if ($superAdmins->count() === 1) {
            throw ValidationException::withMessages([
                'role_id' => 'Super admin terakhir tidak dapat dihapus atau diubah rolenya.',
            ]);
        }
    }
}
