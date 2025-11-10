<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            background-color: #F5EFE6;
            color: #333;
            overflow-x: hidden;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        body::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: #6D94C5;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            overflow-x: hidden;
            -ms-overflow-style: none;
            scrollbar-width: none;
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
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            padding: 15px 20px;
            cursor: pointer;
            transition: background-color 0.3s;
            border-left: 4px solid transparent;
        }

        .sidebar-menu li:hover,
        .sidebar-menu li.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #F5EFE6;
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
        }

        .main-content::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }

        /* Header Section */
        .header {
            background-color: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-user {
            font-size: 24px;
            font-weight: 600;
            color: #6D94C5;
        }

        .header-user span {
            color: #333;
            font-weight: 400;
        }

        .btn-setting {
            background-color: #6D94C5;
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
            background-color: #5a7ba8;
        }

        /* Sensor Data Section */
        .sensor-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .sensor-box {
            background-color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            border-top: 4px solid #6D94C5;
            transition: transform 0.3s;
        }

        .sensor-box:hover {
            transform: translateY(-5px);
        }

        .sensor-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sensor-value {
            font-size: 32px;
            font-weight: bold;
            color: #6D94C5;
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
            background-color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .control-title {
            font-size: 18px;
            font-weight: 600;
            color: #6D94C5;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #CBDCEB;
        }

        /* Actuator Control */
        .actuator-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #F5EFE6;
            border-radius: 10px;
        }

        .actuator-name {
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .status-on {
            background-color: #4CAF50;
            color: white;
        }

        .status-off {
            background-color: #f44336;
            color: white;
        }

        /* Fuzzy Decision */
        .fuzzy-content {
            background-color: #CBDCEB;
            padding: 20px;
            border-radius: 10px;
        }

        .fuzzy-status {
            font-size: 20px;
            font-weight: bold;
            color: #6D94C5;
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
            border-top: 1px solid rgba(109, 148, 197, 0.3);
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
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .chart-title {
            font-size: 20px;
            font-weight: 600;
            color: #6D94C5;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #CBDCEB;
        }

        .chart-wrapper {
            position: relative;
            height: 400px;
        }

        /* Logout Button */
        .btn-logout {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: #f44336;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 0.3s;
            margin-left: 10px;
        }

        .btn-logout:hover {
            background-color: #d32f2f;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                TambaQ
            </div>
            <ul class="sidebar-menu">
                <li class="active">Dashboard</li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header Section -->
            <section class="header">
                <div class="header-user">
                    Selamat Datang, <span>{{ $userName }}</span>
                </div>
                <div class="header-buttons">
                    <button class="btn-setting">⚙ Pengaturan</button>
                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn-logout">
                            <i class="bi bi-box-arrow-left"></i>
                            Logout
                        </button>
                    </form>
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
                    <div class="sensor-label">Salinitas</div>
                    <div class="sensor-value" id="salinity">{{ $sensorData->salinity ?? '-' }}</div>
                    <div class="sensor-unit">ppt</div>
                </div>

                <div class="sensor-box">
                    <div class="sensor-label">Kekeruhan</div>
                    <div class="sensor-value" id="turbidity">{{ $sensorData->turbidity ?? '-' }}</div>
                    <div class="sensor-unit">NTU</div>
                </div>
            </section>

            <!-- Control Section -->
            <section class="control-section">
                <!-- Actuator Control -->
                <div class="control-box">
                    <div class="control-title">Kontrol Aktuator</div>
                    @if($aerator)
                        <div class="actuator-item">
                            <span class="actuator-name">{{ $aerator->name }}</span>
                            <span class="status-badge {{ $aerator->status === 'on' ? 'status-on' : 'status-off' }}">
                                {{ $aerator->status === 'on' ? 'ON' : 'OFF' }}
                            </span>
                        </div>
                    @else
                        <div class="no-data">Data aktuator tidak tersedia</div>
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
        </main>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        let sensorChart;

        // Fungsi untuk update data sensor secara real-time
        async function updateSensorData() {
            try {
                const response = await fetch('/api/sensor/latest');
                const data = await response.json();
                
                // Update nilai sensor di UI
                if (data.sensor) {
                    document.getElementById('ph-value').textContent = data.sensor.ph_value || '-';
                    document.getElementById('water-level').textContent = data.sensor.water_level || '-';
                    document.getElementById('tds-value').textContent = data.sensor.tds_value || '-';
                    document.getElementById('salinity').textContent = data.sensor.salinity || '-';
                    document.getElementById('turbidity').textContent = data.sensor.turbidity || '-';
                }

                // Update status aktuator
                if (data.aerator) {
                    const statusBadge = document.querySelector('.status-badge');
                    if (statusBadge) {
                        statusBadge.textContent = data.aerator.status === 'on' ? 'ON' : 'OFF';
                        statusBadge.className = 'status-badge ' + (data.aerator.status === 'on' ? 'status-on' : 'status-off');
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
                const response = await fetch('/api/sensor/chart');
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
                                borderColor: '#6D94C5',
                                backgroundColor: 'rgba(109, 148, 197, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'Jarak Air (cm)',
                                data: data.waterLevelData,
                                borderColor: '#CBDCEB',
                                backgroundColor: 'rgba(203, 220, 235, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'TDS (ppm)',
                                data: data.tdsData,
                                borderColor: '#F5EFE6',
                                backgroundColor: 'rgba(245, 239, 230, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'Salinitas (ppt)',
                                data: data.salinityData,
                                borderColor: '#4CAF50',
                                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'Kekeruhan (NTU)',
                                data: data.turbidityData,
                                borderColor: '#FF9800',
                                backgroundColor: 'rgba(255, 152, 0, 0.1)',
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
                            },
                            title: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error loading chart data:', error);
            }
        }

        // Update data setiap 3 detik
        setInterval(updateSensorData, 3000);
        
        // Load chart saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            loadChartData();
            
            // Refresh chart setiap 30 detik
            setInterval(loadChartData, 30000);
        });
    </script>
</body>
</html>