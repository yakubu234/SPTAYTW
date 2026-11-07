<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->boolean('first_five_goal')->nullable();
            $table->unsignedTinyInteger('first_five_goal_minute')->nullable();
            $table->timestamp('early_goal_events_checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', fn (Blueprint $table) => $table->dropColumn([
            'first_five_goal', 'first_five_goal_minute', 'early_goal_events_checked_at',
        ]));
    }
};
