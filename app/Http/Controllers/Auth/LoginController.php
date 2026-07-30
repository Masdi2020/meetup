<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return inertia('Login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return match (Auth::user()->role) {
                'admin' => to_route('admin.dashboard'),
                'user' => to_route('home'),
                default => abort(403),
            };
        }

        return back()->withErrors([
            'username' => 'Username atau password salah',
        ]);
    }
}
