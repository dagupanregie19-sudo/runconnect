<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->boolean('is_off_route')->default(false)->after('has_emergency');
            $table->timestamp('off_route_since')->nullable()->after('is_off_route');
            $table->unsignedInteger('off_route_count')->default(0)->after('off_route_since');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['is_off_route', 'off_route_since', 'off_route_count']);
        });
    }
};
