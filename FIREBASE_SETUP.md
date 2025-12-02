# Firebase Firestore Setup Guide

## 1. Download Service Account Key dari Firebase Console

1. Buka [Firebase Console](https://console.firebase.google.com/)
2. Pilih project: **cihuyyy-7eb5ca94**
3. Klik **Settings (⚙️)** > **Project Settings**
4. Tab **Service accounts**
5. Klik **Generate new private key**
6. Save file JSON yang didownload ke folder `storage/firebase/` dengan nama `firebase-credentials.json`

## 2. Update .env File

Buka file `.env` dan update nilai berikut dari service account JSON:

```env
FIREBASE_PROJECT_ID=cihuyyy-7eb5ca94
FIREBASE_PRIVATE_KEY_ID=<dari file JSON: private_key_id>
FIREBASE_PRIVATE_KEY="<dari file JSON: private_key>"
FIREBASE_CLIENT_EMAIL=<dari file JSON: client_email>
FIREBASE_CLIENT_ID=<dari file JSON: client_id>
FIREBASE_CLIENT_CERT_URL=<dari file JSON: client_x509_cert_url>
```

**PENTING:** 
- FIREBASE_PRIVATE_KEY harus dibungkus dengan double quotes
- Replace `\n` di private key dengan newline literal jika perlu

## 3. Struktur Firestore Database

### Collection: `sensorRead`
- **Document ID**: `dataSensor`
- **Fields**:
  - `TDSValue` (number) - TDS dalam PPM
  - `pHValue` (number) - pH air
  - `turbidityValue` (number) - Kekeruhan dalam NTU
  - `ultrasonicValue` (number) - Jarak permukaan air dalam cm

### Collection: `FuzzyAction` (Auto-generated)
Hasil fuzzy logic akan disimpan di sini dengan fields:
- `ph_value`
- `tds_value`
- `turbidity`
- `water_level`
- `salinity_ppt` (converted from TDS)
- `water_quality_score`
- `water_quality_status`
- `recommendation`
- `fuzzy_details`
- `aerator_status`
- `category`
- `timestamp`

## 4. Conversion Formula

**TDS (PPM) to Salinity (PPT):**
```
Salinity (PPT) = TDS (PPM) / (K × 1000)
Where K = 0.57 (for brackish water)
```

**Example:**
- TDS = 2850 PPM → Salinity = 5.00 PPT
- TDS = 3000 PPM → Salinity = 5.26 PPT

## 5. Testing

Test koneksi dengan:
```bash
php artisan tinker
```

```php
$firebase = app(\App\Services\FirebaseService::class);
$data = $firebase->getLatestSensorData();
dd($data);
```

## 6. Data Flow

1. ESP32/Arduino → **Firestore** (`sensorRead/dataSensor`)
2. **Laravel** reads from Firestore via Firebase Admin SDK
3. Laravel converts **TDS (PPM) → Salinity (PPT)**
4. Laravel runs **Fuzzy Mamdani Logic**
5. Laravel saves result to Firestore (`FuzzyAction` collection)
6. **Dashboard** displays real-time data

## 7. Firestore Rules

Pastikan Firestore rules allow read/write untuk service account:

```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    // Allow service account full access
    match /{document=**} {
      allow read, write: if true;
    }
  }
}
```

**Note:** Untuk production, gunakan rules yang lebih strict!
