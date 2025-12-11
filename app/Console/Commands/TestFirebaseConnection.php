<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseService;

class TestFirebaseConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Firebase Firestore connection and fetch sensor data';

    /**
     * Execute the console command.
     */
    public function handle(FirebaseService $firebaseService)
    {
        $this->info('🔥 Testing Firebase Firestore Connection...');
        $this->newLine();

        try {
            // Test 1: Get latest sensor data
            $this->info('📊 Fetching sensor data from Firestore...');
            $sensorData = $firebaseService->getLatestSensorData();

            if (!$sensorData) {
                $this->error('❌ No sensor data found or connection failed!');
                $this->warn('Please check:');
                $this->warn('1. Firebase service account credentials in .env');
                $this->warn('2. Firestore collection "sensorRead" exists');
                $this->warn('3. Document "dataSensor" exists in collection');
                return 1;
            }

            $this->info('✅ Sensor data retrieved successfully!');
            $this->newLine();

            // Display sensor data
            $this->table(
                ['Field', 'Value', 'Unit'],
                [
                    ['pH Value', number_format($sensorData['ph_value'], 2), 'pH'],
                    ['TDS Value', number_format($sensorData['tds_value'], 2), 'PPM'],
                    ['Turbidity', number_format($sensorData['turbidity'], 2), 'NTU'],
                    ['Water Level', number_format($sensorData['water_level'], 2), 'cm'],
                    ['Timestamp', $sensorData['timestamp'], ''],
                ]
            );

            $this->newLine();
            $this->info('🎉 Firebase connection test completed successfully!');
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Firebase connection test failed!');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
