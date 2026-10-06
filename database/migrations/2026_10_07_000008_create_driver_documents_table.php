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
        Schema::create('driver_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->enum('document_type', [
                'government_id',
                'driving_license',
                'address_proof',
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
            $table->text('rejection_reason')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->index('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_documents');
    }
};
