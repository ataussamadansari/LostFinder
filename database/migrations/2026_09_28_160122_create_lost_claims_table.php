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
        Schema::create('lost_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_session_id')->constrained('ride_sessions')->cascadeOnDelete();
            $table->string('item_category', 50); // Phone, Bag, Wallet, etc.
            $table->text('item_description');
            $table->string('item_photo_url')->nullable();
            $table->string('handover_otp', 6);
            $table->enum('claim_status', ['reported', 'searching', 'found', 'returned', 'disputed'])->default('reported');
            $table->decimal('bounty_amount', 10, 2)->default(0.00);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['ride_session_id', 'claim_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lost_claims');
    }
};
