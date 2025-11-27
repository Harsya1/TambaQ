# ESP32 Sensor Calibration Guide

## Overview
Panduan kalibrasi sensor untuk TambaQ Water Quality Monitoring System.

---

## 📌 Sensor yang Digunakan

1. **pH Sensor** (Pin 35) - Analog
2. **TDS Sensor** (Pin 34) - Analog  
3. **Turbidity Sensor** (Pin 33) - Analog
4. **Ultrasonic HC-SR04** (Trig: Pin 5, Echo: Pin 18) - Digital

---

## 🔧 Kalibrasi pH Sensor

### Langkah Kalibrasi:

1. **Siapkan Buffer Solution:**
   - pH 4.0 (asam)
   - pH 7.0 (netral)
   - pH 10.0 (basa)

2. **Test di pH 7.0:**
   ```cpp
   // Upload kode dengan PH_OFFSET = 0
   // Baca nilai pH di Serial Monitor
   // Jika baca: pH = 7.3, maka offset = 7.0 - 7.3 = -0.3
   ```

3. **Update Konstanta:**
   ```cpp
   #define PH_OFFSET -0.3      // Sesuaikan dengan hasil kalibrasi
   #define PH_VOLTAGE_REF 2.5  // Voltage saat pH 7 (ukur dengan multimeter)
   #define PH_SLOPE 0.18       // Slope sensor (dari datasheet)
   ```

4. **Verifikasi dengan pH 4.0 dan pH 10.0:**
   - Celupkan sensor di buffer pH 4.0, harus baca ±4.0
   - Celupkan sensor di buffer pH 10.0, harus baca ±10.0
   - Toleransi: ±0.2 pH

### Troubleshooting:
- **Pembacaan tidak stabil:** Bersihkan elektroda dengan air suling
- **pH selalu 7.0:** Sensor mati atau kabel putus
- **pH >14 atau <0:** Sensor rusak atau wiring salah

---

## 🔧 Kalibrasi TDS Sensor

### Langkah Kalibrasi:

1. **Siapkan Standard Solution:**
   - TDS 0 PPM (air suling/RO water)
   - TDS 1000 PPM (beli TDS calibration solution atau buat sendiri)

2. **Test di 0 PPM:**
   ```cpp
   // Celupkan di air suling
   // Baca nilai TDS di Serial Monitor
   // Jika baca: TDS = 50, sensor perlu cleaning
   ```

3. **Test di 1000 PPM:**
   ```cpp
   // Celupkan di TDS 1000 solution
   // Baca nilai TDS di Serial Monitor
   // Jika baca: TDS = 850, maka K-value perlu disesuaikan
   // K-value baru = 1000 / 850 * 0.5 = 0.588
   ```

4. **Update Konstanta:**
   ```cpp
   #define TDS_KVALUE 0.588    // Sesuaikan dengan hasil kalibrasi
   ```

### Catatan Penting:
- TDS sensor dipengaruhi suhu air (kompensasi otomatis di kode)
- Bersihkan probe setelah digunakan
- Kalibrasi ulang setiap 1-3 bulan

### Membuat TDS 1000 PPM Solution:
```
1. Ambil 1 liter air suling
2. Tambahkan 1 gram garam dapur (NaCl)
3. Aduk sampai larut sempurna
4. TDS ≈ 1000 PPM
```

---

## 🔧 Kalibrasi Turbidity Sensor

### Langkah Kalibrasi:

1. **Siapkan Sample:**
   - Air jernih (filtered/RO water)
   - Air keruh (tambahkan tanah/lumpur bertahap)

2. **Test di Air Jernih:**
   ```cpp
   // Upload kode test untuk baca voltage
   int adc = analogRead(TURBIDITY_PIN);
   float voltage = adc * (3.3 / 4095.0);
   Serial.println(voltage);
   
   // Catat voltage saat air jernih (biasanya 2.5-3.0V)
   ```

3. **Test di Air Keruh:**
   ```cpp
   // Tambahkan tanah/lumpur sampai sangat keruh
   // Baca voltage lagi
   // Catat voltage saat air keruh (biasanya 1.0-1.5V)
   ```

4. **Update Konstanta:**
   ```cpp
   #define TURB_VOLTAGE_CLEAR 2.7  // Voltage saat air jernih
   #define TURB_VOLTAGE_MURKY 1.2  // Voltage saat air keruh
   ```

### Mapping NTU (Nephelometric Turbidity Unit):
```
Voltage    | NTU   | Kondisi
-----------|-------|------------------
> 2.7V     | 0     | Sangat jernih
2.0-2.7V   | 0-300 | Jernih - Agak keruh
1.5-2.0V   | 300-600 | Keruh
< 1.5V     | 600-1000 | Sangat keruh
```

### Troubleshooting:
- **Voltage tidak berubah:** LED sensor mati atau photodiode rusak
- **Bacaan sangat noise:** Tambahkan kapasitor 100µF di pin analog
- **Selalu baca 0:** Cek kabel dan power supply sensor

---

## 🔧 Kalibrasi Ultrasonic HC-SR04

### Langkah Kalibrasi:

1. **Test Jarak Tetap:**
   ```cpp
   // Letakkan benda padat di jarak 50cm dari sensor
   // Ukur dengan penggaris: 50cm
   // Baca di Serial Monitor
   // Jika baca: 52cm, maka akurat
   ```

2. **Verifikasi Multiple Distance:**
   - 10cm → Harus baca ±10cm
   - 50cm → Harus baca ±50cm
   - 100cm → Harus baca ±100cm
   - Toleransi: ±1cm

3. **Setup Tank Configuration:**
   ```cpp
   #define TANK_HEIGHT_CM 100    // Tinggi tangki total
   #define SENSOR_OFFSET_CM 5    // Jarak sensor ke dasar tangki
   
   // Water level = TANK_HEIGHT - ultrasonicValue + SENSOR_OFFSET
   ```

### Troubleshooting:
- **Baca 0 atau sangat besar:** Tidak ada echo (timeout)
  - Periksa wiring TRIG & ECHO
  - Pastikan benda target tidak terlalu miring
- **Bacaan melompat-lompat:** Gunakan median filter (sudah ada di kode)
- **Tidak bisa baca <2cm:** Limitasi sensor (blind zone)

---

## 🔧 Kalibrasi Salinity (Optional)

Salinity dihitung otomatis dari TDS:

```cpp
Salinity (PPT) = TDS (PPM) / (K × 1000)
K = 0.57 (konstanta PSS-78 simplified)
```

### Verifikasi:
1. Ambil sample air laut atau buat sendiri
2. Ukur TDS dengan sensor
3. Hitung salinity dengan rumus
4. Bandingkan dengan refractometer (alat ukur salinity)

### Membuat Air Saline 35 PPT (setara air laut):
```
1. Ambil 1 liter air suling
2. Tambahkan 35 gram garam laut (sea salt)
3. Aduk sampai larut sempurna
4. TDS seharusnya ≈ 20,000 PPM
5. Salinity = 20000 / (0.57 × 1000) = 35 PPT ✅
```

---

## 📊 Validasi Range Sensor

Setelah kalibrasi, pastikan pembacaan dalam range normal:

| Sensor     | Min   | Max    | Satuan | Kondisi Normal       |
|------------|-------|--------|--------|----------------------|
| pH         | 0.0   | 14.0   | pH     | 6.5 - 8.5 (tambak)   |
| TDS        | 0     | 20000  | PPM    | 5000 - 15000 (tambak)|
| Turbidity  | 0     | 1000   | NTU    | 10 - 50 (bagus)      |
| Ultrasonic | 2     | 400    | cm     | Tergantung setup     |
| Salinity   | 0     | 35     | PPT    | 10 - 25 (tambak)     |

---

## 🔍 Testing & Debugging

### 1. Test Individual Sensor

Upload kode test untuk masing-masing sensor:

```cpp
void loop() {
  // Test pH only
  int adc = analogRead(PH_PIN);
  float voltage = adc * (3.3 / 4095.0);
  Serial.printf("pH ADC: %d | Voltage: %.2fV\n", adc, voltage);
  delay(1000);
}
```

### 2. Test Firestore Connection

```cpp
// Kirim dummy data untuk test koneksi
content.set("fields/pHValue/doubleValue", 7.0);
content.set("fields/TDSValue/doubleValue", 1000.0);
content.set("fields/turbidityValue/doubleValue", 50.0);
content.set("fields/ultrasonicValue/doubleValue", 80.0);
```

### 3. Monitor Serial Output

Gunakan Serial Monitor (115200 baud) untuk debug:

```
=== MEMBACA SENSOR ===
--- HASIL BACA SENSOR ---
pH: 7.12 ✅
TDS: 8500.23 ppm ✅
Turbidity: 35.67 NTU ✅
Ultrasonic: 85.30 cm ✅
Salinity: 14.91 PPT
Mengirim ke Firestore... BERHASIL! ✅
-------------------------
```

---

## ⚠️ Common Issues

### Issue 1: "pH selalu 20.88"
**Penyebab:** Formula pH salah atau sensor tidak terhubung  
**Solusi:**
```cpp
// Cek raw voltage dulu
int adc = analogRead(PH_PIN);
float voltage = adc * (3.3 / 4095.0);
Serial.println(voltage);

// Jika voltage = 0V → sensor tidak terhubung
// Jika voltage = 3.3V → kabel short
// Jika voltage = 1.5-2.5V → normal
```

### Issue 2: "TDS = 0 terus"
**Penyebab:** Probe tidak terendam air atau sensor rusak  
**Solusi:**
- Pastikan kedua probe terendam air
- Bersihkan probe dari kerak/kotoran
- Test dengan multimeter (harus ada resistansi)

### Issue 3: "Turbidity tidak berubah"
**Penyebab:** LED sensor mati atau photodiode rusak  
**Solusi:**
- Cek LED sensor menyala atau tidak
- Test di ruangan gelap vs terang
- Ganti sensor jika perlu

### Issue 4: "Ultrasonic baca 0 atau 400+"
**Penyebab:** Echo timeout (tidak ada pantulan)  
**Solusi:**
- Pastikan ada objek di depan sensor (max 4m)
- Cek wiring TRIG dan ECHO tidak terbalik
- Tambahkan timeout di pulseIn: `pulseIn(ECHO_PIN, HIGH, 30000)`

### Issue 5: "Firebase error"
**Penyebab:** WiFi lemah, API key salah, atau quota exceeded  
**Solusi:**
- Cek koneksi WiFi dengan `WiFi.status()`
- Verifikasi API Key dan Project ID
- Cek Firebase Console untuk error log

---

## 📈 Maintenance Schedule

| Task                        | Interval    | Catatan                      |
|-----------------------------|-------------|------------------------------|
| Bersihkan pH probe          | 1 minggu    | Air suling + tissue lembut   |
| Bersihkan TDS probe         | 1 minggu    | Sikat lembut + air suling    |
| Bersihkan turbidity sensor  | 1 minggu    | Lap lensa dengan microfiber  |
| Kalibrasi pH                | 1 bulan     | Buffer pH 4, 7, 10           |
| Kalibrasi TDS               | 3 bulan     | TDS 1000 solution            |
| Kalibrasi Turbidity         | 6 bulan     | Air jernih vs keruh          |
| Test Ultrasonic             | 6 bulan     | Verifikasi akurasi jarak     |
| Replace pH probe            | 1-2 tahun   | Tergantung pemakaian         |

---

## 📝 Catatan Tambahan

### Power Supply
- ESP32: 5V 1A minimum
- Total konsumsi: ~500mA (semua sensor aktif)
- Gunakan USB power adapter berkualitas atau power bank

### Wiring Tips
- Gunakan kabel twisted pair untuk analog signal
- Tambahkan decoupling capacitor 100µF di VCC setiap sensor
- Ground semua sensor ke ESP32 ground yang sama
- Hindari kabel analog parallel dengan kabel power

### Data Quality
- Moving average (5 samples) untuk filter noise
- Median filter untuk ultrasonic (3 samples)
- Validasi range sebelum kirim ke Firestore
- Skip data jika out of range

### Future Improvements
- Tambahkan sensor suhu untuk kompensasi TDS
- OTA (Over-The-Air) update firmware
- Deep sleep mode untuk hemat baterai
- Local data logging (SD card) sebagai backup

---

**Last Updated:** November 26, 2025  
**Author:** TambaQ Development Team  
**Version:** 2.0 (Enhanced with validation & error handling)
