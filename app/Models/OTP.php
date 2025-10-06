<?php

namespace App\Models;

use App\Enums\OTP\Type;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class OTP extends Model {
    use HasFactory;

    protected $table        = 'otps';
    protected $primaryKey   = 'user_id';
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
     *
     * @return bool
     */
    public function isExpired (Type $type) : bool {
        return $this->expires_at->isPast() || $this->type !== $type;
    }

    public function setExpire () {
        $this->expires_at = Carbon::now()->subMinutes(20);
    }
}
