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
        Schema::create('promotion_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('subtitle', 255)->nullable();
            $table->string('image_url');
            $table->enum('action_type', ['url', 'screen', 'none'])->default('url');
            $table->string('action_target')->nullable(); // external URL or in-app route name
            $table->enum('placement', ['tourist_home', 'driver_home', 'ride_screen', 'claim_screen'])->default('tourist_home');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['placement', 'is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_banners');
    }
};
