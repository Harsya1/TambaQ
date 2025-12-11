<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FuzzyMamdaniService;
use App\Services\FirebaseService;
use App\Services\TelegramService;

class DashboardController extends Controller
{
    protected $fuzzyService;
    protected $firebaseService;
    protected $telegramService;

    public function __construct(
        FuzzyMamdaniService $fuzzyService, 
        FirebaseService $firebaseService,
        TelegramService $telegramService
    ) {
        $this->fuzzyService = $fuzzyService;
        $this->firebaseService = $firebaseService;
        $this->telegramService = $telegramService;
    }

    public function index()
    {
        // Ambil data sensor terbaru dari Firestore
        $sensorData = $this->firebaseService->getLatestSensorData();
        
        // Jika ada data sensor, evaluasi dengan fuzzy logic
        if ($sensorData) {
            $fuzzyDecision = $this->processFuzzyLogic($sensorData);
        } else {
            // Default values when no sensor data
            $fuzzyDecision = [
                'water_quality_status' => 'Tidak ada data',
                'recommendation' => 'Menunggu data sensor dari ESP32...',
                'fuzzy_details' => '',
                'water_quality_score' => 0,
                'sensorReading' => (object) [
                    'ph_value' => 0,
                    'tds_value' => 0,
                    'turbidity' => 0,
                    'water_level' => 0,
                    'water_quality_score' => 0
                ]
            ];
            $sensorData = [
                'ph_value' => 0,
                'tds_value' => 0,
                'turbidity' => 0,
                'water_level' => 0
            ];
        }
        
        return view('dashboard', [
            'userName' => Auth::user()->name,
            'sensorData' => (object) $sensorData,
            'fuzzyDecision' => (object) $fuzzyDecision,
        ]);
    }

    /**
     * Proses fuzzy logic
     */
    private function processFuzzyLogic($sensorData)
    {
        // Evaluasi kualitas air dengan Fuzzy Mamdani
        $fuzzyResult = $this->fuzzyService->evaluateWaterQuality(
            $sensorData['ph_value'],
            $sensorData['tds_value'],
            $sensorData['turbidity']
        );

        // Simpan hasil ke Firestore
        $this->firebaseService->saveFuzzyDecision($sensorData, $fuzzyResult);

        // === TELEGRAM ALERT INTEGRATION ===
        // Kirim notifikasi jika kondisi tidak normal (Critical atau Poor)
        $this->checkAndSendTelegramAlert($sensorData, $fuzzyResult);

        // Return fuzzy decision data
        return [
            'water_quality_status' => $fuzzyResult['water_quality_status'],
            'recommendation' => $fuzzyResult['recommendation'],
            'fuzzy_details' => $fuzzyResult['fuzzy_details'],
            'water_quality_score' => $fuzzyResult['water_quality_score'],
            'sensorReading' => (object) array_merge($sensorData, [
                'water_quality_score' => $fuzzyResult['water_quality_score']
            ]),
        ];
    }

    /**
     * Cek kondisi air dan kirim Telegram alert jika abnormal
     * 
     * @param array $sensorData Data sensor saat ini
     * @param array $fuzzyResult Hasil evaluasi fuzzy logic
     * @return void
     */
    private function checkAndSendTelegramAlert(array $sensorData, array $fuzzyResult): void
    {
        // Skip jika Telegram belum dikonfigurasi
        if (!$this->telegramService->isConfigured()) {
            return;
        }

        $score = (float) ($fuzzyResult['water_quality_score'] ?? 0);
        $category = $fuzzyResult['category'] ?? 'Unknown';
        
        // Kirim alert jika kondisi Critical atau Poor (score < 45)
        if ($score < 45) {
            // Rate limiting: cegah spam dengan cache key berdasarkan kategori
            $alertKey = "water_quality_{$category}";
            
            if ($this->telegramService->canSendAlert($alertKey)) {
                $this->telegramService->sendAlert(
                    (float) ($sensorData['ph_value'] ?? 0),
                    (float) ($sensorData['tds_value'] ?? 0),
                    (float) ($sensorData['turbidity'] ?? 0),
                    $category,
                    $score
                );
            }
        }
        
        // Alert spesifik untuk parameter individual yang kritis
        $this->checkParameterAlerts($sensorData);
    }

    /**
     * Cek alert untuk parameter individual
     * Mengirim satu notifikasi gabungan jika ada parameter yang kritis
     * 
     * @param array $sensorData
     * @return void
     */
    private function checkParameterAlerts(array $sensorData): void
    {
        $alerts = [];
        
        // Cek pH (normal: 6.5 - 8.5)
        $ph = $sensorData['ph_value'] ?? 0;
        if ($ph < 6.5 || $ph > 8.5) {
            $alerts['ph'] = [
                'value' => $ph,
                'status' => $ph > 8.5 ? 'ABOVE THRESHOLD' : 'BELOW THRESHOLD',
                'normal' => '6.5 - 8.5'
            ];
        }

        // Cek TDS (normal: 300 - 800 ppm)
        $tds = $sensorData['tds_value'] ?? 0;
        if ($tds < 300 || $tds > 800) {
            $alerts['tds'] = [
                'value' => $tds,
                'status' => $tds > 800 ? 'ABOVE THRESHOLD' : 'BELOW THRESHOLD',
                'normal' => '300 - 800 ppm'
            ];
        }

        // Cek Turbidity (normal: 20 - 45 NTU)
        $turbidity = $sensorData['turbidity'] ?? 0;
        if ($turbidity < 20 || $turbidity > 45) {
            $alerts['turbidity'] = [
                'value' => $turbidity,
                'status' => $turbidity > 45 ? 'ABOVE THRESHOLD' : 'BELOW THRESHOLD',
                'normal' => '20 - 45 NTU'
            ];
        }

        // Kirim satu notifikasi gabungan jika ada alert
        if (!empty($alerts)) {
            $alertKey = 'combined_params_' . implode('_', array_keys($alerts));
            if ($this->telegramService->canSendAlert($alertKey)) {
                $this->telegramService->sendCombinedAlert($ph, $tds, $turbidity, $alerts);
            }
        }
    }

    /**
     * API endpoint untuk mendapatkan data sensor terbaru
     */
    public function getLatestSensorData()
    {
        // Ambil data real-time dari Firestore (with caching and fallback)
        $sensorData = $this->firebaseService->getLatestSensorData();
        
        if (!$sensorData) {
            // Last resort: return mock data dengan pesan informatif
            return response()->json([
                'sensor' => (object) [
                    'ph_value' => 0,
                    'tds_value' => 0,
                    'turbidity' => 0,
                    'water_level' => 0,
                    'water_quality_score' => 0
                ],
                'fuzzyDecision' => (object) [
                    'water_quality_score' => 0,
                    'water_quality_status' => 'No Data Available',
                    'recommendation' => 'System sedang mengalami keterbatasan akses Firebase. Data akan kembali tersedia setelah quota reset atau cache diperbarui.'
                ],
                'message' => 'Firebase quota exceeded - cached data unavailable',
                'status' => 'quota_exceeded'
            ], 200);
        }
        
        // Proses fuzzy logic
        $fuzzyDecision = $this->processFuzzyLogic($sensorData);
        
        return response()->json([
            'sensor' => (object) array_merge($sensorData, [
                'water_quality_score' => $fuzzyDecision['water_quality_score'] ?? 0
            ]),
            'fuzzyDecision' => (object) $fuzzyDecision,
            'status' => 'ok'
        ]);
    }

    /**
     * Halaman Riwayat
     */
    public function history()
    {
        return view('history', [
            'userName' => Auth::user()->name,
        ]);
    }

    /**
     * API endpoint untuk statistik riwayat
     */
    public function getHistoryStats()
    {
        // Total sensors (4 physical sensors: pH, TDS, Turbidity, Ultrasonic)
        $totalDevices = 4;
        
        // Check individual sensor status
        $sensorData = $this->firebaseService->getLatestSensorData();
        $sensorsWithWarning = 0;
        
        if ($sensorData) {
            // pH Sensor: Check if offline (value = 0 or unrealistic) or out of range
            $phValue = $sensorData['ph_value'];
            if ($phValue == 0 || $phValue < 0 || $phValue > 14 || $phValue < 6.5 || $phValue > 9.0) {
                $sensorsWithWarning++;
            }
            
            // TDS Sensor: Check if offline or out of range
            $tdsValue = $sensorData['tds_value'];
            if ($tdsValue == 0 || $tdsValue < 0 || $tdsValue < 500 || $tdsValue > 10000) {
                $sensorsWithWarning++;
            }
            
            // Turbidity Sensor: Check if offline or out of range
            $turbidityValue = $sensorData['turbidity'];
            if ($turbidityValue < 0 || $turbidityValue > 150) {
                $sensorsWithWarning++;
            }
            
            // Ultrasonic Sensor: Check if offline (0 or unrealistic distance)
            $waterLevel = $sensorData['water_level'];
            if ($waterLevel == 0 || $waterLevel < 5 || $waterLevel > 400) {
                $sensorsWithWarning++;
            }
        } else {
            // No data available - all sensors offline
            $sensorsWithWarning = 4;
        }
        
        // Total alerts dalam 24 jam (count Poor/Critical scores from history)
        $alerts24h = $this->firebaseService->countAlertsLast24Hours();
        
        // Average response time (interval antara data uploads)
        $avgResponseTime = $this->firebaseService->getAverageResponseTime();
        
        return response()->json([
            'totalDevices' => $totalDevices,
            'devicesWithWarning' => $sensorsWithWarning,
            'totalAlerts' => $alerts24h,
            'avgResponseTime' => $avgResponseTime,
        ]);
    }
    
    /**
     * API endpoint untuk mendapatkan data history table dengan pagination
     */
    public function getHistoryData(Request $request)
    {
        $startDate = $request->query('startDate', now()->subDays(7));
        $endDate = $request->query('endDate', now());
        $limit = $request->query('limit', 100);
        
        $historyData = $this->firebaseService->getHistoricalData($startDate, $endDate, $limit);
        
        return response()->json([
            'data' => $historyData,
            'total' => count($historyData),
        ]);
    }

    /**
     * API endpoint untuk mendapatkan data grafik riwayat 24 jam
     */
    public function getChartData()
    {
        // Get chart data from Firebase service
        $chartData = $this->firebaseService->getChartData(24);
        
        if (!$chartData) {
            return response()->json([
                'error' => 'No chart data available'
            ], 404);
        }
        
        return response()->json($chartData);
    }

    /**
     * API endpoint untuk mendapatkan data response time 24 jam (per jam)
     */
    /**
     * API endpoint untuk chart alerts frequency 7 days
     */
    public function getAlertsFrequency()
    {
        $data = $this->firebaseService->getAlertsFrequency7Days();
        
        $labels = [];
        $counts = [];
        
        foreach ($data as $item) {
            $labels[] = $item['date'];
            $counts[] = $item['count'];
        }
        
        return response()->json([
            'labels' => $labels,
            'counts' => $counts,
        ]);
    }

    /**
     * API endpoint untuk chart response time 24 hours
     */
    public function getResponseTime24Hours()
    {
        $data = $this->firebaseService->getResponseTime24Hours();
        
        $labels = [];
        $responseTimes = [];
        
        foreach ($data as $item) {
            $labels[] = $item['hour'];
            $responseTimes[] = $item['response_time'];
        }

        return response()->json([
            'labels' => $labels,
            'response_times' => $responseTimes,
        ]);
    }

    /**
     * API endpoint untuk detail status setiap sensor
     */
    public function getSensorStatus()
    {
        $sensorData = $this->firebaseService->getLatestSensorData();
        
        $sensors = [];
        
        if ($sensorData) {
            // 1. pH Sensor
            $phValue = $sensorData['ph_value'];
            $phStatus = 'online';
            $phMessage = 'Normal';
            
            if ($phValue == 0 || $phValue < 0 || $phValue > 14) {
                $phStatus = 'offline';
                $phMessage = 'Sensor tidak terbaca / Error wiring';
            } elseif ($phValue < 6.5 || $phValue > 9.0) {
                $phStatus = 'warning';
                $phMessage = 'Nilai di luar range aman (6.5-9.0)';
            }
            
            $sensors[] = [
                'name' => 'pH Sensor',
                'value' => $phValue,
                'unit' => '',
                'status' => $phStatus,
                'message' => $phMessage
            ];
            
            // 2. TDS Sensor
            $tdsValue = $sensorData['tds_value'];
            $tdsStatus = 'online';
            $tdsMessage = 'Normal';
            
            if ($tdsValue == 0 || $tdsValue < 0) {
                $tdsStatus = 'offline';
                $tdsMessage = 'Sensor tidak terbaca / Error wiring';
            } elseif ($tdsValue < 500 || $tdsValue > 10000) {
                $tdsStatus = 'warning';
                $tdsMessage = 'Nilai ekstrem (normal: 1000-8000 PPM)';
            }
            
            $sensors[] = [
                'name' => 'TDS Sensor',
                'value' => $tdsValue,
                'unit' => 'PPM',
                'status' => $tdsStatus,
                'message' => $tdsMessage
            ];
            
            // 3. Turbidity Sensor
            $turbidityValue = $sensorData['turbidity'];
            $turbidityStatus = 'online';
            $turbidityMessage = 'Normal';
            
            if ($turbidityValue < 0 || $turbidityValue > 150) {
                $turbidityStatus = 'warning';
                $turbidityMessage = 'Nilai tidak realistis (range: 0-150 NTU)';
            } elseif ($turbidityValue < 25 || $turbidityValue > 60) {
                $turbidityStatus = 'warning';
                $turbidityMessage = 'Nilai di luar optimal (25-60 NTU)';
            }
            
            $sensors[] = [
                'name' => 'Turbidity Sensor',
                'value' => $turbidityValue,
                'unit' => 'NTU',
                'status' => $turbidityStatus,
                'message' => $turbidityMessage
            ];
            
            // 4. Ultrasonic Sensor
            $waterLevel = $sensorData['water_level'];
            $ultrasonicStatus = 'online';
            $ultrasonicMessage = 'Normal';
            
            if ($waterLevel == 0 || $waterLevel < 5 || $waterLevel > 400) {
                $ultrasonicStatus = 'offline';
                $ultrasonicMessage = 'Sensor tidak terbaca / Out of range';
            } elseif ($waterLevel < 80 || $waterLevel > 250) {
                $ultrasonicStatus = 'warning';
                $ultrasonicMessage = 'Level air tidak optimal (80-250 cm)';
            }
            
            $sensors[] = [
                'name' => 'Ultrasonic Sensor',
                'value' => $waterLevel,
                'unit' => 'cm',
                'status' => $ultrasonicStatus,
                'message' => $ultrasonicMessage
            ];
            
        } else {
            // No data - all sensors offline
            $sensorNames = ['pH Sensor', 'TDS Sensor', 'Turbidity Sensor', 'Ultrasonic Sensor'];
            foreach ($sensorNames as $name) {
                $sensors[] = [
                    'name' => $name,
                    'value' => 0,
                    'unit' => '',
                    'status' => 'offline',
                    'message' => 'Tidak ada data dari ESP32'
                ];
            }
        }
        
        return response()->json([
            'sensors' => $sensors,
            'total' => 4,
            'online' => count(array_filter($sensors, fn($s) => $s['status'] === 'online')),
            'warning' => count(array_filter($sensors, fn($s) => $s['status'] === 'warning')),
            'offline' => count(array_filter($sensors, fn($s) => $s['status'] === 'offline')),
        ]);
    }
}
