<?php

use App\Models\Qrcode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

test('scanner can check a QR code status', function () {
    $scanner = User::factory()->create(['role' => 'scan']);
    $qr = Qrcode::create([
        'token' => (string) Str::uuid(),
        'type' => 'check_in',
        'expires_at' => now()->addMinutes(2),
        'is_used' => false,
    ]);

    $response = $this->actingAs($scanner)->get('/scan/status/'.$qr->token);

    $response->assertOk()->assertJson(['is_used' => false, 'type' => 'check_in']);
});

test('scan status reflects a used code', function () {
    $scanner = User::factory()->create(['role' => 'scan']);
    $qr = Qrcode::create([
        'token' => (string) Str::uuid(),
        'type' => 'check_in',
        'expires_at' => now()->addMinutes(2),
        'is_used' => true,
    ]);

    $response = $this->actingAs($scanner)->get('/scan/status/'.$qr->token);

    $response->assertOk()->assertJson(['is_used' => true]);
});

test('scan status is unknown for a missing token', function () {
    $scanner = User::factory()->create(['role' => 'scan']);

    $response = $this->actingAs($scanner)->get('/scan/status/'.Str::uuid());

    $response->assertNotFound();
});

test('non-scanner users cannot check a QR code status', function () {
    $user = User::factory()->create(['role' => 'anggota']);
    $qr = Qrcode::create([
        'token' => (string) Str::uuid(),
        'type' => 'check_in',
        'expires_at' => now()->addMinutes(2),
    ]);

    $response = $this->actingAs($user)->get('/scan/status/'.$qr->token);

    $response->assertForbidden();
});

test('one QR code works for only one employee', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-26 08:00:00')); // Monday
    config(['staks.latitude' => -6.2, 'staks.longitude' => 106.8, 'staks.radius_meters' => 50]);

    $first = User::factory()->create();
    $second = User::factory()->create();
    $qr = Qrcode::create([
        'token' => (string) Str::uuid(),
        'type' => 'check_in',
        'expires_at' => now()->addMinutes(2),
        'is_used' => false,
    ]);

    $this->actingAs($first)->post('/scanned/'.$qr->token, [
        'latitude' => -6.2,
        'longitude' => 106.8,
    ])->assertInertia(fn ($page) => $page->where('success', true));

    $this->actingAs($second)->post('/scanned/'.$qr->token, [
        'latitude' => -6.2,
        'longitude' => 106.8,
    ])->assertInertia(fn ($page) => $page
        ->where('success', false)
        ->where('message', 'QR Code sudah digunakan.'));

    Carbon::setTestNow();
});
