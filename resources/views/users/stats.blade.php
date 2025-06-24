@extends('layouts.app')

@section('title', 'User Statistics')

@push('styles')
<style>
.stat-card {
    border: none;
    border-radius: 12px;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-dark, #0056b3) 100%);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-card.bg-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}

.stat-card.bg-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%) !important;
}

.stat-card.bg-info {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%) !important;
}

.stat-card.bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%) !important;
}

.stat-card.bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
}

.chart-container {
    position: relative;
    height: 300px;
    margin: 1rem 0;
}

.chart-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.metric-row {
    border-bottom: 1px solid #dee2e6;
    padding: 0.75rem 0;
}

.metric-row:last-child {
    border-bottom: none;
}

.progress-custom {
    height: 8px;
    border-radius: 4px;
    background-color: #e9ecef;
}

.sync-status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.5rem;
}

.sync-status-success {
    background-color: #28a745;
    animation: pulse-success 2s infinite;
}

.sync-status-warning {
    background-color: #ffc107;
    animation: pulse-warning 2s infinite;
}

.sync-status-error {
    background-color: #dc3545;
    animation: pulse-error 2s infinite;
}

@keyframes pulse-success {
    0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
}

@keyframes pulse-warning {
    0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
}

@keyframes pulse-error {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}

.filter-controls {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.export-button {
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 10;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Statistics</li>
                </ol>
            </nav>
            <h2><i class="fas fa-chart-bar me-2"></i>User Statistics Dashboard</h2>
            <p class="text-muted mb-0">Comprehensive analytics and insights about user activities</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-outline-success" onclick="exportStats()">
                    <i class="fas fa-download me-1"></i>Export Report
                </button>
                <button type="button" class="btn btn-outline-info" onclick="refreshStats()">
                    <i class="fas fa-sync me-1"></i>Refresh
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Users
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="filter-controls">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Time Period</label>
                <select class="form-select" id="timePeriod" onchange="updateCharts()">
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                    <option value="90">Last 3 Months</option>
                    <option value="365">Last Year</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select class="form-select" id="departmentFilter" onchange="updateCharts()">
                    <option value="">All Departments</option>
                    @foreach($stats['by_department'] as $dept)
                        <option value="{{ $dept->department }}">{{ $dept->department }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">User Source</label>
                <select class="form-select" id="sourceFilter" onchange="updateCharts()">
                    <option value="">All Sources</option>
                    <option value="ldap">LDAP Users</option>
                    <option value="manual">Manual Users</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" id="statusFilter" onchange="updateCharts()">
                    <option value="">All Status</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Overview Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0" id="totalUsers">{{ number_format($stats['total_users']) }}</h2>
                            <p class="mb-0">Total Users</p>
                            <small class="opacity-75">
                                @if($stats['new_this_month'] > 0)
                                    <i class="fas fa-arrow-up me-1"></i>+{{ $stats['new_this_month'] }} this month
                                @else
                                    <i class="fas fa-minus me-1"></i>No new users
                                @endif
                            </small>
                        </div>
                        <div>
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0" id="activeUsers">{{ number_format($stats['active_users']) }}</h2>
                            <p class="mb-0">Active Users</p>
                            <small class="opacity-75">
                                {{ number_format(($stats['active_users'] / max($stats['total_users'], 1)) * 100, 1) }}% of total
                            </small>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0" id="recentlyActive">{{ number_format($stats['recently_active'] ?? 0) }}</h2>
                            <p class="mb-0">Recently Active</p>
                            <small class="opacity-75">Last 7 days</small>
                        </div>
                        <div>
                            <i class="fas fa-clock fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0" id="newThisMonth">{{ number_format($stats['new_this_month']) }}</h2>
                            <p class="mb-0">New This Month</p>
                            <small class="opacity-75">{{ date('F Y') }}</small>
                        </div>
                        <div>
                            <i class="fas fa-user-plus fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Metrics Row -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">{{ number_format($stats['ldap_users'] ?? 0) }}</h2>
                            <p class="mb-0">LDAP Users</p>
                            <small class="opacity-75">Synchronized accounts</small>
                        </div>
                        <div>
                            <i class="fas fa-server fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">{{ number_format($stats['manual_users'] ?? 0) }}</h2>
                            <p class="mb-0">Manual Users</p>
                            <small class="opacity-75">Locally created</small>
                        </div>
                        <div>
                            <i class="fas fa-user-edit fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card chart-card">
                <div class="card-body text-center">
                    <h3 class="text-primary">{{ number_format($stats['users_with_preferences'] ?? 0) }}</h3>
                    <p class="mb-1">With Preferences</p>
                    <div class="progress progress-custom">
                        <div class="progress-bar" style="width: {{ ($stats['users_with_preferences'] / max($stats['total_users'], 1)) * 100 }}%"></div>
                    </div>
                    <small class="text-muted">{{ number_format(($stats['users_with_preferences'] / max($stats['total_users'], 1)) * 100, 1) }}%</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card chart-card">
                <div class="card-body text-center">
                    <h3 class="text-success">{{ number_format($stats['users_in_groups'] ?? 0) }}</h3>
                    <p class="mb-1">In Groups</p>
                    <div class="progress progress-custom">
                        <div class="progress-bar bg-success" style="width: {{ ($stats['users_in_groups'] / max($stats['total_users'], 1)) * 100 }}%"></div>
                    </div>
                    <small class="text-muted">{{ number_format(($stats['users_in_groups'] / max($stats['total_users'], 1)) * 100, 1) }}%</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-building me-2"></i>Users by Department
                    </h5>
                    <button class="btn btn-sm btn-outline-secondary export-button" onclick="exportChart('departmentChart')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="departmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-tag me-2"></i>Users by Role
                    </h5>
                    <button class="btn btn-sm btn-outline-secondary export-button" onclick="exportChart('roleChart')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="roleChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Charts -->
    <div class="row mb-4">
        <div class="col-md-12 mb-4">
            <div class="card chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>User Activity Trends
                    </h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-secondary" onclick="changeActivityPeriod('daily')">Daily</button>
                        <button class="btn btn-sm btn-outline-secondary active" onclick="changeActivityPeriod('weekly')">Weekly</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="changeActivityPeriod('monthly')">Monthly</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 400px;">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Metrics -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card chart-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Department Breakdown
                    </h5>
                </div>
                <div class="card-body">
                    @forelse($stats['by_department'] as $dept)
                        <div class="metric-row">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $dept->department ?: 'Unassigned' }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $dept->count }} users</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary">
                                        {{ number_format(($dept->count / max($stats['total_users'], 1)) * 100, 1) }}%
                                    </span>
                                </div>
                            </div>
                            <div class="progress progress-custom mt-2">
                                <div class="progress-bar" style="width: {{ ($dept->count / max($stats['total_users'], 1)) * 100 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No department data available</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card chart-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>Role Distribution
                    </h5>
                </div>
                <div class="card-body">
                    @forelse($stats['by_role'] as $role)
                        <div class="metric-row">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $role->count }} users</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-info">
                                        {{ number_format(($role->count / max($stats['total_users'], 1)) * 100, 1) }}%
                                    </span>
                                </div>
                            </div>
                            <div class="progress progress-custom mt-2">
                                <div class="progress-bar bg-info" style="width: {{ ($role->count / max($stats['total_users'], 1)) * 100 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No role data available</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- LDAP Sync Information -->
    <div class="row">
        <div class="col-12">
            <div class="card chart-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-sync me-2"></i>LDAP Synchronization Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="mb-2">
                                    @php
                                        $syncStatus = $stats['sync_info']['sync_status'] ?? 'unknown';
                                        $statusClass = $syncStatus === 'completed' ? 'success' : ($syncStatus === 'running' ? 'warning' : 'error');
                                    @endphp
                                    <span class="sync-status-indicator sync-status-{{ $statusClass }}"></span>
                                    <strong>Sync Status</strong>
                                </div>
                                <p class="mb-0">
                                    <span class="badge bg-{{ $syncStatus === 'completed' ? 'success' : ($syncStatus === 'running' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($syncStatus) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <strong>Last Sync</strong>
                                <p class="mb-0">
                                    @if($stats['sync_info']['last_sync'])
                                        {{ \Carbon\Carbon::parse($stats['sync_info']['last_sync'])->diffForHumans() }}
                                        <br>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($stats['sync_info']['last_sync'])->format('M d, Y H:i') }}
                                        </small>
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <strong>Sync Results</strong>
                                <p class="mb-0">
                                    @if($stats['sync_info']['sync_stats'])
                                        <span class="text-success">Created: {{ $stats['sync_info']['sync_stats']['created'] ?? 0 }}</span><br>
                                        <span class="text-info">Updated: {{ $stats['sync_info']['sync_stats']['updated'] ?? 0 }}</span>
                                    @else
                                        <span class="text-muted">No data</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <strong>Quick Actions</strong>
                                <div class="btn-group-vertical w-100">
                                    <button class="btn btn-sm btn-outline-primary" onclick="syncLdap()">
                                        <i class="fas fa-sync me-1"></i>Sync Now
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewSyncLogs()">
                                        <i class="fas fa-list me-1"></i>View Logs
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="fas fa-download me-2"></i>Export Statistics Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <select class="form-select" name="format">
                            <option value="pdf">PDF Report</option>
                            <option value="excel">Excel Spreadsheet</option>
                            <option value="csv">CSV Data</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Include</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_charts" checked>
                            <label class="form-check-label">Charts and Graphs</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_details" checked>
                            <label class="form-check-label">Detailed Breakdown</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_trends">
                            <label class="form-check-label">Historical Trends</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <select class="form-select" name="date_range">
                            <option value="current">Current Data Only</option>
                            <option value="30">Last 30 Days</option>
                            <option value="90">Last 3 Months</option>
                            <option value="365">Last Year</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="executeExport()">
                    <i class="fas fa-download me-1"></i>Export Report
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let departmentChart, roleChart, activityChart;
let currentActivityPeriod = 'weekly';

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    setupEventListeners();
});

function initializeCharts() {
    createDepartmentChart();
    createRoleChart();
    createActivityChart();
}

function createDepartmentChart() {
    const ctx = document.getElementById('departmentChart').getContext('2d');
    const data = @json($stats['by_department']->pluck('count'));
    const labels = @json($stats['by_department']->pluck('department'));

    departmentChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                    '#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56'
                ],
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
                            const label = context.label || '';
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} users (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function createRoleChart() {
    const ctx = document.getElementById('roleChart').getContext('2d');
    const data = @json($stats['by_role']->pluck('count'));
    const labels = @json($stats['by_role']->map(function($role) {
        return $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name));
    }));

    roleChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#DC3545', '#FFC107', '#007BFF', '#6C757D', 
                    '#28A745', '#17A2B8', '#6F42C1', '#E83E8C',
                    '#20C997', '#FD7E14', '#6610F2', '#D63384'
                ],
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
                            const label = context.label || '';
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} users (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function createActivityChart() {
    const ctx = document.getElementById('activityChart').getContext('2d');
    
    // Sample data - you would replace this with actual data from backend
    const activityData = {
        daily: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Active Users',
                data: [45, 52, 48, 61, 55, 23, 18],
                borderColor: '#007BFF',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }, {
                label: 'New Logins',
                data: [38, 45, 42, 55, 48, 20, 15],
                borderColor: '#28A745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }]
        },
        weekly: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Active Users',
                data: [280, 320, 290, 340],
                borderColor: '#007BFF',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }, {
                label: 'New Logins',
                data: [250, 290, 260, 310],
                borderColor: '#28A745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }]
        },
        monthly: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Active Users',
                data: [1200, 1350, 1280, 1420, 1380, 1450],
                borderColor: '#007BFF',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }, {
                label: 'New Logins',
                data: [1100, 1250, 1180, 1320, 1280, 1350],
                borderColor: '#28A745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }]
        }
    };

    activityChart = new Chart(ctx, {
        type: 'line',
        data: activityData[currentActivityPeriod],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}

function changeActivityPeriod(period) {
    currentActivityPeriod = period;
    
    // Update active button
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update chart data
    const activityData = {
        daily: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Active Users',
                data: [45, 52, 48, 61, 55, 23, 18],
                borderColor: '#007BFF',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }, {
                label: 'New Logins',
                data: [38, 45, 42, 55, 48, 20, 15],
                borderColor: '#28A745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }]
        },
        weekly: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Active Users',
                data: [280, 320, 290, 340],
                borderColor: '#007BFF',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }, {
                label: 'New Logins',
                data: [250, 290, 260, 310],
                borderColor: '#28A745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }]
        },
        monthly: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Active Users',
                data: [1200, 1350, 1280, 1420, 1380, 1450],
                borderColor: '#007BFF',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }, {
                label: 'New Logins',
                data: [1100, 1250, 1180, 1320, 1280, 1350],
                borderColor: '#28A745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }]
        }
    };
    
    activityChart.data = activityData[period];
    activityChart.update();
}

function updateCharts() {
    // Get filter values
    const timePeriod = document.getElementById('timePeriod').value;
    const department = document.getElementById('departmentFilter').value;
    const source = document.getElementById('sourceFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    // Show loading indicator
    showLoading();
    
    // Fetch updated data
    fetch('/users/stats/data', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            time_period: timePeriod,
            department: department,
            source: source,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        updateChartsWithData(data);
        updateStatCards(data);
        hideLoading();
    })
    .catch(error => {
        console.error('Error updating charts:', error);
        hideLoading();
        showAlert('error', 'Failed to update statistics');
    });
}

function updateChartsWithData(data) {
    // Update department chart
    if (departmentChart && data.by_department) {
        departmentChart.data.labels = data.by_department.map(d => d.department);
        departmentChart.data.datasets[0].data = data.by_department.map(d => d.count);
        departmentChart.update();
    }
    
    // Update role chart
    if (roleChart && data.by_role) {
        roleChart.data.labels = data.by_role.map(r => r.display_name || r.name);
        roleChart.data.datasets[0].data = data.by_role.map(r => r.count);
        roleChart.update();
    }
}

function updateStatCards(data) {
    const elements = {
        'totalUsers': data.total_users,
        'activeUsers': data.active_users,
        'recentlyActive': data.recently_active,
        'newThisMonth': data.new_this_month
    };
    
    Object.keys(elements).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = numberFormat(elements[id]);
        }
    });
}

function refreshStats() {
    location.reload();
}

function exportStats() {
    const modal = new bootstrap.Modal(document.getElementById('exportModal'));
    modal.show();
}

function executeExport() {
    const form = document.getElementById('exportForm');
    const formData = new FormData(form);
    
    // Show loading
    const exportBtn = event.target;
    exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generating...';
    exportBtn.disabled = true;
    
    // Create download
    const params = new URLSearchParams();
    for (let [key, value] of formData.entries()) {
        if (value) params.append(key, value);
    }
    
    const url = `/users/stats/export?${params.toString()}`;
    window.open(url, '_blank');
    
    // Reset button
    setTimeout(() => {
        exportBtn.innerHTML = '<i class="fas fa-download me-1"></i>Export Report';
        exportBtn.disabled = false;
        bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
    }, 2000);
}

function exportChart(chartId) {
    const chart = window[chartId];
    if (chart) {
        const url = chart.toBase64Image();
        const link = document.createElement('a');
        link.download = `${chartId}.png`;
        link.href = url;
        link.click();
    }
}

function syncLdap() {
    if (!confirm('Are you sure you want to start LDAP synchronization?')) {
        return;
    }
    
    fetch('/users/sync-ldap', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Failed to start LDAP sync');
    });
}

function viewSyncLogs() {
    window.open('/admin/logs?filter=ldap', '_blank');
}

function setupEventListeners() {
    // Auto-refresh every 5 minutes
    setInterval(() => {
        updateCharts();
    }, 300000);
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            refreshStats();
        }
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            exportStats();
        }
    });
}

// Utility functions
function numberFormat(num) {
    return new Intl.NumberFormat().format(num);
}

function showLoading() {
    document.body.style.cursor = 'wait';
    const loadingElements = document.querySelectorAll('.chart-container');
    loadingElements.forEach(el => {
        el.style.opacity = '0.5';
        el.style.pointerEvents = 'none';
    });
}

function hideLoading() {
    document.body.style.cursor = 'default';
    const loadingElements = document.querySelectorAll('.chart-container');
    loadingElements.forEach(el => {
        el.style.opacity = '1';
        el.style.pointerEvents = 'auto';
    });
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1055; max-width: 400px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
@endpush