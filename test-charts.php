<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\FirebaseService;

echo "=== TEST HISTORY PAGE DATA ===\n\n";

$firebase = new FirebaseService();

// Test 1: Total Alerts 24h
echo "1. TOTAL ALERTS (Last 24 Hours)\n";
echo str_repeat("-", 50) . "\n";
$totalAlerts = $firebase->countAlertsLast24Hours();
echo sprintf("   Total Alerts (FuzzyValue < 45): %d\n", $totalAlerts);
echo "   Kategori: Critical + Poor\n";

echo "\n";

// Test 2: Alerts Frequency 7 Days
echo "2. ALERTS FREQUENCY (7 Days)\n";
echo str_repeat("-", 50) . "\n";
$alertsData = $firebase->getAlertsFrequency7Days();

if (empty($alertsData)) {
    echo "⚠️  Tidak ada data alerts (sensorHistory masih kosong)\n";
} else {
    $total = 0;
    foreach ($alertsData as $item) {
        echo sprintf("   %s: %d alerts\n", $item['date'], $item['count']);
        $total += $item['count'];
    }
    echo sprintf("\n   Total 7 Days: %d alerts\n", $total);
}

echo "\n";

// Test 3: Response Time 24 Hours
echo "3. RESPONSE TIME (24 Hours)\n";
echo str_repeat("-", 50) . "\n";
$responseData = $firebase->getResponseTime24Hours();

if (empty($responseData)) {
    echo "⚠️  Tidak ada data response time (sensorHistory masih kosong)\n";
} else {
    // Tampilkan hanya 6 jam terakhir untuk readability
    $last6Hours = array_slice($responseData, -6);
    foreach ($last6Hours as $item) {
        echo sprintf("   %s: %.0f ms (%.2f s)\n", 
            $item['hour'], 
            $item['response_time'],
            $item['response_time'] / 1000
        );
    }
    
    // Hitung rata-rata
    $avgAll = array_sum(array_column($responseData, 'response_time')) / count($responseData);
    echo sprintf("\n   Average 24h: %.0f ms (%.2f s)\n", $avgAll, $avgAll / 1000);
}

echo "\n";

// Test 4: Average Response Time (Stat Card)
echo "4. AVERAGE RESPONSE TIME (Stat Card)\n";
echo str_repeat("-", 50) . "\n";
$avgResponseTime = $firebase->getAverageResponseTime();
echo sprintf("   Avg Response Time: %s\n", $avgResponseTime);
echo "   Expected: ~10.0s (ESP32 uploadInterval)\n";

echo "\n=== TEST SELESAI ===\n";
echo "\n💡 NOTE:\n";
echo "   - Data akan muncul setelah ESP32 upload beberapa kali\n";
echo "   - Laravel otomatis save ke sensorHistory setelah proses fuzzy\n";
echo "   - Alerts dihitung dari FuzzyValue < 45 (kategori Poor + Critical)\n";
