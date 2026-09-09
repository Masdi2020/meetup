<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class LoginController extends Controller
{
    public function __construct(private AuthenticationService $authentication) {}

    public function create(): Response
    {
        return inertia('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if ($user = $this->authentication->attempt($credentials)) {
            return match ($user->role) {
                'admin' => to_route('admin.dashboard')
                    ->with('success', 'Login Berhasil'),

                'user' => to_route('availability.index')
                    ->with('success', 'Login Berhasil'),

                'display' => to_route('display.index')
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
        $user = $request->user();

        $this->authentication->logout($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('success', 'Berhasil Keluar');
    }
}
