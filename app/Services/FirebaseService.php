<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

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
        // Try to get cached data first
        $cacheKey = 'latest_sensor_data';
        
        try {
            // Build REST API URL with API key for authentication
            $url = "{$this->baseUrl}/sensorRead/dataSensor?key={$this->apiKey}";
            
            // Make GET request with timeout
            $response = Http::timeout(10)->get($url);
            
            if (!$response->successful()) {
                $statusCode = $response->status();
                $errorBody = $response->body();
                
                // Log different error types
                if ($statusCode === 429) {
                    Log::warning('Firebase quota exceeded - using cached data');
                    // Return cached data if available
                    if (Cache::has($cacheKey)) {
                        Log::info('Returning cached sensor data');
                        return Cache::get($cacheKey);
                    }
                } elseif ($statusCode === 404) {
                    Log::warning('Sensor data document not found');
                } else {
                    Log::warning("Sensor data request failed: $statusCode - $errorBody");
                }
                
                // If quota exceeded, try to get from sensorHistory instead
                if ($statusCode === 429) {
                    Log::info('Attempting to fetch from sensorHistory collection');
                    return $this->getLatestFromHistory();
                }
                
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
            $result = [
                'ph_value' => $phValue,
                'tds_value' => $tdsValue, // PPM
                'turbidity' => $turbidityValue, // NTU
                'water_level' => $ultrasonicValue, // cm
                'timestamp' => $data['updateTime'] ?? now()->toDateTimeString(),
            ];
            
            // Cache the result for 30 minutes (reduced Firebase reads)
            Cache::put($cacheKey, $result, now()->addMinutes(30));
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error fetching sensor data from Firestore: ' . $e->getMessage());
            
            // Try to return cached data on exception
            if (Cache::has($cacheKey)) {
                Log::info('Returning cached data due to exception');
                return Cache::get($cacheKey);
            }
            
            // Try to get from history as last resort
            return $this->getLatestFromHistory();
        }
    }
    
    /**
     * Get latest sensor data from sensorHistory collection (fallback when quota exceeded)
     */
    private function getLatestFromHistory()
    {
        try {
            // Query sensorHistory collection, order by timestamp descending, limit 1
            $url = "{$this->baseUrl}/sensorHistory?orderBy=timestamp desc&pageSize=1";
            
            $response = Http::timeout(5)->get($url);
            
            if (!$response->successful()) {
                Log::warning('Failed to fetch from sensorHistory: ' . $response->status());
                return null;
            }
            
            $data = $response->json();
            
            if (!isset($data['documents']) || count($data['documents']) === 0) {
                Log::warning('No documents found in sensorHistory');
                return null;
            }
            
            $doc = $data['documents'][0];
            $fields = $doc['fields'] ?? [];
            
            // Extract values
            $result = [
                'ph_value' => $this->extractValue($fields, 'ph_value') ?? $this->extractValue($fields, 'pHValue'),
                'tds_value' => $this->extractValue($fields, 'tds_value') ?? $this->extractValue($fields, 'TDSValue'),
                'turbidity' => $this->extractValue($fields, 'turbidity') ?? $this->extractValue($fields, 'turbidityValue'),
                'water_level' => $this->extractValue($fields, 'water_level') ?? $this->extractValue($fields, 'ultrasonicValue'),
                'timestamp' => $this->extractValue($fields, 'timestamp') ?? ($doc['updateTime'] ?? now()->toDateTimeString()),
            ];
            
            Log::info('Successfully fetched data from sensorHistory (fallback)');
            
            // Cache this data for 30 minutes
            Cache::put('latest_sensor_data', $result, now()->addMinutes(30));
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error fetching from sensorHistory: ' . $e->getMessage());
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
     * OPTIMIZED: Reduced default limit to prevent quota exhaustion
     */
    public function getHistoricalData($startDate, $endDate, $orderBy = 'timestamp', $limit = 168)
    {
        // Create cache key based on parameters
        $cacheKey = "historical_data_{$startDate}_{$endDate}_{$orderBy}_{$limit}";
        
        try {
            // Convert string dates to Carbon if needed
            if (is_string($startDate)) {
                $startDate = \Carbon\Carbon::parse($startDate);
            }
            if (is_string($endDate)) {
                $endDate = \Carbon\Carbon::parse($endDate);
            }
            
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
                            'direction' => 'DESCENDING'
                        ]
                    ],
                    'limit' => $limit
                ]
            ];
            
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:runQuery";
            $response = Http::timeout(15)->post($url, $query);
            
            if (!$response->successful()) {
                $statusCode = $response->status();
                
                if ($statusCode === 429) {
                    Log::warning('Firebase quota exceeded for historical data - checking cache');
                    // Return cached data if available
                    if (Cache::has($cacheKey)) {
                        Log::info('Returning cached historical data');
                        return Cache::get($cacheKey);
                    }
                }
                
                Log::error('Failed to fetch historical data: ' . $statusCode);
                
                // Return cached data as fallback
                if (Cache::has($cacheKey)) {
                    return Cache::get($cacheKey);
                }
                
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
                        'water_quality_score' => $this->extractValue($fields, 'water_quality_score'),
                        'category' => $this->extractValue($fields, 'category'),
                        'recommendation' => $this->extractValue($fields, 'recommendation'),
                    ];
                }
            }
            
            // Cache the result for 60 minutes (1 hour) to reduce Firebase reads
            if (!empty($data)) {
                Cache::put($cacheKey, $data, now()->addMinutes(60));
            }
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error('Error fetching historical data: ' . $e->getMessage());
            
            // Return cached data if available
            if (Cache::has($cacheKey)) {
                Log::info('Returning cached historical data due to exception');
                return Cache::get($cacheKey);
            }
            
            return [];
        }
    }
    
    /**
     * Get aggregated daily data (1 record per day) for charts
     * Much more efficient than fetching all raw data
     */
    public function getAggregatedDailyData($startDate, $endDate)
    {
        $cacheKey = "daily_aggregated_{$startDate}_{$endDate}";
        
        // Check cache first (cached for 2 hours)
        if (Cache::has($cacheKey)) {
            Log::info('Returning cached daily aggregated data');
            return Cache::get($cacheKey);
        }
        
        try {
            // Get raw data with limit
            $rawData = $this->getHistoricalData($startDate, $endDate, 'timestamp', 500);
            
            if (empty($rawData)) {
                return [];
            }
            
            // Group by date and aggregate
            $aggregated = collect($rawData)
                ->groupBy(function($item) {
                    return Carbon::parse($item['timestamp'])->format('Y-m-d');
                })
                ->map(function($group) {
                    return [
                        'date' => $group->first()['timestamp'],
                        'ph_avg' => round($group->avg('ph_value'), 2),
                        'tds_avg' => round($group->avg('tds_value'), 2),
                        'turbidity_avg' => round($group->avg('turbidity'), 2),
                        'water_level_avg' => round($group->avg('water_level'), 2),
                        'score_avg' => round($group->avg('water_quality_score'), 2),
                        'score_min' => round($group->min('water_quality_score'), 2),
                        'score_max' => round($group->max('water_quality_score'), 2),
                        'count' => $group->count(),
                    ];
                })
                ->values()
                ->toArray();
            
            // Cache for 2 hours
            Cache::put($cacheKey, $aggregated, now()->addHours(2));
            
            return $aggregated;
            
        } catch (\Exception $e) {
            Log::error('Error getting aggregated daily data: ' . $e->getMessage());
            return [];
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
            $waterLevelData = [];
            
            for ($i = $hours - 1; $i >= 0; $i--) {
                $time = now()->subHours($i);
                $labels[] = $time->format('H:i');
                
                // Add slight random variation for simulation
                // In production, fetch actual historical data
                $phData[] = $currentData['ph_value'] + (rand(-10, 10) / 100);
                $tdsData[] = $currentData['tds_value'] + rand(-50, 50);
                $turbidityData[] = max(0, $currentData['turbidity'] + rand(-5, 5));
                $waterLevelData[] = $currentData['water_level'] + (rand(-2, 2) / 10);
            }
            
            return [
                'labels' => $labels,
                'phData' => $phData,
                'tdsData' => $tdsData,
                'turbidityData' => $turbidityData,
                'waterLevelData' => $waterLevelData,
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting chart data: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Count alerts (Poor/Critical) in last 24 hours from sensorHistory
     */
    public function countAlertsLast24Hours()
    {
        try {
            $startDate = now()->subHours(24);
            $endDate = now();
            
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
                                ],
                                [
                                    'fieldFilter' => [
                                        'field' => ['fieldPath' => 'FuzzyValue'],
                                        'op' => 'LESS_THAN',
                                        'value' => ['doubleValue' => 45] // Poor/Critical threshold
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'limit' => 1000
                ]
            ];
            
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:runQuery";
            $response = Http::post($url, $query);
            
            if (!$response->successful()) {
                return 0;
            }
            
            $results = $response->json();
            $count = 0;
            
            foreach ($results as $result) {
                if (isset($result['document'])) {
                    $count++;
                }
            }
            
            return $count;
            
        } catch (\Exception $e) {
            Log::error('Error counting alerts: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calculate average response time (interval between data uploads)
     */
    public function getAverageResponseTime()
    {
        try {
            // Get last 10 entries to calculate average interval
            $startDate = now()->subHours(1);
            $endDate = now();
            
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
                        ['field' => ['fieldPath' => 'timestamp'], 'direction' => 'DESCENDING']
                    ],
                    'limit' => 10
                ]
            ];
            
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:runQuery";
            $response = Http::post($url, $query);
            
            if (!$response->successful()) {
                return '10.0s'; // Default ESP32 interval
            }
            
            $results = $response->json();
            $timestamps = [];
            
            foreach ($results as $result) {
                if (isset($result['document']['fields']['timestamp'])) {
                    $timestampField = $result['document']['fields']['timestamp'];
                    // Extract timestamp value directly
                    if (isset($timestampField['timestampValue'])) {
                        $timestamps[] = $timestampField['timestampValue'];
                    }
                }
            }
            
            if (count($timestamps) < 2) {
                return '10.0s'; // Default
            }
            
            // Calculate average interval
            $intervals = [];
            for ($i = 0; $i < count($timestamps) - 1; $i++) {
                $time1 = strtotime($timestamps[$i]);
                $time2 = strtotime($timestamps[$i + 1]);
                $intervals[] = abs($time1 - $time2);
            }
            
            $avgInterval = array_sum($intervals) / count($intervals);
            
            return number_format($avgInterval, 1) . 's';
            
        } catch (\Exception $e) {
            Log::error('Error calculating response time: ' . $e->getMessage());
            return '10.0s';
        }
    }

    /**
     * Get alerts frequency for last 7 days (untuk chart)
     * Returns: array of alerts count per day
     */
    public function getAlertsFrequency7Days()
    {
        try {
            $data = [];
            
            // Loop 7 hari terakhir
            for ($i = 6; $i >= 0; $i--) {
                $startDate = now()->subDays($i)->startOfDay();
                $endDate = now()->subDays($i)->endOfDay();
                
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
                                    ],
                                    [
                                        'fieldFilter' => [
                                            'field' => ['fieldPath' => 'FuzzyValue'],
                                            'op' => 'LESS_THAN',
                                            'value' => ['doubleValue' => 45] // Critical + Poor (score < 45)
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:runQuery";
                $response = Http::post($url, $query);
                
                $count = 0;
                if ($response->successful()) {
                    $results = $response->json();
                    foreach ($results as $result) {
                        if (isset($result['document'])) {
                            $count++;
                        }
                    }
                }
                
                $data[] = [
                    'date' => $startDate->format('D'), // Mon, Tue, Wed, etc
                    'count' => $count
                ];
            }
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error('Error getting alerts frequency: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get average response time per hour for last 24 hours (untuk chart)
     * Returns: array of avg response time per hour
     */
    public function getResponseTime24Hours()
    {
        try {
            $data = [];
            
            // Loop 24 jam terakhir
            for ($i = 23; $i >= 0; $i--) {
                $startHour = now()->subHours($i)->startOfHour();
                $endHour = now()->subHours($i)->endOfHour();
                
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
                                            'value' => ['timestampValue' => $startHour->toIso8601String()]
                                        ]
                                    ],
                                    [
                                        'fieldFilter' => [
                                            'field' => ['fieldPath' => 'timestamp'],
                                            'op' => 'LESS_THAN_OR_EQUAL',
                                            'value' => ['timestampValue' => $endHour->toIso8601String()]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'orderBy' => [
                            ['field' => ['fieldPath' => 'timestamp'], 'direction' => 'ASCENDING']
                        ]
                    ]
                ];
                
                $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:runQuery";
                $response = Http::post($url, $query);
                
                $avgResponseTime = 10000; // Default 10 detik = 10000 ms
                
                if ($response->successful()) {
                    $results = $response->json();
                    $timestamps = [];
                    
                    foreach ($results as $result) {
                        if (isset($result['document']['fields']['timestamp']['timestampValue'])) {
                            $timestamps[] = $result['document']['fields']['timestamp']['timestampValue'];
                        }
                    }
                    
                    // Hitung rata-rata interval jika ada minimal 2 data
                    if (count($timestamps) >= 2) {
                        $intervals = [];
                        for ($j = 0; $j < count($timestamps) - 1; $j++) {
                            $time1 = strtotime($timestamps[$j]);
                            $time2 = strtotime($timestamps[$j + 1]);
                            $intervals[] = abs($time2 - $time1) * 1000; // Convert ke milliseconds
                        }
                        $avgResponseTime = array_sum($intervals) / count($intervals);
                    }
                }
                
                $data[] = [
                    'hour' => $startHour->format('H:00'),
                    'response_time' => round($avgResponseTime, 0) // dalam milliseconds
                ];
            }
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error('Error getting response time 24h: ' . $e->getMessage());
            return [];
        }
    }
}
