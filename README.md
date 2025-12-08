# TambaQ - Smart Shrimp Pond Monitoring System

A web-based monitoring system for shrimp pond water quality using **Fuzzy Mamdani Logic** optimized for **low-salinity inland aquaculture** (Litopenaeus vannamei).

![TambaQ Dashboard](https://img.shields.io/badge/Laravel-11-red?logo=laravel) ![PHP](https://img.shields.io/badge/PHP-8.2+-blue?logo=php) ![Firebase](https://img.shields.io/badge/Firebase-Firestore-orange?logo=firebase) ![License](https://img.shields.io/badge/License-MIT-green)

## 🎯 Features

- **Real-time Monitoring** - Live sensor data from Firebase (auto-refresh 5 seconds)
- **Fuzzy Mamdani AI** - 11 rules with veto power logic for water quality assessment
- **Low-Salinity Optimized** - TDS threshold >1000 ppm, pH window 7.5-8.5
- **Telegram Notifications** - Real-time alerts for critical conditions
- **Data Visualization** - Historical charts with Chart.js
- **Analytics Dashboard** - Correlation analysis, trends, forecasting
- **Export Data** - CSV and PDF export functionality
- **Secure Authentication** - Login/Register system

## 📊 Monitored Parameters

| Parameter | Optimal Range | Status Levels |
|-----------|---------------|---------------|
| **pH** | 7.5 - 8.5 | Acidic, Optimal, Alkaline |
| **TDS** | >1000 ppm | Low (Fatal), Marginal, Optimal |
| **Turbidity** | 25-35 NTU | Clear, Optimal, Turbid |
| **Salinity** | Calculated | S(ppt) = TDS(ppm) / 1000 |

## 🧠 Fuzzy Logic System (Low-Salinity Optimized)

The system uses **Fuzzy Mamdani** with biological constraints:

### Key Rules with Veto Power:
```
Rule 3: IF TDS < 1000 ppm → POOR (Fatal Zone - Osmoregulatory Cost)
Rule 4: IF pH > 8.5 → POOR (VETO - Ammonia NH3 Toxicity)
Rule 5: IF pH < 7.5 → POOR (VETO - Acidosis Risk)
Rule 7: IF TDS=Marginal AND pH≠Optimal → POOR (Synergistic Stressor)
```

### Biological Basis:
- **TDS <1000 ppm**: High Na⁺/K⁺-ATPase activity, energy diverted from growth
- **pH >8.5**: NH₃ toxicity (30-50% at pH 9.0)
- **pH <7.5**: Hemolymph acidosis, reduced oxygen transport

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM
- Firebase Project (Firestore)

### Installation

```bash
# Clone repository
git clone https://github.com/Harsya1/TambaQ.git
cd TambaQ

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure .env file (IMPORTANT!)
# Edit .env and fill in:
# - FIREBASE_* credentials (from Firebase Console)
# - TELEGRAM_* credentials (optional, for notifications)

# Run migrations
php artisan migrate

# Build assets
npm run build

# Start server
php artisan serve
```

Visit: `http://localhost:8000`

### Firebase Setup (Required)

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Create/Select your project
3. Go to **Project Settings** > **Service Accounts**
4. Click **Generate New Private Key**
5. Copy the values to your `.env` file:
   - `FIREBASE_PROJECT_ID`
   - `FIREBASE_PRIVATE_KEY_ID`
   - `FIREBASE_PRIVATE_KEY`
   - `FIREBASE_CLIENT_EMAIL`
   - `FIREBASE_CLIENT_ID`
   - `FIREBASE_CLIENT_CERT_URL`

### Telegram Notifications (Optional)

1. Create bot via [@BotFather](https://t.me/BotFather) on Telegram
2. Get your `TELEGRAM_BOT_TOKEN`
3. Send a message to your bot
4. Get `TELEGRAM_CHAT_ID` from: `https://api.telegram.org/bot<TOKEN>/getUpdates`
5. Set `TELEGRAM_WEBHOOK_SECRET` (any random string)

## 🔑 Default Credentials

```
Email: admin@tambaq.com
Password: password123
```

## 📁 Project Structure

```
app/
├── Http/Controllers/
│   ├── AuthController.php        # Authentication logic
│   ├── DashboardController.php   # Dashboard & History API
│   ├── AnalyticsController.php   # Analytics & Export
│   └── TelegramController.php    # Telegram webhook
├── Models/
│   └── User.php
└── Services/
    ├── FuzzyMamdaniService.php   # Fuzzy logic engine (Low-Salinity)
    ├── FirebaseService.php       # Firebase Firestore connection
    └── TelegramService.php       # Telegram notifications

resources/views/
├── dashboard.blade.php           # Main monitoring dashboard
├── history.blade.php             # Historical data view
└── auth/
    ├── login.blade.php
    └── register.blade.php

routes/
└── web.php                       # All routes (web + API)
```

## 🎨 Color Theme

- Primary: `#6D94C5` (Soft Blue)
- Secondary: `#CBDCEB` (Light Blue)
- Background: `#F5EFE6` (Cream)

## 📡 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/dashboard` | Main dashboard view |
| `GET` | `/history` | Historical data view |
| `GET` | `/api/sensor/latest` | Latest sensor readings |
| `GET` | `/api/sensor/chart` | Chart data (24h) |
| `GET` | `/api/history/data` | History table with pagination |
| `GET` | `/api/trend/7days` | 7-day trend analysis |
| `GET` | `/api/correlation` | Parameter correlation |
| `GET` | `/api/export/csv` | Export data as CSV |
| `GET` | `/api/export/pdf` | Export data as PDF |
| `POST` | `/telegram/webhook` | Telegram bot webhook |

## 🛠️ Tech Stack

- **Backend:** Laravel 11, PHP 8.2
- **Database:** Firebase Firestore (real-time), SQLite (auth)
- **Frontend:** Blade Templates, Bootstrap 5
- **Charts:** Chart.js
- **AI Engine:** Custom Fuzzy Mamdani (Low-Salinity Optimized)
- **Notifications:** Telegram Bot API
- **PDF Export:** DomPDF

## 📖 Documentation

- [Fuzzy Logic Documentation](FUZZY_LOGIC_DOCUMENTATION.md) - Complete rule definitions and membership functions
- [Post Test Guide](POST_TEST_GUIDE.md) - Step-by-step guide for demonstrations

## 🧪 Testing

```bash
# Run tests
php artisan test

# Check Fuzzy Logic
php artisan tinker
>>> $fuzzy = new App\Services\FuzzyMamdaniService();
>>> $fuzzy->evaluateWaterQuality(7.8, 1200, 30);
```

## ⚠️ Troubleshooting

### Firebase Connection Error
```bash
# Check .env configuration
php artisan config:clear
php artisan cache:clear
```

### Telegram Not Working
- Ensure HTTPS is enabled (required for webhook)
- Check bot token and chat ID are correct
- Set webhook: `POST /telegram/set-webhook`

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License.

## 👨‍💻 Author

**Harsya**
- GitHub: [@Harsya1](https://github.com/Harsya1)

## 🙏 Acknowledgments

- Laravel Framework
- Chart.js for visualization
- Bootstrap Icons
- Fuzzy Logic Mamdani algorithm research

---

**TambaQ** - Intelligent Shrimp Farming, Simplified 🦐
