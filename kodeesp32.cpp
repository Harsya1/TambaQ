#include <WiFi.h>
#include <Firebase_ESP_Client.h>
#include "addons/TokenHelper.h"

// --------------------------------------------------------------
// 1. KREDENSIAL WIFI & FIREBASE
// --------------------------------------------------------------
#define WIFI_SSID "AdiliBahlil"
#define WIFI_PASSWORD "hisapkandululee"

// Isi dengan API Key dan Project ID dari langkah sebelumnya
#define API_KEY "AIzaSyAQbCAw5eKmrNKOVsjUrCTYtJ0rSmAhoM8"
#define FIREBASE_PROJECT_ID "cihuyyy-7eb5ca94"

// --------------------------------------------------------------
// 2. DEFINISI PIN SENSOR
// --------------------------------------------------------------
#define PH_PIN 35
#define TDS_PIN 34
#define TURBIDITY_PIN 33  // Pastikan kabel sensor kekeruhan masuk ke Pin 33
#define TRIG_PIN 5        // Pin Trigger Ultrasonic
#define ECHO_PIN 18       // Pin Echo Ultrasonic

// --------------------------------------------------------------
// 3. KALIBRASI SENSOR (SESUAIKAN DENGAN KALIBRASI FISIK)
// --------------------------------------------------------------
// pH Sensor Calibration
#define PH_OFFSET 0.0         // Offset kalibrasi pH (default: 0)
#define PH_VOLTAGE_REF 2.5    // Voltage pada pH 7 (standar)
#define PH_SLOPE 0.18         // Slope sensor pH (mV per unit pH)

// TDS Sensor Calibration
#define TDS_KVALUE 0.5        // K-value sensor TDS (0.5 untuk TDS sensor standar)

// Turbidity Sensor Calibration
#define TURB_VOLTAGE_CLEAR 2.7  // Voltage saat air jernih (kalibrasi)
#define TURB_VOLTAGE_MURKY 1.2  // Voltage saat air keruh (kalibrasi)

// Ultrasonic Distance (untuk konversi ke water level)
#define TANK_HEIGHT_CM 100    // Tinggi total tangki (cm)
#define SENSOR_OFFSET_CM 5    // Jarak sensor ke dasar tangki

// Salinity Conversion
#define SALINITY_K 0.57       // Konstanta konversi TDS → Salinity (sesuai Laravel)

// --------------------------------------------------------------
// 4. VALIDASI RANGE SENSOR
// --------------------------------------------------------------
#define PH_MIN 0.0
#define PH_MAX 14.0
#define TDS_MIN 0.0
#define TDS_MAX 20000.0       // 20000 PPM (20 g/L)
#define TURBIDITY_MIN 0.0
#define TURBIDITY_MAX 1000.0  // 1000 NTU
#define DISTANCE_MIN 2.0      // Minimum distance ultrasonic can measure
#define DISTANCE_MAX 400.0    // Maximum distance ultrasonic can measure

// Objek Firebase
FirebaseData fbdo;
FirebaseAuth auth;
FirebaseConfig config;

unsigned long sendDataPrevMillis = 0;
unsigned long wifiCheckPrevMillis = 0;
int consecutiveFirebaseErrors = 0;
bool signupOK = false;

void setup() {
  Serial.begin(115200);
  delay(1000);

  // Setup Pin Mode
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  // Pin Analog (33, 34, 35) otomatis mode INPUT, tidak perlu declare pinMode

  // Koneksi WiFi
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Menghubungkan ke WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    Serial.print(".");
    delay(500);
  }
  Serial.println("\nWiFi Terhubung!");

  // Konfigurasi Firebase
  config.api_key = API_KEY;
  
  if (Firebase.signUp(&config, &auth, "", "")) {
    Serial.println("Sign-up berhasil");
    signupOK = true;
  } else {
    Serial.printf("Sign-up gagal: %s\n", config.signer.signupError.message.c_str());
  }

  config.token_status_callback = tokenStatusCallback;
  Firebase.begin(&config, &auth);
  Firebase.reconnectWiFi(true);
}

// --------------------------------------------------------------
// FUNGSI HELPER: Validasi Sensor
// --------------------------------------------------------------
bool isValidPH(float value) {
  return (value >= PH_MIN && value <= PH_MAX);
}

bool isValidTDS(float value) {
  return (value >= TDS_MIN && value <= TDS_MAX);
}

bool isValidTurbidity(float value) {
  return (value >= TURBIDITY_MIN && value <= TURBIDITY_MAX);
}

bool isValidDistance(float value) {
  return (value >= DISTANCE_MIN && value <= DISTANCE_MAX);
}

// --------------------------------------------------------------
// FUNGSI HELPER: Membaca Sensor dengan Moving Average (5 samples)
// --------------------------------------------------------------
float readPHSensor() {
  float sum = 0;
  int validSamples = 0;
  
  for(int i = 0; i < 5; i++) {
    int adc = analogRead(PH_PIN);
    float voltage = adc * (3.3 / 4095.0);
    float ph = 7 + ((PH_VOLTAGE_REF - voltage) / PH_SLOPE) + PH_OFFSET;
    
    if(isValidPH(ph)) {
      sum += ph;
      validSamples++;
    }
    delay(10);
  }
  
  if(validSamples > 0) {
    return sum / validSamples;
  }
  return 7.0; // Default neutral pH if all readings invalid
}

float readTDSSensor() {
  float sum = 0;
  int validSamples = 0;
  float temperature = 25.0; // Asumsi suhu 25°C, bisa ditambahkan sensor suhu
  
  for(int i = 0; i < 5; i++) {
    int adc = analogRead(TDS_PIN);
    float voltage = adc * (3.3 / 4095.0);
    
    // Kompensasi suhu
    float compensationCoefficient = 1.0 + 0.02 * (temperature - 25.0);
    float compensationVoltage = voltage / compensationCoefficient;
    
    // TDS calculation (sesuai datasheet TDS sensor)
    float tds = (133.42 * pow(compensationVoltage, 3) 
                - 255.86 * pow(compensationVoltage, 2) 
                + 857.39 * compensationVoltage) * TDS_KVALUE;
    
    if(isValidTDS(tds)) {
      sum += tds;
      validSamples++;
    }
    delay(10);
  }
  
  if(validSamples > 0) {
    return sum / validSamples;
  }
  return 0.0; // Default 0 if all readings invalid
}

float readTurbiditySensor() {
  float sum = 0;
  int validSamples = 0;
  
  for(int i = 0; i < 5; i++) {
    int adc = analogRead(TURBIDITY_PIN);
    float voltage = adc * (3.3 / 4095.0);
    
    // Mapping voltage ke NTU (0-1000)
    // Voltage tinggi = air jernih (NTU rendah)
    // Voltage rendah = air keruh (NTU tinggi)
    float turbidity;
    if(voltage > TURB_VOLTAGE_CLEAR) {
      turbidity = 0; // Air sangat jernih
    } else if(voltage < TURB_VOLTAGE_MURKY) {
      turbidity = 1000; // Air sangat keruh
    } else {
      // Linear interpolation
      turbidity = ((TURB_VOLTAGE_CLEAR - voltage) / (TURB_VOLTAGE_CLEAR - TURB_VOLTAGE_MURKY)) * 1000;
    }
    
    if(isValidTurbidity(turbidity)) {
      sum += turbidity;
      validSamples++;
    }
    delay(10);
  }
  
  if(validSamples > 0) {
    return sum / validSamples;
  }
  return 0.0; // Default 0 if all readings invalid
}

float readUltrasonicSensor() {
  // Ambil 3 sample dan ambil median (untuk filter noise)
  float distances[3];
  
  for(int i = 0; i < 3; i++) {
    digitalWrite(TRIG_PIN, LOW);
    delayMicroseconds(2);
    digitalWrite(TRIG_PIN, HIGH);
    delayMicroseconds(10);
    digitalWrite(TRIG_PIN, LOW);
    
    long duration = pulseIn(ECHO_PIN, HIGH, 30000); // Timeout 30ms
    float distance = duration * 0.034 / 2;
    
    distances[i] = distance;
    delay(50);
  }
  
  // Sort untuk ambil median
  for(int i = 0; i < 2; i++) {
    for(int j = i + 1; j < 3; j++) {
      if(distances[i] > distances[j]) {
        float temp = distances[i];
        distances[i] = distances[j];
        distances[j] = temp;
      }
    }
  }
  
  float medianDistance = distances[1]; // Median value
  
  if(!isValidDistance(medianDistance)) {
    return 0.0; // Invalid reading
  }
  
  return medianDistance;
}

// --------------------------------------------------------------
// FUNGSI HELPER: Konversi TDS ke Salinity (sesuai Laravel)
// --------------------------------------------------------------
float convertTDStoSalinity(float tds) {
  return tds / (SALINITY_K * 1000.0);
}

// --------------------------------------------------------------
// FUNGSI HELPER: Reconnect WiFi
// --------------------------------------------------------------
void checkWiFiConnection() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi terputus! Reconnecting...");
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    
    int retry = 0;
    while (WiFi.status() != WL_CONNECTED && retry < 20) {
      delay(500);
      Serial.print(".");
      retry++;
    }
    
    if(WiFi.status() == WL_CONNECTED) {
      Serial.println("\nWiFi Reconnected!");
    } else {
      Serial.println("\nWiFi Reconnect Failed!");
    }
  }
}

void loop() {
  // Cek WiFi setiap 10 detik
  if(millis() - wifiCheckPrevMillis > 10000) {
    wifiCheckPrevMillis = millis();
    checkWiFiConnection();
  }
  
  // Kirim data setiap 3 detik
  if (Firebase.ready() && signupOK && (millis() - sendDataPrevMillis > 3000 || sendDataPrevMillis == 0)) {
    sendDataPrevMillis = millis();

    // ==========================================
    // BAGIAN 1: BACA SEMUA SENSOR (dengan Moving Average)
    // ==========================================

    Serial.println("\n=== MEMBACA SENSOR ===");
    
    // --- 1. Sensor pH ---
    float pHValue = readPHSensor();

    // --- 2. Sensor TDS ---
    float tdsValue = readTDSSensor();

    // --- 3. Sensor Turbidity (Kekeruhan) ---
    float turbidityValue = readTurbiditySensor();

    // --- 4. Sensor Ultrasonic (Jarak/Level Air) ---
    float ultrasonicValue = readUltrasonicSensor();
    
    // --- 5. Hitung Salinity dari TDS (opsional, Laravel juga hitung) ---
    float salinitasValue = convertTDStoSalinity(tdsValue);

    // Debug di Serial Monitor
    Serial.println("--- HASIL BACA SENSOR ---");
    Serial.printf("pH: %.2f %s\n", pHValue, isValidPH(pHValue) ? "✅" : "⚠️ OUT OF RANGE");
    Serial.printf("TDS: %.2f ppm %s\n", tdsValue, isValidTDS(tdsValue) ? "✅" : "⚠️ OUT OF RANGE");
    Serial.printf("Turbidity: %.2f NTU %s\n", turbidityValue, isValidTurbidity(turbidityValue) ? "✅" : "⚠️ OUT OF RANGE");
    Serial.printf("Ultrasonic: %.2f cm %s\n", ultrasonicValue, isValidDistance(ultrasonicValue) ? "✅" : "⚠️ OUT OF RANGE");
    Serial.printf("Salinity: %.2f PPT\n", salinitasValue);

    // ==========================================
    // BAGIAN 2: VALIDASI DATA SEBELUM KIRIM
    // ==========================================
    
    bool dataValid = true;
    String errorMsg = "";
    
    if(!isValidPH(pHValue)) {
      dataValid = false;
      errorMsg += "pH out of range; ";
    }
    if(!isValidTDS(tdsValue)) {
      dataValid = false;
      errorMsg += "TDS out of range; ";
    }
    if(!isValidTurbidity(turbidityValue)) {
      dataValid = false;
      errorMsg += "Turbidity out of range; ";
    }
    if(!isValidDistance(ultrasonicValue)) {
      dataValid = false;
      errorMsg += "Ultrasonic out of range; ";
    }
    
    if(!dataValid) {
      Serial.println("⚠️ DATA TIDAK VALID: " + errorMsg);
      Serial.println("Data tidak dikirim ke Firestore.");
      Serial.println("-------------------------");
      return; // Skip sending invalid data
    }

    // ==========================================
    // BAGIAN 3: PACKING JSON & KIRIM
    // ==========================================
    
    FirebaseJson content;

    // Field sesuai struktur Firestore
    content.set("fields/pHValue/doubleValue", pHValue);
    content.set("fields/TDSValue/doubleValue", tdsValue);
    content.set("fields/turbidityValue/doubleValue", turbidityValue);
    content.set("fields/ultrasonicValue/doubleValue", ultrasonicValue);
    
    // OPTIONAL: Kirim salinitasValue juga (meski Laravel akan recalculate)
    // Ini berguna untuk backup dan debugging
    content.set("fields/salinitasValue/doubleValue", salinitasValue);

    // Path: Collection "sensorRead", Document "dataSensor"
    String documentPath = "sensorRead/dataSensor"; 

    Serial.print("Mengirim ke Firestore... ");
    
    // Gunakan patchDocument agar hanya mengupdate field yang dikirim
    if (Firebase.Firestore.patchDocument(&fbdo, FIREBASE_PROJECT_ID, "", documentPath.c_str(), content.raw(), "")) {
        Serial.println("BERHASIL! ✅");
        consecutiveFirebaseErrors = 0; // Reset error counter
    } else {
        Serial.println("GAGAL ❌");
        Serial.println("Error: " + fbdo.errorReason());
        consecutiveFirebaseErrors++;
        
        // Jika terlalu banyak error berturut-turut, restart ESP32
        if(consecutiveFirebaseErrors > 10) {
          Serial.println("⚠️ Terlalu banyak error! Restarting ESP32...");
          delay(1000);
          ESP.restart();
        }
    }
    Serial.println("-------------------------");
  }
}