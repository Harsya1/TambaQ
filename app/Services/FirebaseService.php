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
            
            // Check if salinity already exists in Firestore, if not calculate and update
            $salinityPpt = $this->extractValue($fields, 'salinitasValue');
            
            if ($salinityPpt === null || $salinityPpt === 0) {
                // Convert TDS to Salinity
                $salinityPpt = $this->convertTDStoSalinity($tdsValue);
                
                // Update Firestore with calculated salinity
                $this->updateSensorDataWithSalinity($salinityPpt);
            }
            
            // Convert field names to match Laravel convention
            return [
                'ph_value' => $phValue,
                'tds_value' => $tdsValue, // PPM
                'turbidity' => $turbidityValue, // NTU
                'water_level' => $ultrasonicValue, // cm
                'salinity_ppt' => $salinityPpt, // PPT (from Firestore or calculated)
                'salinity' => $salinityPpt, // Alias for compatibility
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
            return null;
        }
        
        $field = $fields[$fieldName];
        
        // Try different value types
        if (isset($field['doubleValue'])) {
            return (float) $field['doubleValue'];
        } elseif (isset($field['integerValue'])) {
            return (int) $field['integerValue'];
        } elseif (isset($field['stringValue'])) {
            return $field['stringValue'];
        } elseif (isset($field['timestampValue'])) {
            return $field['timestampValue'];
        }
        
        return null;
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
     * Collection: FuzzyAction > Document: FuzzyData
     * Fields: FuzzyValue, timestamp, category, recommendation
     */
    public function saveFuzzyDecision($sensorData, $fuzzyResult)
    {
        try {
            // Update specific document FuzzyData in FuzzyAction collection
            $url = "{$this->baseUrl}/FuzzyAction/FuzzyData";
            
            // Convert data to Firestore format
            $document = [
                'fields' => [
                    'FuzzyValue' => ['doubleValue' => $fuzzyResult['water_quality_score']],
                    'timestamp' => ['timestampValue' => now()->toIso8601String()],
                    'category' => ['stringValue' => $fuzzyResult['category']],
                    'recommendation' => ['stringValue' => $fuzzyResult['recommendation']],
                ]
            ];
            
            // PATCH request to update existing document
            $updateMask = 'updateMask.fieldPaths=FuzzyValue&updateMask.fieldPaths=timestamp&updateMask.fieldPaths=category&updateMask.fieldPaths=recommendation';
            $response = Http::patch("{$url}?{$updateMask}", $document);
            
            if ($response->successful()) {
                Log::info('Fuzzy decision saved to Firestore', [
                    'FuzzyValue' => $fuzzyResult['water_quality_score'],
                    'category' => $fuzzyResult['category']
                ]);
                
                // Also save to historical data
                $this->saveToHistory($sensorData, $fuzzyResult);
                
                return 'FuzzyData';
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
     * Save historical data to Firestore
     * Collection: sensorHistory
     * Document ID: Auto-generated timestamp-based
     */
    public function saveToHistory($sensorData, $fuzzyResult)
    {
        try {
            $url = "{$this->baseUrl}/sensorHistory";
            
            // Create complete historical record
            $document = [
                'fields' => [
                    'timestamp' => ['timestampValue' => now()->toIso8601String()],
                    'ph_value' => ['doubleValue' => $sensorData['ph_value']],
                    'tds_value' => ['doubleValue' => $sensorData['tds_value']],
                    'turbidity' => ['doubleValue' => $sensorData['turbidity']],
                    'water_level' => ['doubleValue' => $sensorData['water_level']],
                    'salinity_ppt' => ['doubleValue' => $sensorData['salinity_ppt']],
                    'water_quality_score' => ['doubleValue' => $fuzzyResult['water_quality_score']],
                    'category' => ['stringValue' => $fuzzyResult['category']],
                    'recommendation' => ['stringValue' => $fuzzyResult['recommendation']],
                    'fuzzy_details' => ['stringValue' => $fuzzyResult['fuzzy_details']],
                ]
            ];
            
            // POST to create new document with auto-generated ID
            $response = Http::post($url, $document);
            
            if ($response->successful()) {
                Log::info('Historical data saved to Firestore');
                return true;
            } else {
                Log::error('Failed to save historical data: ' . $response->status());
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('Error saving historical data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get historical data from Firestore with date range
     * Collection: sensorHistory
     */
    public function getHistoricalData($startDate, $endDate, $orderBy = 'timestamp', $limit = 1000)
    {
        try {
            // Build structured query
            $query = [
                'structuredQuery' => [
                    'from' => [['collectionId' => 'sensorHistory']],
                    'where' => [
                        'compositeFilter' => [
                            'op' => 'AND',
                            'filters' => [
                                [
                                    'fieldFilter' => [
                                        'field' => ['fieldPath' => 'timestamp'],
                                        'op' => 'GREATER_THAN_OR_EQUAL',
                                        'value' => ['timestampValue' => $startDate->toIso8601String()]
                                    ]
                                ],
                                [
                                    'fieldFilter' => [
                                        'field' => ['fieldPath' => 'timestamp'],
                                        'op' => 'LESS_THAN_OR_EQUAL',
                                        'value' => ['timestampValue' => $endDate->toIso8601String()]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'orderBy' => [
                        [
                            'field' => ['fieldPath' => $orderBy],
                            'direction' => 'ASCENDING'
                        ]
                    ],
                    'limit' => $limit
                ]
            ];
            
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:runQuery";
            $response = Http::post($url, $query);
            
            if (!$response->successful()) {
                Log::error('Failed to fetch historical data: ' . $response->status());
                return [];
            }
            
            $results = $response->json();
            $data = [];
            
            foreach ($results as $result) {
                if (isset($result['document']['fields'])) {
                    $fields = $result['document']['fields'];
                    $data[] = [
                        'timestamp' => $this->extractValue($fields, 'timestamp'),
                        'ph_value' => $this->extractValue($fields, 'ph_value'),
                        'tds_value' => $this->extractValue($fields, 'tds_value'),
                        'turbidity' => $this->extractValue($fields, 'turbidity'),
                        'water_level' => $this->extractValue($fields, 'water_level'),
                        'salinity_ppt' => $this->extractValue($fields, 'salinity_ppt'),
                        'water_quality_score' => $this->extractValue($fields, 'water_quality_score'),
                        'category' => $this->extractValue($fields, 'category'),
                        'recommendation' => $this->extractValue($fields, 'recommendation'),
                    ];
                }
            }
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error('Error fetching historical data: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update sensor data with calculated salinity value
     * Collection: sensorRead > Document: dataSensor
     * Adds: salinitasValue field with converted PPM to PPT
     */
    public function updateSensorDataWithSalinity($salinityPpt)
    {
        try {
            $url = "{$this->baseUrl}/sensorRead/dataSensor";
            
            // Prepare Firestore format for salinity field
            $document = [
                'fields' => [
                    'salinitasValue' => ['doubleValue' => $salinityPpt],
                ]
            ];
            
            // PATCH request to update only salinitasValue field
            $response = Http::patch("{$url}?updateMask.fieldPaths=salinitasValue", $document);
            
            if ($response->successful()) {
                Log::info('Salinity value updated in dataSensor: ' . $salinityPpt . ' PPT');
                return true;
            } else {
                Log::error('Failed to update salinity value: ' . $response->status());
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('Error updating salinity in Firestore: ' . $e->getMessage());
            return false;
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
