<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveUserRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function __construct(private AuditService $audits) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->trim()->toString();

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })->when($role !== '', function ($query) use ($role) {
                $query->where('role', $role);
            })->orderBy('name')
            ->get([
                'id',
                'name',
                'username',
                'role',
            ]);

        return Inertia::render('Admin/User', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
        ]);
    }

    public function update(SaveUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $user, $validated) {
            $oldValues = $user->only(['name', 'username', 'role']);
            $user->update($validated);

            $this->audits->record(
                'User',
                $user->id,
                'updated',
                $oldValues,
                $user->only(['name', 'username', 'role']),
                $request->user()->id,
                'data pengguna diperbarui oleh admin',
            );
        });

        return to_route('admin.users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $password = Str::password(
            length: 8,
            symbols: false
        );

        DB::transaction(function () use ($request, $user, $password) {
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
                $request->user()->id,
                'password pengguna direset oleh admin',
            );
        });

        return back()->with([
            'success' => 'Password berhasil direset',
            'generated_password' => $password,
        ]);
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $generatedPassword = Str::password(8);

        DB::transaction(function () use ($request, $validated, $generatedPassword) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'role' => $validated['role'],
                'password' => Hash::make($generatedPassword),
                'force_change_password' => true,
            ]);

            $this->audits->record(
                'User',
                $user->id,
                'created',
                null,
                $user->only(['name', 'username', 'role', 'force_change_password']),
                $request->user()->id,
                'pengguna dibuat oleh admin',
            );
        });

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dibuat.')
            ->with('generated_password', $generatedPassword);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $oldValues = $user->only(['name', 'username', 'role']);
            $user->delete();

            $this->audits->record(
                'User',
                $user->id,
                'deleted',
                $oldValues,
                null,
                $request->user()->id,
                'pengguna dihapus oleh admin',
            );
        });

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
