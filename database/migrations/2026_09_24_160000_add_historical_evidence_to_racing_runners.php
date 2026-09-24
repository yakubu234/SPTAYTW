<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('racing_runners', function (Blueprint $table) {
            $table->json('historical_evidence')->nullable()->after('decimal_odds');
        });
    }

    public function down(): void {
        Schema::table('racing_runners', function (Blueprint $table) {
            $table->dropColumn('historical_evidence');
        });
    }
};
