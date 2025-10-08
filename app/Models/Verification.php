<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verification extends Model {
    protected $table = 'verifications';
    protected $fillable = [
        'phone',
        'code',
        'status',
        'otp_attempts',
        'otp_retry_count',
        'user_agent',
        'expire_at'
    ];
}
