<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->index(['event_id', 'status', 'priority_score', 'created_at'], 'event_status_priority_created_idx');
        });

        DB::statement("ALTER TABLE event_registrations MODIFY status ENUM('registered','pending_allocation','cancelled') DEFAULT 'registered'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE event_registrations MODIFY status ENUM('registered','cancelled') DEFAULT 'registered'");

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropIndex('event_status_priority_created_idx');
        });
    }
};
