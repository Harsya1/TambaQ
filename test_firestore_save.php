<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$firebaseService = app(App\Services\FirebaseService::class);
$fuzzyService = app(App\Services\FuzzyMamdaniService::class);

echo "=== Testing Firestore Save ===\n\n";

$sensorData = $firebaseService->getLatestSensorData();
if ($sensorData) {
    echo "✅ Sensor Data Retrieved:\n";
    echo "  pH: " . $sensorData['ph_value'] . "\n";
    echo "  TDS: " . $sensorData['tds_value'] . " PPM\n";
    echo "  Turbidity: " . $sensorData['turbidity'] . " NTU\n";
    echo "  Salinity: " . $sensorData['salinity_ppt'] . " PPT\n\n";
    
    $fuzzyResult = $fuzzyService->evaluateWaterQuality(
        $sensorData['ph_value'],
        $sensorData['tds_value'],
        $sensorData['turbidity']
    );
    
    echo "✅ Fuzzy Logic Evaluated:\n";
    echo "  Raw Result: ";
    print_r($fuzzyResult);
    echo "\n";
    echo "  Score: " . ($fuzzyResult['water_quality_score'] ?? 'N/A') . "\n";
    echo "  Category: " . ($fuzzyResult['category'] ?? 'N/A') . "\n";
    echo "  Recommendation: " . ($fuzzyResult['recommendation'] ?? 'N/A') . "\n\n";
    
    $saved = $firebaseService->saveFuzzyDecision($sensorData, $fuzzyResult);
    
    if ($saved) {
        echo "✅ SUCCESS: Data saved to Firestore!\n";
        echo "  - FuzzyAction/FuzzyData updated\n";
        echo "  - sensorHistory record created\n";
    } else {
        echo "❌ FAILED: Could not save to Firestore\n";
    }
} else {
    echo "❌ No sensor data available\n";
}
