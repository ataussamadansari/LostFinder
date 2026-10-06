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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reported_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('lost_item_tickets')->nullOnDelete();
            $table->string('type', 50);
            $table->text('description');
            $table->enum('status', ['open', 'investigating', 'resolved', 'rejected'])->default('open');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('reporter_id', 'idx_reports_reporter');
            $table->index('reported_user_id', 'idx_reports_reported_user');
            $table->index('vehicle_id', 'idx_reports_vehicle');
            $table->index('ticket_id', 'idx_reports_ticket');
            $table->index('status', 'idx_reports_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
