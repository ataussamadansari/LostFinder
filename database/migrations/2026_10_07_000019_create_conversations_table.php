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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('type', ['journey', 'lost_item', 'support']);
            $table->foreignId('journey_id')->nullable()->constrained('journeys')->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('lost_item_tickets')->cascadeOnDelete();
            $table->enum('status', ['active', 'closed', 'blocked'])->default('active');
            $table->timestamps();

            $table->index('journey_id', 'idx_conversations_journey');
            $table->index('ticket_id', 'idx_conversations_ticket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
