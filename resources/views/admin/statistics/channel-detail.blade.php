@extends('layouts.app')

@section('title', 'สถิติช่องทาง: ' . ucfirst($stats['channel']))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.statistics.index') }}">สถิติ</a></li>
                            <li class="breadcrumb-item active">{{ ucfirst($stats['channel']) }}</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0">
                        📡 สถิติช่องทาง: 
                        <span class="badge bg-primary">{{ $this->getChannelLabel($stats['channel']) }}</span>
                    </h1>
                </div>
                <div class="d-flex gap-2">
                    <select id="dateRange" class="form-select" style="width: auto;">
                        <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>วันนี้</option>
                        <option value="yesterday" {{ $dateRange == 'yesterday' ? 'selected' : '' }}>เมื่อวาน</option>
                        <option value="last_7_days" {{ $dateRange == 'last_7_days' ? 'selected' : '' }}>7 วันที่ผ่านมา</option>
                        <option value="last_30_days" {{ $dateRange == 'last_30_days' ? 'selected' : '' }}>30 วันที่ผ่านมา</option>
                    </select>
                    <a href="{{ route('admin.statistics.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> กลับ
                    </a>
                </div>
            </div>

            <!-- Overview Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <div class="display-4 text-primary mb-2">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <h4 class="text-primary">{{ number_format($stats['total']) }}</h4>
                            <p class="text-muted mb-0">การแจ้งเตือนทั้งหมด</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <div class="display-4 text-success mb-2">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h4 class="text-success">{{ $stats['success_rate'] }}%</h4>
                            <p class="text-muted mb-0">อัตราความสำเร็จ</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <div class="display-4 text-warning mb-2">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4 class="text-warning">{{ number_format($stats['hourly_stats']->avg('total') ?? 0, 1) }}</h4>
                            <p class="text-muted mb-0">เฉลี่ยต่อชั่วโมง</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <div class="display-4 text-danger mb-2">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h4 class="text-danger">{{ $stats['error_stats']->count() }}</h4>
                            <p class="text-muted mb-0">ประเภทข้อผิดพลาด</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">สถิติการส่งรายชั่วโมง</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="hourlyChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">สถานะการส่ง</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Analysis -->
            @if($stats['error_stats']->count() > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">🚨 การวิเคราะห์ข้อผิดพลาด</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ข้อผิดพลาด</th>
                                            <th class="text-end">จำนวน</th>
                                            <th class="text-end">เปอร์เซ็นต์</th>
                                            <th class="text-center">ระดับความรุนแรง</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $totalErrors = $stats['error_stats']->sum('count'); @endphp
                                        @foreach($stats['error_stats'] as $error)
                                        <tr>
                                            <td>
                                                <code class="text-danger">{{ $error->error_message ?: 'ไม่ระบุข้อผิดพลาด' }}</code>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-danger">{{ number_format($error->count) }}</span>
                                            </td>
                                            <td class="text-end">
                                                {{ $totalErrors > 0 ? round(($error->count / $totalErrors) * 100, 1) : 0 }}%
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $severity = $error->count > 100 ? 'high' : ($error->count > 50 ? 'medium' : 'low');
                                                    $badgeClass = $severity == 'high' ? 'bg-danger' : ($severity == 'medium' ? 'bg-warning' : 'bg-info');
                                                    $severityText = $severity == 'high' ? 'สูง' : ($severity == 'medium' ? 'ปานกลาง' : 'ต่ำ');
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">{{ $severityText }}</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Notifications -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">📨 การแจ้งเตือนล่าสุด</h5>
                        </div>
                        <div class="card-body">
                            @if($stats['recent_notifications']->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>วันที่/เวลา</th>
                                                <th>Template</th>
                                                <th>ผู้ส่ง</th>
                                                <th>สถานะ</th>
                                                <th class="text-center">การดำเนินการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($stats['recent_notifications'] as $notification)
                                            <tr>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ $notification->created_at->format('d/m/Y H:i:s') }}
                                                    </small>
                                                </td>
                                                <td>
                                                    @if($notification->template)
                                                        <span class="badge bg-info">{{ $notification->template->name }}</span>
                                                    @else
                                                        <span class="text-muted">ไม่ระบุ Template</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($notification->user)
                                                        {{ $notification->user->name }}
                                                        <br><small class="text-muted">{{ $notification->user->email }}</small>
                                                    @else
                                                        <span class="text-muted">ระบบ</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $statusClass = match($notification->status) {
                                                            'sent' => 'bg-success',
                                                            'failed' => 'bg-danger',
                                                            'pending' => 'bg-warning',
                                                            default => 'bg-secondary'
                                                        };
                                                        $statusText = match($notification->status) {
                                                            'sent' => 'ส่งสำเร็จ',
                                                            'failed' => 'ส่งไม่สำเร็จ',
                                                            'pending' => 'รอดำเนินการ',
                                                            default => $notification->status
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('admin.notifications.show', $notification->uuid) }}" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       data-bs-toggle="tooltip" 
                                                       title="ดูรายละเอียด">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>ไม่มีการแจ้งเตือนในช่องทางนี้</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // Date range change handler
    document.getElementById('dateRange').addEventListener('change', function() {
        const dateRange = this.value;
        const channel = '{{ $stats["channel"] }}';
        window.location.href = `{{ route('admin.statistics.channel.detail', '') }}/${channel}?date_range=${dateRange}`;
    });
});

function initializeCharts() {
    // Hourly Chart
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    const hourlyData = @json($stats['hourly_stats']);
    
    // สร้างข้อมูล 24 ชั่วโมง
    const hours = Array.from({length: 24}, (_, i) => i);
    const hourlyTotals = hours.map(hour => {
        const found = hourlyData.find(h => h.hour == hour);
        return found ? found.total : 0;
    });
    const hourlySent = hours.map(hour => {
        const found = hourlyData.find(h => h.hour == hour);
        return found ? found.sent : 0;
    });
    
    new Chart(hourlyCtx, {
        type: 'line',
        data: {
            labels: hours.map(h => h.toString().padStart(2, '0') + ':00'),
            datasets: [{
                label: 'ทั้งหมด',
                data: hourlyTotals,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'ส่งสำเร็จ',
                data: hourlySent,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        title: function(context) {
                            return 'เวลา ' + context[0].label;
                        },
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' รายการ';
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'ชั่วโมง'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'จำนวน'
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });

    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const total = {{ $stats['total'] }};
    const successRate = {{ $stats['success_rate'] }};
    const failureRate = 100 - successRate;
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['ส่งสำเร็จ', 'ส่งไม่สำเร็จ'],
            datasets: [{
                data: [successRate, failureRate],
                backgroundColor: ['#198754', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });
}

// Tooltip initialization
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>

<style>
.card:hover {
    transform: translateY(-2px);
    transition: transform 0.2s ease-in-out;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.badge {
    font-size: 0.875em;
}

.table-responsive {
    max-height: 500px;
    overflow-y: auto;
}

.table-responsive::-webkit-scrollbar {
    width: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

code {
    background: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.875em;
}

.breadcrumb {
    background: transparent;
    padding: 0;
    margin-bottom: 0.5rem;
}

.display-4 {
    font-size: 2.5rem;
}

@media (max-width: 768px) {
    .display-4 {
        font-size: 2rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>
@endpush

@php
function getChannelLabel($channel) {
    $labels = [
        'email' => 'อีเมล',
        'teams' => 'Microsoft Teams',
        'sms' => 'SMS',
        'webhook' => 'Webhook',
        'slack' => 'Slack'
    ];
    return $labels[$channel] ?? ucfirst($channel);
}
@endphp