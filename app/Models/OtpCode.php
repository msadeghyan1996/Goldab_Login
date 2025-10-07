<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
class OtpCode extends Model
{
    protected $table = 'otp_codes';
    protected $fillable = [
        'mobile_number',
        'code_hash',
        'purpose',
        'attempts',
        'is_used',
        'expires_at',
        'verified_at',
    ];
    protected $casts = [
        'attempts' => 'integer',
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    const string PURPOSE_REGISTRATION = 'registration';
    const PURPOSE_LOGIN = 'login';
    const PURPOSE_PASSWORD_RESET = 'password_reset';
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
    public function isValid(): bool
    {
        return !$this->is_used && !$this->isExpired();
    }
    public function hasExceededAttempts(): bool
    {
        $maxAttempts = env('OTP_MAX_ATTEMPTS', 3);
        return $this->attempts >= $maxAttempts;
    }
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
    public function markAsUsed(): void
    {
        $this->is_used = true;
        $this->verified_at = Carbon::now();
        $this->save();
    }
    public function scopeValid($query)
    {
        return $query->where('is_used', false)
                    ->where('expires_at', '>', Carbon::now());
    }
    public function scopeForMobile($query, string $mobileNumber)
    {
        return $query->where('mobile_number', $mobileNumber);
    }
    public function scopeForPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }
}