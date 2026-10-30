<?php

use App\Models\User;
use App\Models\WeeklyReport;

test('weekly report command accepts YYYY-MM-DD', function () {
    $user = User::factory()->create(['role' => 'anggota']);

    $this->artisan('app:generate-weekly-report', ['--week-start' => '2026-10-26'])
        ->assertExitCode(0);

    expect(WeeklyReport::where('user_id', $user->id)
        ->where('week_start', '2026-10-26')
        ->exists())->toBeTrue();
});

test('weekly report command still accepts day-month-year', function () {
    $user = User::factory()->create(['role' => 'anggota']);

    $this->artisan('app:generate-weekly-report', ['--week-start' => '2-11-2026'])
        ->assertExitCode(0);

    expect(WeeklyReport::where('user_id', $user->id)
        ->where('week_start', '2026-11-02')
        ->exists())->toBeTrue();
});

test('weekly report command rejects a garbage date with both formats named', function () {
    $this->artisan('app:generate-weekly-report', ['--week-start' => 'not-a-date'])
        ->expectsOutputToContain('YYYY-MM-DD')
        ->assertExitCode(1);
});
