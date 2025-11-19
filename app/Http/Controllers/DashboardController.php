<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SensorReading;
use App\Models\Actuator;
use App\Models\FuzzyDecision;
use App\Services\FuzzyMamdaniService;

class DashboardController extends Controller
{
    protected $fuzzyService;

    public function __construct(FuzzyMamdaniService $fuzzyService)
    {
        $this->fuzzyService = $fuzzyService;
    }

    public function index()
    {
        // Ambil data sensor terbaru
        $latestSensor = SensorReading::latest()->first();
        
        // Jika ada data sensor, evaluasi dengan fuzzy logic
        if ($latestSensor) {
            $this->processFuzzyLogic($latestSensor);
        }
        
        // Ambil data aktuator (Aerator)
        $aerator = Actuator::where('name', 'Aerator')->first();
        
        // Ambil keputusan fuzzy terbaru
        $latestFuzzyDecision = FuzzyDecision::with('sensorReading')
            ->latest()
            ->first();
        
        return view('dashboard', [
            'userName' => Auth::user()->name,
            'sensorData' => $latestSensor,
            'aerator' => $aerator,
            'fuzzyDecision' => $latestFuzzyDecision,
        ]);
    }

    /**
     * Proses fuzzy logic dan update aerator
     */
    private function processFuzzyLogic($sensorReading)
    {
        // Evaluasi kualitas air dengan Fuzzy Mamdani
        $fuzzyResult = $this->fuzzyService->evaluateWaterQuality(
            $sensorReading->ph_value,
            $sensorReading->tds_value,
            $sensorReading->turbidity
        );

        // Update salinity_ppt di sensor reading
        $sensorReading->update([
            'salinity_ppt' => $fuzzyResult['salinity_ppt']
        ]);

        // Update atau create fuzzy decision
        FuzzyDecision::updateOrCreate(
            ['sensor_reading_id' => $sensorReading->id],
            [
                'water_quality_status' => $fuzzyResult['water_quality_status'],
                'recommendation' => $fuzzyResult['recommendation'],
                'fuzzy_details' => $fuzzyResult['fuzzy_details'],
            ]
        );

        // Update status aerator berdasarkan hasil fuzzy
        $aerator = Actuator::where('name', 'Aerator')->first();
        if ($aerator) {
            $aerator->update(['status' => $fuzzyResult['aerator_status']]);
        }

        return $fuzzyResult;
    }

    /**
     * API endpoint untuk mendapatkan data sensor terbaru
     */
    public function getLatestSensorData()
    {
        // Simulasi data yang berubah-ubah dengan mengambil data random dari database
        $allSensors = SensorReading::all();
        $randomSensor = $allSensors->random();
        
        // Proses fuzzy logic untuk sensor random
        $fuzzyResult = $this->processFuzzyLogic($randomSensor);
        
        // Ambil data aktuator (sudah terupdate dari processFuzzyLogic)
        $aerator = Actuator::where('name', 'Aerator')->first();
        
        // Ambil keputusan fuzzy yang baru saja dibuat
        $latestFuzzyDecision = FuzzyDecision::where('sensor_reading_id', $randomSensor->id)->first();
        
        return response()->json([
            'sensor' => $randomSensor,
            'aerator' => $aerator,
            'fuzzyDecision' => $latestFuzzyDecision,
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
        
        // Devices with warning (contoh: cek sensor readings terakhir)
        $latestSensor = SensorReading::latest()->first();
        $devicesWithWarning = 0;
        
        if ($latestSensor) {
            if ($latestSensor->ph_value < 6.5 || $latestSensor->ph_value > 8.5) $devicesWithWarning++;
            if ($latestSensor->turbidity > 50) $devicesWithWarning++;
        }
        
        // Total alerts dalam 24 jam
        $totalAlerts = FuzzyDecision::where('created_at', '>=', now()->subDay())
            ->where('water_quality_status', 'POOR')
            ->count();
        
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
        // Ambil data sensor dalam 24 jam terakhir
        $sensorReadings = SensorReading::where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'asc')
            ->get();
        
        return response()->json([
            'labels' => $sensorReadings->map(function($reading) {
                return $reading->created_at->format('H:i');
            }),
            'phData' => $sensorReadings->pluck('ph_value'),
            'waterLevelData' => $sensorReadings->pluck('water_level'),
            'tdsData' => $sensorReadings->pluck('tds_value'),
            'salinityData' => $sensorReadings->pluck('salinity'),
            'turbidityData' => $sensorReadings->pluck('turbidity'),
        ]);
    }

    /**
     * API endpoint untuk mendapatkan data response time 24 jam (per jam)
     */
    public function getResponseTime24Hours()
    {
        $twentyFourHoursAgo = now()->subHours(24);
        
        // Query dari water_quality_scores untuk mendapatkan avg response time per jam
        $data = \App\Models\WaterQualityScore::where('recorded_at', '>=', $twentyFourHoursAgo)
            ->orderBy('recorded_at')
            ->get()
            ->groupBy(function($item) {
                return \Carbon\Carbon::parse($item->recorded_at)->format('H:00');
            })
            ->map(function($group) {
                return round($group->avg('response_time'), 2);
            });

        // Generate 24 jam labels (00:00 sampai 23:00)
        $labels = [];
        $responseTimes = [];
        
        for ($i = 0; $i < 24; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
            $labels[] = $hour;
            
            // Jika ada data untuk jam ini, gunakan, jika tidak generate random
            if (isset($data[$hour])) {
                $responseTimes[] = $data[$hour];
            } else {
                // Generate dummy data (50-200 ms)
                $responseTimes[] = rand(50, 200) + (rand(0, 99) / 100);
            }
        }

        return response()->json([
            'labels' => $labels,
            'response_times' => $responseTimes,
        ]);
    }
}
