<?php
namespace App\Models;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Laravel\Lumen\Auth\Authorizable;
class User extends BaseModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;
    protected $table = 'users';
    protected $fillable = [
        'mobile_number',
        'password',
        'first_name',
        'last_name',
        'national_id',
        'is_verified',
        'mobile_verified_at',
    ];
    protected $hidden = [
        'password',
        'deleted_at',
    ];
    protected $casts = [
        'is_verified' => 'boolean',
        'mobile_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    public function hasCompletedRegistration(): bool
    {
        return !empty($this->first_name)
            && !empty($this->last_name)
            && !empty($this->national_id)
            && $this->is_verified;
    }
    public function needsPassword(): bool
    {
        return empty($this->password);
    }
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
    public function verifyMobile(): void
    {
        $this->is_verified = true;
        $this->mobile_verified_at = now();
        $this->save();
    }
}