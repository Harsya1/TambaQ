# 📋 POST TEST GUIDE - TambaQ Smart Shrimp Pond Monitoring

Panduan step-by-step untuk demonstrasi fitur sistem TambaQ.

---

## 📌 Daftar Fitur yang Akan Didemonstrasikan

| No | Fitur | Deskripsi |
|----|-------|-----------|
| 1 | Ubah Fuzzy | Modifikasi parameter membership function |
| 2 | Ubah Rule Base | Modifikasi aturan fuzzy inference |
| 3 | Login Akun (Web) | Autentikasi user ke dashboard |
| 4 | Data Histori | Melihat data historis sensor |

---

## 1️⃣ UBAH FUZZY (Membership Functions)

### Lokasi File
```
app/Services/FuzzyMamdaniService.php
```

### Step-by-Step

#### Step 1.1: Buka file FuzzyMamdaniService.php
```bash
# Path lengkap
c:\Users\dika2\tambaQ\app\Services\FuzzyMamdaniService.php
```

#### Step 1.2: Cari fungsi Membership TDS (baris ~72-98)
```php
/**
 * TDS Membership Function - Low-Salinity Aquaculture Optimized
 * 
 * Membership Sets:
 * - LOW (Bahaya): Left trapezoid [0, 0, 800, 1000] - Fatal osmotic stress
 * - MARGINAL: Triangle [800, 1000, 1200] - Transition zone
 * - OPTIMAL: Right trapezoid [1000, 1200, ∞, ∞] - Viable for growth
 */
private function fuzzifyTds(float $val): void
{
    // LOW: Full membership until 800, drops to 0 at 1000
    $this->fuzzifiedTds['LOW'] = $this->trapezoidLeft($val, 800, 1000);
    
    // MARGINAL: Triangle peaked at 1000
    $this->fuzzifiedTds['MARGINAL'] = $this->triangle($val, 800, 1000, 1200);
    
    // OPTIMAL: Starts rising at 1000, full at 1200+
    $this->fuzzifiedTds['OPTIMAL'] = $this->trapezoidRight($val, 1000, 1200);
}
```

#### Step 1.3: Contoh Modifikasi - Ubah Threshold TDS
**Sebelum (Threshold 1000 ppm):**
```php
$this->fuzzifiedTds['LOW'] = $this->trapezoidLeft($val, 800, 1000);
```

**Sesudah (Threshold diubah ke 900 ppm):**
```php
$this->fuzzifiedTds['LOW'] = $this->trapezoidLeft($val, 700, 900);
```

#### Step 1.4: Cari fungsi Membership pH (baris ~100-145)
```php
/**
 * pH Membership Function - Strict Biological Constraints
 * 
 * - ACIDIC: Left trapezoid [0, 0, 7.0, 7.5] - Acidosis risk
 * - OPTIMAL: Full trapezoid [7.2, 7.5, 8.5, 8.8] - Golden window
 * - ALKALINE: Right trapezoid [8.5, 9.0, 14, 14] - NH3 toxicity (VETO)
 */
private function fuzzifyPh(float $val): void
{
    $this->fuzzifiedPh['ACIDIC'] = $this->trapezoidLeft($val, 7.0, 7.5);
    $this->fuzzifiedPh['OPTIMAL'] = $this->trapezoidFull($val, 7.2, 7.5, 8.5, 8.8);
    $this->fuzzifiedPh['ALKALINE'] = $this->trapezoidRight($val, 8.5, 9.0);
}
```

#### Step 1.5: Contoh Modifikasi - Perlebar Rentang pH Optimal
**Sebelum:**
```php
$this->fuzzifiedPh['OPTIMAL'] = $this->trapezoidFull($val, 7.2, 7.5, 8.5, 8.8);
```

**Sesudah (Rentang diperlebar):**
```php
$this->fuzzifiedPh['OPTIMAL'] = $this->trapezoidFull($val, 7.0, 7.3, 8.7, 9.0);
```

### Penjelasan Parameter Membership Function

| Fungsi | Parameter | Arti |
|--------|-----------|------|
| `trapezoidLeft($val, a, b)` | a, b | Nilai 1 sampai a, turun ke 0 di b |
| `trapezoidRight($val, a, b)` | a, b | Nilai 0 di a, naik ke 1 di b |
| `trapezoidFull($val, a, b, c, d)` | a,b,c,d | Naik a→b, datar b→c, turun c→d |
| `triangle($val, a, b, c)` | a,b,c | Naik a→b, turun b→c |

---

## 2️⃣ UBAH RULE BASE (Fuzzy Rules)

### Lokasi File
```
app/Services/FuzzyMamdaniService.php
```

### Step-by-Step

#### Step 2.1: Cari fungsi evaluateRules() (baris ~232-420)
```php
private function evaluateRules(): void
{
    $this->rulesOutput = [
        'POOR' => 0.0,
        'MODERATE' => 0.0,
        'EXCELLENT' => 0.0
    ];
    // ... rules definition
}
```

#### Step 2.2: Daftar Rule Base yang Tersedia

| Rule | Kondisi | Output | Deskripsi |
|------|---------|--------|-----------|
| 1 | TDS=OPTIMAL ∧ pH=OPTIMAL ∧ Turbidity=OPTIMAL | EXCELLENT | Kondisi ideal |
| 2 | TDS=OPTIMAL ∧ pH=OPTIMAL | EXCELLENT | TDS & pH optimal |
| 3 | TDS=LOW | POOR | Zona FATAL (Osmoregulasi) |
| 4 | pH=ALKALINE | POOR | VETO - Toksisitas NH3 |
| 5 | pH=ACIDIC | POOR | VETO - Asidosis |
| 6 | TDS=MARGINAL ∧ pH=OPTIMAL | MODERATE | Dapat ditoleransi |
| 7 | TDS=MARGINAL ∧ (pH=ACIDIC ∨ pH=ALKALINE) | POOR | Sinergi negatif |
| 8 | TDS=OPTIMAL ∧ pH=OPTIMAL ∧ Turbidity=CLEAR | MODERATE | Air terlalu jernih |
| 9 | TDS=OPTIMAL ∧ pH=OPTIMAL ∧ Turbidity=TURBID | MODERATE | Air terlalu keruh |
| 10 | TDS=LOW ∧ Turbidity=TURBID | POOR | Kritis - insang tersumbat |
| 11 | TDS=OPTIMAL ∧ pH mendekati batas | MODERATE | Monitor ketat |

#### Step 2.3: Contoh Modifikasi - Tambah Rule Baru

Tambahkan di dalam fungsi `evaluateRules()` sebelum bagian agregasi:

```php
// =====================================================================
// RULE 12: CUSTOM - Contoh Rule Baru
// IF TDS=OPTIMAL AND Turbidity=TURBID THEN MODERATE
// =====================================================================
$rule12Strength = min(
    $this->fuzzifiedTds['OPTIMAL'],
    $this->fuzzifiedTurbidity['TURBID']
);
if ($rule12Strength > 0) {
    $this->evaluatedRules[] = [
        'id' => 12,
        'strength' => $rule12Strength,
        'output' => 'MODERATE',
        'description' => 'TDS optimal tapi air keruh - perlu pergantian air'
    ];
}
```

#### Step 2.4: Contoh Modifikasi - Ubah Output Rule yang Ada

**Sebelum (Rule 6 output MODERATE):**
```php
$this->evaluatedRules[] = [
    'id' => 6,
    'strength' => $rule6Strength,
    'output' => 'MODERATE',
    'description' => 'TDS marginal (800-1200 ppm) + pH optimal'
];
```

**Sesudah (Diubah ke POOR untuk lebih ketat):**
```php
$this->evaluatedRules[] = [
    'id' => 6,
    'strength' => $rule6Strength,
    'output' => 'POOR',
    'description' => 'TDS marginal - WASPADA! Tingkatkan mineral segera'
];
```

#### Step 2.5: Contoh Modifikasi - Ubah Weight Rule

**Sebelum (Rule 2 weight 0.9):**
```php
$rule2Strength = min(
    $this->fuzzifiedTds['OPTIMAL'],
    $this->fuzzifiedPh['OPTIMAL']
) * 0.9;
```

**Sesudah (Weight diubah ke 0.8):**
```php
$rule2Strength = min(
    $this->fuzzifiedTds['OPTIMAL'],
    $this->fuzzifiedPh['OPTIMAL']
) * 0.8;
```

---

## 3️⃣ LOGIN AKUN (Web)

### URL Akses
```
http://localhost:8000/login
```

### Step-by-Step

#### Step 3.1: Akses Halaman Login
1. Buka browser (Chrome/Firefox)
2. Ketik URL: `http://localhost:8000/login`
3. Akan muncul form login

#### Step 3.2: Form Login
| Field | Deskripsi |
|-------|-----------|
| Email | Email akun terdaftar |
| Password | Password akun |
| Remember Me | Checkbox untuk tetap login |

#### Step 3.3: Proses Login
1. Masukkan email: `user@tambaq.com`
2. Masukkan password: `password123`
3. Klik tombol **"Login"**
4. Jika berhasil → Redirect ke Dashboard
5. Jika gagal → Muncul pesan error

#### Step 3.4: Registrasi Akun Baru (Opsional)
```
URL: http://localhost:8000/register
```
1. Klik link "Register" di halaman login
2. Isi form:
   - Name: `Nama Lengkap`
   - Email: `email@example.com`
   - Password: `minimal 8 karakter`
   - Confirm Password: `ulangi password`
3. Klik **"Register"**

#### Step 3.5: Logout
1. Klik nama user di pojok kanan atas
2. Klik **"Logout"**
3. Atau akses: `POST /logout`

### File Terkait Autentikasi
```
app/Http/Controllers/AuthController.php   # Logic autentikasi
resources/views/auth/login.blade.php      # Form login
resources/views/auth/register.blade.php   # Form register
routes/web.php                            # Route definitions
```

---

## 4️⃣ DATA HISTORI

### URL Akses
```
http://localhost:8000/history
```

### Step-by-Step

#### Step 4.1: Akses Halaman History
1. Login terlebih dahulu
2. Klik menu **"History"** di sidebar/navbar
3. Atau akses langsung: `http://localhost:8000/history`

#### Step 4.2: Tampilan Halaman History
Halaman history menampilkan:
- **Stats Cards**: Total data, alerts, average quality
- **Filter Date Range**: Pilih rentang tanggal
- **Data Table**: Tabel data sensor historis
- **Charts**: Grafik tren parameter

#### Step 4.3: Filter Data
1. Pilih **Start Date** (tanggal mulai)
2. Pilih **End Date** (tanggal akhir)
3. Klik **"Apply Filter"**
4. Data akan diperbarui sesuai filter

#### Step 4.4: Kolom Data History

| Kolom | Deskripsi | Satuan |
|-------|-----------|--------|
| Timestamp | Waktu pengukuran | DateTime |
| pH | Tingkat keasaman | 0-14 |
| TDS | Total Dissolved Solids | ppm |
| Turbidity | Kekeruhan air | NTU |
| Salinity | Kadar garam | ppt |
| Quality Score | Skor kualitas (Fuzzy) | 0-100 |
| Status | Kategori kualitas | Excellent/Good/Moderate/Poor/Critical |

#### Step 4.5: API Endpoint History
```
GET /api/history/data           # Data tabel dengan pagination
GET /api/history-stats          # Statistik ringkasan
GET /api/sensor/chart           # Data untuk chart
```

#### Step 4.6: Export Data
1. Klik tombol **"Export CSV"** untuk download CSV
2. Klik tombol **"Export PDF"** untuk download PDF
```
GET /api/export/csv?start=2025-01-01&end=2025-01-31
GET /api/export/pdf?start=2025-01-01&end=2025-01-31
```

### File Terkait History
```
app/Http/Controllers/DashboardController.php   # getHistoryData(), history()
app/Services/FirebaseService.php               # getHistoricalData()
resources/views/history.blade.php              # View halaman history
routes/web.php                                 # Route /history
```

---

## 🔧 Troubleshooting

### Error Login
```
# Clear cache session
php artisan cache:clear
php artisan config:clear
```

### Error Fuzzy
```
# Check syntax error
php artisan tinker
>>> new App\Services\FuzzyMamdaniService()
```

### Error History Data
```
# Check Firebase connection
# Pastikan .env memiliki FIREBASE_* config yang benar
```

---

## 📁 Struktur File Penting

```
TambaQ/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php        # Login/Register/Logout
│   │   ├── DashboardController.php   # Dashboard & History
│   │   └── AnalyticsController.php   # Analytics & Export
│   └── Services/
│       ├── FuzzyMamdaniService.php   # Fuzzy Logic (UBAH DI SINI)
│       └── FirebaseService.php       # Firebase connection
├── resources/views/
│   ├── auth/
│   │   ├── login.blade.php           # Form login
│   │   └── register.blade.php        # Form register
│   ├── dashboard.blade.php           # Main dashboard
│   └── history.blade.php             # History page
└── routes/
    └── web.php                       # All routes
```

---

## ✅ Checklist Post Test

- [ ] Ubah Fuzzy Membership Function (TDS/pH/Turbidity)
- [ ] Ubah/Tambah Fuzzy Rule Base
- [ ] Login ke aplikasi web
- [ ] Lihat halaman Data History
- [ ] Filter data berdasarkan tanggal
- [ ] Export data (CSV/PDF)

---

**TambaQ IoT - Smart Shrimp Pond Monitoring System**  
*Powered by Fuzzy Mamdani Logic*
