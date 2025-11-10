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
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->decimal('ph_value', 4, 2)->nullable()->comment('Nilai pH air');
            $table->decimal('water_level', 8, 2)->nullable()->comment('Jarak permukaan air dalam cm');
            $table->decimal('tds_value', 8, 2)->nullable()->comment('Total Dissolved Solids dalam ppm');
            $table->decimal('salinity', 8, 2)->nullable()->comment('Salinitas hasil konversi TDS');
            $table->decimal('turbidity', 8, 2)->nullable()->comment('Kekeruhan air dalam NTU');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
