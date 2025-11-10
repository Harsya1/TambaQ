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
        Schema::create('fuzzy_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_reading_id')->nullable()->constrained('sensor_readings')->onDelete('cascade');
            $table->string('water_quality_status')->comment('Status kualitas air hasil fuzzy');
            $table->string('recommendation')->comment('Rekomendasi tindakan');
            $table->text('fuzzy_details')->nullable()->comment('Detail perhitungan fuzzy logic');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuzzy_decisions');
    }
};
