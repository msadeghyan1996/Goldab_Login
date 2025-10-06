<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'password',
        'national_id',
        'mobile',
        'mobile_verified_at',
        'status'
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts () : array {
        return [
            'mobile_verified_at' => 'datetime',
            'password'           => 'hashed',
        ];
    }


    //======================================================== Relation Part ========================================


    public function otp () : HasOne {
        return $this->hasOne(OTP::class);
    }

    //======================================================== Function Part ========================================
    public function hasVerifiedMobile () : bool {
        return !is_null($this->mobile_verified_at);
    }

}
