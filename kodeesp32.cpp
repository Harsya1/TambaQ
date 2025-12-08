#include <WiFi.h>
#include <Firebase_ESP_Client.h>
#include "addons/TokenHelper.h"
#include "time.h"

// --------------------------------------------------------------
// WIFI & FIREBASE
// --------------------------------------------------------------
#define WIFI_SSID "Ruang 101"
#define WIFI_PASSWORD "@polije.tif"

#define API_KEY "AIzaSyAQbCAw5eKmrNKOVsjUrCTYtJ0rSmAhoM8"
#define FIREBASE_PROJECT_ID "cihuyyy-7eb5ca94"

#define USER_EMAIL "esp32@example.com"
#define USER_PASSWORD "password123"

// --------------------------------------------------------------
// PIN SENSOR
// --------------------------------------------------------------
#define PH_PIN 35
#define TDS_PIN 34
#define TURBIDITY_PIN 33
#define TRIG_PIN 5
#define ECHO_PIN 18

FirebaseData fbdo;
FirebaseAuth auth;
FirebaseConfig config;

unsigned long previousMillis = 0;
const long uploadInterval = 10000;

bool firebaseReady = false;

// --------------------------------------------------------------
// NTP UNTUK SSL - VERSI LEBIH AGRESIF
// --------------------------------------------------------------
void initNTP() {
  Serial.println("\n=== SINKRONISASI WAKTU NTP ===");
  
  // Coba beberapa NTP server berbeda
  const char* ntpServers[][3] = {
    {"pool.ntp.org", "time.nist.gov", "time.google.com"},
    {"id.pool.ntp.org", "asia.pool.ntp.org", "0.asia.pool.ntp.org"},
    {"time.windows.com", "time.cloudflare.com", "ntp.ubuntu.com"}
  };
  
  bool ntpSuccess = false;
  
  for (int serverSet = 0; serverSet < 3 && !ntpSuccess; serverSet++) {
    Serial.printf("\nMencoba NTP Server Set %d:\n", serverSet + 1);
    Serial.printf("  - %s\n", ntpServers[serverSet][0]);
    Serial.printf("  - %s\n", ntpServers[serverSet][1]);
    Serial.printf("  - %s\n", ntpServers[serverSet][2]);
    
    // Configure NTP dengan 3 server
    configTime(7 * 3600, 0, 
               ntpServers[serverSet][0], 
               ntpServers[serverSet][1], 
               ntpServers[serverSet][2]);
    
    Serial.print("Menunggu sinkronisasi");
    
    // Retry lebih lama (30 detik)
    for (int i = 0; i < 60; i++) {
      time_t now = time(nullptr);
      if (now > 100000) {
        ntpSuccess = true;
        Serial.println(" ✓ BERHASIL!");
        Serial.print("Waktu saat ini: ");
        Serial.println(ctime(&now));
        break;
      }
      Serial.print(".");
      delay(500);
    }
    
    if (ntpSuccess) break;
    
    Serial.println(" ✗ Gagal, coba server lain...");
    delay(1000);
  }
  
  if (!ntpSuccess) {
    Serial.println("\n✗✗✗ NTP GAGAL TOTAL! ✗✗✗");
    Serial.println("Kemungkinan masalah:");
    Serial.println("1. Firewall memblokir port 123 (NTP)");
    Serial.println("2. Koneksi internet tidak stabil");
    Serial.println("3. Router tidak mengizinkan NTP");
    Serial.println("\nESP32 akan restart dalam 10 detik...");
    delay(10000);
    ESP.restart();
  }
  
  Serial.println("=================================\n");
}

// --------------------------------------------------------------
void setup() {
  Serial.begin(115200);
  delay(2000);
  
  Serial.println("\n\n=== ESP32 FIRESTORE SENSOR ===\n");

  // Pin ultrasonic
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);

  // WiFi dengan retry lebih agresif
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Menghubungkan ke WiFi");
  
  int wifiRetry = 0;
  while (WiFi.status() != WL_CONNECTED && wifiRetry < 40) {
    Serial.print(".");
    delay(500);
    wifiRetry++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✓ WiFi Terhubung!");
    Serial.print("SSID: ");
    Serial.println(WiFi.SSID());
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());
    Serial.print("Gateway: ");
    Serial.println(WiFi.gatewayIP());
    Serial.print("DNS: ");
    Serial.println(WiFi.dnsIP());
    Serial.print("Signal: ");
    Serial.print(WiFi.RSSI());
    Serial.println(" dBm");
  } else {
    Serial.println("\n✗ WiFi GAGAL! Restart ESP32...");
    delay(5000);
    ESP.restart();
  }

  // Tampilkan info koneksi
  Serial.println("\n=== INFO KONEKSI ===");
  Serial.println("WiFi terhubung, siap untuk NTP sync");

  // WAJIB: NTP untuk SSL
  initNTP();

  // Firebase Config dengan timeout lebih besar
  config.api_key = API_KEY;
  auth.user.email = USER_EMAIL;
  auth.user.password = USER_PASSWORD;
  config.token_status_callback = tokenStatusCallback;
  
  // Timeout lebih besar untuk koneksi lambat
  config.timeout.serverResponse = 15 * 1000;
  config.timeout.socketConnection = 15 * 1000;
  config.timeout.sslHandshake = 30 * 1000; // SSL butuh waktu lama
  config.timeout.rtdbKeepAlive = 45 * 1000;
  config.timeout.rtdbStreamReconnect = 1 * 1000;
  config.timeout.rtdbStreamError = 3 * 1000;

  Firebase.reconnectWiFi(true);
  
  // Buffer lebih besar untuk SSL
  fbdo.setBSSLBufferSize(8192, 2048); // 8KB RX, 2KB TX
  fbdo.setResponseSize(4096);

  Serial.println("\n=== FIREBASE AUTHENTICATION ===");
  Serial.println("Memulai Sign In...");
  Serial.println("(Proses ini bisa memakan waktu 30-60 detik)");
  
  // Begin Firebase dulu (penting!)
  Firebase.begin(&config, &auth);
  
  // Sign in dengan retry
  int authRetry = 0;
  bool authSuccess = false;
  
  while (authRetry < 3 && !authSuccess) {
    Serial.printf("\nPercobaan %d/3...\n", authRetry + 1);
    
    // Coba sign in (bukan sign up)
    // Kosongkan parameter ke-3 dan ke-4 untuk existing user
    if (Firebase.signUp(&config, &auth, "", "")) {
      Serial.println("✓ Authentication BERHASIL!");
      firebaseReady = true;
      authSuccess = true;
    } else {
      Serial.print("✗ Authentication GAGAL: ");
      Serial.println(config.signer.signupError.message.c_str());
      authRetry++;
      
      if (authRetry < 3) {
        Serial.println("Retry dalam 5 detik...");
        delay(5000);
      }
    }
  }
  
  if (!authSuccess) {
    Serial.println("\n✗✗✗ FIREBASE AUTH GAGAL TOTAL! ✗✗✗");
    Serial.println("\nPastikan di Firebase Console:");
    Serial.println("1. Authentication > Sign-in method > Email/Password = ENABLED");
    Serial.println("2. Authentication > Users > User sudah ada:");
    Serial.println("   Email: esp32@example.com");
    Serial.println("   Password: password123");
    Serial.println("3. Project Settings > Web API Key sudah benar");
    Serial.println("4. Firestore Database sudah dibuat");
    Serial.println("\nESP32 akan tetap berjalan, coba lagi di loop...");
    firebaseReady = false;
  }
  
  Serial.println("\n=== SETUP SELESAI ===\n");
  delay(2000);
}

// --------------------------------------------------------------
// BACA SENSOR
// --------------------------------------------------------------
// --------------------------------------------------------------
// BACA SENSOR pH (versi kalibrasi Arduino, disesuaikan ESP32)
// --------------------------------------------------------------
float bacaPH() {
  const int numSamples = 5;     // jumlah sampel
  float PH4 = 2.850;            // tegangan pada pH 4
  float PH7 = 3.015;            // tegangan pada pH 7
  
  float PH_step = (PH7 - PH4) / 3.0;  //  step per 1 pH

  long sum = 0;
  for (int i = 0; i < numSamples; i++) {
    sum += analogRead(PH_PIN);
    delay(10);
  }

  float adcValue = sum / (float)numSamples;

  // ESP32 ADC = 12 bit → 0–4095, tegangan referensi = 3.3V
  float voltage = (3.3 / 4095.0) * adcValue;

  // rumus pH hasil porting
  float pH = 7.00 + ((voltage - PH7) / PH_step);

  return pH;
}


float bacaTDS() {
  int adc = analogRead(TDS_PIN);
  float volt = adc * (3.3 / 4095.0);
  float tds = (volt * 133.42 * volt * volt - 255.86 * volt * volt + 857.39 * volt) * 0.5;
  if (tds < 0) tds = 0;
  return tds;
}

float bacaTurbidity() {
  int adc = analogRead(TURBIDITY_PIN);
  float turb = map(adc, 0, 4095, 100, 0);
  if (turb < 0) turb = 0;
  return turb;
}

float bacaUltrasonic() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);
  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);

  long dur = pulseIn(ECHO_PIN, HIGH, 30000);
  return dur * 0.034 / 2;
}

// --------------------------------------------------------------
void loop() {
  // Cek WiFi
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi terputus! Reconnecting...");
    WiFi.reconnect();
    delay(5000);
    return;
  }

  // Cek Firebase ready
  if (!Firebase.ready()) {
    Serial.println("Firebase belum ready, menunggu...");
    delay(2000);
    return;
  }

  if (millis() - previousMillis >= uploadInterval) {
    previousMillis = millis();

    // Baca semua sensor
    float ph = bacaPH();
    float tds = bacaTDS();
    float turb = bacaTurbidity();
    float jarak = bacaUltrasonic();

    // Serial output
    Serial.println("\n========== DATA SENSOR ==========");
    Serial.printf("pH          : %.2f\n", ph);
    Serial.printf("TDS         : %.2f ppm\n", tds);
    Serial.printf("Turbidity   : %.2f NTU\n", turb);
    Serial.printf("Ultrasonic  : %.2f cm\n", jarak);
    Serial.printf("Free Heap   : %d bytes\n", ESP.getFreeHeap());
    Serial.println("=================================\n");

    // Buat JSON untuk Firestore
    FirebaseJson content;
    content.set("fields/pHValue/doubleValue", ph);
    content.set("fields/TDSValue/doubleValue", tds);
    content.set("fields/turbidityValue/doubleValue", turb);
    content.set("fields/ultrasonicValue/doubleValue", jarak);

    Serial.println("📤 Mengirim ke Firestore...");

    String documentPath = "sensorRead/dataSensor";
    
    if (Firebase.Firestore.patchDocument(
          &fbdo,
          FIREBASE_PROJECT_ID,
          "",
          documentPath.c_str(),
          content.raw(),
          "pHValue,TDSValue,turbidityValue,ultrasonicValue"
        )) {
      
      Serial.println("✓✓✓ BERHASIL KIRIM KE FIRESTORE! ✓✓✓");
      Serial.println("📊 Data yang dikirim:");
      Serial.printf("   - pHValue: %.2f\n", ph);
      Serial.printf("   - TDSValue: %.2f PPM\n", tds);
      Serial.printf("   - turbidityValue: %.2f NTU\n", turb);
      Serial.printf("   - ultrasonicValue: %.2f cm\n", jarak);
      Serial.println("⚙  salinitasValue akan di-update oleh Laravel backend");
      
    } else {
      Serial.println("✗✗✗ GAGAL KIRIM! ✗✗✗");
      Serial.print("Error: ");
      Serial.println(fbdo.errorReason());
      Serial.print("HTTP Code: ");
      Serial.println(fbdo.httpCode());
    }
    
    Serial.println("\n⏱ Menunggu " + String(uploadInterval/1000) + " detik...\n");
  }
}