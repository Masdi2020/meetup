<?php

it('limits login attempts per normalized username and IP for one minute', function () {
    $this->freezeTime();
    $credentials = ['username' => 'employee', 'password' => 'wrong-password'];

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', $credentials)->assertSessionHasErrors('username');
    }

    $this->postJson('/login', ['username' => 'EMPLOYEE', 'password' => 'wrong-password'])
        ->assertTooManyRequests()
        ->assertHeader('Retry-After', '60');

    $this->withHeader('X-Inertia', 'true')->post('/login', $credentials)
        ->assertStatus(303)
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['username' => 'Terlalu banyak percobaan login. Coba lagi dalam 60 detik.']);

    $this->assertDatabaseCount('audits', 5);

    $this->post('/login', ['username' => 'another-employee', 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['username' => 'Username atau password salah']);

    $this->travel(61)->seconds();

    $this->post('/login', $credentials)
        ->assertSessionHasErrors(['username' => 'Username atau password salah']);
});

it('limits login attempts from the same IP across different usernames', function () {
    foreach (range(1, 30) as $attempt) {
        $this->post('/login', ['username' => 'employee-'.$attempt, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('username');
    }

    $this->postJson('/login', ['username' => 'employee-31', 'password' => 'wrong-password'])
        ->assertTooManyRequests();
});
