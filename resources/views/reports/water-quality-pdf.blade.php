<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Water Quality Report - TambaQ</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            padding: 15px;
            color: #333;
            line-height: 1.4;
            font-size: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .header .subtitle {
            color: #666;
            font-size: 11px;
        }

        .period {
            background: #f8faff;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            font-size: 10px;
        }
        
        .realtime-badge {
            background: #43e97b;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 13px;
            color: #667eea;
            margin-bottom: 12px;
            margin-top: 15px;
            border-bottom: 2px solid #e0e7ff;
            padding-bottom: 8px;
            clear: both;
        }
        
        .page-break {
            page-break-after: always;
        }

        .realtime-card {
            background: #f0f9ff;
            padding: 12px;
            border: 2px solid #43e97b;
            margin-bottom: 10px;
        }
        
        .realtime-grid {
            width: 100%;
        }
        
        .realtime-item {
            background: white;
            padding: 8px;
            border: 1px solid #e0e7ff;
            float: left;
            width: 23%;
            margin-right: 2.6%;
            margin-bottom: 8px;
            min-height: 50px;
        }
        
        .realtime-item:nth-child(4n) {
            margin-right: 0;
        }

        .summary-card {
            background: #f8faff;
            padding: 10px;
            border: 1px solid #e0e7ff;
            float: left;
            width: 32%;
            margin-right: 2%;
            margin-bottom: 10px;
            min-height: 60px;
        }

        .summary-card:nth-child(3n) {
            margin-right: 0;
        }

        .summary-card .label,
        .realtime-item .label {
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .summary-card .value,
        .realtime-item .value {
            font-size: 15px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 3px;
        }
        
        .realtime-item .value {
            color: #43e97b;
        }

        .summary-card .range,
        .realtime-item .unit {
            font-size: 7px;
            color: #999;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th {
            background: #667eea;
            color: white;
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
        }

        .data-table td {
            padding: 5px 4px;
            border-bottom: 1px solid #e0e7ff;
            font-size: 7px;
        }

        .data-table tr:nth-child(even) {
            background: #f8faff;
        }

        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #e0e7ff;
            text-align: center;
            color: #999;
            font-size: 8px;
        }

        .score-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7px;
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
        
        .score-critical {
            background: #ff4757;
            color: white;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>TambaQ Water Quality Report</h1>
        <div class="subtitle">Laporan Kualitas Air Tambak Udang Vaname</div>
    </div>

    <!-- Generated Time -->
    <div class="period">
        <strong>Generated:</strong> {{ $generatedAt->format('d F Y H:i:s') }}
    </div>

    <!-- ============= PAGE 1: REAL-TIME DATA ============= -->
    <div class="realtime-section">
        <span class="realtime-badge">LIVE - Data Real-Time</span>
        <h2 class="section-title">Kondisi Saat Ini (Current Status)</h2>
        
        <div class="realtime-card">
            <div class="clearfix">
                <div class="realtime-item">
                    <div class="label">pH Level</div>
                    <div class="value">{{ number_format($realtimeData['ph_value'] ?? 0, 2) }}</div>
                    <div class="unit">pH</div>
                </div>
                
                <div class="realtime-item">
                    <div class="label">TDS</div>
                    <div class="value">{{ number_format($realtimeData['tds_value'] ?? 0, 2) }}</div>
                    <div class="unit">PPM</div>
                </div>
                
                <div class="realtime-item">
                    <div class="label">Turbidity</div>
                    <div class="value">{{ number_format($realtimeData['turbidity'] ?? 0, 2) }}</div>
                    <div class="unit">NTU</div>
                </div>
                
                <div class="realtime-item">
                    <div class="label">Water Level</div>
                    <div class="value">{{ number_format($realtimeData['ultrasonic_value'] ?? 0, 2) }}</div>
                    <div class="unit">cm</div>
                </div>
                
                <div class="realtime-item">
                    <div class="label">Salinity</div>
                    <div class="value">{{ number_format($realtimeData['salinity_ppt'] ?? 0, 2) }}</div>
                    <div class="unit">PPT</div>
                </div>
                
                <div class="realtime-item">
                    <div class="label">Water Quality Score</div>
                    <div class="value">{{ number_format($realtimeData['water_quality_score'] ?? 0, 2) }}</div>
                    <div class="unit">/100</div>
                </div>
                
                <div class="realtime-item">
                    <div class="label">Category</div>
                    <div class="value" style="font-size: 12px;">{{ $realtimeData['water_quality_status'] ?? 'Unknown' }}</div>
                    <div class="unit">Status</div>
                </div>
                
                <div class="realtime-item">
                    <div class="label">Last Update</div>
                    <div class="value" style="font-size: 10px;">{{ isset($realtimeData['timestamp']) ? \Carbon\Carbon::parse($realtimeData['timestamp'])->format('H:i:s') : '-' }}</div>
                    <div class="unit">Time</div>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 15px; padding: 10px; background: #fff9e6; border-left: 4px solid #ffc107;">
            <strong style="color: #f57c00;">Recommendation:</strong><br>
            <span style="font-size: 9px;">{{ $realtimeData['recommendation'] ?? 'Sistem monitoring berjalan normal.' }}</span>
        </div>
    </div>

    <!-- Page Break -->
    <div class="page-break"></div>

    <!-- ============= PAGE 2+: HISTORICAL DATA ============= -->
    <div class="header" style="margin-top: 0;">
        <h1>Historical Data Analysis</h1>
        <div class="subtitle">Analisis Data Periode Tertentu</div>
    </div>

    <!-- Period -->
    <div class="period">
        <strong>Periode Data:</strong> {{ \Carbon\Carbon::parse($start)->format('d F Y') }} - {{ \Carbon\Carbon::parse($end)->format('d F Y') }}
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <h2 class="section-title">Ringkasan Statistik ({{ $summary['total_records'] ?? 0 }} Records)</h2>
        
        <div class="clearfix">
            <div class="summary-card">
                <div class="label">Water Quality Score</div>
                <div class="value">{{ $summary['avg_score'] ?? 0 }}</div>
                <div class="range">Rata-rata</div>
            </div>
            
            <div class="summary-card">
                <div class="label">pH Air</div>
                <div class="value">{{ $summary['avg_ph'] ?? 0 }}</div>
                <div class="range">Min: {{ $summary['min_ph'] ?? 0 }} | Max: {{ $summary['max_ph'] ?? 0 }}</div>
            </div>
            
            <div class="summary-card">
                <div class="label">TDS (ppm)</div>
                <div class="value">{{ $summary['avg_tds'] ?? 0 }}</div>
                <div class="range">Min: {{ $summary['min_tds'] ?? 0 }} | Max: {{ $summary['max_tds'] ?? 0 }}</div>
            </div>
            
            <div class="summary-card">
                <div class="label">Turbidity (NTU)</div>
                <div class="value">{{ $summary['avg_turbidity'] ?? 0 }}</div>
                <div class="range">Min: {{ $summary['min_turbidity'] ?? 0 }} | Max: {{ $summary['max_turbidity'] ?? 0 }}</div>
            </div>
            
            <div class="summary-card">
                <div class="label">Salinity (ppt)</div>
                <div class="value">{{ $summary['avg_salinity'] ?? 0 }}</div>
                <div class="range">Rata-rata Salinitas</div>
            </div>
            
            <div class="summary-card">
                <div class="label">Total Records</div>
                <div class="value">{{ $summary['total_records'] ?? 0 }}</div>
                <div class="range">Data Points</div>
            </div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="data-section">
        <h2 class="section-title">Detail Data Monitoring (Showing {{ min($historicalData->count(), 100) }} of {{ $historicalData->count() }} records)</h2>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 13%;">Timestamp</th>
                    <th style="width: 10%;">Score</th>
                    <th style="width: 8%;">pH</th>
                    <th style="width: 10%;">TDS</th>
                    <th style="width: 10%;">Turbidity</th>
                    <th style="width: 10%;">Salinity</th>
                    <th style="width: 10%;">Ultrasonic</th>
                    <th style="width: 14%;">Category</th>
                </tr>
            </thead>
            <tbody>
                @forelse($historicalData->take(100) as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['timestamp'] ?? now())->format('d/m/Y H:i') }}</td>
                    <td>
                        @php
                            $score = $row['water_quality_score'] ?? 0;
                        @endphp
                        @if($score >= 85)
                            <span class="score-badge score-excellent">{{ $score }}</span>
                        @elseif($score >= 70)
                            <span class="score-badge score-good">{{ $score }}</span>
                        @elseif($score >= 45)
                            <span class="score-badge score-fair">{{ $score }}</span>
                        @elseif($score >= 30)
                            <span class="score-badge score-poor">{{ $score }}</span>
                        @else
                            <span class="score-badge score-critical">{{ $score }}</span>
                        @endif
                    </td>
                    <td>{{ number_format($row['ph_value'] ?? 0, 2) }}</td>
                    <td>{{ number_format($row['tds_value'] ?? 0, 2) }}</td>
                    <td>{{ number_format($row['turbidity'] ?? 0, 2) }}</td>
                    <td>{{ number_format($row['salinity_ppt'] ?? 0, 2) }}</td>
                    <td>{{ number_format($row['ultrasonic_value'] ?? 0, 2) }}</td>
                    <td style="font-size: 7px;">{{ $row['water_quality_status'] ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #999;">Tidak ada data untuk periode ini</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($historicalData->count() > 100)
        <div style="margin-top: 10px; padding: 8px; background: #fff3cd; text-align: center; font-size: 8px;">
            Note: Showing first 100 records only. Total records: {{ $historicalData->count() }}
        </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <p><strong>TambaQ</strong> - Smart Aquaculture Monitoring System</p>
        <p>Report Generated: {{ $generatedAt->format('d F Y H:i:s') }} | Historical Period: {{ \Carbon\Carbon::parse($start)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($end)->format('d/m/Y') }}</p>
    </div>
</body>
</html>
