<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสถิติการแจ้งเตือน</title>
    <style>
        body {
            font-family: 'THSarabunNew', 'TH Sarabun New', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #007bff;
            font-size: 24px;
            margin: 0;
            font-weight: bold;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 16px;
            margin-top: 5px;
        }
        
        .meta-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        
        .meta-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .meta-info td {
            padding: 5px 10px;
            border: none;
        }
        
        .meta-info .label {
            font-weight: bold;
            width: 150px;
            color: #495057;
        }
        
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background: #007bff;
            color: white;
            padding: 10px 15px;
            margin: 0 0 15px 0;
            font-size: 18px;
            font-weight: bold;
            border-radius: 3px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .success { color: #28a745; }
        .warning { color: #ffc107; }
        .danger { color: #dc3545; }
        .info { color: #17a2b8; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 3px;
            color: white;
        }
        
        .badge-success { background: #28a745; }
        .badge-warning { background: #ffc107; color: #212529; }
        .badge-danger { background: #dc3545; }
        .badge-info { background: #17a2b8; }
        
        .chart-placeholder {
            height: 200px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-style: italic;
            margin: 15px 0;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }
        
        @page {
            margin: 2cm;
            size: A4 landscape;
        }
        
        @media print {
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>รายงานสถิติการแจ้งเตือน</h1>
        <div class="subtitle">Smart Notification System</div>
    </div>

    <!-- Meta Information -->
    <div class="meta-info">
        <table>
            <tr>
                <td class="label">ช่วงเวลา:</td>
                <td>{{ $this->getDateRangeLabel($dateRange) }}</td>
                <td class="label">วันที่ส่งออก:</td>
                <td>{{ now()->format('d/m/Y H:i:s') }}</td>
            </tr>
            <tr>
                <td class="label">ผู้ส่งออก:</td>
                <td>{{ auth()->user()->name ?? 'ระบบ' }}</td>
                <td class="label">รุ่นระบบ:</td>
                <td>v2.0</td>
            </tr>
        </table>
    </div>

    <!-- สถิติพื้นฐาน -->
    <div class="section">
        <h2 class="section-title">📊 สถิติพื้นฐาน</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">{{ number_format($data['basic']['total_notifications']) }}</div>
                <div class="stat-label">การแจ้งเตือนทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-number success">{{ number_format($data['basic']['total_sent']) }}</div>
                <div class="stat-label">ส่งสำเร็จ</div>
            </div>
            <div class="stat-card">
                <div class="stat-number warning">{{ number_format($data['basic']['total_pending']) }}</div>
                <div class="stat-label">รอดำเนินการ</div>
            </div>
            <div class="stat-card">
                <div class="stat-number danger">{{ number_format($data['basic']['total_failed']) }}</div>
                <div class="stat-label">ส่งไม่สำเร็จ</div>
            </div>
        </div>
        
        <div class="two-column">
            <div>
                <h3>อัตราความสำเร็จ</h3>
                <div class="stat-card">
                    <div class="stat-number {{ $data['basic']['success_rate'] >= 90 ? 'success' : ($data['basic']['success_rate'] >= 70 ? 'warning' : 'danger') }}">
                        {{ $data['basic']['success_rate'] }}%
                    </div>
                    <div class="stat-label">อัตราความสำเร็จโดยรวม</div>
                </div>
            </div>
            <div>
                <h3>ข้อมูลระบบ</h3>
                <table>
                    <tr>
                        <td>ผู้ใช้งานทั้งหมด</td>
                        <td class="text-right">{{ number_format($data['basic']['total_users']) }}</td>
                    </tr>
                    <tr>
                        <td>Template ทั้งหมด</td>
                        <td class="text-right">{{ number_format($data['basic']['total_templates']) }}</td>
                    </tr>
                    <tr>
                        <td>กลุ่มทั้งหมด</td>
                        <td class="text-right">{{ number_format($data['basic']['total_groups']) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- สถิติตามช่องทาง -->
    <div class="section">
        <h2 class="section-title">📡 สถิติตามช่องทาง</h2>
        @if(count($data['channels']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>ช่องทาง</th>
                        <th class="text-right">จำนวน</th>
                        <th class="text-right">เปอร์เซ็นต์</th>
                        <th class="text-center">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    @php $total = collect($data['channels'])->sum('total'); @endphp
                    @foreach($data['channels'] as $channel)
                    <tr>
                        <td>{{ $channel['label'] }}</td>
                        <td class="text-right">{{ number_format($channel['total']) }}</td>
                        <td class="text-right">{{ $total > 0 ? round(($channel['total'] / $total) * 100, 1) : 0 }}%</td>
                        <td class="text-center">
                            <span class="badge badge-info">ใช้งาน</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">ไม่มีข้อมูลสถิติตามช่องทาง</div>
        @endif
    </div>

    <!-- Page Break -->
    <div class="page-break"></div>

    <!-- สถิติรายวัน -->
    <div class="section">
        <h2 class="section-title">📅 สถิติรายวัน (10 วันล่าสุด)</h2>
        @if(count($data['daily']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th class="text-right">ทั้งหมด</th>
                        <th class="text-right">ส่งสำเร็จ</th>
                        <th class="text-right">ส่งไม่สำเร็จ</th>
                        <th class="text-right">อัตราความสำเร็จ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(collect($data['daily'])->take(10) as $daily)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($daily['date'])->format('d/m/Y') }}</td>
                        <td class="text-right">{{ number_format($daily['total']) }}</td>
                        <td class="text-right">{{ number_format($daily['sent']) }}</td>
                        <td class="text-right">{{ number_format($daily['failed']) }}</td>
                        <td class="text-right">
                            @php $rate = $daily['total'] > 0 ? round(($daily['sent'] / $daily['total']) * 100, 1) : 0; @endphp
                            <span class="badge {{ $rate >= 90 ? 'badge-success' : ($rate >= 70 ? 'badge-warning' : 'badge-danger') }}">
                                {{ $rate }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">ไม่มีข้อมูลสถิติรายวัน</div>
        @endif
    </div>

    <!-- Template ที่ใช้มากที่สุด -->
    <div class="section">
        <h2 class="section-title">📄 Template ที่ใช้มากที่สุด</h2>
        @if(count($data['templates']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>ชื่อ Template</th>
                        <th class="text-right">จำนวนการใช้</th>
                        <th class="text-center">อันดับ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['templates']->take(10) as $index => $template)
                    <tr>
                        <td>{{ $template->template_name }}</td>
                        <td class="text-right">{{ number_format($template->usage_count) }}</td>
                        <td class="text-center">
                            <span class="badge {{ $index < 3 ? 'badge-success' : 'badge-info' }}">
                                #{{ $index + 1 }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">ไม่มีข้อมูล Template</div>
        @endif
    </div>

    <!-- กลุ่มที่ใช้มากที่สุด -->
    <div class="section">
        <h2 class="section-title">👥 กลุ่มที่ใช้มากที่สุด</h2>
        @if(count($data['groups']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>ชื่อกลุ่ม</th>
                        <th class="text-right">จำนวนการใช้</th>
                        <th class="text-center">อันดับ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['groups']->take(10) as $index => $group)
                    <tr>
                        <td>{{ $group->group_name }}</td>
                        <td class="text-right">{{ number_format($group->notification_count) }}</td>
                        <td class="text-center">
                            <span class="badge {{ $index < 3 ? 'badge-success' : 'badge-info' }}">
                                #{{ $index + 1 }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">ไม่มีข้อมูลกลุ่ม</div>
        @endif
    </div>

    <!-- สถิติการส่งตามช่องทาง -->
    <div class="section">
        <h2 class="section-title">📈 สถิติการส่งตามช่องทาง</h2>
        @if(count($data['delivery']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>ช่องทาง</th>
                        <th class="text-right">ส่งสำเร็จ</th>
                        <th class="text-right">ส่งไม่สำเร็จ</th>
                        <th class="text-right">รอดำเนินการ</th>
                        <th class="text-right">รวม</th>
                        <th class="text-right">อัตราความสำเร็จ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['delivery'] as $delivery)
                    @php
                        $total = $delivery['sent'] + $delivery['failed'] + $delivery['pending'];
                        $rate = $total > 0 ? round(($delivery['sent'] / $total) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>{{ $delivery['label'] }}</td>
                        <td class="text-right">{{ number_format($delivery['sent']) }}</td>
                        <td class="text-right">{{ number_format($delivery['failed']) }}</td>
                        <td class="text-right">{{ number_format($delivery['pending']) }}</td>
                        <td class="text-right"><strong>{{ number_format($total) }}</strong></td>
                        <td class="text-right">
                            <span class="badge {{ $rate >= 90 ? 'badge-success' : ($rate >= 70 ? 'badge-warning' : 'badge-danger') }}">
                                {{ $rate }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">ไม่มีข้อมูลสถิติการส่ง</div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>รายงานนี้สร้างโดยระบบ Smart Notification System</p>
        <p>© {{ date('Y') }} - สงวนลิขสิทธิ์</p>
    </div>

    @php
    function getDateRangeLabel($dateRange) {
        $labels = [
            'today' => 'วันนี้',
            'yesterday' => 'เมื่อวาน',
            'last_7_days' => '7 วันที่ผ่านมา',
            'last_30_days' => '30 วันที่ผ่านมา',
            'last_3_months' => '3 เดือนที่ผ่านมา',
            'last_6_months' => '6 เดือนที่ผ่านมา',
            'last_year' => '1 ปีที่ผ่านมา'
        ];
        return $labels[$dateRange] ?? $dateRange;
    }
    @endphp
</body>
</html>