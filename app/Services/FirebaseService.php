<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Firestore;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $firestore;
    protected $database;

    public function __construct()
    {
        try {
            // Initialize Firebase with service account
            $serviceAccount = $this->buildServiceAccountFromEnv();
            
            $factory = (new Factory)
                ->withServiceAccount($serviceAccount);
            
            // Get Firestore instance
            $this->firestore = $factory->createFirestore();
            $this->database = $this->firestore->database();
            
        } catch (\Exception $e) {
            Log::error('Firebase initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build service account array from environment variables
     */
    private function buildServiceAccountFromEnv()
    {
        return [
            'type' => config('firebase.credentials.type'),
            'project_id' => config('firebase.credentials.project_id'),
            'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
            'private_key' => str_replace('\\n', "\n", env('FIREBASE_PRIVATE_KEY')),
            'client_email' => env('FIREBASE_CLIENT_EMAIL'),
            'client_id' => env('FIREBASE_CLIENT_ID'),
            'auth_uri' => config('firebase.credentials.auth_uri'),
            'token_uri' => config('firebase.credentials.token_uri'),
            'auth_provider_x509_cert_url' => config('firebase.credentials.auth_provider_x509_cert_url'),
            'client_x509_cert_url' => env('FIREBASE_CLIENT_CERT_URL'),
        ];
    }

    /**
     * Get latest sensor reading from Firestore
     * Collection: sensorRead > Document: dataSensor
     * Fields: TDSValue, pHValue, turbidityValue, ultrasonicValue
     */
    public function getLatestSensorData()
    {
        try {
            // Get document reference
            $docRef = $this->database
                ->collection('sensorRead')
                ->document('dataSensor');
            
            // Get document snapshot
            $snapshot = $docRef->snapshot();
            
            if (!$snapshot->exists()) {
                Log::warning('Sensor data document not found');
                return null;
            }
            
            $data = $snapshot->data();
            
            // Convert field names to match Laravel convention
            return [
                'ph_value' => $data['pHValue'] ?? 0,
                'tds_value' => $data['TDSValue'] ?? 0, // PPM
                'turbidity' => $data['turbidityValue'] ?? 0, // NTU
                'water_level' => $data['ultrasonicValue'] ?? 0, // cm
                'salinity_ppt' => $this->convertTDStoSalinity($data['TDSValue'] ?? 0), // Convert to PPT
                'timestamp' => $snapshot->updateTime() ? $snapshot->updateTime()->formatAsString() : now()->toDateTimeString(),
            ];
            
        } catch (\Exception $e) {
            Log::error('Error fetching sensor data from Firestore: ' . $e->getMessage());
            return null;
        }
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
     * Save fuzzy decision result to Firestore
     * Collection: FuzzyAction
     */
    public function saveFuzzyDecision($sensorData, $fuzzyResult)
    {
        try {
            $collection = $this->database->collection('FuzzyAction');
            
            $document = [
                'ph_value' => $sensorData['ph_value'],
                'tds_value' => $sensorData['tds_value'],
                'turbidity' => $sensorData['turbidity'],
                'water_level' => $sensorData['water_level'],
                'salinity_ppt' => $sensorData['salinity_ppt'],
                'water_quality_score' => $fuzzyResult['water_quality_score'],
                'water_quality_status' => $fuzzyResult['water_quality_status'],
                'recommendation' => $fuzzyResult['recommendation'],
                'fuzzy_details' => $fuzzyResult['fuzzy_details'],
                'aerator_status' => $fuzzyResult['aerator_status'],
                'category' => $fuzzyResult['category'],
                'timestamp' => now()->toDateTimeString(),
            ];
            
            // Add new document with auto-generated ID
            $addedDocRef = $collection->add($document);
            
            Log::info('Fuzzy decision saved to Firestore: ' . $addedDocRef->id());
            
            return $addedDocRef->id();
            
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

    /**
     * Get Firestore database instance
     */
    public function getDatabase()
    {
        return $this->database;
    }
}
