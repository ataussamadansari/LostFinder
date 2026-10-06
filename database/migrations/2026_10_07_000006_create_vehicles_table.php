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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('vehicle_code', 30)->unique();
            $table->string('registration_number', 30)->unique();
            $table->enum('vehicle_type', [
                'cab',
                'taxi',
                'auto',
                'e_rickshaw',
                'bus',
                'tourist_vehicle',
                'other'
            ]);
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->enum('verification_status', [
                'pending',
                'under_review',
                'verified',
                'rejected',
                'suspended'
            ])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
