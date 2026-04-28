<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyReport extends Model
{
    //
    protected $fillable = [
        'user_id',
        'week_start',
        'total_minutes',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
