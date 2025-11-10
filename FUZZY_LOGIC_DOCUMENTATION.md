# Dokumentasi Fuzzy Logic Mamdani - TambaQ

## Overview
Sistem TambaQ menggunakan **Fuzzy Logic Mamdani** untuk mengevaluasi kualitas air tambak dan mengontrol aerator secara otomatis berdasarkan data sensor.

## Parameter Input

### 1. pH (Tingkat Keasaman Air)
- **Rendah**: pH < 7.0 (Asam)
- **Normal**: pH 6.5 - 8.5 (Optimal untuk budidaya)
- **Tinggi**: pH > 8.0 (Basa)

### 2. TDS - Total Dissolved Solids (Zat Terlarut)
- **Rendah**: < 350 ppm
- **Normal**: 320 - 450 ppm (Optimal)
- **Tinggi**: > 420 ppm

### 3. Turbidity (Kekeruhan Air)
- **Rendah**: < 12 NTU (Air jernih)
- **Sedang**: 10 - 20 NTU
- **Tinggi**: > 18 NTU (Air keruh)

## Fuzzy Rules (Aturan Keputusan)

### Rule 1: Kondisi Buruk (Aerator ON)
**IF** pH Rendah **AND** TDS Tinggi **AND** Turbidity Tinggi  
**THEN** Kualitas = **Buruk**, Aerator = **ON**  
**Rekomendasi**: Kualitas air buruk. pH rendah, TDS dan kekeruhan tinggi. Aerator AKTIF untuk meningkatkan oksigen.

### Rule 2: Kondisi Sedang (Aerator ON)
**IF** pH Rendah **AND** TDS Normal **AND** Turbidity Sedang  
**THEN** Kualitas = **Sedang**, Aerator = **ON**  
**Rekomendasi**: Kualitas air sedang. pH rendah perlu perhatian. Aerator AKTIF untuk stabilisasi.

### Rule 3: Kondisi Baik (Aerator OFF)
**IF** pH Normal **AND** TDS Normal **AND** Turbidity Rendah  
**THEN** Kualitas = **Baik**, Aerator = **OFF**  
**Rekomendasi**: Kualitas air baik. Semua parameter dalam batas normal. Aerator NONAKTIF untuk efisiensi energi.

### Rule 4: Kondisi Sangat Baik (Aerator OFF)
**IF** pH Normal **AND** TDS Rendah **AND** Turbidity Rendah  
**THEN** Kualitas = **Sangat Baik**, Aerator = **OFF**  
**Rekomendasi**: Kualitas air sangat baik. Kondisi optimal untuk budidaya. Aerator NONAKTIF.

### Rule 5: pH Tinggi (Aerator ON)
**IF** pH Tinggi  
**THEN** Kualitas = **Buruk**, Aerator = **ON**  
**Rekomendasi**: pH terlalu tinggi, berbahaya untuk udang. Aerator AKTIF untuk membantu stabilisasi.

### Rule 6: Turbidity Tinggi (Aerator ON)
**IF** Turbidity Tinggi  
**THEN** Kualitas = **Buruk**, Aerator = **ON**  
**Rekomendasi**: Kekeruhan air sangat tinggi. Aerator AKTIF untuk meningkatkan sirkulasi air.

### Rule 7: Parameter Utama Normal (Aerator OFF)
**IF** pH Normal **AND** TDS Normal  
**THEN** Kualitas = **Baik**, Aerator = **OFF**  
**Rekomendasi**: Parameter utama normal. Aerator NONAKTIF, lakukan monitoring rutin.

### Rule 8: TDS dan Turbidity Tinggi (Aerator ON)
**IF** TDS Tinggi **AND** Turbidity Tinggi  
**THEN** Kualitas = **Buruk**, Aerator = **ON**  
**Rekomendasi**: TDS dan kekeruhan tinggi. Pertimbangkan pergantian air. Aerator AKTIF.

### Rule 9: pH Rendah dan Turbidity Tinggi (Aerator ON)
**IF** pH Rendah **AND** Turbidity Tinggi  
**THEN** Kualitas = **Buruk**, Aerator = **ON**  
**Rekomendasi**: Kombinasi pH rendah dan kekeruhan tinggi berbahaya. Aerator AKTIF segera.

### Rule 10: Kondisi Optimal (Aerator OFF)
**IF** TDS Rendah **AND** Turbidity Rendah  
**THEN** Kualitas = **Baik**, Aerator = **OFF**  
**Rekomendasi**: Air jernih dengan TDS rendah. Kondisi baik. Aerator NONAKTIF.

## Metode Defuzzifikasi
Sistem menggunakan metode **Maximum** (Max) dimana rule dengan **strength tertinggi** yang akan dipilih sebagai output.

**Rule Strength** dihitung menggunakan operator **MIN** untuk operasi AND:
```
strength = MIN(membership_pH, membership_TDS, membership_Turbidity)
```

## Fungsi Keanggotaan

### Fungsi Segitiga (Triangle)
Digunakan untuk kategori "Normal" dan "Sedang"
```
           1.0
           /\
          /  \
         /    \
        /      \
    ___/        \___
    a    b    c
```

### Fungsi Trapesium (Trapezoid)
Digunakan untuk kategori "Rendah" dan "Tinggi"
```
         _____
        /     \
       /       \
      /         \
  ___/           \___
  a    b    c    d
```

## Cara Menggunakan

### 1. Melalui Dashboard
Dashboard secara otomatis menjalankan fuzzy logic setiap kali data sensor diupdate (setiap 3 detik).

### 2. Melalui Command Line
```bash
php artisan fuzzy:process
```

Command ini akan:
- Membaca data sensor terbaru
- Mengevaluasi dengan fuzzy logic
- Update status aerator otomatis
- Menyimpan hasil keputusan ke database

### 3. Scheduled Task (Opsional)
Tambahkan ke `app/Console/Kernel.php` untuk menjalankan otomatis:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('fuzzy:process')->everyMinute();
}
```

Jalankan scheduler:
```bash
php artisan schedule:work
```

## Output Sistem

### Status Kualitas Air
- **Sangat Baik**: Semua parameter optimal
- **Baik**: Parameter dalam batas normal
- **Sedang**: Beberapa parameter perlu perhatian
- **Buruk**: Parameter kritis, perlu tindakan segera

### Kontrol Aerator
- **ON**: Aerator aktif untuk meningkatkan oksigen dan sirkulasi
- **OFF**: Aerator nonaktif untuk efisiensi energi

## Database Schema

### Tabel: sensor_readings
- `ph_value`: Nilai pH air
- `tds_value`: Total Dissolved Solids
- `turbidity`: Tingkat kekeruhan
- `water_level`: Jarak permukaan air
- `salinity`: Salinitas (konversi dari TDS)

### Tabel: fuzzy_decisions
- `sensor_reading_id`: Relasi ke sensor_readings
- `water_quality_status`: Hasil evaluasi kualitas
- `recommendation`: Rekomendasi tindakan
- `fuzzy_details`: Detail perhitungan membership

### Tabel: actuators
- `name`: Nama aktuator (Aerator)
- `status`: Status on/off

## Contoh Skenario

### Skenario 1: Kondisi Normal
```
Input:
- pH: 7.2
- TDS: 350 ppm
- Turbidity: 12 NTU

Output:
- Kualitas: Baik
- Aerator: OFF
- Rule: pH Normal AND TDS Normal
```

### Skenario 2: Air Keruh
```
Input:
- pH: 6.8
- TDS: 420 ppm
- Turbidity: 25 NTU

Output:
- Kualitas: Buruk
- Aerator: ON
- Rule: Turbidity Tinggi
```

### Skenario 3: Kondisi Optimal
```
Input:
- pH: 7.5
- TDS: 320 ppm
- Turbidity: 8 NTU

Output:
- Kualitas: Sangat Baik
- Aerator: OFF
- Rule: pH Normal AND TDS Rendah AND Turbidity Rendah
```

## File Terkait
- Service: `app/Services/FuzzyMamdaniService.php`
- Controller: `app/Http/Controllers/DashboardController.php`
- Command: `app/Console/Commands/ProcessFuzzyLogic.php`
- Seeder: `database/seeders/FuzzyDecisionSeeder.php`
