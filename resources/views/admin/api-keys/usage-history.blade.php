@extends('layouts.app')

@section('title', 'Usage History - ' . $apiKey->name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.api-keys.index') }}">API Keys</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.api-keys.show', $apiKey) }}">{{ $apiKey->name }}</a></li>
                    <li class="breadcrumb-item active">Usage History</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">Usage History: {{ $apiKey->name }}</h1>
            <p class="mb-0 text-muted">Detailed usage logs and activity history</p>
        </div>
        <div>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                    <i class="fas fa-download"></i> Export
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="{{ route('admin.api-keys.export-usage', [$apiKey, 'csv']) }}">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                    <a class="dropdown-item" href="{{ route('admin.api-keys.export-usage', [$apiKey, 'excel']) }}">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a class="dropdown-item" href="{{ route('admin.api-keys.export-usage', [$apiKey, 'json']) }}">
                        <i class="fas fa-file-code"></i> Export JSON
                    </a>
                </div>
            </div>
            <a href="{{ route('admin.api-keys.show', $apiKey) }}" class="btn btn-secondary ml-2">
                <i class="fas fa-arrow-left"></i> Back to Details
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Requests
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($logs->total()) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                Success Rate
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($apiKey->getSuccessRate(), 1) }}%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                Avg Response Time
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($apiKey->usageLogs()->avg('response_time') ?? 0) }}ms
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                Rate Limit Hits
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($apiKey->usageLogs()->where('response_code', 429)->count()) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.api-keys.usage-history', $apiKey) }}">
                <div class="row">
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
                            <label for="endpoint">Endpoint</label>
                            <input type="text" class="form-control" id="endpoint" name="endpoint" 
                                   value="{{ request('endpoint') }}" placeholder="Filter by endpoint">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="method">Method</label>
                            <select class="form-control" id="method" name="method">
                                <option value="">All Methods</option>
                                <option value="GET" {{ request('method') === 'GET' ? 'selected' : '' }}>GET</option>
                                <option value="POST" {{ request('method') === 'POST' ? 'selected' : '' }}>POST</option>
                                <option value="PUT" {{ request('method') === 'PUT' ? 'selected' : '' }}>PUT</option>
                                <option value="DELETE" {{ request('method') === 'DELETE' ? 'selected' : '' }}>DELETE</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status_code">Status Code</label>
                            <select class="form-control" id="status_code" name="status_code">
                                <option value="">All Status</option>
                                <option value="200" {{ request('status_code') === '200' ? 'selected' : '' }}>200 - Success</option>
                                <option value="400" {{ request('status_code') === '400' ? 'selected' : '' }}>400 - Bad Request</option>
                                <option value="401" {{ request('status_code') === '401' ? 'selected' : '' }}>401 - Unauthorized</option>
                                <option value="403" {{ request('status_code') === '403' ? 'selected' : '' }}>403 - Forbidden</option>
                                <option value="404" {{ request('status_code') === '404' ? 'selected' : '' }}>404 - Not Found</option>
                                <option value="429" {{ request('status_code') === '429' ? 'selected' : '' }}>429 - Rate Limited</option>
                                <option value="500" {{ request('status_code') === '500' ? 'selected' : '' }}>500 - Server Error</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="{{ route('admin.api-keys.usage-history', $apiKey) }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Usage Logs Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Usage Logs</h6>
        </div>
        <div class="card-body">
            @if($logs->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Endpoint</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Response Time</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
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
                                        <code>{{ $log->endpoint }}</code>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $log->method }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $log->getStatusColorClass() }}">
                                            {{ $log->response_code }}
                                        </span>
                                        @if($log->error_message)
                                            <br><small class="text-danger">{{ Str::limit($log->error_message, 30) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $log->getResponseTimeColorClass() }}">
                                            {{ $log->response_time_human }}
                                        </span>
                                    </td>
                                    <td>
                                        <code>{{ $log->ip_address }}</code>
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $log->user_agent }}">
                                            {{ $log->user_agent ?: 'Unknown' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="showLogDetails({{ $log->id }})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($log->notification_id)
                                            <a href="{{ route('notifications.show', $log->notification_id) }}" 
                                               class="btn btn-sm btn-outline-info" title="View Notification">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                        @endif
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
                    <h5 class="text-muted">No Usage Logs Found</h5>
                    <p class="text-muted">No usage logs match your current filters or this API key hasn't been used yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Details</h5>
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
@endsection

@push('styles')
<style>
    .text-truncate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .badge {
        font-size: 0.75em;
    }
    
    code {
        font-size: 0.8em;
        padding: 0.2em 0.4em;
        background-color: #f8f9fc;
        border-radius: 0.25rem;
    }
</style>
@endpush

@push('scripts')
<script>
function showLogDetails(logId) {
    $('#logDetailsModal').modal('show');
    
    // Load log details via AJAX
    fetch(`/admin/api-usage/${logId}`)
        .then(response => response.json())
        .then(data => {
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Request Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>Timestamp:</strong></td><td>${data.created_at}</td></tr>
                            <tr><td><strong>Method:</strong></td><td><span class="badge badge-info">${data.method}</span></td></tr>
                            <tr><td><strong>Endpoint:</strong></td><td><code>${data.endpoint}</code></td></tr>
                            <tr><td><strong>IP Address:</strong></td><td><code>${data.ip_address}</code></td></tr>
                            <tr><td><strong>User Agent:</strong></td><td class="small">${data.user_agent || 'Unknown'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Response Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>Status Code:</strong></td><td><span class="badge badge-${getStatusBadgeClass(data.response_code)}">${data.response_code}</span></td></tr>
                            <tr><td><strong>Response Time:</strong></td><td>${data.response_time || 0}ms</td></tr>
                            <tr><td><strong>Request ID:</strong></td><td><code>${data.request_id || 'N/A'}</code></td></tr>
                            ${data.error_message ? `<tr><td><strong>Error:</strong></td><td class="text-danger small">${data.error_message}</td></tr>` : ''}
                        </table>
                    </div>
                </div>
                
                ${data.request_data ? `
                <div class="mt-3">
                    <h6>Request Data</h6>
                    <pre class="bg-light p-3 rounded"><code>${JSON.stringify(data.request_data, null, 2)}</code></pre>
                </div>
                ` : ''}
                
                ${data.response_data ? `
                <div class="mt-3">
                    <h6>Response Data</h6>
                    <pre class="bg-light p-3 rounded"><code>${JSON.stringify(data.response_data, null, 2)}</code></pre>
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

function getStatusBadgeClass(statusCode) {
    if (statusCode >= 200 && statusCode < 300) return 'success';
    if (statusCode >= 300 && statusCode < 400) return 'info';
    if (statusCode >= 400 && statusCode < 500) return 'warning';
    if (statusCode >= 500) return 'danger';
    return 'secondary';
}

// Auto-refresh functionality
let autoRefreshInterval;

function toggleAutoRefresh() {
    const button = document.getElementById('autoRefreshBtn');
    
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        button.innerHTML = '<i class="fas fa-play"></i> Auto Refresh';
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-success');
    } else {
        autoRefreshInterval = setInterval(() => {
            window.location.reload();
        }, 30000); // Refresh every 30 seconds
        
        button.innerHTML = '<i class="fas fa-pause"></i> Stop Refresh';
        button.classList.remove('btn-outline-success');
        button.classList.add('btn-success');
    }
}

// Add auto-refresh button
document.addEventListener('DOMContentLoaded', function() {
    const headerButtons = document.querySelector('.d-flex.justify-content-between .div:last-child');
    if (headerButtons) {
        const autoRefreshBtn = document.createElement('button');
        autoRefreshBtn.id = 'autoRefreshBtn';
        autoRefreshBtn.className = 'btn btn-outline-success ml-2';
        autoRefreshBtn.innerHTML = '<i class="fas fa-play"></i> Auto Refresh';
        autoRefreshBtn.onclick = toggleAutoRefresh;
        headerButtons.appendChild(autoRefreshBtn);
    }
});

// Real-time updates notification
function checkForNewLogs() {
    const latestTimestamp = document.querySelector('tbody tr:first-child td:first-child')?.dataset.timestamp;
    
    if (latestTimestamp) {
        fetch(`/admin/api-keys/${{{ $apiKey->id }}}/usage-history?latest=${latestTimestamp}&check_new=1`)
            .then(response => response.json())
            .then(data => {
                if (data.has_new_logs) {
                    showNewLogsNotification(data.new_count);
                }
            })
            .catch(error => console.error('Error checking for new logs:', error));
    }
}

function showNewLogsNotification(count) {
    if (document.getElementById('newLogsAlert')) return; // Don't show multiple alerts
    
    const alert = document.createElement('div');
    alert.id = 'newLogsAlert';
    alert.className = 'alert alert-info alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-info-circle"></i>
        <strong>${count}</strong> new log${count > 1 ? 's' : ''} available.
        <button type="button" class="btn btn-sm btn-outline-primary ml-2" onclick="window.location.reload()">
            Refresh
        </button>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto-dismiss after 10 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 10000);
}

// Check for new logs every 30 seconds (only if not auto-refreshing)
setInterval(() => {
    if (!autoRefreshInterval) {
        checkForNewLogs();
    }
}, 30000);
</script>
@endpush