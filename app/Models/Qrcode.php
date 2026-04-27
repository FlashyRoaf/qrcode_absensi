<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Qrcode extends Model
{
    //
    protected $fillable = ['token', 'type', 'expires_at'];

    protected $dates = ['expires_at'];

    public function isExpired(): bool {
        return now()->greaterThan($this->expires_at);
    }
}
