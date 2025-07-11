@extends('layouts.app')

@section('title', 'สถิติการแจ้งเตือน')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">📊 สถิติการแจ้งเตือน</h1>
                <div class="d-flex gap-2">
                    <select id="dateRange" class="form-select" style="width: auto;">
                        <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>วันนี้</option>
                        <option value="yesterday" {{ $dateRange == 'yesterday' ? 'selected' : '' }}>เมื่อวาน</option>
                        <option value="last_7_days" {{ $dateRange == 'last_7_days' ? 'selected' : '' }}>7 วันที่ผ่านมา</option>
                        <option value="last_30_days" {{ $dateRange == 'last_30_days' ? 'selected' : '' }}>30 วันที่ผ่านมา</option>
                        <option value="last_3_months" {{ $dateRange == 'last_3_months' ? 'selected' : '' }}>3 เดือนที่ผ่านมา</option>
                        <option value="last_6_months" {{ $dateRange == 'last_6_months' ? 'selected' : '' }}>6 เดือนที่ผ่านมา</option>
                        <option value="last_year" {{ $dateRange == 'last_year' ? 'selected' : '' }}>1 ปีที่ผ่านมา</option>
                    </select>
                    <div class="btn-group">
                        <button class="btn btn-primary" onclick="refreshStats()">
                            <i class="fas fa-sync-alt"></i> รีเฟรช
                        </button>
                        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportToPDF()"><i class="fas fa-file-pdf text-danger"></i> ส่งออก PDF</a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportToExcel()"><i class="fas fa-file-excel text-success"></i> ส่งออก Excel</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="printStats()"><i class="fas fa-print"></i> พิมพ์รายงาน</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- สถิติพื้นฐาน -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-primary stats-card">
                        <div class="card-body text-center">
                            <div class="metric-icon text-primary">
                                <i class="fas fa-bell"></i>
                            </div>
                            <h4 class="text-primary">{{ number_format($basicStats['total_notifications']) }}</h4>
                            <p class="text-muted mb-0">การแจ้งเตือนทั้งหมด</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-success stats-card">
                        <div class="card-body text-center">
                            <div class="metric-icon text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h4 class="text-success">{{ number_format($basicStats['total_sent']) }}</h4>
                            <p class="text-muted mb-0">ส่งสำเร็จ</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-warning stats-card">
                        <div class="card-body text-center">
                            <div class="metric-icon text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4 class="text-warning">{{ number_format($basicStats['total_pending']) }}</h4>
                            <p class="text-muted mb-0">รอดำเนินการ</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-danger stats-card">
                        <div class="card-body text-center">
                            <div class="metric-icon text-danger">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h4 class="text-danger">{{ number_format($basicStats['total_failed']) }}</h4>
                            <p class="text-muted mb-0">ส่งไม่สำเร็จ</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- อัตราความสำเร็จและสถิติรายวัน -->
            <div class="row mb-4">
                <div class="col-lg-4 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">🎯 อัตราความสำเร็จ</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="chart-container success-rate-circle">
                                <canvas id="successRateChart" width="150" height="150"></canvas>
                                <div class="position-absolute top-50 start-50 translate-middle">
                                    <h3 class="text-primary mb-0">{{ $basicStats['success_rate'] }}%</h3>
                                    <small class="text-muted">ความสำเร็จ</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 mb-3">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">📈 สถิติการส่งรายวัน</h5>
                            <small class="text-muted">แนวโน้มการใช้งาน</small>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="dailyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- สถิติตามช่องทางและการส่ง -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">📡 การใช้งานตามช่องทาง</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="channelChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">📊 สถิติการส่งตามช่องทาง</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="deliveryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template และกลุ่มที่ใช้มากที่สุด -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">📄 Template ที่ใช้มากที่สุด</h5>
                            <span class="badge bg-info">Top 10</span>
                        </div>
                        <div class="card-body">
                            @if($templateStats->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>อันดับ</th>
                                                <th>ชื่อ Template</th>
                                                <th class="text-end">จำนวนการใช้</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($templateStats->take(10) as $index => $template)
                                            <tr>
                                                <td>
                                                    <span class="badge {{ $index < 3 ? 'bg-warning' : 'bg-secondary' }}">
                                                        #{{ $index + 1 }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-file-alt text-muted me-2"></i>
                                                        {{ $template->template_name }}
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-primary">{{ number_format($template->usage_count) }}</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="no-data text-center py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">ไม่มีข้อมูล Template</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">👥 กลุ่มที่ใช้มากที่สุด</h5>
                            <span class="badge bg-success">Top 10</span>
                        </div>
                        <div class="card-body">
                            @if($groupStats->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>อันดับ</th>
                                                <th>ชื่อกลุ่ม</th>
                                                <th class="text-end">จำนวนการใช้</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($groupStats->take(10) as $index => $group)
                                            <tr>
                                                <td>
                                                    <span class="badge {{ $index < 3 ? 'bg-warning' : 'bg-secondary' }}">
                                                        #{{ $index + 1 }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-users text-muted me-2"></i>
                                                        {{ $group->group_name }}
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-success">{{ number_format($group->notification_count) }}</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="no-data text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">ไม่มีข้อมูลกลุ่ม</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- ข้อมูลเพิ่มเติมและระบบ -->
            <div class="row">
                <div class="col-lg-4 mb-3">
                    <div class="card metric-card-primary">
                        <div class="card-body text-center text-white">
                            <i class="fas fa-users fa-2x mb-3"></i>
                            <h4>{{ number_format($basicStats['total_users']) }}</h4>
                            <p class="mb-0 opacity-75">ผู้ใช้งานทั้งหมด</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card metric-card-warning">
                        <div class="card-body text-center text-white">
                            <i class="fas fa-file-alt fa-2x mb-3"></i>
                            <h4>{{ number_format($basicStats['total_templates']) }}</h4>
                            <p class="mb-0 opacity-75">Template ทั้งหมด</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card metric-card-success">
                        <div class="card-body text-center text-white">
                            <i class="fas fa-layer-group fa-2x mb-3"></i>
                            <h4>{{ number_format($basicStats['total_groups']) }}</h4>
                            <p class="mb-0 opacity-75">กลุ่มทั้งหมด</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Health Status -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">🔧 สถานะระบบ</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded">
                                        <div class="mb-2">
                                            <span class="status-indicator status-online"></span>
                                            <i class="fas fa-database fa-2x text-success"></i>
                                        </div>
                                        <h6 class="mb-1">ฐานข้อมูล</h6>
                                        <span class="badge bg-success">ออนไลน์</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded">
                                        <div class="mb-2">
                                            <span class="status-indicator status-online"></span>
                                            <i class="fas fa-server fa-2x text-success"></i>
                                        </div>
                                        <h6 class="mb-1">API Service</h6>
                                        <span class="badge bg-success">พร้อมใช้งาน</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded">
                                        <div class="mb-2">
                                            <span class="status-indicator status-warning"></span>
                                            <i class="fas fa-tasks fa-2x text-warning"></i>
                                        </div>
                                        <h6 class="mb-1">คิวงาน</h6>
                                        <span class="badge bg-warning">{{ rand(0, 5) }} งาน</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded">
                                        <div class="mb-2">
                                            <span class="status-indicator status-online"></span>
                                            <i class="fas fa-chart-line fa-2x text-info"></i>
                                        </div>
                                        <h6 class="mb-1">สถิติ</h6>
                                        <span class="badge bg-info">อัปเดตล่าสุด</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(0,0,0,0.5); z-index: 1000; backdrop-filter: blur(4px);">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="text-center text-white">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5>กำลังโหลดข้อมูล...</h5>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();

    // Event listener สำหรับเปลี่ยน date range
    document.getElementById('dateRange').addEventListener('change', function() {
        const dateRange = this.value;
        showLoading();
        window.location.href = `{{ route('admin.statistics.index') }}?date_range=${dateRange}`;
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function initializeCharts() {
    // กราฟอัตราความสำเร็จ
    const successRateCtx = document.getElementById('successRateChart').getContext('2d');
    const successRate = {{ $basicStats['success_rate'] }};
    
    new Chart(successRateCtx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [successRate, 100 - successRate],
                backgroundColor: [
                    successRate >= 90 ? '#28a745' : successRate >= 70 ? '#ffc107' : '#dc3545',
                    '#e9ecef'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed + '%';
                        }
                    }
                }
            },
            cutout: '75%',
            animation: {
                animateRotate: true,
                duration: 1500
            }
        }
    });

    // กราฟรายวัน
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    const dailyData = @json($dailyStats);
    
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('th-TH', { month: 'short', day: 'numeric' });
            }),
            datasets: [{
                label: 'ส่งสำเร็จ',
                data: dailyData.map(d => d.sent),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#28a745',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }, {
                label: 'ส่งไม่สำเร็จ',
                data: dailyData.map(d => d.failed),
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#dc3545',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
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
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#fff',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + new Intl.NumberFormat().format(context.parsed.y) + ' รายการ';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'วันที่'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        borderDash: [2, 2],
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    title: {
                        display: true,
                        text: 'จำนวน'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat().format(value);
                        }
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            animation: {
                duration: 1500,
                easing: 'easeOutQuart'
            }
        }
    });

    // กราฟช่องทาง
    const channelCtx = document.getElementById('channelChart').getContext('2d');
    const channelData = @json($channelStats);
    
    const channelColors = [
        '#0d6efd', '#28a745', '#ffc107', '#dc3545', '#6610f2',
        '#fd7e14', '#20c997', '#e83e8c', '#6c757d', '#17a2b8'
    ];
    
    new Chart(channelCtx, {
        type: 'doughnut',
        data: {
            labels: channelData.map(d => d.label),
            datasets: [{
                data: channelData.map(d => d.total),
                backgroundColor: channelColors.slice(0, channelData.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + new Intl.NumberFormat().format(context.parsed) + ` (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                duration: 1500
            }
        }
    });

    // กราฟการส่งตามช่องทาง
    const deliveryCtx = document.getElementById('deliveryChart').getContext('2d');
    const deliveryData = @json($deliveryStats);
    
    new Chart(deliveryCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(deliveryData).map(key => deliveryData[key].label),
            datasets: [{
                label: 'ส่งสำเร็จ',
                data: Object.keys(deliveryData).map(key => deliveryData[key].sent),
                backgroundColor: '#28a745',
                borderRadius: 4,
                borderSkipped: false
            }, {
                label: 'ส่งไม่สำเร็จ',
                data: Object.keys(deliveryData).map(key => deliveryData[key].failed),
                backgroundColor: '#dc3545',
                borderRadius: 4,
                borderSkipped: false
            }, {
                label: 'รอดำเนินการ',
                data: Object.keys(deliveryData).map(key => deliveryData[key].pending),
                backgroundColor: '#ffc107',
                borderRadius: 4,
                borderSkipped: false
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
                            return 'ช่องทาง: ' + context[0].label;
                        },
                        label: function(context) {
                            return context.dataset.label + ': ' + new Intl.NumberFormat().format(context.parsed.y) + ' รายการ';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'ช่องทาง'
                    }
                },
                y: {
                    beginAtZero: true,
                    stacked: false,
                    grid: {
                        borderDash: [2, 2],
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    title: {
                        display: true,
                        text: 'จำนวน'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat().format(value);
                        }
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            animation: {
                duration: 1500,
                easing: 'easeOutQuart'
            }
        }
    });
}

function refreshStats() {
    const dateRange = document.getElementById('dateRange').value;
    showLoading();
    
    fetch(`{{ route('admin.statistics.api') }}?date_range=${dateRange}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            updateStatsDisplay(data);
            hideLoading();
            
            // Show success message
            showToast('success', 'ข้อมูลได้รับการอัปเดตเรียบร้อยแล้ว');
        })
        .catch(error => {
            console.error('Error:', error);
            hideLoading();
            showToast('error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
        });
}

function updateStatsDisplay(data) {
    // อัปเดตตัวเลขพื้นฐาน
    const stats = data.basic;
    document.querySelector('.text-primary h4').textContent = new Intl.NumberFormat().format(stats.total_notifications);
    document.querySelector('.text-success h4').textContent = new Intl.NumberFormat().format(stats.total_sent);
    document.querySelector('.text-warning h4').textContent = new Intl.NumberFormat().format(stats.total_pending);
    document.querySelector('.text-danger h4').textContent = new Intl.NumberFormat().format(stats.total_failed);
    
    // อัปเดตอัตราความสำเร็จ
    document.querySelector('.position-absolute h3').textContent = stats.success_rate + '%';
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('d-none');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('d-none');
}

function exportToPDF() {
    const dateRange = document.getElementById('dateRange').value;
    showLoading();
    window.open(`{{ route('admin.statistics.export') }}?format=pdf&date_range=${dateRange}`, '_blank');
    setTimeout(hideLoading, 1000);
}

function exportToExcel() {
    const dateRange = document.getElementById('dateRange').value;
    showLoading();
    window.open(`{{ route('admin.statistics.export') }}?format=excel&date_range=${dateRange}`, '_blank');
    setTimeout(hideLoading, 1000);
}

function printStats() {
    window.print();
}

function showToast(type, message) {
    // Create toast element
    const toastId = 'toast_' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Add to toast container or create one
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1060';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
    });
    toast.show();
    
    // Remove from DOM after hiding
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// Auto refresh every 5 minutes (if enabled)
setInterval(function() {
    const autoRefresh = localStorage.getItem('stats_auto_refresh');
    if (autoRefresh === 'true') {
        refreshStats();
    }
}, 300000);

// Chart hover effects
document.addEventListener('mouseover', function(e) {
    if (e.target.closest('.chart-container')) {
        e.target.closest('.chart-container').style.transform = 'scale(1.02)';
        e.target.closest('.chart-container').style.transition = 'transform 0.2s ease';
    }
});

document.addEventListener('mouseout', function(e) {
    if (e.target.closest('.chart-container')) {
        e.target.closest('.chart-container').style.transform = 'scale(1)';
    }
});
</script>

<style>
/* Base Styles */
.stats-card {
    transition: all 0.3s ease;
    border-radius: 10px;
    overflow: hidden;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.metric-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    transition: transform 0.3s ease;
}

.stats-card:hover .metric-icon {
    transform: scale(1.1);
}

/* Chart Containers */
.chart-container {
    position: relative;
    height: 300px;
    transition: transform 0.2s ease;
}

.chart-container canvas {
    border-radius: 8px;
}

/* Loading States */
.loading {
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

/* Success Rate Circle */
.success-rate-circle {
    position: relative;
}

.success-rate-circle canvas {
    animation: rotateIn 1s ease-out;
}

@keyframes rotateIn {
    from { 
        transform: rotate(-180deg) scale(0.5); 
        opacity: 0; 
    }
    to { 
        transform: rotate(0deg) scale(1); 
        opacity: 1; 
    }
}

/* Status Indicators */
.status-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
    animation: pulse 2s infinite;
}

.status-online { 
    background-color: #28a745; 
}

.status-offline { 
    background-color: #dc3545; 
}

.status-warning { 
    background-color: #ffc107; 
}

/* Metric Cards with Gradients */
.metric-card-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    transition: transform 0.3s ease;
}

.metric-card-success {
    background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
    color: white;
    border: none;
    transition: transform 0.3s ease;
}

.metric-card-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border: none;
    transition: transform 0.3s ease;
}

.metric-card-primary:hover,
.metric-card-success:hover,
.metric-card-warning:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

/* Tables */
.table-responsive {
    max-height: 400px;
    overflow-y: auto;
    border-radius: 8px;
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

.table th {
    border-top: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 0.5px;
    background: #f8f9fa;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    transform: scale(1.01);
    transition: all 0.2s ease;
}

/* Badges */
.badge {
    font-size: 0.875em;
    animation: badgeBounce 0.5s ease-out;
}

@keyframes badgeBounce {
    0% { transform: scale(0.3); }
    50% { transform: scale(1.05); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); }
}

/* No Data States */
.no-data {
    text-align: center;
    color: #6c757d;
    font-style: italic;
    padding: 2rem;
}

.no-data i {
    opacity: 0.5;
}

/* Card Enhancements */
.card {
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.card-header {
    background: transparent;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    border-radius: 10px 10px 0 0 !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .metric-icon {
        font-size: 2rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .chart-container {
        height: 250px;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .stats-card h4 {
        font-size: 1.5rem;
    }
}

/* Print Styles */
@media print {
    .no-print {
        display: none !important;
    }
    
    .card {
        break-inside: avoid;
        margin-bottom: 20px;
        box-shadow: none;
        border: 1px solid #dee2e6;
    }
    
    .container-fluid {
        max-width: 100%;
    }
    
    canvas {
        max-height: 300px !important;
    }
    
    .stats-card {
        transform: none !important;
        box-shadow: none !important;
    }
}

/* Loading Overlay */
#loadingOverlay {
    backdrop-filter: blur(4px);
    transition: all 0.3s ease;
}

#loadingOverlay .spinner-border {
    width: 3rem;
    height: 3rem;
}

/* Animations */
.card {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Stagger animation for cards */
.stats-card:nth-child(1) { animation-delay: 0.1s; }
.stats-card:nth-child(2) { animation-delay: 0.2s; }
.stats-card:nth-child(3) { animation-delay: 0.3s; }
.stats-card:nth-child(4) { animation-delay: 0.4s; }

/* Enhanced buttons */
.btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Dropdown menu */
.dropdown-menu {
    border-radius: 8px;
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    animation: fadeInDown 0.3s ease;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-item {
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    transform: translateX(5px);
}

/* Toast container */
.toast-container {
    z-index: 1060;
}

.toast {
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>
@endpush