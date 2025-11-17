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
        Schema::create('water_quality_scores', function (Blueprint $table) {
            $table->id();
            $table->decimal('score', 5, 2); // Water quality score 0-100
            $table->decimal('ph_value', 4, 2)->nullable();
            $table->decimal('tds_value', 6, 2)->nullable();
            $table->decimal('turbidity', 5, 2)->nullable();
            $table->decimal('salinity', 5, 2)->nullable();
            $table->decimal('water_level', 5, 2)->nullable();
            $table->decimal('ph_min', 4, 2)->nullable();
            $table->decimal('ph_max', 4, 2)->nullable();
            $table->decimal('tds_min', 6, 2)->nullable();
            $table->decimal('tds_max', 6, 2)->nullable();
            $table->decimal('turbidity_min', 5, 2)->nullable();
            $table->decimal('turbidity_max', 5, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            // Index untuk query yang lebih cepat
            $table->index('recorded_at');
            $table->index(['recorded_at', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_quality_scores');
    }
};
