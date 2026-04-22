<?php

/*
 * Offline recommendation dataset (thesis / SOP: data-driven hybrid evaluation).
 * Filled by: php artisan reco:import-excel
 * race_category = evaluation ground truth; research_consent = ethics filter.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recommendation_training_samples', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('source_row')->nullable();
            $table->timestamp('observed_at')->nullable();
            $table->string('full_name')->nullable();
            $table->string('age_group')->nullable();
            $table->string('gender')->nullable();
            $table->string('running_experience')->nullable();
            $table->string('race_category')->nullable();
            $table->string('total_events_joined')->nullable();
            $table->boolean('running_club_member')->default(false);
            $table->string('average_pace_category')->nullable();
            $table->decimal('exact_avg_pace_min_per_km', 5, 2)->nullable();
            $table->string('best_5km_time')->nullable();
            $table->text('health_conditions')->nullable();
            $table->boolean('research_consent')->default(true);
            $table->timestamps();

            $table->index('research_consent');
            $table->index('observed_at');
            $table->index('race_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_training_samples');
    }
};

