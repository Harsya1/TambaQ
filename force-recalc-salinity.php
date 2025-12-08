<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FORCE RECALCULATE SALINITY ===" . PHP_EOL . PHP_EOL;

$firebaseService = app(App\Services\FirebaseService::class);

// Step 1: Manually set salinity to 0 in Firestore
echo "1. Resetting salinity to 0 in Firestore..." . PHP_EOL;
$result = $firebaseService->updateSensorDataWithSalinity(0);

if ($result) {
    echo "✅ Salinity reset to 0" . PHP_EOL;
} else {
    echo "❌ Failed to reset salinity" . PHP_EOL;
}

sleep(1);

// Step 2: Read again to trigger recalculation
echo PHP_EOL . "2. Reading data (should trigger auto-calculation)..." . PHP_EOL;
$sensorData = $firebaseService->getLatestSensorData();

if ($sensorData) {
    echo "✅ Data read:" . PHP_EOL;
    echo "   TDS: " . $sensorData['tds_value'] . " PPM" . PHP_EOL;
    echo "   Salinity: " . $sensorData['salinity_ppt'] . " PPT" . PHP_EOL;
    
    // Verify calculation
    $expected = round(($sensorData['tds_value'] * 0.57) / 1000, 2);
    echo PHP_EOL . "   Expected (TDS × 0.57 / 1000): $expected PPT" . PHP_EOL;
    
    if ($sensorData['salinity_ppt'] == $expected) {
        echo "   ✅ Calculation CORRECT!" . PHP_EOL;
    } else {
        echo "   ⚠️ Calculation mismatch" . PHP_EOL;
    }
}

echo PHP_EOL . "=== DONE ===" . PHP_EOL;
