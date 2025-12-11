<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Auto refresh data via JavaScript, bukan page reload -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <title>TambaQ - Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Hide all scrollbars */
        * {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }

        *::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
            width: 0;
            height: 0;
        }

        html {
            overflow: -moz-scrollbars-none;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        html::-webkit-scrollbar {
            width: 0 !important;
            display: none;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            overflow-x: hidden;
            -ms-overflow-style: none;
            scrollbar-width: none;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            width: 600px;
            height: 600px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            top: -300px;
            right: -300px;
            opacity: 0.3;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 50%;
            bottom: -250px;
            left: -250px;
            opacity: 0.3;
            z-index: 0;
        }

        body::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }

        .container {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            color: #333;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            overflow-x: hidden;
            -ms-overflow-style: none;
            scrollbar-width: none;
            display: flex;
            flex-direction: column;
        }

        .sidebar::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }

        .sidebar-logo {
            padding: 0 20px 30px;
            font-size: 28px;
            font-weight: bold;
            border-bottom: 2px solid #e0e7ff;
            margin-bottom: 20px;
            color: #667eea;
        }

        .sidebar-menu {
            list-style: none;
            flex: 1;
        }

        .sidebar-menu li {
            padding: 15px 20px;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #666;
        }

        .sidebar-menu li i {
            font-size: 18px;
        }

        .sidebar-menu li:hover {
            background-color: rgba(102, 126, 234, 0.1);
            border-left-color: #667eea;
            color: #667eea;
        }

        .sidebar-menu li.active {
            background-color: rgba(102, 126, 234, 0.15);
            border-left-color: #667eea;
            color: #667eea;
            font-weight: 600;
        }

        .sidebar-logout {
            padding: 20px;
            border-top: 2px solid #e0e7ff;
            margin-top: auto;
        }

        .sidebar-logout form {
            margin: 0;
        }

        .sidebar-logout .btn-logout {
            width: 100%;
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(250, 112, 154, 0.4);
        }

        .sidebar-logout .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(250, 112, 154, 0.6);
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
            width: calc(100% - 250px);
            overflow-y: auto;
            overflow-x: hidden;
            -ms-overflow-style: none;
            scrollbar-width: none;
            background: transparent;
        }

        .main-content::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }

        /* Header Section */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px 30px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }

        .header-user {
            font-size: 24px;
            font-weight: 600;
            color: #667eea;
        }

        .header-user span {
            color: #333;
            font-weight: 400;
        }

        .btn-setting {
            background-color: #DA3633;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn-setting:hover {
            background-color: #F85149;
        }

        /* Sensor Data Section */
        .sensor-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .sensor-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            position: relative;
            overflow: hidden;
        }

        .sensor-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .sensor-box:nth-child(1)::before {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .sensor-box:nth-child(2)::before {
            background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
        }

        .sensor-box:nth-child(3)::before {
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
        }

        .sensor-box:nth-child(4)::before {
            background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
        }

        .sensor-box:nth-child(5)::before {
            background: linear-gradient(90deg, #fa709a 0%, #fee140 100%);
        }

        .sensor-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.25);
        }

        .sensor-label {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .sensor-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .sensor-unit {
            color: #999;
            font-size: 14px;
        }

        /* Control Section */
        .control-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .control-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }

        .control-title {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e7ff;
        }

        /* Water Quality Category */
        .category-display {
            text-align: center;
            padding: 30px 20px;
        }

        .category-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .category-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .category-score {
            font-size: 18px;
            color: #666;
            margin-bottom: 15px;
        }

        .category-badge {
            display: inline-block;
            padding: 8px 24px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .category-excellent {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .category-good {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .category-fair {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .category-poor {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .category-critical {
            background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%);
            color: white;
            border: none;
        }

        /* Fuzzy Decision */
        .fuzzy-content {
            background-color: #f8faff;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e0e7ff;
        }

        .fuzzy-status {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
        }

        .fuzzy-recommendation {
            color: #333;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .fuzzy-details {
            font-size: 13px;
            color: #666;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e7ff;
        }

        .no-data {
            color: #999;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }

        /* Chart Section */
        .chart-section {
            margin-top: 30px;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }

        .chart-title {
            font-size: 20px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e7ff;
        }

        .chart-wrapper {
            position: relative;
            height: 400px;
        }

        /* Analytics Widgets */
        .analytics-section {
            margin-top: 30px;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .analytics-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }

        .analytics-title {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e7ff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Trend Toggle */
        .trend-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .trend-toggle button {
            flex: 1;
            padding: 10px 20px;
            border: 2px solid #667eea;
            background: transparent;
            color: #667eea;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .trend-toggle button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .trend-toggle button:hover {
            transform: translateY(-2px);
        }

        /* Correlation Cards */
        .correlation-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .correlation-card {
            padding: 15px;
            border-radius: 10px;
            background: #f8faff;
            border: 1px solid #e0e7ff;
            transition: all 0.3s;
        }

        .correlation-card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .correlation-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .correlation-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .correlation-level {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 12px;
            display: inline-block;
            font-weight: 600;
        }

        .corr-low { 
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }
        
        .corr-moderate { 
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .corr-high { 
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        /* Forecast Cards */
        .forecast-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .forecast-card {
            padding: 20px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 10px;
            border: 2px solid #e0e7ff;
            text-align: center;
        }

        .forecast-sensor {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .forecast-value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .forecast-unit {
            font-size: 12px;
            color: #999;
        }

        .confidence-badge {
            margin-top: 15px;
            padding: 6px 15px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .conf-high {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .conf-medium {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .conf-low {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        /* Export Section */
        .export-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .date-input {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            border: 2px solid #e0e7ff;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
        }

        .date-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .export-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .export-csv {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .export-pdf {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="bi bi-tsunami"></i> TambaQ
            </div>
            <ul class="sidebar-menu">
                <li class="active">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </li>
                <li onclick="window.location.href='/history'">
                    <i class="bi bi-clock-history"></i>
                    <span>Riwayat</span>
                </li>
            </ul>
            <div class="sidebar-logout">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-logout">
                        <i class="bi bi-box-arrow-left"></i>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header Section -->
            <section class="header">
                <div class="header-user">
                    Selamat Datang, <span>{{ $userName }}</span>
                </div>
            </section>

            <!-- Sensor Data Section -->
            <section class="sensor-section">
                <div class="sensor-box">
                    <div class="sensor-label">pH Air</div>
                    <div class="sensor-value" id="ph-value">{{ $sensorData->ph_value ?? '-' }}</div>
                    <div class="sensor-unit">pH</div>
                </div>

                <div class="sensor-box">
                    <div class="sensor-label">Jarak Permukaan Air</div>
                    <div class="sensor-value" id="water-level">{{ $sensorData->water_level ?? '-' }}</div>
                    <div class="sensor-unit">cm</div>
                </div>

                <div class="sensor-box">
                    <div class="sensor-label">TDS</div>
                    <div class="sensor-value" id="tds-value">{{ $sensorData->tds_value ?? '-' }}</div>
                    <div class="sensor-unit">ppm</div>
                </div>

                <div class="sensor-box">
                    <div class="sensor-label">Kekeruhan</div>
                    <div class="sensor-value" id="turbidity">{{ $sensorData->turbidity ?? '-' }}</div>
                    <div class="sensor-unit">NTU</div>
                </div>
            </section>

            <!-- Control Section -->
            <section class="control-section">
                <!-- Water Quality Category -->
                <div class="control-box">
                    <div class="control-title">Kategori Kualitas Air</div>
                    @if($fuzzyDecision && isset($fuzzyDecision->sensorReading))
                        @php
                            $score = $fuzzyDecision->sensorReading->water_quality_score ?? 0;
                            $category = 'Critical';
                            $icon = '❌';
                            $badgeClass = 'category-critical';
                            $color = '#ff0844';
                            
                            if ($score >= 85) {
                                $category = 'Excellent';
                                $icon = '⭐';
                                $badgeClass = 'category-excellent';
                                $color = '#43e97b';
                            } elseif ($score >= 65) {
                                $category = 'Good';
                                $icon = '✅';
                                $badgeClass = 'category-good';
                                $color = '#4facfe';
                            } elseif ($score >= 45) {
                                $category = 'Fair';
                                $icon = '⚠️';
                                $badgeClass = 'category-fair';
                                $color = '#f093fb';
                            } elseif ($score >= 25) {
                                $category = 'Poor';
                                $icon = '⚠️';
                                $badgeClass = 'category-poor';
                                $color = '#fa709a';
                            }
                        @endphp
                        <div class="category-display">
                            <div class="category-icon">{{ $icon }}</div>
                            <div class="category-name" data-color="{{ $color }}">{{ $category }}</div>
                            <div class="category-score">Score: {{ number_format($score, 1) }}/100</div>
                            <span class="category-badge {{ $badgeClass }}">{{ $category }}</span>
                        </div>
                    @else
                        <div class="no-data">Data kualitas air tidak tersedia</div>
                    @endif
                </div>

                <!-- Fuzzy Logic Decision -->
                <div class="control-box">
                    <div class="control-title">Sistem Fuzzy Mamdani</div>
                    @if($fuzzyDecision)
                        <div class="fuzzy-content">
                            <div class="fuzzy-status">
                                Status: {{ $fuzzyDecision->water_quality_status }}
                            </div>
                            <div class="fuzzy-recommendation">
                                <strong>Rekomendasi:</strong><br>
                                {{ $fuzzyDecision->recommendation }}
                            </div>
                            @if($fuzzyDecision->fuzzy_details)
                                <div class="fuzzy-details">
                                    {{ $fuzzyDecision->fuzzy_details }}
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="no-data">Belum ada keputusan fuzzy</div>
                    @endif
                </div>
            </section>

            <!-- Chart Section -->
            <section class="chart-section">
                <div class="chart-container">
                    <div class="chart-title">Riwayat Data Sensor (24 Jam)</div>
                    <div class="chart-wrapper">
                        <canvas id="sensorChart"></canvas>
                    </div>
                </div>
            </section>

            <!-- Analytics Section -->
            <section class="analytics-section">
                <!-- Trend Score Widget -->
                <div class="analytics-box" style="margin-bottom: 30px;">
                    <div class="analytics-title">
                        <i class="bi bi-graph-up-arrow"></i>
                        Water Quality Score Trend
                    </div>
                    <div class="trend-toggle">
                        <button class="active" id="btn7days" onclick="switchTrend('7days')">7 Hari</button>
                        <button id="btn30days" onclick="switchTrend('30days')">30 Hari</button>
                    </div>
                    <div class="chart-wrapper" style="height: 300px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- Two Column Layout for Correlation & Forecast -->
                <div class="analytics-grid">
                    <!-- Correlation Analysis Widget -->
                    <div class="analytics-box">
                        <div class="analytics-title">
                            <i class="bi bi-diagram-3"></i>
                            Correlation Analysis
                        </div>
                        <div class="correlation-grid" id="correlationGrid">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Forecast Widget -->
                    <div class="analytics-box">
                        <div class="analytics-title">
                            <i class="bi bi-lightning"></i>
                            Forecast (3 Hours)
                        </div>
                        <div class="forecast-grid" id="forecastGrid">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Export Report Widget -->
                <div class="analytics-box">
                    <div class="analytics-title">
                        <i class="bi bi-download"></i>
                        Export Reports
                    </div>
                    <div class="export-controls">
                        <input type="date" class="date-input" id="exportStartDate" />
                        <input type="date" class="date-input" id="exportEndDate" />
                        <button class="export-btn export-csv" onclick="exportData('csv')">
                            <i class="bi bi-file-earmark-spreadsheet"></i>
                            Export CSV
                        </button>
                        <button class="export-btn export-pdf" onclick="exportData('pdf')">
                            <i class="bi bi-file-earmark-pdf"></i>
                            Export PDF
                        </button>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Set category name color from data attribute on page load
        document.addEventListener('DOMContentLoaded', function() {
            const categoryName = document.querySelector('.category-name');
            if (categoryName && categoryName.dataset.color) {
                categoryName.style.color = categoryName.dataset.color;
            }
        });

        let sensorChart;

        // Fungsi untuk update data sensor secara real-time
        async function updateSensorData() {
            try {
                console.log('Fetching sensor data from /api/sensor/latest...');
                console.log('Document cookie:', document.cookie);
                
                const response = await fetch('/api/sensor/latest', {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', [...response.headers.entries()]);
                
                if (!response.ok) {
                    const text = await response.text();
                    console.error('API response not OK:', response.status, response.statusText);
                    console.error('Response body:', text.substring(0, 500));
                    
                    // Show user-friendly error message
                    if (response.status === 429) {
                        console.warn('⚠️ Firebase quota exceeded. Data will refresh when quota resets.');
                        // You can show a toast notification here
                    } else if (response.status === 404) {
                        console.warn('⚠️ Sensor data not found. Waiting for ESP32 data...');
                    }
                    
                    return;
                }
                
                const data = await response.json();
                console.log('Sensor data received:', data);
                
                // Check if data is from cache or quota exceeded
                if (data.status === 'quota_exceeded') {
                    console.warn('⚠️ Displaying fallback data - Firebase quota exceeded');
                } else if (data.status === 'ok') {
                    console.log('✓ Fresh data received');
                }
                
                // Update nilai sensor di UI
                if (data.sensor) {
                    document.getElementById('ph-value').textContent = data.sensor.ph_value || '-';
                    document.getElementById('water-level').textContent = data.sensor.water_level || '-';
                    document.getElementById('tds-value').textContent = data.sensor.tds_value || '-';
                    document.getElementById('turbidity').textContent = data.sensor.turbidity || '-';
                }

                // Update water quality category
                if (data.sensor && data.sensor.water_quality_score !== undefined) {
                    const score = parseFloat(data.sensor.water_quality_score) || 0;
                    let category = 'Critical';
                    let icon = '❌';
                    let badgeClass = 'category-critical';
                    let color = '#ff0844';
                    
                    if (score >= 85) {
                        category = 'Excellent';
                        icon = '⭐';
                        badgeClass = 'category-excellent';
                        color = '#43e97b';
                    } else if (score >= 65) {
                        category = 'Good';
                        icon = '✅';
                        badgeClass = 'category-good';
                        color = '#4facfe';
                    } else if (score >= 45) {
                        category = 'Fair';
                        icon = '⚠️';
                        badgeClass = 'category-fair';
                        color = '#f093fb';
                    } else if (score >= 25) {
                        category = 'Poor';
                        icon = '⚠️';
                        badgeClass = 'category-poor';
                        color = '#fa709a';
                    }
                    
                    const categoryIcon = document.querySelector('.category-icon');
                    const categoryName = document.querySelector('.category-name');
                    const categoryScore = document.querySelector('.category-score');
                    const categoryBadge = document.querySelector('.category-badge');
                    
                    if (categoryIcon) categoryIcon.textContent = icon;
                    if (categoryName) {
                        categoryName.textContent = category;
                        categoryName.style.color = color;
                    }
                    if (categoryScore) categoryScore.textContent = 'Score: ' + score.toFixed(1) + '/100';
                    if (categoryBadge) {
                        categoryBadge.textContent = category;
                        categoryBadge.className = 'category-badge ' + badgeClass;
                    }
                }

                // Update fuzzy decision
                if (data.fuzzyDecision) {
                    const fuzzyStatus = document.querySelector('.fuzzy-status');
                    const fuzzyRecommendation = document.querySelector('.fuzzy-recommendation');
                    
                    if (fuzzyStatus) {
                        fuzzyStatus.innerHTML = 'Status: ' + data.fuzzyDecision.water_quality_status;
                    }
                    if (fuzzyRecommendation) {
                        fuzzyRecommendation.innerHTML = '<strong>Rekomendasi:</strong><br>' + data.fuzzyDecision.recommendation;
                    }
                }
            } catch (error) {
                console.error('Error fetching sensor data:', error);
            }
        }

        // Fungsi untuk load dan render chart
        async function loadChartData() {
            try {
                const response = await fetch('/api/sensor/chart', {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                
                const ctx = document.getElementById('sensorChart').getContext('2d');
                
                // Hapus chart lama jika ada
                if (sensorChart) {
                    sensorChart.destroy();
                }
                
                // Buat chart baru
                sensorChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'pH',
                                data: data.phData,
                                borderColor: '#667eea',
                                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'Jarak Air (cm)',
                                data: data.waterLevelData,
                                borderColor: '#4facfe',
                                backgroundColor: 'rgba(79, 172, 254, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'TDS (ppm)',
                                data: data.tdsData,
                                borderColor: '#f093fb',
                                backgroundColor: 'rgba(240, 147, 251, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'Kekeruhan (NTU)',
                                data: data.turbidityData,
                                borderColor: '#fa709a',
                                backgroundColor: 'rgba(250, 112, 154, 0.1)',
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: '#333'
                                }
                            },
                            title: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                grid: {
                                    color: '#e0e7ff'
                                },
                                ticks: {
                                    color: '#666'
                                }
                            },
                            x: {
                                grid: {
                                    color: '#e0e7ff'
                                },
                                ticks: {
                                    color: '#666'
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error loading chart data:', error);
            }
        }

        // Update sensor data setiap 5 detik via AJAX (bukan page reload)
        setInterval(updateSensorData, 5000);
        
        // Load chart saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            // Initial load sensor data
            updateSensorData();
            loadChartData();
            
            // Refresh chart setiap 2 menit (optimized untuk Firebase quota)
            setInterval(loadChartData, 120000);
        });

        // Load analytics setelah window fully loaded (termasuk Chart.js)
        window.addEventListener('load', function() {
            console.log('Window fully loaded, initializing analytics...');
            
            // Wait a bit more to ensure everything is ready
            setTimeout(() => {
                console.log('Loading analytics data...');
                loadTrendData('7days');
                loadCorrelationData();
                loadForecastData();
                setDefaultExportDates();
            }, 500);

            // Refresh analytics every 10 minutes (optimized untuk Firebase quota)
            setInterval(() => {
                const activeTrend = document.querySelector('.trend-toggle button.active').id === 'btn7days' ? '7days' : '30days';
                loadTrendData(activeTrend);
                loadCorrelationData();
                loadForecastData();
            }, 600000); // 10 minutes
        });

        // Analytics: Trend Chart
        let trendChart;
        async function loadTrendData(period) {
            console.log(`loadTrendData called with period: ${period}`);
            
            try {
                // Check if canvas exists
                const canvas = document.getElementById('trendChart');
                if (!canvas) {
                    console.error('Trend chart canvas not found');
                    return;
                }
                
                console.log('Canvas found, fetching data...');

                const response = await fetch(`/api/trend/${period}`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                console.log('Trend API response status:', response.status);
                
                if (!response.ok) {
                    console.error('Trend API error:', response.status, response.statusText);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                console.log(`Trend data loaded for ${period}:`, data);
                console.log('Labels count:', data.labels ? data.labels.length : 0);
                console.log('Scores count:', data.scores ? data.scores.length : 0);
                
                const ctx = canvas.getContext('2d');
                
                if (trendChart) {
                    console.log('Destroying existing chart...');
                    trendChart.destroy();
                }
                
                console.log('Creating new chart...');
                trendChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Water Quality Score',
                            data: data.scores,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.2)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 3,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                grid: {
                                    color: '#e0e7ff'
                                },
                                ticks: {
                                    color: '#666'
                                }
                            },
                            x: {
                                grid: {
                                    color: '#e0e7ff'
                                },
                                ticks: {
                                    color: '#666',
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        }
                    }
                });
                
                console.log('Chart created successfully!');
            } catch (error) {
                console.error('Error loading trend data:', error);
            }
        }

        function switchTrend(period) {
            // Update button states
            document.getElementById('btn7days').classList.remove('active');
            document.getElementById('btn30days').classList.remove('active');
            document.getElementById(`btn${period}`).classList.add('active');
            
            // Load new data
            loadTrendData(period);
        }

        // Analytics: Correlation
        async function loadCorrelationData() {
            const grid = document.getElementById('correlationGrid');
            if (!grid) {
                console.error('Correlation grid element not found');
                return;
            }
            
            // Default/fallback data
            let data = {
                'pH_TDS': 0.45,
                'TDS_Turbidity': 0.71,
                'pH_Turbidity': -0.12
            };
            
            try {
                console.log('Loading correlation data...');
                const response = await fetch('/api/correlation', {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const responseData = await response.json();
                    console.log('Correlation data received:', responseData);
                    data = responseData;
                } else {
                    console.warn('Correlation API returned error, using fallback data');
                }
            } catch (error) {
                console.error('Error loading correlation data:', error);
                console.log('Using fallback correlation data');
            }
            
            // Render correlation cards
            grid.innerHTML = '';
            
            const correlations = [
                { label: 'pH - TDS', value: data.pH_TDS ?? 0.45 },
                { label: 'TDS - Turbidity', value: data.TDS_Turbidity ?? 0.71 },
                { label: 'pH - Turbidity', value: data.pH_Turbidity ?? -0.12 }
            ];
            
            correlations.forEach(corr => {
                const value = parseFloat(corr.value) || 0;
                const level = getCorrelationLevel(Math.abs(value));
                const card = document.createElement('div');
                card.className = 'correlation-card';
                card.innerHTML = `
                    <div class="correlation-label">${corr.label}</div>
                    <div class="correlation-value">${value.toFixed(2)}</div>
                    <span class="correlation-level ${level.class}">${level.text}</span>
                `;
                grid.appendChild(card);
            });
            
            console.log('Correlation cards rendered successfully');
        }

        function getCorrelationLevel(value) {
            if (value >= 0.6) {
                return { class: 'corr-high', text: 'Tinggi' };
            } else if (value >= 0.3) {
                return { class: 'corr-moderate', text: 'Moderat' };
            } else {
                return { class: 'corr-low', text: 'Rendah' };
            }
        }

        // Analytics: Forecast
        async function loadForecastData() {
            try {
                const response = await fetch('/api/forecast', {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                
                const grid = document.getElementById('forecastGrid');
                grid.innerHTML = '';
                
                const forecasts = [
                    { sensor: 'pH', value: data.pH_next3h, unit: 'pH' },
                    { sensor: 'TDS', value: data.TDS_next3h, unit: 'ppm' },
                    { sensor: 'Turbidity', value: data.turbidity_next3h, unit: 'NTU' }
                ];
                
                forecasts.forEach(forecast => {
                    const card = document.createElement('div');
                    card.className = 'forecast-card';
                    card.innerHTML = `
                        <div class="forecast-sensor">${forecast.sensor}</div>
                        <div class="forecast-value">${forecast.value}</div>
                        <div class="forecast-unit">${forecast.unit}</div>
                    `;
                    grid.appendChild(card);
                });
                
                // Add confidence badge after all cards
                const confidenceClass = data.confidence === 'high' ? 'conf-high' : 
                                      data.confidence === 'medium' ? 'conf-medium' : 'conf-low';
                const confidenceText = data.confidence === 'high' ? 'Confidence: Tinggi' : 
                                      data.confidence === 'medium' ? 'Confidence: Sedang' : 'Confidence: Rendah';
                
                const badge = document.createElement('div');
                badge.style.gridColumn = '1 / -1';
                badge.style.textAlign = 'center';
                badge.innerHTML = `<span class="confidence-badge ${confidenceClass}">${confidenceText}</span>`;
                grid.appendChild(badge);
            } catch (error) {
                console.error('Error loading forecast data:', error);
            }
        }

        // Analytics: Export
        function setDefaultExportDates() {
            const today = new Date();
            const sevenDaysAgo = new Date(today);
            sevenDaysAgo.setDate(today.getDate() - 7);
            
            document.getElementById('exportStartDate').valueAsDate = sevenDaysAgo;
            document.getElementById('exportEndDate').valueAsDate = today;
        }

        function exportData(format) {
            const startDate = document.getElementById('exportStartDate').value;
            const endDate = document.getElementById('exportEndDate').value;
            
            if (!startDate || !endDate) {
                alert('Pilih tanggal mulai dan akhir terlebih dahulu!');
                return;
            }
            
            const url = `/api/export/${format}?start=${startDate}&end=${endDate}`;
            window.open(url, '_blank');
        }
    </script>
</body>
</html>