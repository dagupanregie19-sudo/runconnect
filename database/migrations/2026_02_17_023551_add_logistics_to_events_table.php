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
        Schema::table('events', function (Blueprint $table) {
            $table->string('location')->after('slots')->nullable();
            $table->date('event_date')->after('location')->nullable();
            $table->time('event_time')->after('event_date')->nullable();
            $table->decimal('registration_fee', 8, 2)->after('event_time')->nullable();
            $table->string('route_data')->after('registration_fee')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['location', 'event_date', 'event_time', 'registration_fee', 'route_data']);
        });
    }
};
