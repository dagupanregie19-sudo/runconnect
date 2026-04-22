<?php

/*
 * Stores each reco:evaluate / reco:grid-search run (SOP: Precision@K, Recall@K, F1@K evidence).
 * metrics JSON: precision_at_k, recall_at_k, f1_at_k, hit_rate_at_k, accuracy_at_1, ...
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recommendation_experiment_runs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('k')->default(3);
            $table->string('split_strategy')->default('time');
            $table->decimal('test_ratio', 4, 2)->default(0.20);
            $table->json('weights');
            $table->unsignedInteger('train_size')->default(0);
            $table->unsignedInteger('test_size')->default(0);
            $table->json('metrics');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('split_strategy');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_experiment_runs');
    }
};

