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
        Schema::table('journey_passengers', function (Blueprint $table) {
            $table->boolean('share_details')->default(false)->after('status');
            $table->json('shared_fields')->nullable()->after('share_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journey_passengers', function (Blueprint $table) {
            $table->dropColumn(['share_details', 'shared_fields']);
        });
    }
};
