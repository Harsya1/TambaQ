<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FuzzyDecision;
use App\Models\SensorReading;
use App\Models\Actuator;
use App\Services\FuzzyMamdaniService;

class FuzzyDecisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fuzzyService = new FuzzyMamdaniService();
        
        // Ambil semua sensor readings
        $sensorReadings = SensorReading::all();
        
        // Ambil aerator
        $aerator = Actuator::where('name', 'Aerator')->first();
        
        // Proses setiap sensor reading dengan fuzzy logic
        foreach ($sensorReadings as $sensor) {
            // Evaluasi dengan Fuzzy Mamdani
            $fuzzyResult = $fuzzyService->evaluateWaterQuality(
                $sensor->ph_value,
                $sensor->tds_value,
                $sensor->turbidity
            );
            
            // Create fuzzy decision berdasarkan hasil evaluasi
            FuzzyDecision::create([
                'sensor_reading_id' => $sensor->id,
                'water_quality_status' => $fuzzyResult['water_quality_status'],
                'recommendation' => $fuzzyResult['recommendation'],
                'fuzzy_details' => $fuzzyResult['fuzzy_details'],
            ]);
            
            // Update aerator status berdasarkan data terbaru (sensor terakhir)
            if ($sensor->id === $sensorReadings->last()->id && $aerator) {
                $aerator->update(['status' => $fuzzyResult['aerator_status']]);
            }
        }
    }
}
