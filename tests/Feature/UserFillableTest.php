<?php

use App\Models\User;

// G2-1: privileged fields must not be mass-assignable, but the
// admin controller must still be able to set them one by one.
test('mass assignment cannot set admin or device fields', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'mass@example.com',
        'password' => 'password',
        'is_admin' => true,
        'role' => 'scan',
        'device_id' => 'device-123',
        'identifier' => 'ident-123',
    ]);

    expect($user->fresh()->is_admin)->toBeFalse()
        // Not null: role falls back to its database default
        // ('anggota'). What matters is it is NOT the 'scan' value
        // the mass assignment tried to set.
        ->and($user->fresh()->role)->toBe('anggota')
        ->and($user->fresh()->device_id)->toBeNull();
});

test('admin creating a user still sets admin and role fields', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'New Employee',
        'email' => 'new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '628123456789',
        'is_admin' => true,
        'role' => 'scan',
    ])->assertRedirect();

    $created = User::where('email', 'new@example.com')->first();
    expect($created)->not->toBeNull()
        ->and($created->is_admin)->toBeTrue()
        ->and($created->role)->toBe('scan')
        ->and($created->admin_assigned_at)->not->toBeNull();
});

test('admin device reset still clears device fields', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $user->device_id = 'device-123';
    $user->identifier = 'ident-123';
    $user->save();

    $this->actingAs($admin)->post('/admin/users/'.$user->id.'/reset-device')
        ->assertRedirect();

    expect($user->fresh()->device_id)->toBeNull()
        ->and($user->fresh()->identifier)->toBeNull();
});
