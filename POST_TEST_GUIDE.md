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
app/Services/FuzzyMamdaniService.php
```

#### Step 1.2: Cari fungsi Membership pH (baris ~32-44)
```php
/**
 * Membership Function untuk pH (3 trapezoids)
 * Low: [0, 0, 6.5, 7.2] - Asam berbahaya
 * Normal: [7.0, 7.5, 8.0, 8.5] - Optimal untuk udang
 * High: [8.2, 9.0, 14, 14] - Basa berbahaya
 */
private function phMembership($value)
{
    return [
        'low' => $this->trapezoid($value, 0, 0, 6.5, 7.2),
        'normal' => $this->trapezoid($value, 7.0, 7.5, 8.0, 8.5),
        'high' => $this->trapezoid($value, 8.2, 9.0, 14, 14),
    ];
}
```

#### Step 1.3: Contoh Modifikasi - Ubah Rentang pH Normal
**Sebelum:**
```php
'normal' => $this->trapezoid($value, 7.0, 7.5, 8.0, 8.5),
```

**Sesudah (Rentang diperlebar):**
```php
'normal' => $this->trapezoid($value, 6.8, 7.3, 8.2, 8.7),
```

#### Step 1.4: Cari fungsi Membership TDS (baris ~72-82)
```php
/**
 * Membership Function untuk TDS/PPM (3 trapezoids)
 * Disesuaikan untuk BUDIDAYA AIR TAWAR (Low Salinity Farming)
 * Low: [0, 0, 200, 350] - Terlalu tawar (defisiensi mineral)
 * Medium: [300, 400, 600, 800] - Optimal air tawar (0.5-1.4 ppt)
 * High: [700, 1000, 3000, 3000] - Terlalu tinggi untuk air tawar
 */
private function tdsMembership($value)
{
    return [
        'low' => $this->trapezoid($value, 0, 0, 200, 350),
        'medium' => $this->trapezoid($value, 300, 400, 600, 800),
        'high' => $this->trapezoid($value, 700, 1000, 3000, 3000),
    ];
}
```

#### Step 1.5: Contoh Modifikasi - Ubah Threshold TDS
**Sebelum (TDS Low: 0-350 ppm):**
```php
'low' => $this->trapezoid($value, 0, 0, 200, 350),
```

**Sesudah (TDS Low diperlebar: 0-500 ppm):**
```php
'low' => $this->trapezoid($value, 0, 0, 300, 500),
```

#### Step 1.6: Cari fungsi Membership Turbidity (baris ~46-70)
```php
/**
 * Membership Function untuk Turbidity/NTU (3 trapezoids)
 * Clear/Jernih: [0, 0, 15, 25] - NTU rendah (kurang plankton)
 * Optimal: [20, 25, 35, 45] - NTU sedang (plankton seimbang)
 * Turbid/Keruh: [40, 60, 150, 150] - NTU tinggi (sumbat insang)
 */
private function turbidityMembership($value)
{
    return [
        'clear' => $this->trapezoid($value, 0, 0, 15, 25),
        'optimal' => $this->trapezoid($value, 20, 25, 35, 45),
        'turbid' => $this->trapezoid($value, 40, 60, 150, 150),
    ];
}
```

### Penjelasan Parameter Trapezoid

```
Trapezoid Function: trapezoid($value, $a, $b, $c, $d)

        1.0  ___________
            /           \
           /             \
        0 /_______________\___
          a    b     c    d

- a: Titik mulai naik (membership = 0)
- b: Titik mencapai puncak (membership = 1)
- c: Titik akhir puncak (membership = 1)
- d: Titik turun ke 0 (membership = 0)
```

| Contoh | Parameter | Arti |
|--------|-----------|------|
| `trapezoid($val, 0, 0, 6.5, 7.2)` | a=0, b=0, c=6.5, d=7.2 | Nilai 1 dari 0-6.5, turun ke 0 di 7.2 |
| `trapezoid($val, 7.0, 7.5, 8.0, 8.5)` | a=7.0, b=7.5, c=8.0, d=8.5 | Naik dari 7.0, puncak 7.5-8.0, turun ke 8.5 |

---

## 2️⃣ UBAH RULE BASE (Fuzzy Rules)

### Lokasi File
```
app/Services/FuzzyMamdaniService.php
```

### Step-by-Step

#### Step 2.1: Cari fungsi getFuzzyRules() (baris ~100-170)
```php
private function getFuzzyRules()
{
    return [
        // Rule 1-9: pH Low
        // Rule 10-18: pH Normal
        // Rule 19-27: pH High
        // ... 27 rules total
    ];
}
```

#### Step 2.2: Daftar 27 Rule Base

**pH Low (Asam - Rule 1-9):**
| Rule | pH | Turbidity | TDS | Score | Category |
|------|-----|-----------|-----|-------|----------|
| 1 | low | clear | low | 35 | Poor |
| 2 | low | clear | medium | 40 | Poor |
| 3 | low | clear | high | 25 | Critical |
| 4 | low | optimal | low | 38 | Poor |
| 5 | low | optimal | medium | 45 | Fair |
| 6 | low | optimal | high | 35 | Poor |
| 7 | low | turbid | low | 20 | Critical |
| 8 | low | turbid | medium | 30 | Poor |
| 9 | low | turbid | high | 15 | Critical |

**pH Normal (Optimal - Rule 10-18):**
| Rule | pH | Turbidity | TDS | Score | Category |
|------|-----|-----------|-----|-------|----------|
| 10 | normal | clear | low | 55 | Fair |
| 11 | normal | clear | medium | 75 | Good |
| 12 | normal | clear | high | 60 | Fair |
| 13 | normal | optimal | low | 72 | Good |
| 14 | normal | optimal | medium | **95** | **Excellent** |
| 15 | normal | optimal | high | 78 | Good |
| 16 | normal | turbid | low | 50 | Fair |
| 17 | normal | turbid | medium | 58 | Fair |
| 18 | normal | turbid | high | 42 | Poor |

**pH High (Basa - Rule 19-27):**
| Rule | pH | Turbidity | TDS | Score | Category |
|------|-----|-----------|-----|-------|----------|
| 19 | high | clear | low | 40 | Poor |
| 20 | high | clear | medium | 52 | Fair |
| 21 | high | clear | high | 38 | Poor |
| 22 | high | optimal | low | 48 | Fair |
| 23 | high | optimal | medium | 68 | Good |
| 24 | high | optimal | high | 55 | Fair |
| 25 | high | turbid | low | 32 | Poor |
| 26 | high | turbid | medium | 40 | Poor |
| 27 | high | turbid | high | 18 | Critical |

#### Step 2.3: Contoh Modifikasi - Ubah Score Rule

Cari rule yang ingin diubah di dalam array `getFuzzyRules()`:

**Sebelum (Rule 14 - Score 95):**
```php
['ph' => 'normal', 'turbidity' => 'optimal', 'tds' => 'medium', 'score' => 95, 'category' => 'Excellent', 'reason' => 'KONDISI OPTIMAL! Semua parameter ideal'],
```

**Sesudah (Score diubah ke 90):**
```php
['ph' => 'normal', 'turbidity' => 'optimal', 'tds' => 'medium', 'score' => 90, 'category' => 'Excellent', 'reason' => 'KONDISI OPTIMAL! Semua parameter ideal'],
```

#### Step 2.4: Contoh Modifikasi - Ubah Category Rule

**Sebelum (Rule 5 - Category Fair):**
```php
['ph' => 'low', 'turbidity' => 'optimal', 'tds' => 'medium', 'score' => 45, 'category' => 'Fair', 'reason' => 'pH rendah, kekeruhan & TDS optimal'],
```

**Sesudah (Category diubah ke Poor):**
```php
['ph' => 'low', 'turbidity' => 'optimal', 'tds' => 'medium', 'score' => 45, 'category' => 'Poor', 'reason' => 'pH rendah - WASPADA meski parameter lain OK'],
```

#### Step 2.5: Contoh Modifikasi - Tambah Rule Baru (Rule 28)

Tambahkan di akhir array sebelum `];`:

```php
// Rule 28: Custom rule
['ph' => 'normal', 'turbidity' => 'clear', 'tds' => 'medium', 'score' => 80, 'category' => 'Good', 'reason' => 'Custom: pH & TDS optimal, air agak jernih'],
```

### Penjelasan Label Turbidity

| Label | NTU Range | Arti | Kondisi |
|-------|-----------|------|---------|
| `clear` | 0-25 | Air JERNIH | Kurang plankton (tidak baik) |
| `optimal` | 20-45 | Air AGAK KERUH | Plankton seimbang (ideal) |
| `turbid` | 40-150 | Air SANGAT KERUH | Bahaya insang (kritis) |

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

## 📝 STUDI CASE: Validasi Password Register

### Lokasi File
```
app/Http/Controllers/AuthController.php
```

### Requirement
Password harus memenuhi kriteria berikut:
- ✅ Minimal 8 karakter
- ✅ Mengandung huruf **KAPITAL** (A-Z)
- ✅ Mengandung huruf **kecil** (a-z)
- ✅ Mengandung **angka** (0-9)
- ✅ Mengandung **simbol** (@$!%*?&#)

### Contoh Password Valid
| Password | Valid | Alasan |
|----------|-------|--------|
| `Password123!` | ✅ | Ada kapital, kecil, angka, simbol |
| `Tambaq@2025` | ✅ | Ada kapital, kecil, angka, simbol |
| `password123` | ❌ | Tidak ada kapital dan simbol |
| `PASSWORD123` | ❌ | Tidak ada huruf kecil dan simbol |
| `Pass123` | ❌ | Kurang dari 8 karakter, tidak ada simbol |

### Implementasi Kode

#### Cari fungsi register() di AuthController.php (baris ~50-75)

**Kode Validasi Password dengan Regex:**
```php
public function register(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'phone_number' => 'required|string|max:20',
        'password' => [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/[A-Z]/',      // Harus ada huruf kapital
            'regex:/[a-z]/',      // Harus ada huruf kecil
            'regex:/[0-9]/',      // Harus ada angka
            'regex:/[@$!%*?&#]/', // Harus ada simbol
        ],
    ], [
        'password.confirmed' => 'Konfirmasi password harus sama dengan password.',
        'email.unique' => 'Email sudah terdaftar.',
        'password.min' => 'Password minimal 8 karakter.',
        'password.regex' => 'Password harus mengandung huruf kapital, huruf kecil, angka, dan simbol (@$!%*?&#).',
    ]);
    
    // ... rest of the code
}
```

### Penjelasan Regex

| Regex | Arti |
|-------|------|
| `regex:/[A-Z]/` | Harus mengandung minimal 1 huruf kapital (A-Z) |
| `regex:/[a-z]/` | Harus mengandung minimal 1 huruf kecil (a-z) |
| `regex:/[0-9]/` | Harus mengandung minimal 1 angka (0-9) |
| `regex:/[@$!%*?&#]/` | Harus mengandung minimal 1 simbol dari daftar |

### Studi Case: Modifikasi Validasi

#### Case 1: Tambah Simbol yang Diizinkan
**Sebelum:**
```php
'regex:/[@$!%*?&#]/', // Simbol terbatas
```

**Sesudah (Tambah simbol -, _, .):**
```php
'regex:/[@$!%*?&#\-_.]/', // Simbol lebih lengkap
```

#### Case 2: Ubah Minimal Karakter
**Sebelum:**
```php
'min:8',
```

**Sesudah (Minimal 10 karakter):**
```php
'min:10',
```

#### Case 3: Hapus Requirement Simbol (Jika tidak perlu)
**Sebelum:**
```php
'password' => [
    'required',
    'string',
    'min:8',
    'confirmed',
    'regex:/[A-Z]/',
    'regex:/[a-z]/',
    'regex:/[0-9]/',
    'regex:/[@$!%*?&#]/', // Hapus baris ini
],
```

**Sesudah (Tanpa simbol):**
```php
'password' => [
    'required',
    'string',
    'min:8',
    'confirmed',
    'regex:/[A-Z]/',
    'regex:/[a-z]/',
    'regex:/[0-9]/',
    // Simbol tidak wajib
],
```

### Testing Validasi

1. Akses `http://localhost:8000/register`
2. Isi form dengan password yang **tidak valid**: `password123`
3. Klik **Register**
4. Akan muncul error: *"Password harus mengandung huruf kapital, huruf kecil, angka, dan simbol"*
5. Ubah password menjadi **valid**: `Password123!`
6. Klik **Register** lagi
7. Registrasi berhasil ✅

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
