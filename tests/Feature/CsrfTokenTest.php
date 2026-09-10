<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

it('provides a fresh csrf token without requiring a csrf token first', function () {
    $response = $this->get('/csrf-token');

    $response
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonStructure(['token']);
});

it('does not expose the csrf token endpoint as a state-changing request', function () {
    $this->withMiddleware(PreventRequestForgery::class)
        ->post('/csrf-token')
        ->assertMethodNotAllowed();
});
