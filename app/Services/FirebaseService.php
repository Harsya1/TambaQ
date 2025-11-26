<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
    protected $projectId;
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        try {
            $this->projectId = config('firebase.credentials.project_id');
            $this->apiKey = config('firebase.api_key');
            $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
            
        } catch (\Exception $e) {
            Log::error('Firebase initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get latest sensor reading from Firestore via REST API
     * Collection: sensorRead > Document: dataSensor
     * Fields: TDSValue, pHValue, turbidityValue, ultrasonicValue
     */
    public function getLatestSensorData()
    {
        try {
            // Build REST API URL
            $url = "{$this->baseUrl}/sensorRead/dataSensor";
            
            // Make GET request
            $response = Http::get($url);
            
            if (!$response->successful()) {
                Log::warning('Sensor data document not found or request failed: ' . $response->status());
                return null;
            }
            
            $data = $response->json();
            
            // Check if document exists
            if (!isset($data['fields'])) {
                Log::warning('No fields found in sensor data document');
                return null;
            }
            
            $fields = $data['fields'];
            
            // Extract values from Firestore format
            $tdsValue = $this->extractValue($fields, 'TDSValue');
            $phValue = $this->extractValue($fields, 'pHValue');
            $turbidityValue = $this->extractValue($fields, 'turbidityValue');
            $ultrasonicValue = $this->extractValue($fields, 'ultrasonicValue');
            
            // Convert field names to match Laravel convention
            return [
                'ph_value' => $phValue,
                'tds_value' => $tdsValue, // PPM
                'turbidity' => $turbidityValue, // NTU
                'water_level' => $ultrasonicValue, // cm
                'salinity_ppt' => $this->convertTDStoSalinity($tdsValue), // Convert to PPT
                'salinity' => $this->convertTDStoSalinity($tdsValue), // Alias for compatibility
                'timestamp' => $data['updateTime'] ?? now()->toDateTimeString(),
            ];
            
        } catch (\Exception $e) {
            Log::error('Error fetching sensor data from Firestore: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract value from Firestore field format
     * Firestore stores values as: {"fieldName": {"doubleValue": 123.45}}
     */
    private function extractValue($fields, $fieldName)
    {
        if (!isset($fields[$fieldName])) {
            return 0;
        }
        
        $field = $fields[$fieldName];
        
        // Try different value types
        if (isset($field['doubleValue'])) {
            return (float) $field['doubleValue'];
        } elseif (isset($field['integerValue'])) {
            return (int) $field['integerValue'];
        } elseif (isset($field['stringValue'])) {
            return $field['stringValue'];
        }
        
        return 0;
    }

    /**
     * Convert TDS (PPM) to Salinity (PPT)
     * Using simplified PSS-78 formula with K=0.57
     */
    private function convertTDStoSalinity($tdsValue, $temperature = 25)
    {
        $K = 0.57;
        $salinity_ppt = $tdsValue / ($K * 1000);
        return round($salinity_ppt, 2);
    }

    /**
     * Save fuzzy decision result to Firestore via REST API
     * Collection: FuzzyAction
     */
    public function saveFuzzyDecision($sensorData, $fuzzyResult)
    {
        try {
            $url = "{$this->baseUrl}/FuzzyAction";
            
            // Convert data to Firestore format
            $document = [
                'fields' => [
                    'ph_value' => ['doubleValue' => $sensorData['ph_value']],
                    'tds_value' => ['doubleValue' => $sensorData['tds_value']],
                    'turbidity' => ['doubleValue' => $sensorData['turbidity']],
                    'water_level' => ['doubleValue' => $sensorData['water_level']],
                    'salinity_ppt' => ['doubleValue' => $sensorData['salinity_ppt']],
                    'water_quality_score' => ['doubleValue' => $fuzzyResult['water_quality_score']],
                    'water_quality_status' => ['stringValue' => $fuzzyResult['water_quality_status']],
                    'recommendation' => ['stringValue' => $fuzzyResult['recommendation']],
                    'fuzzy_details' => ['stringValue' => $fuzzyResult['fuzzy_details']],
                    'aerator_status' => ['stringValue' => $fuzzyResult['aerator_status']],
                    'category' => ['stringValue' => $fuzzyResult['category']],
                    'timestamp' => ['stringValue' => now()->toDateTimeString()],
                ]
            ];
            
            // POST request to create new document
            $response = Http::post($url, $document);
            
            if ($response->successful()) {
                $docId = $response->json()['name'] ?? 'unknown';
                Log::info('Fuzzy decision saved to Firestore: ' . $docId);
                return $docId;
            } else {
                Log::error('Failed to save fuzzy decision: ' . $response->status());
                return null;
            }
            
        } catch (\Exception $e) {
            Log::error('Error saving fuzzy decision to Firestore: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recent sensor readings for charts (last 24 hours simulation)
     */
    public function getChartData($hours = 24)
    {
        try {
            // Since we only have one document that updates, we'll simulate chart data
            // In production, you might want to store historical data in a sub-collection
            $currentData = $this->getLatestSensorData();
            
            if (!$currentData) {
                return null;
            }
            
            // Generate labels for last N hours
            $labels = [];
            $phData = [];
            $tdsData = [];
            $turbidityData = [];
            $salinityData = [];
            $waterLevelData = [];
            
            for ($i = $hours - 1; $i >= 0; $i--) {
                $time = now()->subHours($i);
                $labels[] = $time->format('H:i');
                
                // Add slight random variation for simulation
                // In production, fetch actual historical data
                $phData[] = $currentData['ph_value'] + (rand(-10, 10) / 100);
                $tdsData[] = $currentData['tds_value'] + rand(-50, 50);
                $turbidityData[] = max(0, $currentData['turbidity'] + rand(-5, 5));
                $salinityData[] = $currentData['salinity_ppt'] + (rand(-5, 5) / 100);
                $waterLevelData[] = $currentData['water_level'] + (rand(-2, 2) / 10);
            }
            
            return [
                'labels' => $labels,
                'phData' => $phData,
                'tdsData' => $tdsData,
                'turbidityData' => $turbidityData,
                'salinityData' => $salinityData,
                'waterLevelData' => $waterLevelData,
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting chart data: ' . $e->getMessage());
            return null;
        }
    }
}
