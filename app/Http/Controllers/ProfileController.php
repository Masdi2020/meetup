<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return inertia('Profile', [
            'user' => request()->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^\S+$/u',
                Rule::unique('users')->ignore($request->user()->id),
            ],
        ], ['username.regex' => 'Username tidak boleh mengandung spasi.']);

        $request->user()->update($validated);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'regex:/^\S+$/u'],
            'password' => ['required', 'string', 'confirmed', 'min:8', 'regex:/^\S+$/u'],
            'password_confirmation' => ['required', 'string', 'regex:/^\S+$/u'],
        ], [
            'current_password.regex' => 'Password lama tidak boleh mengandung spasi.',
            'password.regex' => 'Password baru tidak boleh mengandung spasi.',
            'password_confirmation.regex' => 'Konfirmasi password tidak boleh mengandung spasi.',
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            return back()->withErrors([
                'current_password' => 'Password lama tidak sesuai.',
            ]);
        }

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'force_change_password' => false,
        ]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    public function updateForcedPassword(Request $request): RedirectResponse
    {
        abort_unless($request->user()->force_change_password, 403);

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', 'min:8', 'regex:/^\S+$/u'],
            'password_confirmation' => ['required', 'string', 'regex:/^\S+$/u'],
        ], [
            'password.regex' => 'Password baru tidak boleh mengandung spasi.',
            'password_confirmation.regex' => 'Konfirmasi password tidak boleh mengandung spasi.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'force_change_password' => false,
        ]);

        return back()->with('success', 'Password berhasil dibuat.');
    }
}
