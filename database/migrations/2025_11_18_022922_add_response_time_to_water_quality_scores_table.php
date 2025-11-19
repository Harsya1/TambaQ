<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('water_quality_scores', function (Blueprint $table) {
            $table->decimal('response_time', 8, 2)->nullable()->after('water_level')->comment('Average response time in milliseconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('water_quality_scores', function (Blueprint $table) {
            $table->dropColumn('response_time');
        });
    }
};
