<?php

use App\Models\Penalty;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Support\Facades\Storage;

function makePenaltyWithProof(?User $owner = null): Penalty
{
    $owner ??= User::factory()->create();
    $report = WeeklyReport::create([
        'user_id' => $owner->id,
        'week_start' => '2026-10-26',
        'total_minutes' => 100,
        'status' => 'tidak_memenuhi',
    ]);

    Storage::disk('local')->put('penalties/test-proof.png', 'fake-image');

    return Penalty::create([
        'user_id' => $owner->id,
        'weekly_report_id' => $report->id,
        'status' => 'uploaded',
        'proof_path' => 'penalties/test-proof.png',
    ]);
}

test('owner can view their penalty proof', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $penalty = makePenaltyWithProof($owner);

    $this->actingAs($owner)->get('/penalties/'.$penalty->id.'/proof')->assertOk();
});

test('other users cannot view someone else penalty proof', function () {
    Storage::fake('local');
    $penalty = makePenaltyWithProof();
    $other = User::factory()->create();

    $this->actingAs($other)->get('/penalties/'.$penalty->id.'/proof')->assertForbidden();
});

test('admin can view any penalty proof', function () {
    Storage::fake('local');
    $penalty = makePenaltyWithProof();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/penalties/'.$penalty->id.'/proof')->assertOk();
});

test('proof route is 404 when there is no proof file', function () {
    $owner = User::factory()->create();
    $report = WeeklyReport::create([
        'user_id' => $owner->id,
        'week_start' => '2026-10-26',
        'total_minutes' => 100,
        'status' => 'tidak_memenuhi',
    ]);
    $penalty = Penalty::create([
        'user_id' => $owner->id,
        'weekly_report_id' => $report->id,
        'status' => 'pending',
        'proof_path' => null,
    ]);

    $this->actingAs($owner)->get('/penalties/'.$penalty->id.'/proof')->assertNotFound();
});
