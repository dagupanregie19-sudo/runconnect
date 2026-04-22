<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('challenge_logs', function (Blueprint $table) {
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->json('start_coords')->nullable();
            $table->json('end_coords')->nullable();
            $table->json('route_points')->nullable(); // For path reconstruction
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('challenge_logs', function (Blueprint $table) {
            //
        });
    }
};
