<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Quality Report - TambaQ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            padding: 40px;
            color: #333;
            line-height: 1.6;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            z-index: 1000;
        }

        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header .subtitle {
            color: #666;
            font-size: 16px;
        }

        .period {
            background: #f8faff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }

        .period strong {
            color: #667eea;
        }

        .summary-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 20px;
            color: #667eea;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e7ff;
            padding-bottom: 10px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: #f8faff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e0e7ff;
        }

        .summary-card .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .summary-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .summary-card .range {
            font-size: 11px;
            color: #999;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e7ff;
            font-size: 12px;
        }

        .data-table tr:nth-child(even) {
            background: #f8faff;
        }

        .data-table tr:hover {
            background: #e0e7ff;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e0e7ff;
            text-align: center;
            color: #999;
            font-size: 12px;
        }

        .score-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 12px;
        }

        .score-excellent {
            background: #43e97b;
            color: white;
        }

        .score-good {
            background: #4facfe;
            color: white;
        }

        .score-fair {
            background: #f093fb;
            color: white;
        }

        .score-poor {
            background: #fa709a;
            color: white;
        }

        @media print {
            .print-button {
                display: none;
            }

            body {
                padding: 20px;
            }
            
            .data-table {
                font-size: 10px;
            }

            .data-table th,
            .data-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <button class="print-button" onclick="window.print()">🖨️ Print / Save as PDF</button>

    <!-- Header -->
    <div class="header">
        <h1>🐟 TambaQ Water Quality Report</h1>
        <div class="subtitle">Laporan Kualitas Air Tambak</div>
    </div>

    <!-- Period -->
    <div class="period">
        <strong>Periode:</strong> {{ \Carbon\Carbon::parse($start)->format('d F Y') }} - {{ \Carbon\Carbon::parse($end)->format('d F Y') }}
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <h2 class="section-title">📊 Ringkasan Statistik</h2>
        
        <div class="summary-grid">
            <div class="summary-card">
                <div class="label">Water Quality Score</div>
                <div class="value">{{ $summary['avg_score'] }}</div>
                <div class="range">Rata-rata</div>
            </div>
            
            <div class="summary-card">
                <div class="label">pH Air</div>
                <div class="value">{{ $summary['avg_ph'] }}</div>
                <div class="range">Min: {{ $summary['min_ph'] }} | Max: {{ $summary['max_ph'] }}</div>
            </div>
            
            <div class="summary-card">
                <div class="label">TDS (ppm)</div>
                <div class="value">{{ $summary['avg_tds'] }}</div>
                <div class="range">Min: {{ $summary['min_tds'] }} | Max: {{ $summary['max_tds'] }}</div>
            </div>
            
            <div class="summary-card">
                <div class="label">Turbidity (NTU)</div>
                <div class="value">{{ $summary['avg_turbidity'] }}</div>
                <div class="range">Min: {{ $summary['min_turbidity'] }} | Max: {{ $summary['max_turbidity'] }}</div>
            </div>
            
            <div class="summary-card">
                <div class="label">Salinity (ppt)</div>
                <div class="value">{{ $summary['avg_salinity'] }}</div>
                <div class="range">Rata-rata Salinitas</div>
            </div>
            
            <div class="summary-card">
                <div class="label">Total Records</div>
                <div class="value">{{ $data->count() }}</div>
                <div class="range">Data Points</div>
            </div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="data-section">
        <h2 class="section-title">📋 Detail Data</h2>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Score</th>
                    <th>pH</th>
                    <th>TDS (ppm)</th>
                    <th>Turbidity (NTU)</th>
                    <th>Salinity (ppt)</th>
                    <th>Water Level (cm)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row->recorded_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($row->score >= 80)
                            <span class="score-badge score-excellent">{{ $row->score }}</span>
                        @elseif($row->score >= 60)
                            <span class="score-badge score-good">{{ $row->score }}</span>
                        @elseif($row->score >= 40)
                            <span class="score-badge score-fair">{{ $row->score }}</span>
                        @else
                            <span class="score-badge score-poor">{{ $row->score }}</span>
                        @endif
                    </td>
                    <td>{{ $row->ph_value ?? '-' }}</td>
                    <td>{{ $row->tds_value ?? '-' }}</td>
                    <td>{{ $row->turbidity ?? '-' }}</td>
                    <td>{{ $row->salinity ?? '-' }}</td>
                    <td>{{ $row->water_level ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #999;">Tidak ada data untuk periode ini</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p><strong>TambaQ</strong> - Smart Aquaculture Monitoring System</p>
        <p>Digenerate pada: {{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</p>
    </div>
</body>
</html>
