@extends('layouts.app')

@section('title', 'API Key Details - ' . $apiKey->name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.api-keys.index') }}">API Keys</a></li>
                    <li class="breadcrumb-item active">{{ $apiKey->name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">
                {{ $apiKey->name }}
                @switch($apiKey->status)
                    @case('active')
                        <span class="badge badge-success ml-2">Active</span>
                        @break
                    @case('inactive')
                        <span class="badge badge-secondary ml-2">Inactive</span>
                        @break
                    @case('expired')
                        <span class="badge badge-danger ml-2">Expired</span>
                        @break
                    @case('expiring_soon')
                        <span class="badge badge-warning ml-2">Expiring Soon</span>
                        @break
                @endswitch
            </h1>
            @if($apiKey->description)
                <p class="mb-0 text-muted">{{ $apiKey->description }}</p>
            @endif
        </div>
        <div>
            @can('manage-api-keys')
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.api-keys.edit', $apiKey) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form action="{{ route('admin.api-keys.toggle-status', $apiKey) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn {{ $apiKey->is_active ? 'btn-secondary' : 'btn-success' }}"
                                onclick="return confirm('Are you sure you want to {{ $apiKey->is_active ? 'deactivate' : 'activate' }} this API key?')">
                            <i class="fas fa-{{ $apiKey->is_active ? 'pause' : 'play' }}"></i> 
                            {{ $apiKey->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    <div class="btn-group" role="group">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu">
                            <form action="{{ route('admin.api-keys.regenerate', $apiKey) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item"
                                        onclick="return confirm('Are you sure you want to regenerate this API key? This will invalidate the current key.')">
                                    <i class="fas fa-sync"></i> Regenerate Key
                                </button>
                            </form>
                            <form action="{{ route('admin.api-keys.reset-usage', $apiKey) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item"
                                        onclick="return confirm('Are you sure you want to reset usage statistics?')">
                                    <i class="fas fa-chart-line"></i> Reset Usage
                                </button>
                            </form>
                            <div class="dropdown-divider"></div>
                            <a href="{{ route('admin.api-keys.usage-history', $apiKey) }}" class="dropdown-item">
                                <i class="fas fa-history"></i> Usage History
                            </a>
                            <a href="{{ route('admin.api-keys.audit-log') }}?api_key_id={{ $apiKey->id }}" class="dropdown-item">
                                <i class="fas fa-file-alt"></i> Audit Log
                            </a>
                            <div class="dropdown-divider"></div>
                            <form action="{{ route('admin.api-keys.destroy', $apiKey) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger"
                                        onclick="return confirm('Are you sure you want to delete this API key? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </div>

    <!-- New API Key Alert (shown once after creation/regeneration) -->
    @if(session('new_api_key') && session('api_key_id') == $apiKey->id)
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h6 class="alert-heading"><i class="fas fa-key"></i> API Key Generated Successfully</h6>
            <p class="mb-2"><strong>Please save this API key now. For security reasons, it will not be shown again.</strong></p>
            <div class="input-group">
                <input type="text" class="form-control font-monospace" id="new-api-key" 
                       value="{{ session('new_api_key') }}" readonly>
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" onclick="copyApiKey()">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <!-- Security Alerts -->
    @if(isset($securityAlerts) && count($securityAlerts) > 0)
        @foreach($securityAlerts as $alert)
            <div class="alert alert-{{ $alert['type'] }} alert-dismissible fade show" role="alert">
                <strong>{{ ucfirst($alert['type']) }}:</strong> {{ $alert['message'] }}
                @if(isset($alert['action']) && $alert['action'])
                    <br><small>Recommended action: {{ $alert['action'] }}</small>
                @endif
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endforeach
    @endif

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- API Key Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">API Key Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Name:</strong></td>
                                    <td>{{ $apiKey->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Key:</strong></td>
                                    <td><code>{{ $apiKey->masked_key }}</code></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        @switch($apiKey->status)
                                            @case('active')
                                                <span class="badge badge-success">Active</span>
                                                @break
                                            @case('inactive')
                                                <span class="badge badge-secondary">Inactive</span>
                                                @break
                                            @case('expired')
                                                <span class="badge badge-danger">Expired</span>
                                                @break
                                            @case('expiring_soon')
                                                <span class="badge badge-warning">Expiring Soon</span>
                                                @break
                                        @endswitch
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Rate Limit:</strong></td>
                                    <td>
                                        {{ number_format($apiKey->rate_limit_per_minute) }} requests/minute
                                        @if($apiKey->usage_percentage > 0)
                                            <div class="progress mt-1" style="height: 6px;">
                                                <div class="progress-bar usage-percentage
                                                    @if($apiKey->usage_percentage > 80) bg-danger
                                                    @elseif($apiKey->usage_percentage > 60) bg-warning
                                                    @else bg-success @endif"
                                                    style="width: {{ min($apiKey->usage_percentage, 100) }}%">
                                                </div>
                                            </div>
                                            <small class="text-muted usage-percentage">{{ $apiKey->usage_percentage }}% used this minute</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>
                                        {{ $apiKey->created_at->format('M j, Y H:i') }}
                                        <br><small class="text-muted">({{ $apiKey->created_at->diffForHumans() }})</small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Created By:</strong></td>
                                    <td>{{ $apiKey->createdBy->display_name ?? 'Unknown' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Assigned To:</strong></td>
                                    <td>
                                        @if($apiKey->assignedTo)
                                            {{ $apiKey->assignedTo->display_name }}
                                            <br><small class="text-muted">{{ $apiKey->assignedTo->email }}</small>
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Expires:</strong></td>
                                    <td>
                                        @if($apiKey->expires_at)
                                            {{ $apiKey->expires_at->format('M j, Y') }}
                                            <br><small class="text-muted">({{ $apiKey->expires_at->diffForHumans() }})</small>
                                            @if($apiKey->days_until_expiry <= 30)
                                                <br><small class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    Expires in {{ $apiKey->days_until_expiry }} days
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted">Never expires</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Last Used:</strong></td>
                                    <td>
                                        @if($apiKey->last_used_at)
                                            {{ $apiKey->last_used_at->format('M j, Y H:i') }}
                                            <br><small class="text-muted">({{ $apiKey->last_used_at->diffForHumans() }})</small>
                                        @else
                                            <span class="text-muted">Never used</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($apiKey->description)
                        <div class="mt-3">
                            <strong>Description:</strong>
                            <p class="mt-1">{{ $apiKey->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Permissions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Permissions</h6>
                </div>
                <div class="card-body">
                    @if($apiKey->permissions && count($apiKey->permissions) > 0)
                        <div class="row">
                            @foreach($apiKey->permissions as $permission)
                                <div class="col-md-6 mb-2">
                                    <span class="badge badge-info">{{ $permission }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No specific permissions assigned (full access)</p>
                    @endif
                </div>
            </div>

            <!-- IP Whitelist -->
            @if($apiKey->ip_whitelist && count($apiKey->ip_whitelist) > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">IP Whitelist</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($apiKey->ip_whitelist as $ip)
                                <div class="col-md-4 mb-2">
                                    <code>{{ $ip }}</code>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Usage Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Usage Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="h4 mb-0 font-weight-bold text-primary">
                                {{ number_format($usageStats['total_requests'] ?? 0) }}
                            </div>
                            <div class="text-xs text-uppercase text-muted">Total Requests</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h4 mb-0 font-weight-bold text-success">
                                {{ number_format($usageStats['requests_today'] ?? 0) }}
                            </div>
                            <div class="text-xs text-uppercase text-muted">Today</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h4 mb-0 font-weight-bold text-info">
                                {{ number_format($usageStats['requests_this_week'] ?? 0) }}
                            </div>
                            <div class="text-xs text-uppercase text-muted">This Week</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h4 mb-0 font-weight-bold text-warning">
                                {{ number_format($usageStats['requests_this_month'] ?? 0) }}
                            </div>
                            <div class="text-xs text-uppercase text-muted">This Month</div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="h5 mb-0 font-weight-bold">
                                {{ number_format($usageStats['average_response_time'] ?? 0) }}ms
                            </div>
                            <div class="text-xs text-uppercase text-muted">Avg Response Time</div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="h5 mb-0 font-weight-bold">
                                {{ number_format($performanceMetrics['success_rate'] ?? 0, 1) }}%
                            </div>
                            <div class="text-xs text-uppercase text-muted">Success Rate</div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="h5 mb-0 font-weight-bold">
                                {{ number_format($performanceMetrics['throughput_per_minute'] ?? 0, 1) }}
                            </div>
                            <div class="text-xs text-uppercase text-muted">Requests/Min</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Performance Metrics</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td>Average Response Time:</td>
                                    <td><strong>{{ number_format($performanceMetrics['avg_response_time'] ?? 0) }}ms</strong></td>
                                </tr>
                                <tr>
                                    <td>Min Response Time:</td>
                                    <td><strong>{{ number_format($performanceMetrics['min_response_time'] ?? 0) }}ms</strong></td>
                                </tr>
                                <tr>
                                    <td>Max Response Time:</td>
                                    <td><strong>{{ number_format($performanceMetrics['max_response_time'] ?? 0) }}ms</strong></td>
                                </tr>
                                <tr>
                                    <td>95th Percentile:</td>
                                    <td><strong>{{ number_format($performanceMetrics['p95_response_time'] ?? 0) }}ms</strong></td>
                                </tr>
                                <tr>
                                    <td>99th Percentile:</td>
                                    <td><strong>{{ number_format($performanceMetrics['p99_response_time'] ?? 0) }}ms</strong></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td>Success Rate:</td>
                                    <td><strong>{{ number_format($performanceMetrics['success_rate'] ?? 0, 2) }}%</strong></td>
                                </tr>
                                <tr>
                                    <td>Throughput:</td>
                                    <td><strong>{{ number_format($performanceMetrics['throughput_per_minute'] ?? 0, 1) }} req/min</strong></td>
                                </tr>
                                <tr>
                                    <td>Rate Limit Utilization:</td>
                                    <td>
                                        <strong>{{ number_format($performanceMetrics['rate_limit_utilization'] ?? 0, 1) }}%</strong>
                                        <div class="progress mt-1" style="height: 4px;">
                                            <div class="progress-bar bg-info" 
                                                 style="width: {{ min($performanceMetrics['rate_limit_utilization'] ?? 0, 100) }}%">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Endpoints -->
            @if(isset($topEndpoints) && $topEndpoints->isNotEmpty())
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Top Endpoints</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Endpoint</th>
                                        <th>Method</th>
                                        <th>Requests</th>
                                        <th>Usage %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topEndpoints as $endpoint)
                                        <tr>
                                            <td><code>{{ $endpoint->endpoint }}</code></td>
                                            <td><span class="badge badge-info">{{ $endpoint->method }}</span></td>
                                            <td>{{ number_format($endpoint->count) }}</td>
                                            <td>
                                                @php
                                                    $totalRequests = $usageStats['total_requests'] ?? 1;
                                                    $percentage = $totalRequests > 0 
                                                        ? ($endpoint->count / $totalRequests) * 100 
                                                        : 0;
                                                @endphp
                                                {{ number_format($percentage, 1) }}%
                                                <div class="progress mt-1" style="height: 4px;">
                                                    <div class="progress-bar bg-primary" 
                                                         style="width: {{ $percentage }}%">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Recent Activity -->
            @if(isset($recentLogs) && $recentLogs->isNotEmpty())
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                        <a href="{{ route('admin.api-keys.usage-history', $apiKey) }}" class="btn btn-sm btn-outline-primary">
                            View All History
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Endpoint</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Response Time</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentLogs->take(10) as $log)
                                        <tr>
                                            <td>{{ $log->created_at->format('M j H:i') }}</td>
                                            <td><code>{{ $log->endpoint }}</code></td>
                                            <td><span class="badge badge-info">{{ $log->method }}</span></td>
                                            <td>
                                                <span class="badge badge-{{ $log->getStatusColorClass() }}">
                                                    {{ $log->response_code }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $log->getResponseTimeColorClass() }}">
                                                    {{ $log->response_time_human }}
                                                </span>
                                            </td>
                                            <td><code>{{ $log->ip_address }}</code></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('admin.api-keys.usage-history', $apiKey) }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-history text-info"></i>
                            <span class="ml-2">View Usage History</span>
                        </a>
                        <a href="{{ route('admin.api-keys.audit-log') }}?api_key_id={{ $apiKey->id }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-alt text-warning"></i>
                            <span class="ml-2">View Audit Log</span>
                        </a>
                        @can('manage-api-keys')
                            <a href="{{ route('admin.api-keys.edit', $apiKey) }}" class="list-group-item list-group-item-action">
                                <i class="fas fa-edit text-primary"></i>
                                <span class="ml-2">Edit Settings</span>
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Associated Notifications -->
            @if(isset($associatedNotifications) && $associatedNotifications->isNotEmpty())
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Recent Notifications
                            <span class="badge badge-secondary ml-2">{{ $apiKey->notifications_count ?? 0 }}</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        @foreach($associatedNotifications as $notification)
                            <div class="media mb-3">
                                <div class="media-body">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mt-0">
                                            @if($notification->template)
                                                {{ $notification->template->name }}
                                            @else
                                                Notification #{{ $notification->id }}
                                            @endif
                                        </h6>
                                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge badge-{{ $notification->status === 'sent' ? 'success' : 'info' }}">
                                            {{ ucfirst($notification->status) }}
                                        </span>
                                        <a href="{{ route('notifications.show', $notification) }}" class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if(($apiKey->notifications_count ?? 0) > 10)
                            <div class="text-center">
                                <a href="{{ route('notifications.index') }}?api_key_id={{ $apiKey->id }}" class="btn btn-sm btn-outline-primary">
                                    View All ({{ $apiKey->notifications_count }})
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Usage Charts -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Usage Trend (30 Days)</h6>
                </div>
                <div class="card-body">
                    <canvas id="usageChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Hourly Distribution -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Hourly Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="hourlyChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Configuration Summary -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Configuration</h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <div><strong>Auto Notifications:</strong> {{ $apiKey->auto_notifications ? 'Enabled' : 'Disabled' }}</div>
                        @if($apiKey->notification_webhook)
                            <div><strong>Webhook:</strong> Configured</div>
                        @endif
                        <div><strong>Permissions:</strong> {{ $apiKey->permissions ? count($apiKey->permissions) : 0 }}</div>
                        <div><strong>IP Restrictions:</strong> {{ $apiKey->ip_whitelist ? count($apiKey->ip_whitelist) : 'None' }}</div>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .font-monospace {
        font-family: 'Courier New', Courier, monospace;
    }
    
    .progress {
        height: 6px;
    }
    
    .media:last-child {
        margin-bottom: 0 !important;
    }
    
    .list-group-item-action:hover {
        background-color: #f8f9fc;
    }
    
    .card-header h6 {
        margin-bottom: 0;
    }
    
    .table-borderless td {
        border: none;
        padding: 0.5rem 0;
    }
</style>
@endpush

@push('scripts')
<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
function copyApiKey() {
    const apiKeyInput = document.getElementById('new-api-key');
    apiKeyInput.select();
    apiKeyInput.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Show feedback
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
    button.classList.add('btn-success');
    button.classList.remove('btn-outline-secondary');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}

function initializeUsageChart(chartData) {
    const usageCtx = document.getElementById('usageChart').getContext('2d');
    new Chart(usageCtx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Requests',
                data: chartData.data,
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
                    grid: {
                        display: true
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            elements: {
                point: {
                    radius: 3,
                    hoverRadius: 5
                }
            }
        }
    });
}

function initializeHourlyChart(chartData) {
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Requests per Hour',
                data: chartData.data,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
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
                        display: true
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function showChartError() {
    // Show error message in chart containers
    ['usageChart', 'hourlyChart'].forEach(chartId => {
        const container = document.getElementById(chartId).parentElement;
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-chart-line fa-2x mb-2"></i>
                <p>Unable to load chart data</p>
            </div>
        `;
    });
}


</script>
@endpush