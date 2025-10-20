<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * user model.
 *
 * @property int $id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $phone
 * @property string|null $national_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read bool $is_verification_completed
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'national_id',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }


    /**
     * Computed attribute indicating whether the user completed verification.
     *
     * Returns true when the `national_id` is present (non-null), false otherwise.
     *
     * @return Attribute<bool, never>
     */
    protected function isVerificationCompleted(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => (bool) $this->national_id,
        );
    }
}
