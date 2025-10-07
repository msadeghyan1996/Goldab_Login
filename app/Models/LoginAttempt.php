<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
class LoginAttempt extends Model
{
    protected $table = 'login_attempts';
    protected $fillable = [
        'mobile_number',
        'ip_address',
        'successful',
        'user_agent',
    ];
    protected $casts = [
        'successful' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function scopeFailed($query)
    {
        return $query->where('successful', false);
    }
    public function scopeSuccessful($query)
    {
        return $query->where('successful', true);
    }
    public function scopeForMobile($query, string $mobileNumber)
    {
        return $query->where('mobile_number', $mobileNumber);
    }
    public function scopeForIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }
    public function scopeRecent($query, int $minutes = 15)
    {
        return $query->where('created_at', '>', Carbon::now()->subMinutes($minutes));
    }
}