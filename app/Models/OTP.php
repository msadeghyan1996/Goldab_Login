<?php

namespace App\Models;

use App\Enums\OTP\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OTP extends Model {

    protected $table        = 'otps';
    public    $incrementing = false;


    protected $fillable = [
        'user_id',
        'code',
        'type',
        'expires_at'
    ];

    protected function casts () {
        return [
            'expires_at' => 'datetime',
            'type'       => Type::class,
        ];
    }


    //======================================================== Relation Part ========================================


    public function user () : BelongsTo {
        return $this->belongsTo(User::class);
    }

    //======================================================== Function Part ========================================

    /**
     * Check if OTP is valid for a given code
     *
     * @param string $code
     *
     * @return bool
     */
    public function isValid (string $code) : bool {
        return $this->code === $code && $this->expires_at && $this->expires_at->isFuture();
    }

    /**
     * check if expired time
     * @return bool
     */
    public function isExpired () : bool {
        return $this->expires_at->isPast();
    }
}
