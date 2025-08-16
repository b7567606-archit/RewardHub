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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('age')->nullable();
            $table->string('number')->nullable();
            $table->string('otp')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->text('image')->nullable();
            $table->string('dob')->nullable();
            $table->string('gender')->nullable();
            $table->text('token')->nullable();
            $table->string('register')->nullable();
            $table->text('reg_id')->nullable();
            $table->string('active_status')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->timestamp('email_verified_at')->nullable(); 
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
