<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->unsignedSmallInteger('home_corners')->nullable();
            $table->unsignedSmallInteger('away_corners')->nullable();
            $table->timestamp('corner_stats_checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', fn (Blueprint $table) => $table->dropColumn(['home_corners', 'away_corners', 'corner_stats_checked_at']));
    }
};
