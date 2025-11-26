<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FuzzyMamdaniService;
use App\Services\FirebaseService;

class DashboardController extends Controller
{
    protected $fuzzyService;
    protected $firebaseService;

    public function __construct(FuzzyMamdaniService $fuzzyService, FirebaseService $firebaseService)
    {
        $this->fuzzyService = $fuzzyService;
        $this->firebaseService = $firebaseService;
    }

    public function index()
    {
        // Ambil data sensor terbaru dari Firestore
        $sensorData = $this->firebaseService->getLatestSensorData();
        
        $fuzzyDecision = null;
        
        // Jika ada data sensor, evaluasi dengan fuzzy logic
        if ($sensorData) {
            $fuzzyDecision = $this->processFuzzyLogic($sensorData);
        }
        
        return view('dashboard', [
            'userName' => Auth::user()->name,
            'sensorData' => (object) $sensorData, // Convert to object for blade compatibility
            'fuzzyDecision' => (object) $fuzzyDecision, // Convert to object
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
     * API endpoint untuk mendapatkan data sensor terbaru
     */
    public function getLatestSensorData()
    {
        // Ambil data real-time dari Firestore
        $sensorData = $this->firebaseService->getLatestSensorData();
        
        if (!$sensorData) {
            return response()->json([
                'error' => 'No sensor data available'
            ], 404);
        }
        
        // Proses fuzzy logic
        $fuzzyDecision = $this->processFuzzyLogic($sensorData);
        
        return response()->json([
            'sensor' => (object) array_merge($sensorData, [
                'water_quality_score' => $fuzzyDecision['water_quality_score'] ?? 0
            ]),
            'fuzzyDecision' => (object) $fuzzyDecision,
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
        // Total devices (5 sensors)
        $totalDevices = 5;
        
        // Devices with warning (check current sensor values)
        $sensorData = $this->firebaseService->getLatestSensorData();
        $devicesWithWarning = 0;
        
        if ($sensorData) {
            if ($sensorData['ph_value'] < 6.5 || $sensorData['ph_value'] > 8.5) $devicesWithWarning++;
            if ($sensorData['turbidity'] > 50) $devicesWithWarning++;
            if ($sensorData['tds_value'] > 10000) $devicesWithWarning++;
        }
        
        // Total alerts dalam 24 jam (simulated for now)
        $totalAlerts = rand(0, 5);
        
        // Average response time (simulasi - dalam detik)
        $avgResponseTime = '1.2s';
        
        return response()->json([
            'totalDevices' => $totalDevices,
            'devicesWithWarning' => $devicesWithWarning,
            'totalAlerts' => $totalAlerts,
            'avgResponseTime' => $avgResponseTime,
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
    public function getResponseTime24Hours()
    {
        // Generate 24 jam labels (00:00 sampai 23:00)
        $labels = [];
        $responseTimes = [];
        
        for ($i = 0; $i < 24; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
            $labels[] = $hour;
            
            // Generate simulated response time data (50-200 ms)
            $responseTimes[] = rand(50, 200) + (rand(0, 99) / 100);
        }

        return response()->json([
            'labels' => $labels,
            'response_times' => $responseTimes,
        ]);
    }
}
