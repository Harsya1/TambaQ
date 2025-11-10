<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <title>TambaQ - Riwayat</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        *::-webkit-scrollbar {
            display: none;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            overflow-x: hidden;
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

        .container {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* Sidebar Styles - Dark Mode */
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
            display: flex;
            flex-direction: column;
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
            background: transparent;
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

        .header-title {
            font-size: 28px;
            font-weight: 600;
            color: #667eea;
        }

        /* Stats Cards Row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 25px;
            transition: all 0.3s;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card:nth-child(1)::before {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card:nth-child(2)::before {
            background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card:nth-child(3)::before {
            background: linear-gradient(90deg, #fa709a 0%, #fee140 100%);
        }

        .stat-card:nth-child(4)::before {
            background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.25);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .stat-icon.red {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
        }

        /* Two Column Layout */
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Device Status Box */
        .device-status-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }

        .box-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .box-title i {
            color: #667eea;
        }

        .device-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .device-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #f8faff;
            border-radius: 10px;
            border: 1px solid #e0e7ff;
        }

        .device-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .device-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .device-name {
            font-weight: 600;
            color: #333;
        }

        .device-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .device-status.online {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .device-status.offline {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
        }

        /* Alerts Frequency Chart */
        .chart-container {
            height: 300px;
            margin-top: 20px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
            .two-column {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-logo">
                <i class="bi bi-tsunami"></i> TambaQ
            </div>
            <ul class="sidebar-menu">
                <li onclick="window.location.href='/dashboard'">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </li>
                <li class="active">
                    <i class="bi bi-clock-history"></i>
                    <span>Riwayat</span>
                </li>
            </ul>
            <div class="sidebar-logout">
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="btn-logout">
                        <i class="bi bi-box-arrow-left"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-title">
                    <i class="bi bi-clock-history"></i> Riwayat Device & Alerts
                </div>
            </div>

            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="bi bi-hdd-network"></i>
                    </div>
                    <div class="stat-value" id="totalDevices">5</div>
                    <div class="stat-label">Total Devices</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value" id="devicesWithWarning">2</div>
                    <div class="stat-label">Device with Warning</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="bi bi-bell"></i>
                    </div>
                    <div class="stat-value" id="totalAlerts">24</div>
                    <div class="stat-label">Total Alerts (24h)</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="bi bi-speedometer"></i>
                    </div>
                    <div class="stat-value" id="avgResponseTime">1.2s</div>
                    <div class="stat-label">Avg Response Time</div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="two-column">
                <!-- Device Status -->
                <div class="device-status-box">
                    <div class="box-title">
                        <i class="bi bi-router"></i>
                        Status Devices
                    </div>
                    <div class="device-list">
                        <div class="device-item">
                            <div class="device-info">
                                <div class="device-icon">
                                    <i class="bi bi-droplet"></i>
                                </div>
                                <div class="device-name">pH Sensor</div>
                            </div>
                            <div class="device-status online">
                                <span class="status-dot"></span>
                                Online
                            </div>
                        </div>

                        <div class="device-item">
                            <div class="device-info">
                                <div class="device-icon">
                                    <i class="bi bi-water"></i>
                                </div>
                                <div class="device-name">TDS Sensor</div>
                            </div>
                            <div class="device-status online">
                                <span class="status-dot"></span>
                                Online
                            </div>
                        </div>

                        <div class="device-item">
                            <div class="device-info">
                                <div class="device-icon">
                                    <i class="bi bi-eye"></i>
                                </div>
                                <div class="device-name">Turbidity Sensor</div>
                            </div>
                            <div class="device-status offline">
                                <span class="status-dot"></span>
                                Offline
                            </div>
                        </div>

                        <div class="device-item">
                            <div class="device-info">
                                <div class="device-icon">
                                    <i class="bi bi-tsunami"></i>
                                </div>
                                <div class="device-name">Water Level Sensor</div>
                            </div>
                            <div class="device-status online">
                                <span class="status-dot"></span>
                                Online
                            </div>
                        </div>

                        <div class="device-item">
                            <div class="device-info">
                                <div class="device-icon">
                                    <i class="bi bi-moisture"></i>
                                </div>
                                <div class="device-name">Salinity Sensor</div>
                            </div>
                            <div class="device-status online">
                                <span class="status-dot"></span>
                                Online
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts Frequency -->
                <div class="device-status-box">
                    <div class="box-title">
                        <i class="bi bi-bar-chart"></i>
                        Alerts Frequency (7 Days)
                    </div>
                    <div class="chart-container">
                        <canvas id="alertsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Alerts Frequency Chart
        const ctx = document.getElementById('alertsChart').getContext('2d');
        const alertsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Alerts',
                    data: [12, 19, 15, 25, 22, 18, 24],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
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

        // Update stats dynamically (example)
        function updateStats() {
            fetch('/api/history-stats')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('totalDevices').textContent = data.totalDevices || 5;
                    document.getElementById('devicesWithWarning').textContent = data.devicesWithWarning || 2;
                    document.getElementById('totalAlerts').textContent = data.totalAlerts || 24;
                    document.getElementById('avgResponseTime').textContent = data.avgResponseTime || '1.2s';
                })
                .catch(error => console.error('Error fetching stats:', error));
        }

        // Initial load
        updateStats();
    </script>
</body>
</html>
