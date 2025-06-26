@extends('layouts.app')

@section('title', 'Notification Logs')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Delivery Logs</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.notifications.index') }}">Notifications</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.notifications.show', $notification) }}">{{ Str::limit($notification->title, 30) }}</a></li>
                    <li class="breadcrumb-item active">Logs</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.notifications.show', $notification) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Details
            </a>
            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                <i class="fas fa-download"></i> Export
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">
                    <i class="fas fa-file-csv"></i> Export as CSV
                </a>
                <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">
                    <i class="fas fa-file-excel"></i> Export as Excel
                </a>
                <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}">
                    <i class="fas fa-file-pdf"></i> Export as PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Notification Info Bar -->
    <div class="alert alert-info mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="alert-heading mb-1">{{ $notification->title }}</h5>
                <p class="mb-0">
                    <strong>Status:</strong> 
                    <span class="badge badge-{{ $notification->status == 'sent' ? 'success' : ($notification->status == 'failed' ? 'danger' : 'warning') }}">
                        {{ ucfirst($notification->status) }}
                    </span>
                    <strong class="ml-3">Created:</strong> {{ $notification->created_at->format('M d, Y H:i') }}
                    @if($notification->scheduled_at)
                        <strong class="ml-3">Scheduled:</strong> {{ $notification->scheduled_at->format('M d, Y H:i') }}
                    @endif
                </p>
            </div>
            <div class="col-md-4 text-right">
                <div class="d-flex justify-content-end">
                    @foreach($notification->channels as $channel)
                        <span class="badge badge-outline-primary mr-1">
                            <i class="fas fa-{{ $channel == 'teams' ? 'users' : 'envelope' }}"></i>
                            {{ ucfirst($channel) }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Recipients</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['total']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Delivered</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['delivered']) }}</div>
                            <div class="text-xs text-success">
                                {{ $summary['total'] > 0 ? round(($summary['delivered'] / $summary['total']) * 100, 1) : 0 }}% success rate
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
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Failed</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['failed']) }}</div>
                            <div class="text-xs text-danger">
                                {{ $summary['total'] > 0 ? round(($summary['failed'] / $summary['total']) * 100, 1) : 0 }}% failure rate
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['pending']) }}</div>
                            <div class="text-xs text-warning">
                                {{ $summary['total'] > 0 ? round(($summary['pending'] / $summary['total']) * 100, 1) : 0 }}% pending
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filter Logs
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.notifications.logs', $notification) }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ request('search') }}" placeholder="Email, name, error...">
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="channel" class="form-label">Channel</label>
                        <select class="form-control" id="channel" name="channel">
                            <option value="">All Channels</option>
                            <option value="teams" {{ request('channel') == 'teams' ? 'selected' : '' }}>Teams</option>
                            <option value="email" {{ request('channel') == 'email' ? 'selected' : '' }}>Email</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="{{ request('date_to') }}">
                    </div>

                    <div class="col-md-1 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear Filters
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="refreshLogs()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="col-md-6 text-right">
                        <small class="text-muted">
                            Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} 
                            of {{ $logs->total() }} logs
                        </small>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Delivery Logs ({{ $logs->total() }} entries)
            </h6>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" onclick="selectAllLogs()">
                    <i class="fas fa-check-square"></i> Select All
                </button>
                
            </div>
        </div>

        <div class="card-body p-0">
            @if($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                </th>
                                <th>Recipient</th>
                                <th>Channel</th>
                                <th>Status</th>
                                <th>Sent At</th>
                                <th>Delivered At</th>
                                <th>Delivery Time</th>
                                <th>Error Message</th>
                                <th width="80">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr class="{{ $log->status == 'failed' ? 'table-danger' : ($log->status == 'delivered' ? 'table-success' : '') }}">
                                    <td>
                                        <input type="checkbox" class="log-checkbox" value="{{ $log->id }}" 
                                               onchange="updateBulkActions()" {{ $log->status == 'failed' ? '' : 'disabled' }}>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($log->user)
                                                <div class="avatar-sm mr-2">
                                                    <span class="avatar-title rounded-circle bg-primary text-white">
                                                        {{ substr($log->user->name, 0, 1) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold">{{ $log->user->name }}</div>
                                                    <small class="text-muted">{{ $log->recipient_email }}</small>
                                                    @if($log->user->department)
                                                        <small class="text-muted d-block">{{ $log->user->department }}</small>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-envelope text-muted mr-2"></i>
                                                    <div>
                                                        <div>{{ $log->recipient_email }}</div>
                                                        <small class="text-muted">External recipient</small>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-outline-{{ $log->channel == 'teams' ? 'primary' : 'info' }}">
                                            <i class="fas fa-{{ $log->channel == 'teams' ? 'users' : 'envelope' }}"></i>
                                            {{ ucfirst($log->channel) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $log->status == 'delivered' ? 'success' : ($log->status == 'failed' ? 'danger' : 'warning') }}">
                                            <i class="fas fa-{{ $log->status == 'delivered' ? 'check-circle' : ($log->status == 'failed' ? 'times-circle' : 'clock') }}"></i>
                                            {{ ucfirst($log->status) }}
                                        </span>
                                        @if($log->attempts > 1)
                                            <small class="text-muted d-block">
                                                Attempt {{ $log->attempts }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->created_at)
                                            <div>{{ $log->created_at->format('M d, H:i:s') }}</div>
                                            <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->delivered_at)
                                            <div>{{ $log->delivered_at->format('M d, H:i:s') }}</div>
                                            <small class="text-muted">{{ $log->delivered_at->diffForHumans() }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->delivered_at && $log->created_at)
                                            @php
                                                $deliveryTime = abs($log->delivered_at->diffInSeconds($log->created_at));
                                            @endphp
                                            <span class="badge badge-{{ $deliveryTime < 5 ? 'success' : ($deliveryTime < 30 ? 'warning' : 'danger') }}">
                                                {{ $deliveryTime }}s
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->error_message)
                                            <span class="text-danger" data-toggle="tooltip" 
                                                  title="{{ $log->error_message }}" style="cursor: help;">
                                                {{ Str::limit($log->error_message, 50) }}
                                            </span>
                                            @if(strlen($log->error_message) > 50)
                                                <button class="btn btn-sm btn-outline-danger ml-1" 
                                                        onclick="showErrorDetails('{{ addslashes($log->error_message) }}', '{{ $log->recipient_email }}')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            
                                            <button class="btn btn-outline-info" 
                                                    onclick="showLogDetails({{ $log->id }})" title="Details">
                                                <i class="fas fa-info"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} 
                                of {{ $logs->total() }} logs
                            </div>
                            {{ $logs->links() }}
                        </div>
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No logs found</h5>
                    <p class="text-muted">
                        @if(request()->hasAny(['search', 'status', 'channel', 'date_from', 'date_to']))
                            Try adjusting your filters to see more results.
                        @else
                            Logs will appear here once the notification is processed.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Error Details Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Error Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold">Recipient:</label>
                    <div id="errorRecipient" class="form-control-plaintext"></div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Error Message:</label>
                    <div id="errorMessage" class="border rounded p-3 bg-light" style="font-family: monospace; white-space: pre-wrap;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" onclick="copyErrorToClipboard()">
                    <i class="fas fa-copy"></i> Copy Error
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle"></i> Log Details
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.avatar-sm {
    height: 32px;
    width: 32px;
}

.avatar-title {
    align-items: center;
    display: flex;
    font-size: 14px;
    font-weight: 600;
    height: 100%;
    justify-content: center;
    width: 100%;
}

.badge-outline-primary {
    color: #007bff;
    border: 1px solid #007bff;
    background: transparent;
}

.badge-outline-info {
    color: #17a2b8;
    border: 1px solid #17a2b8;
    background: transparent;
}

.table-success {
    background-color: rgba(40, 167, 69, 0.1);
}

.table-danger {
    background-color: rgba(220, 53, 69, 0.1);
}

.form-control-plaintext {
    padding: 0.375rem 0;
}
</style>
@endpush

@push('scripts')
<script>
function clearFilters() {
    document.getElementById('filterForm').reset();
    window.location.href = '{{ route("admin.notifications.logs", $notification) }}';
}

function refreshLogs() {
    window.location.reload();
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.log-checkbox:not([disabled])');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const checked = document.querySelectorAll('.log-checkbox:checked');
    const retryBtn = document.getElementById('retryBtn');
    
    retryBtn.disabled = checked.length === 0;
}

function selectAllLogs() {
    document.getElementById('selectAllCheckbox').checked = true;
    toggleSelectAll();
}

function showErrorDetails(errorMessage, recipient) {
    document.getElementById('errorRecipient').textContent = recipient;
    document.getElementById('errorMessage').textContent = errorMessage;
    $('#errorModal').modal('show');
}

function showLogDetails(logId) {
}

function copyErrorToClipboard() {
    const errorText = document.getElementById('errorMessage').textContent;
    navigator.clipboard.writeText(errorText).then(() => {
        showToast('Error message copied to clipboard', 'success');
    });
}

function showToast(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 'alert-info';
    
    const toast = $(`
        <div class="alert ${alertClass} alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `);
    
    $('body').append(toast);
    
    setTimeout(() => {
        toast.alert('close');
    }, 5000);
}

// Initialize tooltips
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
    
    // Auto-refresh for pending notifications
    @if($summary['pending'] > 0)
        setInterval(function() {
            window.location.reload();
        }, 30000); // Refresh every 30 seconds
    @endif
});

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.log-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
});
</script>
@endpush
@endsection