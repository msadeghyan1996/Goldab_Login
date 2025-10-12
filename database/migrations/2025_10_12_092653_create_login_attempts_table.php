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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mobile')->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('channel', 50);
            $table->string('method', 50);
            $table->string('result', 50);
            $table->json('context')->nullable();
            $table->timestampTz('occurred_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
