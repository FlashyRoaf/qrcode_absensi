<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\Penalty;
use App\Models\WeeklyReport;
use Carbon\Carbon;

// The one place that decides whether a weekly report becomes a
// penalty. Both the weekly report generator and the manual
// backfill command call this, so the rule cannot drift apart
// between the two paths.
class PenaltyService
{
    public static function createIfNeeded(WeeklyReport $report): bool
    {
        if ($report->status !== 'tidak_memenuhi') {
            return false;
        }

        $weekStart = Carbon::parse($report->week_start)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(5)->endOfDay();

        // Same rule as weekly reports: only a leave covering the
        // whole Monday-Saturday week excuses the penalty.
        if (Leave::coversFullWeek($report->user_id, $weekStart, $weekEnd)) {
            return false;
        }

        $exists = Penalty::where('user_id', $report->user_id)
            ->where('weekly_report_id', $report->id)
            ->exists();

        if ($exists) {
            return false;
        }

        Penalty::firstOrCreate(
            [
                'user_id' => $report->user_id,
                'weekly_report_id' => $report->id,
            ],
            ['status' => 'pending'],
        );

        return true;
    }
}
