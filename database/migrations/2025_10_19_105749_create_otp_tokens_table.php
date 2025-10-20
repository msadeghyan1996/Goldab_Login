<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('otp_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 16)->index()->comment('Normalized E.164 phone number');
            $table->char('code_hash', 64)->comment('SHA-256 hash of OTP: hash(pepper + salt + code)');
            $table->char('salt', 32)->comment('Hex salt used when hashing the OTP code');
            $table->unsignedTinyInteger('purpose')->index()->comment('OtpPurpose enum: 1=REGISTER, 2=LOGIN');
            $table->unsignedTinyInteger('attempts_count')->default(0)->comment('Number of verification attempts consumed');
            $table->unsignedTinyInteger('max_attempts')->default(5)->comment('Maximum allowed verification attempts');
            $table->timestamp('expires_at')->index()->comment('Timestamp when this OTP expires');
            $table->timestamp('consumed_at')->index()->nullable()->comment('Timestamp when a valid OTP was consumed');
            $table->ipAddress('request_ip')->nullable()->comment('IP address that requested OTP issuance');
            $table->string('device_id', 64)->nullable()->comment('Client device identifier from X-Device-Id header');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_tokens');
    }
};
