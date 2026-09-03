<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Response;

class LoginController extends Controller
{
    public function __construct(private AuditService $audits) {}

    public function create(): Response
    {
        return inertia('Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $user = User::where('username', $credentials['username'])->first();

        if (
            $user &&
            Hash::check($credentials['password'], $user->password)
        ) {
            $remember = $user->role === 'display';

            Auth::login($user, $remember);

            $this->audits->record(
                'User',
                $user->id,
                'logged_in',
                null,
                ['role' => $user->role],
                $user->id,
                'pengguna masuk ke sistem',
            );

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
        } else {
            $this->audits->record(
                'Authentication',
                0,
                'login_failed',
                null,
                ['username' => $credentials['username']],
                null,
                'percobaan masuk gagal untuk username yang tidak dikenal',
            );
        }

        return back()->withErrors([
            'username' => 'Username atau password salah',
        ])->withInput();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

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

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('success', 'Berhasil Keluar');
    }
}
