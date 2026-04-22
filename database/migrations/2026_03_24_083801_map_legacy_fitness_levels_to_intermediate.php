<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update runner profiles
        DB::table('runner_profiles')
            ->whereIn('fitness_level', ['advanced', 'competitive'])
            ->update(['fitness_level' => 'intermediate']);

        // Update user challenges
        DB::table('user_challenges')
            ->whereIn('level', ['advanced', 'competitive'])
            ->update(['level' => 'intermediate']);

        // Update events
        DB::table('events')
            ->whereIn('difficulty', ['Advanced', 'Competitive'])
            ->update(['difficulty' => 'Intermediate']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration, no need to revert
    }
};
