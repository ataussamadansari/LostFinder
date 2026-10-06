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
        Schema::create('lost_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('category', [
                'mobile',
                'wallet',
                'bag',
                'documents',
                'jewellery',
                'laptop',
                'camera',
                'clothing',
                'keys',
                'passport',
                'electronics',
                'other'
            ]);
            $table->string('name', 150);
            $table->text('description');
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lost_items');
    }
};
