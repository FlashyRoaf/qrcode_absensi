<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;

// Shared weekly-minutes calculation, used by both the dashboard
// view (Monday to now, only days with a check-out) and the weekly
// report generator (the whole Monday-Saturday week).
class AttendanceService
{
    public static function weeklyMinutes(
        int $userId,
        Carbon $start,
        Carbon $end,
        bool $requireCheckout = false,
    ): int {
        $query = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);

        if ($requireCheckout) {
            $query->whereNotNull('check_out');
        }

        return (int) $query->sum('duration_minutes');
    }
}
