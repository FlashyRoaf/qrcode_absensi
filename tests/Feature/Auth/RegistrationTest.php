<?php

// Public registration was removed: only admins create accounts, from
// Admin > User Management. These tests lock that in — /register must
// not render a sign-up page and must not create an account.

test('registration screen is not available', function () {
    $response = $this->get('/register');

    $response->assertStatus(404);
});

test('new users cannot self-register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(404);
    $this->assertGuest();
});
