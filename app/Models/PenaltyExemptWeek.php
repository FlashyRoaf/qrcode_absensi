<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenaltyExemptWeek extends Model
{
    //
    protected $fillable = [
        'week_start',
        'reason',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];
}
