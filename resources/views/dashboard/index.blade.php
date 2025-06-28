@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
<style>
.stats-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.stats-card:hover {
    transform: translateY(-2px);
}
.stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.widget-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.quick-action-btn {
    border-radius: 50px;
    padding: 10px 20px;
    margin: 5px;
}
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}
</style>
@endsection

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-tachometer-alt me-2"></i>
            Dashboard
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> รีเฟรช
                </button>
            </div>
        </div>
    </div>

    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card widget-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-1">ยินดีต้อนรับ, {{ $user->display_name ?? $user->username }}!</h4>
                            <p class="text-muted mb-0">Smart Notification System</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <p class="mb-1"><strong>แผนก:</strong> {{ $user->department ?? 'ไม่ระบุ' }}</p>
                            <p class="mb-0"><strong>ล็อกอินล่าสุด:</strong> {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'ไม่ระบุ' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">การแจ้งเตือนทั้งหมด</div>
                            <div class="h5 mb-0 font-weight-bold" id="total-notifications">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="stats-icon bg-white text-primary">
                                <i class="fas fa-bell"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">ส่งสำเร็จ</div>
                            <div class="h5 mb-0 font-weight-bold" id="sent-notifications">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="stats-icon bg-white text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card bg-warning text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">รอดำเนินการ</div>
                            <div class="h5 mb-0 font-weight-bold" id="pending-notifications">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="stats-icon bg-white text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card bg-danger text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">ล้มเหลว</div>
                            <div class="h5 mb-0 font-weight-bold" id="failed-notifications">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="stats-icon bg-white text-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card widget-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        การดำเนินการด่วน
                    </h5>
                </div>
                <div class="card-body text-center">
                    @can('create-notifications')
                    <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary quick-action-btn">
                        <i class="fas fa-plus"></i> สร้างการแจ้งเตือนใหม่
                    </a>
                    @endcan
                    
                    @can('view-notifications')
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-info quick-action-btn">
                        <i class="fas fa-list"></i> ดูการแจ้งเตือนทั้งหมด
                    </a>
                    @endcan

                    @can('view-users')
                    <a href="{{ route('users.index') }}" class="btn btn-secondary quick-action-btn">
                        <i class="fas fa-users"></i> จัดการผู้ใช้
                    </a>
                    @endcan

                    @can('view-groups')
                    <a href="{{ route('groups.index') }}" class="btn btn-success quick-action-btn">
                        <i class="fas fa-layer-group"></i> จัดการกลุ่ม
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Recent Notifications -->
        <div class="col-lg-8 mb-4">
            <div class="card widget-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bell me-2"></i>
                        การแจ้งเตือนล่าสุด
                    </h5>
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-outline-primary">
                        ดูทั้งหมด
                    </a>
                </div>
                <div class="card-body position-relative" id="recent-notifications">
                    <div class="loading-overlay">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">กำลังโหลด...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status & User Info -->
        <div class="col-lg-4">
            <!-- System Status -->
            <div class="card widget-card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-server me-2"></i>
                        สถานะระบบ
                    </h5>
                </div>
                <div class="card-body" id="system-status">
                    <div class="loading-overlay">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">กำลังโหลด...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Activity -->
            @can('view-activity-logs')
            <div class="card widget-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        กิจกรรมของฉัน
                    </h5>
                </div>
                <div class="card-body" id="user-activity">
                    <div class="loading-overlay">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">กำลังโหลด...</span>
                        </div>
                    </div>
                </div>
            </div>
            @endcan
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Wait for DOM to be ready and check if jQuery is available
document.addEventListener('DOMContentLoaded', function() {
    // Check if jQuery is available, if not use vanilla JavaScript
    if (typeof jQuery !== 'undefined') {
        initializeDashboardWithJQuery();
    } else {
        initializeDashboardVanilla();
    }
});

// jQuery version
function initializeDashboardWithJQuery() {
    $(document).ready(function() {
        // Load dashboard data
        loadDashboardStats();
        loadRecentNotifications();
        loadSystemStatus();
        @can('view-activity-logs')
        loadUserActivity();
        @endcan
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            loadDashboardStats();
            loadRecentNotifications();
            loadSystemStatus();
        }, 30000);
    });
}

// Vanilla JavaScript version
function initializeDashboardVanilla() {
    // Load dashboard data
    loadDashboardStatsVanilla();
    loadRecentNotificationsVanilla();
    loadSystemStatusVanilla();
    @can('view-activity-logs')
    loadUserActivityVanilla();
    @endcan
    
    // Auto-refresh every 30 seconds
    setInterval(function() {
        loadDashboardStatsVanilla();
        loadRecentNotificationsVanilla();
        loadSystemStatusVanilla();
    }, 30000);
}

// jQuery AJAX functions
function loadDashboardStats() {
    if (typeof jQuery === 'undefined') {
        loadDashboardStatsVanilla();
        return;
    }
    
    $.ajax({
        url: '{{ route("dashboard.data") }}',
        method: 'GET',
        success: function(response) {
            if (response.stats) {
                $('#total-notifications').text(response.stats.total || 0);
                $('#sent-notifications').text(response.stats.sent || 0);
                $('#pending-notifications').text(response.stats.pending || 0);
                $('#failed-notifications').text(response.stats.failed || 0);
            }
        },
        error: function() {
            $('#total-notifications').text('--');
            $('#sent-notifications').text('--');
            $('#pending-notifications').text('--');
            $('#failed-notifications').text('--');
        }
    });
}

function loadRecentNotifications() {
    if (typeof jQuery === 'undefined') {
        loadRecentNotificationsVanilla();
        return;
    }
    
    $.ajax({
        url: '{{ route("dashboard.widget", "recent-notifications") }}',
        method: 'GET',
        success: function(response) {
            $('#recent-notifications').html(response);
        },
        error: function() {
            $('#recent-notifications').html('<div class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</div>');
        }
    });
}

function loadSystemStatus() {
    if (typeof jQuery === 'undefined') {
        loadSystemStatusVanilla();
        return;
    }
    
    $.ajax({
        url: '{{ route("dashboard.widget", "system-status") }}',
        method: 'GET',
        success: function(response) {
            $('#system-status').html(response);
        },
        error: function() {
            $('#system-status').html('<div class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</div>');
        }
    });
}

@can('view-activity-logs')
function loadUserActivity() {
    if (typeof jQuery === 'undefined') {
        loadUserActivityVanilla();
        return;
    }
    
    $.ajax({
        url: '{{ route("dashboard.widget", "user-activity") }}',
        method: 'GET',
        success: function(response) {
            $('#user-activity').html(response);
        },
        error: function() {
            $('#user-activity').html('<div class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</div>');
        }
    });
}
@endcan

// Vanilla JavaScript AJAX functions
function loadDashboardStatsVanilla() {
    fetch('{{ route("dashboard.data") }}')
        .then(response => response.json())
        .then(data => {
            if (data.stats) {
                document.getElementById('total-notifications').textContent = data.stats.total || 0;
                document.getElementById('sent-notifications').textContent = data.stats.sent || 0;
                document.getElementById('pending-notifications').textContent = data.stats.pending || 0;
                document.getElementById('failed-notifications').textContent = data.stats.failed || 0;
            }
        })
        .catch(error => {
            console.error('Error loading dashboard stats:', error);
            document.getElementById('total-notifications').textContent = '--';
            document.getElementById('sent-notifications').textContent = '--';
            document.getElementById('pending-notifications').textContent = '--';
            document.getElementById('failed-notifications').textContent = '--';
        });
}

function loadRecentNotificationsVanilla() {
    fetch('{{ route("dashboard.widget", "recent-notifications") }}')
        .then(response => response.text())
        .then(html => {
            document.getElementById('recent-notifications').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading recent notifications:', error);
            document.getElementById('recent-notifications').innerHTML = '<div class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</div>';
        });
}

function loadSystemStatusVanilla() {
    fetch('{{ route("dashboard.widget", "system-status") }}')
        .then(response => response.text())
        .then(html => {
            document.getElementById('system-status').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading system status:', error);
            document.getElementById('system-status').innerHTML = '<div class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</div>';
        });
}

@can('view-activity-logs')
function loadUserActivityVanilla() {
    fetch('{{ route("dashboard.widget", "user-activity") }}')
        .then(response => response.text())
        .then(html => {
            document.getElementById('user-activity').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading user activity:', error);
            document.getElementById('user-activity').innerHTML = '<div class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</div>';
        });
}
@endcan

// Utility functions
function formatNumber(num) {
    return new Intl.NumberFormat('th-TH').format(num);
}

function showToast(message, type = 'info') {
    // Implement toast notification if needed
    console.log(`${type}: ${message}`);
}
</script>
@endsection