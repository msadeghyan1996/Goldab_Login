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
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 11)->unique();
            $table->string('code');
            $table->boolean('status');
            $table->tinyInteger('otp_attempts')->default(0);
            $table->tinyInteger('otp_retry_count')->default(0);
            $table->text('user_agent')->nullable();
            $table->string('expire_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
