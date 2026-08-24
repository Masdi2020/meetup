<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $role = $request->input('role');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })->when($role, function ($query, $role) {
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

        $user->update($validated);

        return to_route('admin.users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $password = Str::password(
            length: 8,
            symbols: false
        );

        $user->update([
            'password' => Hash::make($password),
            'force_change_password' => true,
        ]);

        return back()->with([
            'success' => 'Password berhasil direset',
            'generated_password' => $password,
        ]);
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $generatedPassword = Str::password(8);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'role' => $validated['role'],
            'password' => Hash::make($generatedPassword),
            'force_change_password' => true,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dibuat.')
            ->with('generated_password', $generatedPassword);
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
