<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TEST FIRESTORE REST API ===" . PHP_EOL . PHP_EOL;

// Test 1: Direct HTTP call
$url = "https://firestore.googleapis.com/v1/projects/cihuyyy-7eb5ca94/databases/(default)/documents/sensorRead/dataSensor";
echo "1. Testing direct URL: " . PHP_EOL;
echo "   $url" . PHP_EOL . PHP_EOL;

$response = file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['fields'])) {
    echo "✅ Data berhasil dibaca dari Firestore!" . PHP_EOL . PHP_EOL;
    echo "Raw Fields:" . PHP_EOL;
    print_r($data['fields']);
    echo PHP_EOL;
} else {
    echo "❌ Gagal membaca data!" . PHP_EOL;
    print_r($data);
}

// Test 2: Using FirebaseService
echo PHP_EOL . "2. Testing FirebaseService->getLatestSensorData():" . PHP_EOL;
$firebaseService = app(App\Services\FirebaseService::class);
$sensorData = $firebaseService->getLatestSensorData();

if ($sensorData) {
    echo "✅ FirebaseService berhasil!" . PHP_EOL . PHP_EOL;
    echo "Parsed Data:" . PHP_EOL;
    print_r($sensorData);
} else {
    echo "❌ FirebaseService return NULL!" . PHP_EOL;
}

// Test 3: Using FuzzyMamdaniService
if ($sensorData) {
    echo PHP_EOL . "3. Testing Fuzzy Mamdani Evaluation:" . PHP_EOL;
    $fuzzyService = app(App\Services\FuzzyMamdaniService::class);
    
    $fuzzyResult = $fuzzyService->evaluateWaterQuality(
        $sensorData['ph_value'],
        $sensorData['tds_value'],
        $sensorData['turbidity']
    );
    
    echo "✅ Fuzzy evaluation complete!" . PHP_EOL . PHP_EOL;
    echo "Water Quality Score: " . $fuzzyResult['water_quality_score'] . "/100" . PHP_EOL;
    echo "Category: " . $fuzzyResult['category'] . PHP_EOL;
    echo "Status: " . $fuzzyResult['water_quality_status'] . PHP_EOL;
    echo "Recommendation: " . $fuzzyResult['recommendation'] . PHP_EOL;
}

echo PHP_EOL . "=== TEST SELESAI ===" . PHP_EOL;
