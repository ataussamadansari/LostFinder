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
            $table->string('name', 100);
            $table->string('country_code', 5)->default('+91');
            $table->string('phone', 20)->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('email', 120)->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable(); // Tourist safety
            $table->enum('role', ['admin', 'driver', 'tourist'])->default('tourist');
            $table->boolean('is_active')->default(true);
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
