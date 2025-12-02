<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class AnalyticsController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Get 7 days trend data
     */
    public function getTrend7Days()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $now = Carbon::now();
        
        // Get historical data from Firestore
        $historyData = $this->firebaseService->getHistoricalData($sevenDaysAgo, $now);
        
        if (empty($historyData)) {
            return response()->json($this->generateDummyTrendData(7));
        }
        
        // Group by date and calculate averages
        $groupedData = collect($historyData)
            ->groupBy(function($item) {
                return Carbon::parse($item['timestamp'])->format('Y-m-d');
            })
            ->sortKeys()  // Sort by date ascending (oldest to newest)
            ->map(function($group) {
                return [
                    'score' => round(collect($group)->avg('water_quality_score'), 2),
                    'ph_avg' => round(collect($group)->avg('ph_value'), 2),
                    'tds_avg' => round(collect($group)->avg('tds_value'), 2),
                    'turbidity_avg' => round(collect($group)->avg('turbidity'), 2),
                ];
            });
        
        $labels = $groupedData->keys()->map(function($key) {
            return Carbon::parse($key)->format('d M');
        })->values()->toArray();

        return response()->json([
            'labels' => $labels,
            'scores' => $groupedData->pluck('score')->values()->toArray(),
            'ph_data' => $groupedData->pluck('ph_avg')->values()->toArray(),
            'tds_data' => $groupedData->pluck('tds_avg')->values()->toArray(),
            'turbidity_data' => $groupedData->pluck('turbidity_avg')->values()->toArray(),
        ]);
    }

    /**
     * Get 30 days trend data
     */
    public function getTrend30Days()
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $now = Carbon::now();
        
        // Get historical data from Firestore
        $historyData = $this->firebaseService->getHistoricalData($thirtyDaysAgo, $now);
        
        if (empty($historyData)) {
            return response()->json($this->generateDummyTrendData(30));
        }
        
        // Group by date and calculate averages
        $groupedData = collect($historyData)
            ->groupBy(function($item) {
                return Carbon::parse($item['timestamp'])->format('Y-m-d');
            })
            ->sortKeys()  // Sort by date ascending (oldest to newest)
            ->map(function($group) {
                return [
                    'score' => round(collect($group)->avg('water_quality_score'), 2),
                    'ph_avg' => round(collect($group)->avg('ph_value'), 2),
                    'tds_avg' => round(collect($group)->avg('tds_value'), 2),
                    'turbidity_avg' => round(collect($group)->avg('turbidity'), 2),
                ];
            });
        
        $labels = $groupedData->keys()->map(function($key) {
            return Carbon::parse($key)->format('d M');
        })->values()->toArray();

        return response()->json([
            'labels' => $labels,
            'scores' => $groupedData->pluck('score')->values()->toArray(),
            'ph_data' => $groupedData->pluck('ph_avg')->values()->toArray(),
            'tds_data' => $groupedData->pluck('tds_avg')->values()->toArray(),
            'turbidity_data' => $groupedData->pluck('turbidity_avg')->values()->toArray(),
        ]);
    }

    /**
     * Calculate correlation between sensors (24 hours)
     */
    public function getCorrelation()
    {
        $twentyFourHoursAgo = Carbon::now()->subHours(24);
        $now = Carbon::now();
        
        // Get historical data from Firestore
        $historyData = $this->firebaseService->getHistoricalData($twentyFourHoursAgo, $now);

        // Generate dummy data if not enough data
        if (count($historyData) < 10) {
            return response()->json([
                'pH_TDS' => 0.45,
                'TDS_Turbidity' => 0.71,
                'pH_Turbidity' => -0.12,
                'Salinity_TDS' => 0.90,
                'pH_Salinity' => 0.35,
                'Turbidity_Salinity' => 0.58,
            ]);
        }

        $data = collect($historyData);
        $pH = $data->pluck('ph_value')->toArray();
        $tds = $data->pluck('tds_value')->toArray();
        $turbidity = $data->pluck('turbidity')->toArray();
        $salinity = $data->pluck('salinity_ppt')->toArray();

        return response()->json([
            'pH_TDS' => round($this->calculatePearsonCorrelation($pH, $tds), 2),
            'TDS_Turbidity' => round($this->calculatePearsonCorrelation($tds, $turbidity), 2),
            'pH_Turbidity' => round($this->calculatePearsonCorrelation($pH, $turbidity), 2),
            'Salinity_TDS' => round($this->calculatePearsonCorrelation($salinity, $tds), 2),
            'pH_Salinity' => round($this->calculatePearsonCorrelation($pH, $salinity), 2),
            'Turbidity_Salinity' => round($this->calculatePearsonCorrelation($turbidity, $salinity), 2),
        ]);
    }

    /**
     * Forecast next 3-6 hours using Simple Moving Average
     */
    public function getForecast()
    {
        $sixHoursAgo = Carbon::now()->subHours(6);
        $now = Carbon::now();
        
        // Get historical data from Firestore
        $historyData = $this->firebaseService->getHistoricalData($sixHoursAgo, $now, 'timestamp', 12);

        if (count($historyData) < 3) {
            // Dummy forecast
            return response()->json([
                'pH_next3h' => 7.12,
                'TDS_next3h' => 270,
                'turbidity_next3h' => 11.4,
                'salinity_next3h' => 15.2,
                'confidence' => 'medium'
            ]);
        }

        // Calculate Simple Moving Average
        $data = collect($historyData);
        $phForecast = $data->avg('ph_value');
        $tdsForecast = $data->avg('tds_value');
        $turbidityForecast = $data->avg('turbidity');
        $salinityForecast = $data->avg('salinity_ppt');

        // Calculate confidence based on standard deviation
        $confidence = $this->calculateConfidence($data);

        return response()->json([
            'pH_next3h' => round($phForecast, 2),
            'TDS_next3h' => round($tdsForecast, 2),
            'turbidity_next3h' => round($turbidityForecast, 2),
            'salinity_next3h' => round($salinityForecast, 2),
            'confidence' => $confidence
        ]);
    }

    /**
     * Export data as CSV
     */
    public function exportCsv(Request $request)
    {
        $start = Carbon::parse($request->query('start', Carbon::now()->subDays(7)));
        $end = Carbon::parse($request->query('end', Carbon::now()));

        // Get historical data from Firestore
        $historyData = $this->firebaseService->getHistoricalData($start, $end);

        $filename = 'water_quality_' . Carbon::now()->format('YmdHis') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($historyData) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, ['Timestamp', 'Score', 'pH', 'TDS (ppm)', 'Turbidity (NTU)', 'Salinity (ppt)', 'Water Level (cm)', 'Category']);
            
            // Data rows
            foreach ($historyData as $row) {
                fputcsv($file, [
                    $row['timestamp'],
                    $row['water_quality_score'],
                    $row['ph_value'],
                    $row['tds_value'],
                    $row['turbidity'],
                    $row['salinity_ppt'],
                    $row['water_level'],
                    $row['category'],
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export data as PDF (Hybrid: Real-time + Historical)
     */
    public function exportPdf(Request $request)
    {
        $start = Carbon::parse($request->query('start', Carbon::now()->subDays(7)));
        $end = Carbon::parse($request->query('end', Carbon::now()));

        // ========== PAGE 1: REAL-TIME DATA ==========
        // Get current sensor and fuzzy data (latest from historical data)
        $latestData = $this->firebaseService->getHistoricalData(Carbon::now()->subMinutes(5), Carbon::now(), 'timestamp', 1);
        $realtimeData = !empty($latestData) ? $latestData[0] : [];
        
        // ========== PAGE 2+: HISTORICAL DATA ==========
        // Get historical data from Firestore
        $historyData = $this->firebaseService->getHistoricalData($start, $end);
        $historicalCollection = collect($historyData);

        // Calculate summary statistics from historical data
        $summary = [
            'avg_score' => round($historicalCollection->avg('water_quality_score') ?? 0, 2),
            'avg_ph' => round($historicalCollection->avg('ph_value') ?? 0, 2),
            'avg_tds' => round($historicalCollection->avg('tds_value') ?? 0, 2),
            'avg_turbidity' => round($historicalCollection->avg('turbidity') ?? 0, 2),
            'avg_salinity' => round($historicalCollection->avg('salinity_ppt') ?? 0, 2),
            'min_ph' => round($historicalCollection->min('ph_value') ?? 0, 2),
            'max_ph' => round($historicalCollection->max('ph_value') ?? 0, 2),
            'min_tds' => round($historicalCollection->min('tds_value') ?? 0, 2),
            'max_tds' => round($historicalCollection->max('tds_value') ?? 0, 2),
            'min_turbidity' => round($historicalCollection->min('turbidity') ?? 0, 2),
            'max_turbidity' => round($historicalCollection->max('turbidity') ?? 0, 2),
            'total_records' => $historicalCollection->count(),
        ];

        // Generate PDF using DomPDF
        $pdf = Pdf::loadView('reports.water-quality-pdf', [
            'realtimeData' => $realtimeData,
            'historicalData' => $historicalCollection,
            'summary' => $summary,
            'start' => $start,
            'end' => $end,
            'generatedAt' => Carbon::now(),
        ]);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'landscape');

        // Set filename
        $filename = 'water_quality_report_' . Carbon::now()->format('YmdHis') . '.pdf';
        
        // Download PDF
        return $pdf->download($filename);
    }

    /**
     * Private helper: Calculate Pearson Correlation
     */
    private function calculatePearsonCorrelation($x, $y)
    {
        $n = count($x);
        
        if ($n === 0 || $n !== count($y)) {
            return 0;
        }

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += pow($x[$i], 2);
            $sumY2 += pow($y[$i], 2);
        }

        $numerator = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = sqrt((($n * $sumX2) - pow($sumX, 2)) * (($n * $sumY2) - pow($sumY, 2)));

        if ($denominator == 0) {
            return 0;
        }

        return $numerator / $denominator;
    }

    /**
     * Private helper: Calculate confidence level
     */
    private function calculateConfidence($data)
    {
        if ($data->count() < 5) {
            return 'low';
        }

        $phStdDev = $this->standardDeviation($data->pluck('ph_value')->toArray());
        $tdsStdDev = $this->standardDeviation($data->pluck('tds_value')->toArray());

        // Low variance = high confidence
        if ($phStdDev < 0.3 && $tdsStdDev < 50) {
            return 'high';
        } elseif ($phStdDev < 0.6 && $tdsStdDev < 100) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Private helper: Calculate standard deviation
     */
    private function standardDeviation($array)
    {
        $n = count($array);
        if ($n === 0) return 0;

        $mean = array_sum($array) / $n;
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $array)) / $n;

        return sqrt($variance);
    }

    /**
     * Private helper: Generate dummy trend data
     */
    private function generateDummyTrendData($days)
    {
        $data = collect();
        $now = Carbon::now();

        for ($i = 0; $i < $days; $i++) {
            $date = $now->copy()->subDays($days - $i - 1);
            
            // Daily data for both 7 and 30 days
            $key = $date->format('Y-m-d');
            $data[$key] = [
                'score' => rand(70, 95) + (rand(0, 99) / 100),
                'ph_avg' => 7.0 + (rand(-20, 50) / 100),
                'tds_avg' => 300 + rand(-50, 100),
                'turbidity_avg' => 10 + rand(0, 50) / 10,
            ];
        }

        return $data;
    }
}
