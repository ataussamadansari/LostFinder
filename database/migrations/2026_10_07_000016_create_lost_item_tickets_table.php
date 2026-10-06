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
        Schema::create('lost_item_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 30)->unique();
            $table->foreignId('journey_id')->constrained('journeys')->restrictOnDelete();
            $table->foreignId('passenger_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('lost_item_id')->constrained('lost_items')->restrictOnDelete();
            $table->enum('status', [
                'created',
                'driver_notified',
                'searching',
                'item_found',
                'recovery_pending',
                'handed_over',
                'closed',
                'not_found',
                'cancelled',
                'disputed',
                'escalated'
            ])->default('created');
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('passenger_id', 'idx_lit_passenger');
            $table->index('driver_id', 'idx_lit_driver');
            $table->index('vehicle_id', 'idx_lit_vehicle');
            $table->index('journey_id', 'idx_lit_journey');
            $table->index('status', 'idx_lit_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lost_item_tickets');
    }
};
