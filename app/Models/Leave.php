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

}
