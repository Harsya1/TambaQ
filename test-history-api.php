<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TEST HISTORY PAGE API ===" . PHP_EOL . PHP_EOL;

// Test 1: History Stats
echo "1. Testing /api/history-stats:" . PHP_EOL;
$controller = app(App\Http\Controllers\DashboardController::class);
$response = $controller->getHistoryStats();
$data = json_decode($response->getContent(), true);

echo "✅ Total Devices: " . $data['totalDevices'] . PHP_EOL;
echo "✅ Devices with Warning: " . $data['devicesWithWarning'] . PHP_EOL;
echo "✅ Total Alerts (24h): " . $data['totalAlerts'] . PHP_EOL;
echo "✅ Avg Response Time: " . $data['avgResponseTime'] . PHP_EOL;

echo PHP_EOL . "2. Testing /api/history/data:" . PHP_EOL;
$request = new Illuminate\Http\Request([
    'startDate' => now()->subDays(7)->toDateString(),
    'endDate' => now()->toDateString(),
    'limit' => 10
]);

$response = $controller->getHistoryData($request);
$data = json_decode($response->getContent(), true);

echo "✅ Historical records found: " . $data['total'] . PHP_EOL;
if ($data['total'] > 0) {
    echo "   Latest record:" . PHP_EOL;
    $latest = $data['data'][0];
    echo "   - Timestamp: " . ($latest['timestamp'] ?? 'N/A') . PHP_EOL;
    echo "   - pH: " . ($latest['pH'] ?? 'N/A') . PHP_EOL;
    echo "   - TDS: " . ($latest['TDS'] ?? 'N/A') . PHP_EOL;
    echo "   - Turbidity: " . ($latest['turbidity'] ?? 'N/A') . PHP_EOL;
    echo "   - Water Quality Score: " . ($latest['FuzzyValue'] ?? 'N/A') . PHP_EOL;
    echo "   - Category: " . ($latest['category'] ?? 'N/A') . PHP_EOL;
}

echo PHP_EOL . "=== TEST SELESAI ===" . PHP_EOL;
