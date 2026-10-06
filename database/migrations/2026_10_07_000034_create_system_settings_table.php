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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->index('idx_settings_group');
            $table->string('key', 100)->unique('uq_settings_key');
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, boolean, integer, float, json, encrypted
            $table->string('label', 150);
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false)->index('idx_settings_public');
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
