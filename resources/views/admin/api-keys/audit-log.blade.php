@extends('layouts.app')

@section('title', 'API Audit Log')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.api-keys.index') }}">API Keys</a></li>
                    <li class="breadcrumb-item active">Audit Log</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">API Audit Log</h1>
            <p class="mb-0 text-muted">Complete audit trail of API key management activities</p>
        </div>
        <div>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                    <i class="fas fa-download"></i> Export
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="{{ route('admin.api-keys.audit-log') }}?export=csv">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                    <a class="dropdown-item" href="{{ route('admin.api-keys.audit-log') }}?export=excel">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a class="dropdown-item" href="{{ route('admin.api-keys.audit-log') }}?export=pdf">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                </div>
            </div>
            <button class="btn btn-secondary ml-2" onclick="location.reload()">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.api-keys.audit-log') }}">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="api_key_id">API Key</label>
                            <select class="form-control" id="api_key_id" name="api_key_id">
                                <option value="">All API Keys</option>
                                @foreach($apiKeys ?? [] as $key)
                                    <option value="{{ $key->id }}" {{ request('api_key_id') == $key->id ? 'selected' : '' }}>
                                        {{ $key->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="event">Event Type</label>
                            <select class="form-control" id="event" name="event">
                                <option value="">All Events</option>
                                <option value="created" {{ request('event') === 'created' ? 'selected' : '' }}>Created</option>
                                <option value="updated" {{ request('event') === 'updated' ? 'selected' : '' }}>Updated</option>
                                <option value="deleted" {{ request('event') === 'deleted' ? 'selected' : '' }}>Deleted</option>
                                <option value="regenerated" {{ request('event') === 'regenerated' ? 'selected' : '' }}>Regenerated</option>
                                <option value="activated" {{ request('event') === 'activated' ? 'selected' : '' }}>Activated</option>
                                <option value="deactivated" {{ request('event') === 'deactivated' ? 'selected' : '' }}>Deactivated</option>
                                <option value="usage_reset" {{ request('event') === 'usage_reset' ? 'selected' : '' }}>Usage Reset</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="user">User</label>
                            <select class="form-control" id="user" name="user">
                                <option value="">All Users</option>
                                @foreach($users ?? [] as $user)
                                    <option value="{{ $user->id }}" {{ request('user') == $user->id ? 'selected' : '' }}>
                                        {{ $user->display_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_from">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="{{ request('date_from') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_to">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="{{ route('admin.api-keys.audit-log') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Events
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $logs->total() ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Events Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="events-today">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Active Users
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="active-users">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Critical Events
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="critical-events">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Log Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Audit Trail</h6>
        </div>
        <div class="card-body">
            @if(isset($logs) && $logs->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Event</th>
                                <th>API Key</th>
                                <th>User</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Changes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>
                                        <div>{{ $log->created_at->format('M j, Y') }}</div>
                                        <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $eventColors = [
                                                'created' => 'success',
                                                'updated' => 'info',
                                                'deleted' => 'danger',
                                                'regenerated' => 'warning',
                                                'activated' => 'success',
                                                'deactivated' => 'secondary',
                                                'usage_reset' => 'info'
                                            ];
                                            $eventIcons = [
                                                'created' => 'plus',
                                                'updated' => 'edit',
                                                'deleted' => 'trash',
                                                'regenerated' => 'sync',
                                                'activated' => 'play',
                                                'deactivated' => 'pause',
                                                'usage_reset' => 'chart-line'
                                            ];
                                        @endphp
                                        <span class="badge badge-{{ $eventColors[$log->event] ?? 'secondary' }}">
                                            <i class="fas fa-{{ $eventIcons[$log->event] ?? 'info' }}"></i>
                                            {{ ucfirst(str_replace('_', ' ', $log->event)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($log->subject)
                                            <div>
                                                <strong>{{ $log->subject->name }}</strong>
                                            </div>
                                            <small class="text-muted">{{ $log->subject->masked_key }}</small>
                                        @else
                                            <span class="text-muted">Deleted API Key</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->causer)
                                            <div>
                                                <strong>{{ $log->causer->display_name }}</strong>
                                            </div>
                                            <small class="text-muted">{{ $log->causer->email }}</small>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $log->description }}
                                        @if($log->properties && isset($log->properties['reason']))
                                            <br><small class="text-muted">Reason: {{ $log->properties['reason'] }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($log->properties['ip_address']))
                                            <code>{{ $log->properties['ip_address'] }}</code>
                                        @else
                                            <span class="text-muted">Unknown</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->properties && (isset($log->properties['old']) || isset($log->properties['attributes'])))
                                            <button class="btn btn-sm btn-outline-info" onclick="showChanges({{ $log->id }})">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="showLogDetails({{ $log->id }})">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                            @if($log->subject)
                                                <a href="{{ route('admin.api-keys.show', $log->subject) }}" 
                                                   class="btn btn-sm btn-outline-success" title="View API Key">
                                                    <i class="fas fa-key"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} results
                    </div>
                    <div>
                        {{ $logs->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Audit Logs Found</h5>
                    <p class="text-muted">No audit logs match your current filters.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Activity Chart -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Activity Timeline (Last 30 Days)</h6>
                </div>
                <div class="card-body">
                    <canvas id="activityChart" width="400" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Event Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="eventChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Critical Events -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Critical Events</h6>
        </div>
        <div class="card-body">
            <div class="timeline" id="critical-events-timeline">
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading critical events...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- User Activity Summary -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Most Active Users (Last 7 Days)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm" id="active-users-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Events</th>
                                    <th>Last Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="text-center">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Event Frequency by Type</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm" id="event-frequency-table">
                            <thead>
                                <tr>
                                    <th>Event Type</th>
                                    <th>Count</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="text-center">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit Log Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Changes Modal -->
<div class="modal fade" id="changesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Changes Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="changesContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Loading changes...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table td {
        vertical-align: middle;
    }
    
    .badge {
        font-size: 0.75em;
    }
    
    .btn-group .btn {
        border-radius: 0;
    }
    
    .btn-group .btn:first-child {
        border-top-left-radius: 0.35rem;
        border-bottom-left-radius: 0.35rem;
    }
    
    .btn-group .btn:last-child {
        border-top-right-radius: 0.35rem;
        border-bottom-right-radius: 0.35rem;
    }
    
    .change-added {
        background-color: #d4edda;
        color: #155724;
        padding: 2px 4px;
        border-radius: 3px;
    }
    
    .change-removed {
        background-color: #f8d7da;
        color: #721c24;
        padding: 2px 4px;
        border-radius: 3px;
    }
    
    .change-modified {
        background-color: #fff3cd;
        color: #856404;
        padding: 2px 4px;
        border-radius: 3px;
    }
    
    .json-key {
        color: #007bff;
        font-weight: bold;
    }
    
    .json-string {
        color: #28a745;
    }
    
    .json-number {
        color: #dc3545;
    }
    
    .json-boolean {
        color: #6f42c1;
    }
    
    .json-null {
        color: #6c757d;
        font-style: italic;
    }
    
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-marker {
        position: absolute;
        left: -25px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #dee2e6;
    }
    
    .timeline-content {
        background: #f8f9fc;
        border-radius: 0.35rem;
        padding: 0.75rem;
    }
    
    .timeline-title {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .timeline-text {
        font-size: 0.8rem;
        margin-bottom: 0;
        color: #6c757d;
    }
</style>
@endpush

@push('scripts')
<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadStatistics();
    initializeCharts();
    loadCriticalEvents();
    loadActiveUsers();
    loadEventFrequency();
    
    // Auto-refresh every 30 seconds
    setInterval(loadStatistics, 30000);
});

// Load statistics
function loadStatistics() {
    fetch('/admin/api-keys/audit-statistics')
        .then(response => response.json())
        .then(data => {
            document.getElementById('events-today').textContent = data.events_today || 0;
            document.getElementById('active-users').textContent = data.active_users || 0;
            document.getElementById('critical-events').textContent = data.critical_events || 0;
        })
        .catch(error => {
            console.error('Error loading statistics:', error);
        });
}

// Initialize charts
function initializeCharts() {
    initializeActivityChart();
    initializeEventChart();
}

// Activity timeline chart
function initializeActivityChart() {
    fetch('/admin/api-keys/audit-chart-data')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('activityChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.activity_chart.labels,
                    datasets: [{
                        label: 'Events',
                        data: data.activity_chart.data,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.1,
                        fill: true
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
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading activity chart:', error);
        });
}

// Event distribution chart
function initializeEventChart() {
    fetch('/admin/api-keys/audit-chart-data')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('eventChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.event_chart.labels,
                    datasets: [{
                        data: data.event_chart.data,
                        backgroundColor: [
                            '#36a2eb',
                            '#ff6384',
                            '#ff9f40',
                            '#ffcd56',
                            '#4bc0c0',
                            '#9966ff',
                            '#ff6b6b'
                        ]
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
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading event chart:', error);
        });
}

// Load critical events timeline
function loadCriticalEvents() {
    fetch('/admin/api-keys/critical-events')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('critical-events-timeline');
            
            if (data.events && data.events.length > 0) {
                let html = '';
                data.events.forEach(event => {
                    const markerClass = event.severity === 'high' ? 'bg-danger' : 
                                       event.severity === 'medium' ? 'bg-warning' : 'bg-info';
                    html += `
                        <div class="timeline-item">
                            <div class="timeline-marker ${markerClass}"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">${event.title}</div>
                                <div class="timeline-text">
                                    ${event.description}<br>
                                    <small class="text-muted">${event.time}</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-shield-alt fa-2x mb-2"></i>
                        <p class="small">No critical events in the last 24 hours</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading critical events:', error);
            document.getElementById('critical-events-timeline').innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p class="small">Failed to load events</p>
                </div>
            `;
        });
}

// Load active users
function loadActiveUsers() {
    fetch('/admin/api-keys/active-users')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#active-users-table tbody');
            
            if (data.users && data.users.length > 0) {
                let html = '';
                data.users.forEach(user => {
                    html += `
                        <tr>
                            <td>
                                <strong>${user.display_name}</strong><br>
                                <small class="text-muted">${user.email}</small>
                            </td>
                            <td><span class="badge badge-primary">${user.event_count}</span></td>
                            <td><small class="text-muted">${user.last_activity}</small></td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center text-muted">No active users found</td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading active users:', error);
            document.querySelector('#active-users-table tbody').innerHTML = `
                <tr>
                    <td colspan="3" class="text-center text-danger">Error loading data</td>
                </tr>
            `;
        });
}

// Load event frequency
function loadEventFrequency() {
    fetch('/admin/api-keys/event-frequency')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#event-frequency-table tbody');
            
            if (data.events && data.events.length > 0) {
                let html = '';
                data.events.forEach(event => {
                    const trendIcon = event.trend > 0 ? 'fa-arrow-up text-success' : 
                                     event.trend < 0 ? 'fa-arrow-down text-danger' : 
                                     'fa-minus text-muted';
                    html += `
                        <tr>
                            <td>
                                <span class="badge badge-${getEventBadgeClass(event.type)}">${event.type}</span>
                            </td>
                            <td><strong>${event.count}</strong></td>
                            <td>
                                <i class="fas ${trendIcon}"></i>
                                <span class="small">${Math.abs(event.trend)}%</span>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center text-muted">No events found</td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading event frequency:', error);
            document.querySelector('#event-frequency-table tbody').innerHTML = `
                <tr>
                    <td colspan="3" class="text-center text-danger">Error loading data</td>
                </tr>
            `;
        });
}

// Show log details
function showLogDetails(logId) {
    $('#logDetailsModal').modal('show');
    
    fetch(`/admin/api-keys/audit-log/${logId}`)
        .then(response => response.json())
        .then(data => {
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Event Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>Event ID:</strong></td><td>${data.id}</td></tr>
                            <tr><td><strong>Event Type:</strong></td><td><span class="badge badge-${getEventBadgeClass(data.event)}">${data.event}</span></td></tr>
                            <tr><td><strong>Description:</strong></td><td>${data.description}</td></tr>
                            <tr><td><strong>Timestamp:</strong></td><td>${data.created_at}</td></tr>
                            <tr><td><strong>IP Address:</strong></td><td><code>${data.ip_address || 'Unknown'}</code></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Context Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>User:</strong></td><td>${data.causer ? data.causer.display_name : 'System'}</td></tr>
                            <tr><td><strong>API Key:</strong></td><td>${data.subject ? data.subject.name : 'Deleted'}</td></tr>
                            <tr><td><strong>User Agent:</strong></td><td class="small">${data.user_agent || 'Unknown'}</td></tr>
                            <tr><td><strong>Session ID:</strong></td><td><code>${data.session_id || 'N/A'}</code></td></tr>
                        </table>
                    </div>
                </div>
                
                ${data.properties ? `
                <div class="mt-3">
                    <h6>Additional Properties</h6>
                    <pre class="bg-light p-3 rounded"><code>${formatJSON(data.properties)}</code></pre>
                </div>
                ` : ''}
            `;
            
            document.getElementById('logDetailsContent').innerHTML = content;
        })
        .catch(error => {
            document.getElementById('logDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Failed to load log details. Please try again.
                </div>
            `;
        });
}

// Show changes details
function showChanges(logId) {
    $('#changesModal').modal('show');
    
    fetch(`/admin/api-keys/audit-log/${logId}/changes`)
        .then(response => response.json())
        .then(data => {
            let content = '';
            
            if (data.changes && Object.keys(data.changes).length > 0) {
                content = '<div class="table-responsive"><table class="table table-sm">';
                content += '<thead><tr><th>Field</th><th>Old Value</th><th>New Value</th></tr></thead><tbody>';
                
                Object.entries(data.changes).forEach(([field, change]) => {
                    content += `
                        <tr>
                            <td><strong>${field}</strong></td>
                            <td>${change.old !== undefined ? `<span class="change-removed">${formatValue(change.old)}</span>` : '<span class="text-muted">-</span>'}</td>
                            <td>${change.new !== undefined ? `<span class="change-added">${formatValue(change.new)}</span>` : '<span class="text-muted">-</span>'}</td>
                        </tr>
                    `;
                });
                
                content += '</tbody></table></div>';
            } else {
                content = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        No detailed changes recorded for this event.
                    </div>
                `;
            }
            
            document.getElementById('changesContent').innerHTML = content;
        })
        .catch(error => {
            document.getElementById('changesContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Failed to load changes. Please try again.
                </div>
            `;
        });
}

// Utility functions
function getEventBadgeClass(event) {
    const classes = {
        'created': 'success',
        'updated': 'info',
        'deleted': 'danger',
        'regenerated': 'warning',
        'activated': 'success',
        'deactivated': 'secondary',
        'usage_reset': 'info'
    };
    return classes[event] || 'secondary';
}

function formatValue(value) {
    if (value === null) return '<span class="json-null">null</span>';
    if (typeof value === 'boolean') return `<span class="json-boolean">${value}</span>`;
    if (typeof value === 'number') return `<span class="json-number">${value}</span>`;
    if (typeof value === 'string') return `<span class="json-string">"${value}"</span>`;
    if (typeof value === 'object') return `<code>${JSON.stringify(value, null, 2)}</code>`;
    return value;
}

function formatJSON(obj) {
    if (!obj) return '';
    
    const json = JSON.stringify(obj, null, 2);
    return json
        .replace(/(".*?")(:\s*)/g, '<span class="json-key">$1</span>$2')
        .replace(/:\s*(".*?")/g, ': <span class="json-string">$1</span>')
        .replace(/:\s*(\d+)/g, ': <span class="json-number">$1</span>')
        .replace(/:\s*(true|false)/g, ': <span class="json-boolean">$1</span>')
        .replace(/:\s*(null)/g, ': <span class="json-null">$1</span>');
}

// Real-time updates
function enableRealTimeUpdates() {
    setInterval(() => {
        const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
        if (currentPage == 1) { // Only check for updates on first page
            fetch('/admin/api-keys/audit-log/latest')
                .then(response => response.json())
                .then(data => {
                    if (data.has_new_events) {
                        showNewEventsNotification(data.new_count);
                    }
                })
                .catch(error => console.error('Error checking for updates:', error));
        }
    }, 15000); // Check every 15 seconds
}

function showNewEventsNotification(count) {
    if (document.getElementById('newEventsAlert')) return;
    
    const alert = document.createElement('div');
    alert.id = 'newEventsAlert';
    alert.className = 'alert alert-info alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-info-circle"></i>
        <strong>${count}</strong> new audit event${count > 1 ? 's' : ''} available.
        <button type="button" class="btn btn-sm btn-outline-primary ml-2" onclick="window.location.reload()">
            Refresh
        </button>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 10000);
}

// Enable real-time updates
enableRealTimeUpdates();

// Search functionality
document.getElementById('api_key_id').addEventListener('change', function() {
    if (this.value) {
        // Auto-submit form when API key is selected
        this.form.submit();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + R to refresh
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        window.location.reload();
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        $('.modal').modal('hide');
    }
});

// Export functionality
function exportAuditLog(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    window.open(`${window.location.pathname}?${params.toString()}`, '_blank');
}

// Auto-complete for API key selection
$(document).ready(function() {
    $('#api_key_id').select2({
        placeholder: 'Select an API Key',
        allowClear: true,
        width: '100%'
    });
    
    $('#user').select2({
        placeholder: 'Select a User',
        allowClear: true,
        width: '100%'
    });
});

// Advanced filtering
function toggleAdvancedFilters() {
    const advancedFilters = document.getElementById('advanced-filters');
    const toggleBtn = document.getElementById('toggle-advanced-btn');
    
    if (advancedFilters.style.display === 'none') {
        advancedFilters.style.display = 'block';
        toggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Advanced Filters';
    } else {
        advancedFilters.style.display = 'none';
        toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Show Advanced Filters';
    }
}

// Print functionality
function printAuditLog() {
    window.print();
}

// Save filters as preset
function saveFilterPreset() {
    const presetName = prompt('Enter a name for this filter preset:');
    if (presetName) {
        const filters = {
            api_key_id: document.getElementById('api_key_id').value,
            event: document.getElementById('event').value,
            user: document.getElementById('user').value,
            date_from: document.getElementById('date_from').value,
            date_to: document.getElementById('date_to').value
        };
        
        localStorage.setItem(`audit_filter_${presetName}`, JSON.stringify(filters));
        alert('Filter preset saved successfully!');
        loadFilterPresets();
    }
}

// Load filter presets
function loadFilterPresets() {
    const presets = [];
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key.startsWith('audit_filter_')) {
            const presetName = key.replace('audit_filter_', '');
            presets.push(presetName);
        }
    }
    
    const presetSelect = document.getElementById('filter-presets');
    if (presetSelect) {
        presetSelect.innerHTML = '<option value="">Select a preset...</option>';
        presets.forEach(preset => {
            const option = document.createElement('option');
            option.value = preset;
            option.textContent = preset;
            presetSelect.appendChild(option);
        });
    }
}

// Apply filter preset
function applyFilterPreset(presetName) {
    if (!presetName) return;
    
    const filtersJson = localStorage.getItem(`audit_filter_${presetName}`);
    if (filtersJson) {
        const filters = JSON.parse(filtersJson);
        
        Object.entries(filters).forEach(([key, value]) => {
            const element = document.getElementById(key);
            if (element) {
                element.value = value;
                if (element.tagName === 'SELECT' && window.jQuery) {
                    $(element).trigger('change');
                }
            }
        });
    }
}

// Delete filter preset
function deleteFilterPreset(presetName) {
    if (confirm(`Are you sure you want to delete the preset "${presetName}"?`)) {
        localStorage.removeItem(`audit_filter_${presetName}`);
        loadFilterPresets();
        alert('Filter preset deleted successfully!');
    }
}

// Bulk operations
function selectAllLogs() {
    const checkboxes = document.querySelectorAll('.log-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = true);
    updateBulkActions();
}

function deselectAllLogs() {
    const checkboxes = document.querySelectorAll('.log-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    updateBulkActions();
}

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.log-checkbox:checked');
    const bulkActions = document.getElementById('bulk-actions');
    
    if (bulkActions) {
        bulkActions.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
        document.getElementById('selected-count').textContent = checkedBoxes.length;
    }
}

// Performance monitoring
function trackPagePerformance() {
    if (window.performance && window.performance.timing) {
        const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
        
        if (loadTime > 3000) {
            console.warn('Audit log page loaded slowly:', loadTime + 'ms');
            
            // Show performance warning
            const warning = document.createElement('div');
            warning.className = 'alert alert-warning alert-dismissible fade show';
            warning.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                This page took ${Math.round(loadTime/1000)} seconds to load. Consider applying filters to improve performance.
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            `;
            
            document.querySelector('.container-fluid').insertBefore(warning, document.querySelector('.container-fluid').firstChild);
        }
    }
}

// Initialize performance tracking
window.addEventListener('load', trackPagePerformance);

// Accessibility improvements
document.addEventListener('DOMContentLoaded', function() {
    // Add ARIA labels
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        if (!button.getAttribute('aria-label') && button.title) {
            button.setAttribute('aria-label', button.title);
        }
    });
    
    // Keyboard navigation for modals
    $('.modal').on('shown.bs.modal', function() {
        $(this).find('[autofocus]').focus();
    });
    
    // Focus management
    $('.modal').on('hidden.bs.modal', function() {
        $('[data-toggle="modal"]').focus();
    });
});

// Data refresh indicator
function showRefreshIndicator() {
    const indicator = document.createElement('div');
    indicator.id = 'refresh-indicator';
    indicator.className = 'position-fixed';
    indicator.style.cssText = 'top: 10px; left: 50%; transform: translateX(-50%); z-index: 9999;';
    indicator.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-sync fa-spin"></i> Refreshing data...
        </div>
    `;
    
    document.body.appendChild(indicator);
}

function hideRefreshIndicator() {
    const indicator = document.getElementById('refresh-indicator');
    if (indicator) {
        indicator.remove();
    }
}

// Auto-save scroll position
window.addEventListener('scroll', function() {
    localStorage.setItem('audit_log_scroll_position', window.scrollY);
});

// Restore scroll position
window.addEventListener('load', function() {
    const scrollPosition = localStorage.getItem('audit_log_scroll_position');
    if (scrollPosition) {
        window.scrollTo(0, parseInt(scrollPosition));
    }
});

// Custom date range picker
function initializeDateRangePicker() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        // Set max date to today
        input.max = new Date().toISOString().split('T')[0];
        
        // Add quick date selection
        const quickDates = document.createElement('div');
        quickDates.className = 'btn-group btn-group-sm mt-1';
        quickDates.innerHTML = `
            <button type="button" class="btn btn-outline-secondary" onclick="setQuickDate('${input.id}', 0)">Today</button>
            <button type="button" class="btn btn-outline-secondary" onclick="setQuickDate('${input.id}', 7)">7 days</button>
            <button type="button" class="btn btn-outline-secondary" onclick="setQuickDate('${input.id}', 30)">30 days</button>
        `;
        
        input.parentNode.appendChild(quickDates);
    });
}

function setQuickDate(inputId, daysAgo) {
    const date = new Date();
    date.setDate(date.getDate() - daysAgo);
    document.getElementById(inputId).value = date.toISOString().split('T')[0];
}

// Initialize components
initializeDateRangePicker();
loadFilterPresets();
</script>
@endpush') }}</div>
                                        <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $eventColors = [
                                                'created' => 'success',
                                                'updated' => 'info',
                                                'deleted' => 'danger',
                                                'regenerated' => 'warning',
                                                'activated' => 'success',
                                                'deactivated' => 'secondary',
                                                'usage_reset' => 'info'
                                            ];
                                            $eventIcons = [
                                                'created' => 'plus',
                                                'updated' => 'edit',
                                                'deleted' => 'trash',
                                                'regenerated' => 'sync',
                                                'activated' => 'play',
                                                'deactivated' => 'pause',
                                                'usage_reset' => 'chart-line'
                                            ];
                                        @endphp
                                        <span class="badge badge-{{ $eventColors[$log->event] ?? 'secondary' }}">
                                            <i class="fas fa-{{ $eventIcons[$log->event] ?? 'info' }}"></i>
                                            {{ ucfirst(str_replace('_', ' ', $log->event)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($log->subject)
                                            <div>
                                                <strong>{{ $log->subject->name }}</strong>
                                            </div>
                                            <small class="text-muted">{{ $log->subject->masked_key }}</small>
                                        @else
                                            <span class="text-muted">Deleted API Key</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->causer)
                                            <div>
                                                <strong>{{ $log->causer->display_name }}</strong>
                                            </div>
                                            <small class="text-muted">{{ $log->causer->email }}</small>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $log->description }}
                                        @if($log->properties && isset($log->properties['reason']))
                                            <br><small class="text-muted">Reason: {{ $log->properties['reason'] }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($log->properties['ip_address']))
                                            <code>{{ $log->properties['ip_address'] }}</code>
                                        @else
                                            <span class="text-muted">Unknown</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->properties && (isset($log->properties['old']) || isset($log->properties['attributes'])))
                                            <button class="btn btn-sm btn-outline-info" onclick="showChanges({{ $log->id }})">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="showLogDetails({{ $log->id }})">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                            @if($log->subject)
                                                <a href="{{ route('admin.api-keys.show', $log->subject) }}" 
                                                   class="btn btn-sm btn-outline-success" title="View API Key">
                                                    <i class="fas fa-key"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} results
                    </div>
                    <div>
                        {{ $logs->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Audit Logs Found</h5>
                    <p class="text-muted">No audit logs match your current filters.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Activity Chart -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Activity Timeline (Last 30 Days)</h6>
                </div>
                <div class="card-body">
                    <canvas id="activityChart" width="400" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Event Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="eventChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit Log Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Changes Modal -->
<div class="modal fade" id="changesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Changes Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="changesContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Loading changes...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table td {
        vertical-align: middle;
    }
    
    .badge {
        font-size: 0.75em;
    }
    
    .btn-group .btn {
        border-radius: 0;
    }
    
    .btn-group .btn:first-child {
        border-top-left-radius: 0.35rem;
        border-bottom-left-radius: 0.35rem;
    }
    
    .btn-group .btn:last-child {
        border-top-right-radius: 0.35rem;
        border-bottom-right-radius: 0.35rem;
    }
    
    .change-added {
        background-color: #d4edda;
        color: #155724;
        padding: 2px 4px;
        border-radius: 3px;
    }
    
    .change-removed {
        background-color: #f8d7da;
        color: #721c24;
        padding: 2px 4px;
        border-radius: 3px;
    }
    
    .change-modified {
        background-color: #fff3cd;
        color: #856404;
        padding: 2px 4px;
        border-radius: 3px;
    }
    
    .json-key {
        color: #007bff;
        font-weight: bold;
    }
    
    .json-string {
        color: #28a745;
    }
    
    .json-number {
        color: #dc3545;
    }
    
    .json-boolean {
        color: #6f42c1;
    }
    
    .json-null {
        color: #6c757d;
        font-style: italic;
    }
</style>
@endpush

@push('scripts')
<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadStatistics();
    initializeCharts();
    
    // Auto-refresh every 30 seconds
    setInterval(loadStatistics, 30000);
});

// Load statistics
function loadStatistics() {
    fetch('/admin/api-keys/audit-statistics')
        .then(response => response.json())
        .then(data => {
            document.getElementById('events-today').textContent = data.events_today || 0;
            document.getElementById('active-users').textContent = data.active_users || 0;
            document.getElementById('critical-events').textContent = data.critical_events || 0;
        })
        .catch(error => {
            console.error('Error loading statistics:', error);
        });
}

// Initialize charts
function initializeCharts() {
    initializeActivityChart();
    initializeEventChart();
}

// Activity timeline chart
function initializeActivityChart() {
    fetch('/admin/api-keys/audit-chart-data')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('activityChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.activity_chart.labels,
                    datasets: [{
                        label: 'Events',
                        data: data.activity_chart.data,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.1,
                        fill: true
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
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading activity chart:', error);
        });
}

// Event distribution chart
function initializeEventChart() {
    fetch('/admin/api-keys/audit-chart-data')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('eventChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.event_chart.labels,
                    datasets: [{
                        data: data.event_chart.data,
                        backgroundColor: [
                            '#36a2eb',
                            '#ff6384',
                            '#ff9f40',
                            '#ffcd56',
                            '#4bc0c0',
                            '#9966ff',
                            '#ff6b6b'
                        ]
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
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading event chart:', error);
        });
}

// Show log details
function showLogDetails(logId) {
    $('#logDetailsModal').modal('show');
    
    fetch(`/admin/api-keys/audit-log/${logId}`)
        .then(response => response.json())
        .then(data => {
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Event Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>Event ID:</strong></td><td>${data.id}</td></tr>
                            <tr><td><strong>Event Type:</strong></td><td><span class="badge badge-${getEventBadgeClass(data.event)}">${data.event}</span></td></tr>
                            <tr><td><strong>Description:</strong></td><td>${data.description}</td></tr>
                            <tr><td><strong>Timestamp:</strong></td><td>${data.created_at}</td></tr>
                            <tr><td><strong>IP Address:</strong></td><td><code>${data.ip_address || 'Unknown'}</code></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Context Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>User:</strong></td><td>${data.causer ? data.causer.display_name : 'System'}</td></tr>
                            <tr><td><strong>API Key:</strong></td><td>${data.subject ? data.subject.name : 'Deleted'}</td></tr>
                            <tr><td><strong>User Agent:</strong></td><td class="small">${data.user_agent || 'Unknown'}</td></tr>
                            <tr><td><strong>Session ID:</strong></td><td><code>${data.session_id || 'N/A'}</code></td></tr>
                        </table>
                    </div>
                </div>
                
                ${data.properties ? `
                <div class="mt-3">
                    <h6>Additional Properties</h6>
                    <pre class="bg-light p-3 rounded"><code>${formatJSON(data.properties)}</code></pre>
                </div>
                ` : ''}
            `;
            
            document.getElementById('logDetailsContent').innerHTML = content;
        })
        .catch(error => {
            document.getElementById('logDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Failed to load log details. Please try again.
                </div>
            `;
        });
}

// Show changes details
function showChanges(logId) {
    $('#changesModal').modal('show');
    
    fetch(`/admin/api-keys/audit-log/${logId}/changes`)
        .then(response => response.json())
        .then(data => {
            let content = '';
            
            if (data.changes && Object.keys(data.changes).length > 0) {
                content = '<div class="table-responsive"><table class="table table-sm">';
                content += '<thead><tr><th>Field</th><th>Old Value</th><th>New Value</th></tr></thead><tbody>';
                
                Object.entries(data.changes).forEach(([field, change]) => {
                    content += `
                        <tr>
                            <td><strong>${field}</strong></td>
                            <td>${change.old !== undefined ? `<span class="change-removed">${formatValue(change.old)}</span>` : '<span class="text-muted">-</span>'}</td>
                            <td>${change.new !== undefined ? `<span class="change-added">${formatValue(change.new)}</span>` : '<span class="text-muted">-</span>'}</td>
                        </tr>
                    `;
                });
                
                content += '</tbody></table></div>';
            } else {
                content = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        No detailed changes recorded for this event.
                    </div>
                `;
            }
            
            document.getElementById('changesContent').innerHTML = content;
        })
        .catch(error => {
            document.getElementById('changesContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Failed to load changes. Please try again.
                </div>
            `;
        });
}

// Utility functions
function getEventBadgeClass(event) {
    const classes = {
        'created': 'success',
        'updated': 'info',
        'deleted': 'danger',
        'regenerated': 'warning',
        'activated': 'success',
        'deactivated': 'secondary',
        'usage_reset': 'info'
    };
    return classes[event] || 'secondary';
}

function formatValue(value) {
    if (value === null) return '<span class="json-null">null</span>';
    if (typeof value === 'boolean') return `<span class="json-boolean">${value}</span>`;
    if (typeof value === 'number') return `<span class="json-number">${value}</span>`;
    if (typeof value === 'string') return `<span class="json-string">"${value}"</span>`;
    if (typeof value === 'object') return `<code>${JSON.stringify(value, null, 2)}</code>`;
    return value;
}

function formatJSON(obj) {
    if (!obj) return '';
    
    const json = JSON.stringify(obj, null, 2);
    return json
        .replace(/(".*?")(:\s*)/g, '<span class="json-key">$1</span>$2')
        .replace(/:\s*(".*?")/g, ': <span class="json-string">$1</span>')
        .replace(/:\s*(\d+)/g, ': <span class="json-number">$1</span>')
        .replace(/:\s*(true|false)/g, ': <span class="json-boolean">$1</span>')
        .replace(/:\s*(null)/g, ': <span class="json-null">$1</span>');
}

// Real-time updates
function enableRealTimeUpdates() {
    setInterval(() => {
        const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
        if (currentPage == 1) { // Only check for updates on first page
            fetch('/admin/api-keys/audit-log/latest')
                .then(response => response.json())
                .then(data => {
                    if (data.has_new_events) {
                        showNewEventsNotification(data.new_count);
                    }
                })
                .catch(error => console.error('Error checking for updates:', error));
        }
    }, 15000); // Check every 15 seconds
}

function showNewEventsNotification(count) {
    if (document.getElementById('newEventsAlert')) return;
    
    const alert = document.createElement('div');
    alert.id = 'newEventsAlert';
    alert.className = 'alert alert-info alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-info-circle"></i>
        <strong>${count}</strong> new audit event${count > 1 ? 's' : ''} available.
        <button type="button" class="btn btn-sm btn-outline-primary ml-2" onclick="window.location.reload()">
            Refresh
        </button>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 10000);
}

// Enable real-time updates
enableRealTimeUpdates();

// Search functionality
document.getElementById('api_key_id').addEventListener('change', function() {
    if (this.value) {
        // Auto-submit form when API key is selected
        this.form.submit();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + R to refresh
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        window.location.reload();
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        $('.modal').modal('hide');
    }
});
</script>
@endpush