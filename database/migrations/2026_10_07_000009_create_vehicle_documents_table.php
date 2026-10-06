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
        Schema::create('vehicle_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->enum('document_type', [
                'registration',
                'insurance',
                'permit',
                'fitness',
                'other'
            ]);
            $table->string('document_number', 100)->nullable();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->enum('status', [
                'pending',
                'verified',
                'rejected',
                'expired'
            ])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->index('vehicle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_documents');
    }
};
