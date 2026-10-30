<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;
    //
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'reason',
    ];

    /**
     * The one rule for "is this user on leave for this week?", used by
     * weekly reports, penalty generation and the admin report pages.
     * A week only counts as leave if the leave covers the whole week
     * (Monday to Saturday). A leave for part of a week does not excuse
     * the rest of that week.
     */
    public static function coversFullWeek(int $userId, Carbon $weekStart, Carbon $weekEnd): bool
    {
        return static::where('user_id', $userId)
            ->where('start_date', '<=', $weekStart->toDateString())
            ->where('end_date', '>=', $weekEnd->toDateString())
            ->exists();
    }

    // Same rule, but against leaves already loaded in memory, so a
    // page listing many reports does not run one query per row.
    public static function coversFullWeekFromCollection($leaves, Carbon $weekStart, Carbon $weekEnd): bool
    {
        foreach ($leaves as $leave) {
            // Normalise to Y-m-d: a date column may arrive as a string
            // or a Carbon instance, and a Carbon string includes a time.
            $start = $leave->start_date instanceof Carbon
                ? $leave->start_date->toDateString()
                : substr((string) $leave->start_date, 0, 10);
            $end = $leave->end_date instanceof Carbon
                ? $leave->end_date->toDateString()
                : substr((string) $leave->end_date, 0, 10);

            if ($start <= $weekStart->toDateString() && $end >= $weekEnd->toDateString()) {
                return true;
            }
        }

        return false;
    }

}
