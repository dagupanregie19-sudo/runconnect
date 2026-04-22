<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['beginner', 'improving', 'intermediate'])->default('beginner');
            $table->enum('status', ['active', 'completed', 'failed'])->default('active');
            $table->decimal('target_distance', 8, 2)->default(0); // total km target
            $table->decimal('distance_logged', 8, 2)->default(0); // total km logged so far
            $table->decimal('daily_target', 8, 2)->nullable(); // km per day limit or requirement
            $table->integer('duration_days'); // challenge duration in days
            $table->timestamp('started_at');
            $table->timestamp('deadline_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_challenges');
    }
};
