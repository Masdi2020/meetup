<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveUserRequest;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function __construct(private UserService $users) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->trim()->toString();

        return Inertia::render('Admin/User', [
            'users' => $this->users->search($search, $role),
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
        ]);
    }

    public function update(SaveUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $this->users->update($user, $validated, $request->user()->id);

        return to_route('admin.users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $password = $this->users->resetPassword($user, $request->user()->id);

        return back()->with([
            'success' => 'Password berhasil direset',
            'generated_password' => $password,
        ]);
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $result = $this->users->create($validated, $request->user()->id);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dibuat.')
            ->with('generated_password', $result['generated_password']);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->users->delete($user, $request->user()->id);

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
