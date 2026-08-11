<?php

namespace App\Http\Controllers;

use App\Models\User;
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
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
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

    public function resetPassword(User $user)
    {
        $password = Str::password(
            length: 8
        );

        $user->update([
            'password' => Hash::make($password),
        ]);

        return back()->with([
            'success' => 'Password berhasil direset',
            'generated_password' => $password,
        ]);
    }
}
