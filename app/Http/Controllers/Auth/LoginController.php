<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return inertia('Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if (
            $user &&
            Hash::check($credentials['password'], $user->password)
        ) {
            $remember = $user->role === 'display';

            Auth::login($user, $remember);

            return match ($user->role) {
                'admin' => to_route('admin.dashboard')
                    ->with('success', 'Login Berhasil'),

                'user' => to_route('availability.index')
                    ->with('success', 'Login Berhasil'),

                'display' => to_route('meeting.banner')
                    ->with('success', 'Login Berhasil'),

                default => abort(403),
            };
        }

        return back()->withErrors([
            'username' => 'Username atau password salah',
        ])->withInput();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('success', 'Berhasil Keluar');
    }
}
