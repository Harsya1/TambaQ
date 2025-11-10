<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SensorReading;
use App\Models\Actuator;
use App\Models\FuzzyDecision;
use App\Services\FuzzyMamdaniService;

class ProcessFuzzyLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fuzzy:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process fuzzy logic Mamdani untuk evaluasi kualitas air dan kontrol aerator otomatis';

    protected $fuzzyService;

    public function __construct(FuzzyMamdaniService $fuzzyService)
    {
        parent::__construct();
        $this->fuzzyService = $fuzzyService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses Fuzzy Logic Mamdani...');
        
        // Ambil data sensor terbaru
        $latestSensor = SensorReading::latest()->first();
        
        if (!$latestSensor) {
            $this->error('Tidak ada data sensor yang tersedia!');
            return 1;
        }

        $this->info("Data Sensor Terbaru:");
        $this->line("- pH: {$latestSensor->ph_value}");
        $this->line("- TDS: {$latestSensor->tds_value} ppm");
        $this->line("- Turbidity: {$latestSensor->turbidity} NTU");
        
        // Evaluasi dengan Fuzzy Mamdani
        $fuzzyResult = $this->fuzzyService->evaluateWaterQuality(
            $latestSensor->ph_value,
            $latestSensor->tds_value,
            $latestSensor->turbidity
        );

        $this->newLine();
        $this->info("Hasil Evaluasi Fuzzy Mamdani:");
        $this->line("- Status Kualitas Air: {$fuzzyResult['water_quality_status']}");
        $this->line("- Status Aerator: " . strtoupper($fuzzyResult['aerator_status']));
        $this->line("- Rekomendasi: {$fuzzyResult['recommendation']}");
        
        // Update atau create fuzzy decision
        FuzzyDecision::updateOrCreate(
            ['sensor_reading_id' => $latestSensor->id],
            [
                'water_quality_status' => $fuzzyResult['water_quality_status'],
                'recommendation' => $fuzzyResult['recommendation'],
                'fuzzy_details' => $fuzzyResult['fuzzy_details'],
            ]
        );

        // Update status aerator
        $aerator = Actuator::where('name', 'Aerator')->first();
        if ($aerator) {
            $oldStatus = $aerator->status;
            $aerator->update(['status' => $fuzzyResult['aerator_status']]);
            
            if ($oldStatus !== $fuzzyResult['aerator_status']) {
                $this->warn("Status Aerator berubah dari {$oldStatus} menjadi {$fuzzyResult['aerator_status']}");
            } else {
                $this->comment("Status Aerator tetap: {$fuzzyResult['aerator_status']}");
            }
        }

        $this->newLine();
        $this->info('Proses Fuzzy Logic selesai!');
        
        return 0;
    }
}
