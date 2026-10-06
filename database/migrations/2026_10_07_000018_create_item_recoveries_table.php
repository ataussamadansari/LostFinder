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
        Schema::create('item_recoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained('lost_item_tickets')->cascadeOnDelete();
            $table->foreignId('found_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('found_at')->useCurrent();
            $table->enum('handover_method', ['in_person', 'delivery', 'other'])->nullable();
            $table->timestamp('handover_at')->nullable();
            $table->boolean('passenger_confirmed')->default(false);
            $table->boolean('driver_confirmed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_recoveries');
    }
};
