<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('football_history_coverages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_provider_id');
            $table->unsignedSmallInteger('season');
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['team_provider_id', 'season']);
            $table->index('fetched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_history_coverages');
    }
};
