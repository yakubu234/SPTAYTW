<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('racing_meetings', function (Blueprint $t) {
            $t->id(); $t->string('provider_id')->unique(); $t->string('course'); $t->string('country', 8)->nullable();
            $t->date('meeting_date')->index(); $t->string('going')->nullable(); $t->timestamps();
        });
        Schema::create('racing_races', function (Blueprint $t) {
            $t->id(); $t->foreignId('meeting_id')->constrained('racing_meetings')->cascadeOnDelete();
            $t->string('provider_id')->unique(); $t->string('name'); $t->dateTime('off_time')->index();
            $t->unsignedSmallInteger('distance_yards')->nullable(); $t->string('race_class')->nullable();
            $t->string('surface')->nullable(); $t->string('status')->default('scheduled'); $t->unsignedSmallInteger('field_size')->default(0);
            $t->timestamps();
        });
        Schema::create('racing_runners', function (Blueprint $t) {
            $t->id(); $t->foreignId('race_id')->constrained('racing_races')->cascadeOnDelete();
            $t->string('provider_id'); $t->string('horse'); $t->string('jockey')->nullable(); $t->string('trainer')->nullable();
            $t->unsignedSmallInteger('draw')->nullable(); $t->decimal('weight_lbs', 6, 2)->nullable();
            $t->decimal('official_rating', 6, 2)->nullable(); $t->decimal('speed_rating', 6, 2)->nullable();
            $t->decimal('performance_rating', 6, 2)->nullable(); $t->decimal('decimal_odds', 8, 3)->nullable();
            $t->boolean('non_runner')->default(false); $t->unsignedSmallInteger('finish_position')->nullable(); $t->timestamps();
            $t->unique(['race_id','provider_id']);
        });
        Schema::create('racing_analyses', function (Blueprint $t) {
            $t->id(); $t->foreignId('race_id')->constrained('racing_races')->cascadeOnDelete();
            $t->foreignId('runner_id')->constrained('racing_runners')->cascadeOnDelete();
            $t->decimal('win_probability', 6, 3); $t->decimal('place_probability', 6, 3);
            $t->decimal('fair_odds', 8, 3)->nullable(); $t->decimal('market_probability', 6, 3)->nullable();
            $t->decimal('edge_points', 6, 3)->nullable(); $t->unsignedTinyInteger('score'); $t->unsignedTinyInteger('data_quality');
            $t->string('race_confidence', 2); $t->string('status'); $t->json('evidence')->nullable();
            $t->unsignedSmallInteger('actual_finish')->nullable(); $t->boolean('win_hit')->nullable(); $t->boolean('place_hit')->nullable();
            $t->timestamps(); $t->unique(['race_id','runner_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('racing_analyses'); Schema::dropIfExists('racing_runners');
        Schema::dropIfExists('racing_races'); Schema::dropIfExists('racing_meetings');
    }
};