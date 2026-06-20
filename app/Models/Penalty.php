<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function weeklyReport(): BelongsTo
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
