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
            background-color: #0D1117;
            color: #C9D1D9;
            overflow-x: hidden;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles - Dark Mode */
        .sidebar {
            width: 250px;
            background-color: #161B22;
            color: #C9D1D9;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }

        .sidebar-logo {
            padding: 0 20px 30px;
            font-size: 28px;
            font-weight: bold;
            border-bottom: 2px solid rgba(139, 148, 158, 0.2);
            margin-bottom: 20px;
            color: #58A6FF;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            padding: 15px 20px;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-menu li i {
            font-size: 18px;
        }

        .sidebar-menu li:hover {
            background-color: rgba(88, 166, 255, 0.1);
            border-left-color: #58A6FF;
        }

        .sidebar-menu li.active {
            background-color: rgba(88, 166, 255, 0.15);
            border-left-color: #58A6FF;
            color: #58A6FF;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
            width: calc(100% - 250px);
            background-color: #0D1117;
        }

        /* Header Section */
        .header {
            background-color: #FFFFFF;
            padding: 25px 30px;
            border-radius: 12px;
            border: 1px solid #E1E4E8;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header-title {
            font-size: 28px;
            font-weight: 600;
            color: #1F6FEB;
        }

        .btn-logout {
            background-color: #DA3633;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-logout:hover {
            background-color: #F85149;
        }

        /* Stats Cards Row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #FFFFFF;
            border: 1px solid #E1E4E8;
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(88, 166, 255, 0.2);
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
            background-color: rgba(88, 166, 255, 0.1);
            color: #58A6FF;
        }

        .stat-icon.orange {
            background-color: rgba(242, 140, 40, 0.1);
            color: #F2994A;
        }

        .stat-icon.red {
            background-color: rgba(248, 81, 73, 0.1);
            color: #F85149;
        }

        .stat-icon.green {
            background-color: rgba(63, 185, 80, 0.1);
            color: #3FB950;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #24292F;
        }

        .stat-label {
            font-size: 14px;
            color: #57606A;
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
            background-color: #FFFFFF;
            border: 1px solid #E1E4E8;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .box-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1F6FEB;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .box-title i {
            color: #58A6FF;
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
            background-color: #F6F8FA;
            border-radius: 8px;
            border: 1px solid #E1E4E8;
        }

        .device-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .device-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background-color: rgba(88, 166, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #58A6FF;
            font-size: 18px;
        }

        .device-name {
            font-weight: 600;
            color: #24292F;
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
            background-color: rgba(63, 185, 80, 0.1);
            color: #3FB950;
        }

        .device-status.offline {
            background-color: rgba(248, 81, 73, 0.1);
            color: #F85149;
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
                <li>
                    <i class="bi bi-gear"></i>
                    <span>Pengaturan</span>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-title">
                    <i class="bi bi-clock-history"></i> Riwayat Device & Alerts
                </div>
                <form method="POST" action="/logout" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-logout">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </button>
                </form>
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
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Alerts',
                    data: [12, 19, 15, 25, 22, 18, 24],
                    backgroundColor: 'rgba(88, 166, 255, 0.8)',
                    borderColor: '#58A6FF',
                    borderWidth: 1,
                    borderRadius: 6
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
                            color: '#E1E4E8'
                        },
                        ticks: {
                            color: '#57606A'
                        }
                    },
                    x: {
                        grid: {
                            color: '#E1E4E8'
                        },
                        ticks: {
                            color: '#57606A'
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
