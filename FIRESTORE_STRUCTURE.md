# Firestore Database Structure

## Overview
TambaQ menggunakan **hybrid database architecture**:
- **MySQL**: User authentication, accounts, sessions
- **Firestore**: Real-time sensor data, fuzzy logic results, historical data

## Collections & Documents
    
### 1. `sensorRead` Collection
**Purpose**: Real-time sensor data from ESP32

#### Document: `dataSensor`
```javascript
{
  "pHValue": 7.2,              // pH sensor (0-14)
  "TDSValue": 350,             // TDS sensor in PPM
  "turbidityValue": 12.5,      // Turbidity in NTU
  "ultrasonicValue": 85.3,     // Water level in cm
  "salinitasValue": 0.61       // Calculated salinity in PPT (auto-updated by Laravel)
}
```

**Notes**:
- ESP32 writes: `pHValue`, `TDSValue`, `turbidityValue`, `ultrasonicValue`
- Laravel calculates and updates: `salinitasValue` (TDS PPM → Salinity PPT)
- Conversion formula: `Salinity (PPT) = TDS / (K × 1000)` where `K = 0.57`

---

### 2. `FuzzyAction` Collection
**Purpose**: Latest fuzzy logic evaluation results

#### Document: `FuzzyData`
```javascript
{
  "FuzzyValue": 85.5,          // Water quality score (0-100)
  "timestamp": "2025-11-26T10:30:00Z",
  "category": "Good",          // Excellent/Good/Fair/Poor
  "recommendation": "Maintain current conditions"
}
```

**Notes**:
- Updated every time fuzzy logic runs
- Single document (overwrite on each evaluation)
- Used for real-time dashboard display

---

### 3. `sensorHistory` Collection
**Purpose**: Historical data for analytics and trends

#### Documents: Auto-generated IDs (timestamp-based)
```javascript
{
  "timestamp": "2025-11-26T10:30:00Z",
  "ph_value": 7.2,
  "tds_value": 350,
  "turbidity": 12.5,
  "water_level": 85.3,
  "salinity_ppt": 0.61,
  "water_quality_score": 85.5,
  "category": "Good",
  "recommendation": "Maintain current conditions",
  "fuzzy_details": "Applied 27 fuzzy rules..."
}
```

**Notes**:
- New document created on every fuzzy evaluation
- Used for analytics: trends, correlation, forecasting
- Queried with date ranges for reports
- Retention policy: Configure as needed (e.g., keep 90 days)

---

## Data Flow

### Write Flow (ESP32 → Firestore → Laravel)
```
1. ESP32 → Firestore: /sensorRead/dataSensor
   - Writes: pHValue, TDSValue, turbidityValue, ultrasonicValue

2. Laravel reads → Calculates salinity → Updates Firestore
   - Reads: dataSensor
   - Calculates: TDS PPM → Salinity PPT
   - Writes back: salinitasValue

3. Laravel runs Fuzzy Logic
   - Input: All sensor values
   - Output: water_quality_score, category, recommendation

4. Laravel writes results to Firestore
   - Updates: /FuzzyAction/FuzzyData (latest)
   - Creates: /sensorHistory/{auto-id} (history)
```

### Read Flow (Dashboard)
```
Dashboard → Laravel → Firestore
  ├─ Real-time data: /sensorRead/dataSensor
  ├─ Latest fuzzy: /FuzzyAction/FuzzyData
  └─ Historical: /sensorHistory (filtered by date)
```

---

## Laravel Integration

### FirebaseService Methods

#### Reading Data
- `getLatestSensorData()`: Get current sensor readings from `sensorRead/dataSensor`
- `getHistoricalData($startDate, $endDate)`: Query historical data with date range

#### Writing Data
- `updateSensorDataWithSalinity($salinity)`: Update `salinitasValue` in `dataSensor`
- `saveFuzzyDecision($sensorData, $fuzzyResult)`: Save to `FuzzyAction/FuzzyData`
- `saveToHistory($sensorData, $fuzzyResult)`: Create new history document

### Controllers Using Firestore

#### DashboardController
- ✅ `index()`: Display dashboard with latest data
- ✅ `getLatestSensorData()`: API endpoint for real-time updates
- ✅ `getHistoryStats()`: Calculate warning statistics

#### AnalyticsController
- ✅ `getTrend7Days()`: 7-day trend analysis
- ✅ `getTrend30Days()`: 30-day trend analysis
- ✅ `getCorrelation()`: Parameter correlation (24h)
- ✅ `getForecast()`: Forecast next 3-6 hours
- ✅ `exportCsv()`: Export historical data as CSV
- ✅ `exportPdf()`: Export with summary statistics

---

## Firestore REST API

### Base URL
```
https://firestore.googleapis.com/v1/projects/cihuyyy-7eb5ca94/databases/(default)/documents
```

### Authentication
- Uses Firebase credentials from `.env`
- Public read/write rules for development
- **TODO**: Implement security rules for production

### Query Examples

#### Get Latest Sensor Data
```http
GET /sensorRead/dataSensor
```

#### Update Salinity Value
```http
PATCH /sensorRead/dataSensor?updateMask.fieldPaths=salinitasValue
Content-Type: application/json

{
  "fields": {
    "salinitasValue": {"doubleValue": 0.61}
  }
}
```

#### Create History Record
```http
POST /sensorHistory
Content-Type: application/json

{
  "fields": {
    "timestamp": {"timestampValue": "2025-11-26T10:30:00Z"},
    "ph_value": {"doubleValue": 7.2},
    "water_quality_score": {"doubleValue": 85.5}
    // ... other fields
  }
}
```

#### Query Historical Data (Structured Query)
```http
POST /documents:runQuery
Content-Type: application/json

{
  "structuredQuery": {
    "from": [{"collectionId": "sensorHistory"}],
    "where": {
      "compositeFilter": {
        "op": "AND",
        "filters": [
          {
            "fieldFilter": {
              "field": {"fieldPath": "timestamp"},
              "op": "GREATER_THAN_OR_EQUAL",
              "value": {"timestampValue": "2025-11-19T00:00:00Z"}
            }
          },
          {
            "fieldFilter": {
              "field": {"fieldPath": "timestamp"},
              "op": "LESS_THAN_OR_EQUAL",
              "value": {"timestampValue": "2025-11-26T23:59:59Z"}
            }
          }
        ]
      }
    },
    "orderBy": [{"field": {"fieldPath": "timestamp"}, "direction": "ASCENDING"}],
    "limit": 1000
  }
}
```

---

## Security Considerations

### Current Setup (Development)
- Public read/write access
- No authentication required for REST API
- Suitable for development/testing only

### Production Recommendations
1. Implement Firestore Security Rules
2. Use Firebase Authentication for API access
3. Set up data retention policies
4. Enable audit logging
5. Configure backup schedule

### Example Security Rules
```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    // Only ESP32 can write sensor data
    match /sensorRead/{document} {
      allow read: if true;
      allow write: if request.auth != null && request.auth.token.device == 'esp32';
    }
    
    // Only Laravel backend can write fuzzy results
    match /FuzzyAction/{document} {
      allow read: if true;
      allow write: if request.auth != null && request.auth.token.backend == true;
    }
    
    // History is append-only
    match /sensorHistory/{document} {
      allow read: if true;
      allow create: if request.auth != null;
      allow update, delete: if false;
    }
  }
}
```

---

## Migration Notes

### From MySQL to Firestore
- **Before**: `sensor_readings`, `fuzzy_decisions`, `water_quality_scores` tables
- **After**: `sensorRead`, `FuzzyAction`, `sensorHistory` collections
- **Unchanged**: `users`, `sessions` tables remain in MySQL

### Breaking Changes
- AnalyticsController now uses `FirebaseService` instead of `WaterQualityScore` model
- Historical queries require date range parameters
- Timestamps are ISO8601 strings instead of MySQL datetime

### Migration Command
Not required - clean start with Firestore. Old MySQL data can be archived.

---

## Testing

### Test Firebase Connection
```bash
php artisan firebase:test
```

### Manual API Tests
```bash
# Get latest sensor data
curl http://localhost:8000/api/sensor/latest

# Get 7-day trend
curl http://localhost:8000/api/trend/7days

# Get correlation
curl http://localhost:8000/api/correlation

# Get forecast
curl http://localhost:8000/api/forecast
```

---

## Monitoring & Maintenance

### Firestore Console
https://console.firebase.google.com/project/cihuyyy-7eb5ca94/firestore

### Key Metrics to Monitor
- Document read/write counts
- Query performance
- Storage size (especially `sensorHistory`)
- API error rates

### Cleanup Tasks
- Archive old history documents (>90 days)
- Monitor storage quota
- Review security rules
- Check API usage limits

---

## Future Enhancements

### Planned Features
1. Real-time listeners for live updates
2. Cloud Functions for automated processing
3. Firebase Analytics integration
4. Push notifications via FCM
5. Offline data sync for ESP32
6. Data aggregation for long-term trends

### Performance Optimization
1. Composite indexes for complex queries
2. Caching layer (Redis) for frequently accessed data
3. Pagination for large result sets
4. Query optimization based on access patterns

---

## Support & Documentation

- Firebase Firestore Docs: https://firebase.google.com/docs/firestore
- REST API Reference: https://firebase.google.com/docs/firestore/use-rest-api
- Laravel Integration: `app/Services/FirebaseService.php`
- Test Command: `app/Console/Commands/TestFirebaseConnection.php`

---

**Last Updated**: November 26, 2025  
**Project**: TambaQ - Water Quality Monitoring System  
**Database Version**: Firestore (Firebase)
