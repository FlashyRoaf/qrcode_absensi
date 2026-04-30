<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'user_id',
        'qrcode',
        // 'shift',
        // 'division',
        'date',
        // 'status',
        'check_in',
        'check_out',
        'duration_minutes',
        'location',
    ];

    public function user()
{
    return $this->belongsTo(User::class);
}

}
