@extends('layouts.app')

@section('title', 'API Keys Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">API Keys Management</h1>
            <p class="mb-0 text-muted">Manage API keys for external system integration</p>
        </div>
        <div>
            @can('manage-api-keys')
                <a href="{{ route('admin.api-keys.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create API Key
                </a>
            @endcan
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
                                Total API Keys
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-key fa-2x text-gray-300"></i>
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
                                Active API Keys
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                Expired Keys
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['expired'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                                Requests Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_requests_today']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if($expiringSoon->isNotEmpty())
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> API Keys Expiring Soon</h6>
            <p class="mb-2">The following API keys will expire within 30 days:</p>
            <ul class="mb-0">
                @foreach($expiringSoon as $key)
                    <li>
                        <strong>{{ $key->name }}</strong> - 
                        expires {{ $key->expires_at->diffForHumans() }}
                        <a href="{{ route('admin.api-keys.edit', $key) }}" class="btn btn-sm btn-outline-warning ml-2">
                            Extend
                        </a>
                    </li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <!-- Filters and Search -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters & Search</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.api-keys.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Search by name or description">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
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
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="{{ route('admin.api-keys.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- API Keys Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">API Keys List</h6>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle btn-sm" type="button" data-toggle="dropdown">
                    <i class="fas fa-download"></i> Export
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="{{ route('admin.api-keys.export', 'csv') }}">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                    <a class="dropdown-item" href="{{ route('admin.api-keys.export', 'excel') }}">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a class="dropdown-item" href="{{ route('admin.api-keys.export', 'pdf') }}">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($apiKeys->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}">
                                        Name
                                        @if(request('sort_by') === 'name')
                                            <i class="fas fa-sort-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>Status</th>
                                <th>Rate Limit</th>
                                <th>Usage</th>
                                <th>Assigned To</th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'last_used_at', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}">
                                        Last Used
                                        @if(request('sort_by') === 'last_used_at')
                                            <i class="fas fa-sort-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'expires_at', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}">
                                        Expires
                                        @if(request('sort_by') === 'expires_at')
                                            <i class="fas fa-sort-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($apiKeys as $apiKey)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="api-key-checkbox" value="{{ $apiKey->id }}">
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $apiKey->name }}</strong>
                                            @if($apiKey->description)
                                                <br>
                                                <small class="text-muted">{{ Str::limit($apiKey->description, 50) }}</small>
                                            @endif
                                        </div>
                                    </td>
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
                                            @default
                                                <span class="badge badge-light">Unknown</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div>{{ number_format($apiKey->rate_limit_per_minute) }}/min</div>
                                        @if($apiKey->usage_percentage > 0)
                                            <div class="progress mt-1" style="height: 4px;">
                                                <div class="progress-bar 
                                                    @if($apiKey->usage_percentage > 80) bg-danger
                                                    @elseif($apiKey->usage_percentage > 60) bg-warning
                                                    @else bg-success @endif"
                                                    style="width: {{ min($apiKey->usage_percentage, 100) }}%">
                                                </div>
                                            </div>
                                            <small class="text-muted">{{ $apiKey->usage_percentage }}% used</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ number_format($apiKey->usage_count) }}</strong> total
                                        </div>
                                        <div>
                                            <small class="text-muted">{{ $apiKey->usage_logs_count }} logs</small>
                                        </div>
                                        <div>
                                            <small class="text-muted">{{ $apiKey->notifications_count }} notifications</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($apiKey->assignedTo)
                                            <div>
                                                <strong>{{ $apiKey->assignedTo->display_name }}</strong>
                                            </div>
                                            <small class="text-muted">{{ $apiKey->assignedTo->email }}</small>
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($apiKey->last_used_at)
                                            <div>{{ $apiKey->last_used_at->diffForHumans() }}</div>
                                            <small class="text-muted">{{ $apiKey->last_used_at->format('M j, Y H:i') }}</small>
                                        @else
                                            <span class="text-muted">Never used</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($apiKey->expires_at)
                                            <div>{{ $apiKey->expires_at->diffForHumans() }}</div>
                                            <small class="text-muted">{{ $apiKey->expires_at->format('M j, Y') }}</small>
                                        @else
                                            <span class="text-muted">Never expires</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.api-keys.show', $apiKey) }}" 
                                               class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('manage-api-keys')
                                                <a href="{{ route('admin.api-keys.edit', $apiKey) }}" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.api-keys.toggle-status', $apiKey) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-sm {{ $apiKey->is_active ? 'btn-secondary' : 'btn-success' }}"
                                                            title="{{ $apiKey->is_active ? 'Deactivate' : 'Activate' }}"
                                                            onclick="return confirm('Are you sure you want to {{ $apiKey->is_active ? 'deactivate' : 'activate' }} this API key?')">
                                                        <i class="fas fa-{{ $apiKey->is_active ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            type="button" data-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <form action="{{ route('admin.api-keys.regenerate', $apiKey) }}" 
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item"
                                                                    onclick="return confirm('Are you sure you want to regenerate this API key? This will invalidate the current key.')">
                                                                <i class="fas fa-sync"></i> Regenerate
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('admin.api-keys.reset-usage', $apiKey) }}" 
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item"
                                                                    onclick="return confirm('Are you sure you want to reset usage statistics?')">
                                                                <i class="fas fa-chart-line"></i> Reset Usage
                                                            </button>
                                                        </form>
                                                        <div class="dropdown-divider"></div>
                                                        <form action="{{ route('admin.api-keys.destroy', $apiKey) }}" 
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger"
                                                                    onclick="return confirm('Are you sure you want to delete this API key? This action cannot be undone.')">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endcan
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
                        Showing {{ $apiKeys->firstItem() }} to {{ $apiKeys->lastItem() }} of {{ $apiKeys->total() }} results
                    </div>
                    <div>
                        {{ $apiKeys->links() }}
                    </div>
                </div>

                <!-- Bulk Actions -->
                @can('manage-api-keys')
                    <div class="mt-3">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Bulk Actions</h6>
                            </div>
                            <div class="card-body">
                                <form id="bulk-actions-form" method="POST">
                                    @csrf
                                    <input type="hidden" name="api_key_ids" id="selected-ids">
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <select class="form-control" id="bulk-action" name="action">
                                                <option value="">Select Action</option>
                                                <option value="activate">Activate Selected</option>
                                                <option value="deactivate">Deactivate Selected</option>
                                                <option value="update_limits">Update Rate Limits</option>
                                                <option value="delete">Delete Selected</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3" id="rate-limit-input" style="display: none;">
                                            <input type="number" class="form-control" name="rate_limit_per_minute" 
                                                   placeholder="Rate limit per minute" min="1" max="10000">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-primary" id="execute-bulk-action" disabled>
                                                Execute
                                            </button>
                                        </div>
                                        <div class="col-md-3">
                                            <span id="selected-count" class="text-muted">0 items selected</span>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endcan

            @else
                <div class="text-center py-5">
                    <i class="fas fa-key fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No API Keys Found</h5>
                    <p class="text-muted">No API keys match your current filters.</p>
                    @can('manage-api-keys')
                        <a href="{{ route('admin.api-keys.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create First API Key
                        </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Activity -->
    @if($recentActivity->isNotEmpty())
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent API Activity</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>API Key</th>
                                <th>Endpoint</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Response Time</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentActivity as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('H:i:s') }}</td>
                                    <td>
                                        @if($log->apiKey)
                                            <a href="{{ route('admin.api-keys.show', $log->apiKey) }}">
                                                {{ $log->apiKey->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">Deleted</span>
                                        @endif
                                    </td>
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
@endsection

@push('styles')
<style>
    .progress {
        height: 4px;
    }
    
    .table th a {
        color: inherit;
        text-decoration: none;
    }
    
    .table th a:hover {
        color: #007bff;
    }
    
    .badge-light {
        background-color: #f8f9fc;
        color: #5a5c69;
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
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Select all checkbox functionality
    $('#select-all').change(function() {
        $('.api-key-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActions();
    });
    
    // Individual checkbox change
    $('.api-key-checkbox').change(function() {
        updateBulkActions();
        
        // Update select-all checkbox
        var totalCheckboxes = $('.api-key-checkbox').length;
        var checkedCheckboxes = $('.api-key-checkbox:checked').length;
        
        $('#select-all').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        $('#select-all').prop('checked', checkedCheckboxes === totalCheckboxes);
    });
    
    // Bulk action dropdown change
    $('#bulk-action').change(function() {
        var action = $(this).val();
        
        if (action === 'update_limits') {
            $('#rate-limit-input').show();
        } else {
            $('#rate-limit-input').hide();
        }
        
        updateBulkActions();
    });
    
    // Execute bulk action
    $('#execute-bulk-action').click(function() {
        var action = $('#bulk-action').val();
        var selectedIds = $('.api-key-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedIds.length === 0) {
            alert('Please select at least one API key.');
            return;
        }
        
        var confirmMessage = '';
        var formAction = '';
        
        switch (action) {
            case 'activate':
                confirmMessage = 'Are you sure you want to activate ' + selectedIds.length + ' API key(s)?';
                formAction = '{{ route("admin.api-keys.bulk.toggle-status") }}';
                break;
            case 'deactivate':
                confirmMessage = 'Are you sure you want to deactivate ' + selectedIds.length + ' API key(s)?';
                formAction = '{{ route("admin.api-keys.bulk.toggle-status") }}';
                break;
            case 'update_limits':
                var rateLimit = $('input[name="rate_limit_per_minute"]').val();
                if (!rateLimit || rateLimit <= 0) {
                    alert('Please enter a valid rate limit.');
                    return;
                }
                confirmMessage = 'Are you sure you want to update rate limits for ' + selectedIds.length + ' API key(s)?';
                formAction = '{{ route("admin.api-keys.bulk.update-limits") }}';
                break;
            case 'delete':
                confirmMessage = 'Are you sure you want to delete ' + selectedIds.length + ' API key(s)? This action cannot be undone.';
                formAction = '{{ route("admin.api-keys.bulk.delete") }}';
                break;
            default:
                alert('Please select a valid action.');
                return;
        }
        
        if (confirm(confirmMessage)) {
            $('#selected-ids').val(JSON.stringify(selectedIds));
            $('#bulk-actions-form').attr('action', formAction);
            $('#bulk-actions-form').submit();
        }
    });
    
    function updateBulkActions() {
        var selectedCount = $('.api-key-checkbox:checked').length;
        var action = $('#bulk-action').val();
        
        $('#selected-count').text(selectedCount + ' item(s) selected');
        $('#execute-bulk-action').prop('disabled', selectedCount === 0 || !action);
    }
});
</script>
@endpush