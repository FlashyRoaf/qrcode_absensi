<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penalty extends Model
{
    //
    protected $fillable = [
        'user_id',
        'weekly_report_id',
        'status',
        'proof_path',
        'rejection_reason',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function weeklyReport()
    {
        return $this->belongsTo(WeeklyReport::class);
    }

        public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isUploaded(): bool
    {
        return $this->status === 'uploaded';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isExempted(): bool
    {
        return $this->status === 'exempted';
    }
}
