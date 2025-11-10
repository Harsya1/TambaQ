# TambaQ - Smart Shrimp Pond Monitoring System

A web-based monitoring system for shrimp pond water quality using **Fuzzy Mamdani Logic** for automated aerator control.

![TambaQ Dashboard](https://img.shields.io/badge/Laravel-11-red?logo=laravel) ![PHP](https://img.shields.io/badge/PHP-8.2+-blue?logo=php) ![License](https://img.shields.io/badge/License-MIT-green)

## 🎯 Features

- **Real-time Monitoring** - Live sensor data updates every 3 seconds
- **Fuzzy Mamdani AI** - 10 rules for intelligent water quality assessment
- **Automated Control** - Automatic aerator ON/OFF based on AI decisions
- **Data Visualization** - 24-hour historical data charts with Chart.js
- **Secure Authentication** - Login/Register system with validation
- **Responsive Design** - Clean UI with custom color theme

## 📊 Monitored Parameters

| Parameter | Range | Status Levels |
|-----------|-------|---------------|
| **pH** | 0-14 | Low, Normal, High |
| **TDS** | 0-1000 ppm | Low, Normal, High |
| **Turbidity** | 0-100 NTU | Clear, Moderate, Turbid |
| **Water Level** | 0-200 cm | Low, Normal, High |
| **Salinity** | 0-40 ppt | Low, Normal, High |

## 🧠 Fuzzy Logic System

The system uses **Fuzzy Mamdani** with 10 decision rules:

```
Rule 1: IF pH=Low AND TDS=Low → Quality=POOR → Aerator ON
Rule 2: IF pH=Normal AND TDS=Normal AND Turbidity=Clear → Quality=GOOD → Aerator OFF
Rule 3: IF Turbidity=Turbid AND Salinity=High → Quality=POOR → Aerator ON
...
(See FUZZY_LOGIC_DOCUMENTATION.md for complete rules)
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL/SQLite

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

# Configure database in .env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

# Run migrations & seeders
php artisan migrate --seed

# Build assets
npm run build

# Start server
php artisan serve
```

Visit: `http://localhost:8000`

## 🔑 Default Credentials

```
Email: admin@tambaq.com
Password: password123
```

## 📁 Project Structure

```
app/
├── Http/Controllers/
│   ├── AuthController.php      # Authentication logic
│   └── DashboardController.php # Dashboard & API endpoints
├── Models/
│   ├── User.php
│   ├── SensorReading.php
│   ├── Actuator.php
│   └── FuzzyDecision.php
└── Services/
    └── FuzzyMamdaniService.php # Fuzzy logic engine

resources/views/
├── dashboard.blade.php         # Main monitoring dashboard
└── auth/
    ├── login.blade.php
    └── register.blade.php

database/
├── migrations/                 # Database schema
└── seeders/
    └── DatabaseSeeder.php      # Sample data generator
```

## 🎨 Color Theme

- Primary: `#6D94C5` (Soft Blue)
- Secondary: `#CBDCEB` (Light Blue)
- Background: `#F5EFE6` (Cream)

## 📡 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/dashboard` | Main dashboard view |
| `GET` | `/api/latest-sensor-data` | Latest sensor readings |
| `GET` | `/api/chart-data` | 24h historical data |
| `POST` | `/api/process-fuzzy-logic` | Trigger AI evaluation |
| `POST` | `/login` | User authentication |
| `POST` | `/register` | User registration |

## 🛠️ Tech Stack

- **Backend:** Laravel 11, PHP 8.2
- **Frontend:** Blade Templates, Bootstrap 5, Bootstrap Icons
- **Charts:** Chart.js
- **Database:** MySQL/SQLite with Eloquent ORM
- **AI Engine:** Custom Fuzzy Mamdani Implementation

## 📖 Documentation

- [Fuzzy Logic Documentation](FUZZY_LOGIC_DOCUMENTATION.md) - Complete rule definitions and membership functions

## 🧪 Testing

```bash
# Run tests
php artisan test

# Generate sample data
php artisan db:seed
```

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
