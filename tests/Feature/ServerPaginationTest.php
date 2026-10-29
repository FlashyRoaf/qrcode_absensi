<?php

use App\Models\Attendance;
use App\Models\Penalty;
use App\Models\User;
use App\Models\WeeklyReport;
use Inertia\Testing\AssertableInertia as Assert;

test('admin attendance is paged at 50 rows', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $employee = User::factory()->create();
    Attendance::factory()->count(55)->create(['user_id' => $employee->id]);

    $this->actingAs($admin)->get('/admin/attendance')->assertInertia(
        fn (Assert $page) => $page
            ->component('admin/Attendance')
            ->where('attendances.meta.per_page', 50)
            ->where('attendances.meta.total', 55)
    );
});

test('penalties are paged at 20 rows', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $employee = User::factory()->create();
    $report = WeeklyReport::create([
        'user_id' => $employee->id,
        'week_start' => '2026-10-26',
        'total_minutes' => 100,
        'status' => 'tidak_memenuhi',
    ]);
    foreach (range(1, 25) as $i) {
        Penalty::create([
            'user_id' => $employee->id,
            'weekly_report_id' => $report->id,
            'status' => 'pending',
        ]);
    }

    $this->actingAs($admin)->get('/admin/penalties?tab=all')->assertInertia(
        fn (Assert $page) => $page
            ->component('admin/Penalty')
            ->where('penalties.meta.per_page', 20)
    );
});

test('weekly report table is paged at 10 rows', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $employee = User::factory()->create();
    foreach (range(1, 12) as $week) {
        WeeklyReport::create([
            'user_id' => $employee->id,
            'week_start' => now()->startOfWeek()->subWeeks($week)->toDateString(),
            'total_minutes' => 100,
            'status' => 'tidak_memenuhi',
        ]);
    }

    $this->actingAs($admin)->get('/admin/weekly-report')->assertInertia(
        fn (Assert $page) => $page
            ->component('admin/WeeklyReport')
            ->where('reports.meta.per_page', 10)
            ->where('reports.meta.total', 12)
    );
});
