<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up () : void {
        Schema::create('otps', function (Blueprint $table) {

            $table->unsignedBigInteger('user_id')->primary();
            $table->string('code', 10);
            $table->string('type', 10);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down () : void {
        Schema::dropIfExists('otps');
    }
};
