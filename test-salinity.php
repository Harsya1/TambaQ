<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TEST SALINITY UPDATE ===" . PHP_EOL . PHP_EOL;

$firebaseService = app(App\Services\FirebaseService::class);

// Step 1: Get current data
echo "1. Getting current sensor data..." . PHP_EOL;
$sensorData = $firebaseService->getLatestSensorData();

if ($sensorData) {
    echo "✅ Current data:" . PHP_EOL;
    echo "   pH: " . $sensorData['ph_value'] . PHP_EOL;
    echo "   TDS: " . $sensorData['tds_value'] . " PPM" . PHP_EOL;
    echo "   Turbidity: " . $sensorData['turbidity'] . " NTU" . PHP_EOL;
    echo "   Water Level: " . $sensorData['water_level'] . " cm" . PHP_EOL;
    echo "   Salinity: " . $sensorData['salinity_ppt'] . " PPT" . PHP_EOL;
    
    // Step 2: Check if salinity was auto-calculated
    echo PHP_EOL . "2. Checking if salinity was auto-calculated and saved..." . PHP_EOL;
    
    // Wait 2 seconds and read again
    sleep(2);
    
    $sensorData2 = $firebaseService->getLatestSensorData();
    echo "   Salinity after re-read: " . $sensorData2['salinity_ppt'] . " PPT" . PHP_EOL;
    
    if ($sensorData2['salinity_ppt'] > 0) {
        echo "   ✅ Salinity successfully saved to Firestore!" . PHP_EOL;
    } else {
        echo "   ⚠️ Salinity still 0 - check logs for errors" . PHP_EOL;
    }
    
    // Step 3: Manual calculation test
    echo PHP_EOL . "3. Testing TDS to Salinity conversion:" . PHP_EOL;
    $testTDS = $sensorData['tds_value'];
    $K = 0.57;
    $expectedSalinity = round(($testTDS * $K / 1000), 2);
    echo "   TDS: $testTDS PPM" . PHP_EOL;
    echo "   Expected Salinity: $expectedSalinity PPT" . PHP_EOL;
    echo "   Actual Salinity: " . $sensorData2['salinity_ppt'] . " PPT" . PHP_EOL;
    
} else {
    echo "❌ Failed to get sensor data!" . PHP_EOL;
}

echo PHP_EOL . "=== TEST SELESAI ===" . PHP_EOL;
echo PHP_EOL . "Check storage/logs/laravel.log for detailed update logs" . PHP_EOL;
