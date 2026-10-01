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
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('vehicle_number', 25)->unique(); // e.g. UP 65 AB 1234
            $table->enum('vehicle_type', ['auto', 'e_rickshaw', 'cab', 'bike'])->default('auto');
            $table->string('license_number', 50)->nullable();
            $table->string('rc_photo_url')->nullable();
            $table->string('qr_code_token', 64)->unique();
            $table->boolean('is_verified')->default(false);
            $table->text('fcm_token')->nullable();
            $table->unsignedInteger('total_trips')->default(0);
            $table->timestamps();

            $table->index('qr_code_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};
