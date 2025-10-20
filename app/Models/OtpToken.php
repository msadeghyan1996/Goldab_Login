<?php

namespace App\Models;

use App\Models\Enum\OtpPurpose;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * OTP token model.
 *
 * @property int $id
 * @property string $phone
 * @property string $code_hash
 * @property string $salt
 * @property OtpPurpose $purpose
 * @property int $attempts_count
 * @property int $max_attempts
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 * @property string|null $request_ip
 * @property string|null $device_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read bool $can_attempt
 */
class OtpToken extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'purpose' => OtpPurpose::class,
        ];
    }

	/**
	 * Mutator for the `code_hash` attribute that hashes a raw OTP with a
	 * per-record random salt and a global pepper, and stores both the hash
	 * and the salt on the model.
	 *
	 * The hash is computed as SHA-256 of (pepper + salt + value), where
	 * `salt` is a 16-byte random value encoded as hex.
	 *
	 * @return Attribute
	 */
	protected function codeHash(): Attribute
    {
        return Attribute::set(function ($value) {
            $pepper = config('otp.pepper');
            $salt = bin2hex(random_bytes(16));
            $codeHash = hash('sha256', $pepper . $salt . $value);

            return [
                'code_hash' => $codeHash,
                'salt' => $salt
            ];
        });
    }

    /**
     * Computed attribute indicating whether another verification attempt is allowed.
     *
     * True when attempts are below the maximum and the token is neither expired nor consumed.
     *
     * @return Attribute
     */
    protected function canAttempt(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => ($this->attempts_count <= $this->max_attempts),
        );
    }
}
