<?php

use App\Models\Leave;
use App\Models\Penalty;
use App\Models\PenaltyExemptWeek;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Services\AttendanceService;
use App\Services\PenaltyService;
use Carbon\Carbon;

function makeReport(User $user, string $status = 'tidak_memenuhi'): WeeklyReport
{
    return WeeklyReport::create([
        'user_id' => $user->id,
        'week_start' => '2026-10-26',
        'total_minutes' => 100,
        'status' => $status,
    ]);
}

test('penalty service creates a penalty for a failing report', function () {
    $user = User::factory()->create();
    $report = makeReport($user);

    expect(PenaltyService::createIfNeeded($report))->toBeTrue()
        ->and(Penalty::where('weekly_report_id', $report->id)->count())->toBe(1);
});

test('penalty service skips passing reports and duplicates', function () {
    $user = User::factory()->create();
    $passing = makeReport($user, 'memenuhi');
    $failing = makeReport($user);

    expect(PenaltyService::createIfNeeded($passing))->toBeFalse();
    expect(PenaltyService::createIfNeeded($failing))->toBeTrue();
    expect(PenaltyService::createIfNeeded($failing))->toBeFalse()
        ->and(Penalty::where('weekly_report_id', $failing->id)->count())->toBe(1);
});

test('penalty service skips a full-week leave', function () {
    $user = User::factory()->create();
    Leave::create([
        'user_id' => $user->id,
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-31',
    ]);
    $report = makeReport($user);

    expect(PenaltyService::createIfNeeded($report))->toBeFalse();
});

test('exempt-week check matches a stored date with whereDate', function () {
    // The weekly report generator used where('week_start', datetime),
    // which can miss a date stored as 2026-10-26 on SQLite. This is
    // the query form the generator now uses.
    PenaltyExemptWeek::create(['week_start' => '2026-10-26']);

    expect(PenaltyExemptWeek::whereDate('week_start', '2026-10-26')->exists())->toBeTrue();
});

test('attendance service sums weekly minutes', function () {
    $user = User::factory()->create();
    $monday = Carbon::parse('2026-10-26');
    $saturday = Carbon::parse('2026-10-31');

    \App\Models\Attendance::create([
        'user_id' => $user->id,
        'qrcode' => 'test-1',
        'date' => '2026-10-26',
        'check_in' => '2026-10-26 08:00:00',
        'check_out' => '2026-10-26 12:00:00',
        'duration_minutes' => 240,
    ]);
    \App\Models\Attendance::create([
        'user_id' => $user->id,
        'qrcode' => 'test-2',
        'date' => '2026-10-27',
        'check_in' => '2026-10-27 08:00:00',
        'check_out' => null,
        'duration_minutes' => null,
    ]);

    expect(AttendanceService::weeklyMinutes($user->id, $monday, $saturday))->toBe(240)
        ->and(AttendanceService::weeklyMinutes($user->id, $monday, $saturday, true))->toBe(240);
});
