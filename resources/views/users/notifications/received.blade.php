@extends('layouts.app')

@section('title', 'My Notifications')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">My Notifications</h1>
            <p class="mb-0 text-muted">View and manage your received notifications</p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary" onclick="markAllAsRead()">
                <i class="fas fa-check-double"></i> Mark All Read
            </button>
            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                <i class="fas fa-cog"></i> Settings
            </button>
            <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="#" onclick="openPreferences()">
                    <i class="fas fa-bell"></i> Notification Preferences
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" onclick="exportNotifications()">
                    <i class="fas fa-download"></i> Export Notifications
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Notifications</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bell fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Unread</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['unread']) }}
                                @if($stats['unread'] > 0)
                                    <span class="badge badge-warning ml-2">New</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['today']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">This Week</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['this_week']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
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
                <i class="fas fa-filter"></i> Filter Notifications
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('notifications.received') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ request('search') }}" placeholder="Search notifications...">
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="read_status" class="form-label">Read Status</label>
                        <select class="form-control" id="read_status" name="read_status">
                            <option value="">All</option>
                            <option value="unread" {{ request('read_status') == 'unread' ? 'selected' : '' }}>Unread</option>
                            <option value="read" {{ request('read_status') == 'read' ? 'selected' : '' }}>Read</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="channel" class="form-label">Channel</label>
                        <select class="form-control" id="channel" name="channel">
                            <option value="">All Channels</option>
                            @foreach($channels as $channel)
                                <option value="{{ $channel }}" {{ request('channel') == $channel ? 'selected' : '' }}>
                                    {{ ucfirst($channel) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-control" id="priority" name="priority">
                            <option value="">All Priorities</option>
                            @foreach($priorities as $priority)
                                <option value="{{ $priority }}" {{ request('priority') == $priority ? 'selected' : '' }}>
                                    {{ ucfirst($priority) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="{{ request('date_from') }}">
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
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="refreshNotifications()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="col-md-6 text-right">
                        <small class="text-muted">
                            Showing {{ $notifications->firstItem() ?? 0 }} to {{ $notifications->lastItem() ?? 0 }} 
                            of {{ $notifications->total() }} notifications
                        </small>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Notifications ({{ $notifications->total() }} total)
            </h6>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" onclick="selectAllNotifications()">
                    <i class="fas fa-check-square"></i> Select All
                </button>
                <button type="button" class="btn btn-outline-primary" onclick="markSelectedAsRead()" id="markReadBtn" disabled>
                    <i class="fas fa-check"></i> Mark Read
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="archiveSelected()" id="archiveBtn" disabled>
                    <i class="fas fa-archive"></i> Archive
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            @if($notifications->count() > 0)
                <div class="notification-list">
                    @foreach($notifications as $notificationLog)
                        <div class="notification-item {{ !$notificationLog->read_at ? 'unread' : '' }}" 
                             data-notification-id="{{ $notificationLog->id }}">
                            <div class="d-flex align-items-start p-3 border-bottom">
                                <div class="notification-checkbox mr-3">
                                    <input type="checkbox" class="notification-check" value="{{ $notificationLog->id }}" 
                                           onchange="updateBulkActions()">
                                </div>

                                <div class="notification-content flex-grow-1" onclick="openNotification({{ $notificationLog->id }})">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="notification-header">
                                            <h6 class="notification-title mb-1">
                                                {{ $notificationLog->notification->title }}
                                                @if(!$notificationLog->read_at)
                                                    <span class="badge badge-warning badge-sm ml-2">New</span>
                                                @endif
                                            </h6>
                                            <div class="notification-meta">
                                                <small class="text-muted">
                                                    <i class="fas fa-user mr-1"></i>
                                                    From: {{ $notificationLog->notification->creator->name ?? 'System' }}
                                                </small>
                                                <small class="text-muted ml-3">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    {{ $notificationLog->created_at->diffForHumans() }}
                                                </small>
                                                <small class="text-muted ml-3">
                                                    <i class="fas fa-{{ $notificationLog->channel == 'teams' ? 'users' : 'envelope' }} mr-1"></i>
                                                    {{ ucfirst($notificationLog->channel) }}
                                                </small>
                                            </div>
                                        </div>
                                        <div class="notification-actions">
                                            <span class="badge badge-{{ $notificationLog->notification->priority == 'urgent' ? 'danger' : ($notificationLog->notification->priority == 'high' ? 'warning' : ($notificationLog->notification->priority == 'normal' ? 'primary' : 'secondary')) }}">
                                                {{ ucfirst($notificationLog->notification->priority) }}
                                            </span>
                                            <span class="badge badge-{{ $notificationLog->status == 'delivered' ? 'success' : 'warning' }} ml-1">
                                                {{ ucfirst($notificationLog->status) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="notification-preview">
                                        <p class="mb-2 text-muted">
                                            {{ Str::limit(strip_tags($notificationLog->notification->content), 120) }}
                                        </p>
                                    </div>

                                    <div class="notification-footer d-flex justify-content-between align-items-center">
                                        <div class="notification-time">
                                            <small class="text-muted">
                                                Received: {{ $notificationLog->created_at->format('M d, Y H:i') }}
                                                @if($notificationLog->delivered_at)
                                                    • Delivered: {{ $notificationLog->delivered_at->format('H:i') }}
                                                @endif
                                            </small>
                                        </div>
                                        <div class="notification-quick-actions">
                                            @if(!$notificationLog->read_at)
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="markAsRead({{ $notificationLog->id }}, event)">
                                                    <i class="fas fa-check"></i> Mark Read
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-outline-secondary" 
                                                        onclick="markAsUnread({{ $notificationLog->id }}, event)">
                                                    <i class="fas fa-undo"></i> Mark Unread
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-outline-info ml-1" 
                                                    onclick="openNotification({{ $notificationLog->id }})">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger ml-1" 
                                                    onclick="archiveNotification({{ $notificationLog->id }}, event)">
                                                <i class="fas fa-archive"></i> Archive
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($notifications->hasPages())
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                Showing {{ $notifications->firstItem() }} to {{ $notifications->lastItem() }} 
                                of {{ $notifications->total() }} notifications
                            </div>
                            {{ $notifications->links() }}
                        </div>
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No notifications found</h5>
                    <p class="text-muted">
                        @if(request()->hasAny(['search', 'read_status', 'channel', 'priority', 'date_from']))
                            Try adjusting your filters to see more notifications.
                        @else
                            You haven't received any notifications yet.
                        @endif
                    </p>
                    @if(request()->hasAny(['search', 'read_status', 'channel', 'priority', 'date_from']))
                        <button class="btn btn-outline-primary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear Filters
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Notification Preferences Modal -->
<div class="modal fade" id="preferencesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-bell"></i> Notification Preferences
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="preferencesForm">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-primary mb-3">Delivery Channels</h6>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="emailEnabled" name="email_enabled">
                                    <label class="custom-control-label" for="emailEnabled">
                                        <i class="fas fa-envelope text-info"></i> Email Notifications
                                    </label>
                                </div>
                                <small class="form-text text-muted">Receive notifications via email</small>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="teamsEnabled" name="teams_enabled">
                                    <label class="custom-control-label" for="teamsEnabled">
                                        <i class="fas fa-users text-primary"></i> Microsoft Teams
                                    </label>
                                </div>
                                <small class="form-text text-muted">Receive notifications in Teams</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-primary mb-3">Priority Filter</h6>
                            <div class="form-group">
                                <label class="form-label">Receive notifications with priority:</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="lowPriority" name="priority_filter[]" value="low">
                                    <label class="custom-control-label" for="lowPriority">
                                        <span class="badge badge-secondary">Low</span> Priority
                                    </label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="normalPriority" name="priority_filter[]" value="normal">
                                    <label class="custom-control-label" for="normalPriority">
                                        <span class="badge badge-primary">Normal</span> Priority
                                    </label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="highPriority" name="priority_filter[]" value="high">
                                    <label class="custom-control-label" for="highPriority">
                                        <span class="badge badge-warning">High</span> Priority
                                    </label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="urgentPriority" name="priority_filter[]" value="urgent">
                                    <label class="custom-control-label" for="urgentPriority">
                                        <span class="badge badge-danger">Urgent</span> Priority
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h6 class="font-weight-bold text-primary mb-3">Quiet Hours</h6>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="quietHoursEnabled" name="quiet_hours[enabled]">
                                    <label class="custom-control-label" for="quietHoursEnabled">
                                        Enable Quiet Hours
                                    </label>
                                </div>
                                <small class="form-text text-muted">Notifications will be delayed during quiet hours</small>
                            </div>

                            <div class="row" id="quietHoursSettings" style="display: none;">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="quietStart" class="form-label">Start Time</label>
                                        <input type="time" class="form-control" id="quietStart" name="quiet_hours[start]" value="22:00">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="quietEnd" class="form-label">End Time</label>
                                        <input type="time" class="form-control" id="quietEnd" name="quiet_hours[end]" value="08:00">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="savePreferences()">
                    <i class="fas fa-save"></i> Save Preferences
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-download"></i> Export Notifications
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Export Format</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="exportPdf" name="export_format" value="pdf" class="custom-control-input" checked>
                        <label class="custom-control-label" for="exportPdf">
                            <i class="fas fa-file-pdf text-danger"></i> PDF Document
                        </label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="exportCsv" name="export_format" value="csv" class="custom-control-input">
                        <label class="custom-control-label" for="exportCsv">
                            <i class="fas fa-file-csv text-success"></i> CSV Spreadsheet
                        </label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="exportExcel" name="export_format" value="excel" class="custom-control-input">
                        <label class="custom-control-label" for="exportExcel">
                            <i class="fas fa-file-excel text-success"></i> Excel Workbook
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="downloadExport()">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.notification-item {
    transition: all 0.3s ease;
    cursor: pointer;
}

.notification-item:hover {
    background-color: #f8f9fc;
}

.notification-item.unread {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}

.notification-item.unread:hover {
    background-color: #ffeaa7;
}

.notification-content {
    cursor: pointer;
}

.notification-title {
    color: #5a5c69;
    font-size: 1rem;
}

.notification-item.unread .notification-title {
    font-weight: 600;
    color: #3a3b45;
}

.notification-preview {
    font-size: 0.9rem;
    line-height: 1.4;
}

.notification-quick-actions .btn {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.notification-item:hover .notification-quick-actions .btn {
    opacity: 1;
}

.notification-checkbox {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.notification-item:hover .notification-checkbox {
    opacity: 1;
}

.notification-item.selected .notification-checkbox {
    opacity: 1;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.card {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}

.badge-sm {
    font-size: 0.75rem;
    padding: 0.25em 0.5em;
}

@media (max-width: 768px) {
    .notification-quick-actions .btn {
        opacity: 1;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .notification-meta {
        display: block;
    }
    
    .notification-meta small {
        display: block;
        margin-left: 0 !important;
        margin-top: 0.25rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Define route templates for dynamic URL generation
const routes = {
    markRead: '{{ route("notifications.mark-read", ":uuid") }}',
    markUnread: '{{ route("notifications.mark-unread", ":uuid") }}',
    delete: '{{ route("notifications.delete", ":uuid") }}',
};

function clearFilters() {
    document.getElementById('filterForm').reset();
    window.location.href = '{{ route("notifications.received") }}';
}

function refreshNotifications() {
    window.location.reload();
}

function selectAllNotifications() {
    const checkboxes = document.querySelectorAll('.notification-check');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
        const item = checkbox.closest('.notification-item');
        if (checkbox.checked) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const checked = document.querySelectorAll('.notification-check:checked');
    const markReadBtn = document.getElementById('markReadBtn');
    const archiveBtn = document.getElementById('archiveBtn');
    
    const hasSelection = checked.length > 0;
    markReadBtn.disabled = !hasSelection;
    archiveBtn.disabled = !hasSelection;
    
    // Update UI for selected items
    document.querySelectorAll('.notification-item').forEach(item => {
        const checkbox = item.querySelector('.notification-check');
        if (checkbox.checked) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
    });
}

function markAsRead(notificationId, event) {
    event.stopPropagation();
    
    const url = routes.markRead.replace(':uuid', notificationId);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
            item.classList.remove('unread');
            
            // Update button
            const button = event.target.closest('button');
            button.innerHTML = '<i class="fas fa-undo"></i> Mark Unread';
            button.className = 'btn btn-sm btn-outline-secondary';
            button.onclick = (e) => markAsUnread(notificationId, e);
            
            // Update unread count
            updateUnreadCount();
            showToast('Notification marked as read', 'success');
        }
    })
    .catch(error => {
        showToast('Error updating notification', 'error');
    });
}

function markAsUnread(notificationId, event) {
    event.stopPropagation();
    
    const url = routes.markUnread.replace(':uuid', notificationId);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
            item.classList.add('unread');
            
            // Update button
            const button = event.target.closest('button');
            button.innerHTML = '<i class="fas fa-check"></i> Mark Read';
            button.className = 'btn btn-sm btn-outline-primary';
            button.onclick = (e) => markAsRead(notificationId, e);
            
            // Update unread count
            updateUnreadCount();
            showToast('Notification marked as unread', 'success');
        }
    })
    .catch(error => {
        showToast('Error updating notification', 'error');
    });
}

function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        fetch('{{ route("notifications.mark-all-read") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Update all buttons
                document.querySelectorAll('[onclick*="markAsRead"]').forEach(button => {
                    const notificationId = button.onclick.toString().match(/\d+/)[0];
                    button.innerHTML = '<i class="fas fa-undo"></i> Mark Unread';
                    button.className = 'btn btn-sm btn-outline-secondary';
                    button.onclick = (e) => markAsUnread(notificationId, e);
                });
                
                updateUnreadCount();
                showToast('All notifications marked as read', 'success');
            }
        })
        .catch(error => {
            showToast('Error updating notifications', 'error');
        });
    }
}

function markSelectedAsRead() {
    const selected = Array.from(document.querySelectorAll('.notification-check:checked'))
                         .map(cb => cb.value);
    
    if (selected.length === 0) return;
    
    // Implementation for bulk mark as read
    selected.forEach(id => {
        markAsRead(id, { stopPropagation: () => {} });
    });
}

function archiveNotification(notificationId, event) {
    event.stopPropagation();
    
    if (confirm('Archive this notification?')) {
        const url = routes.delete.replace(':uuid', notificationId);
        
        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
                item.style.transition = 'opacity 0.5s ease';
                item.style.opacity = '0';
                setTimeout(() => item.remove(), 500);
                
                showToast('Notification archived', 'success');
            }
        })
        .catch(error => {
            showToast('Error archiving notification', 'error');
        });
    }
}

function archiveSelected() {
    const selected = Array.from(document.querySelectorAll('.notification-check:checked'))
                         .map(cb => cb.value);
    
    if (selected.length === 0) return;
    
    if (confirm(`Archive ${selected.length} selected notifications?`)) {
        selected.forEach(id => {
            archiveNotification(id, { stopPropagation: () => {} });
        });
    }
}

function openNotification(notificationId) {
    const url = routes.show.replace(':uuid', notificationId);
    window.location.href = url;
}

function openPreferences() {
    // Load current preferences
    fetch('{{ route("notifications.preferences") }}')
        .then(response => response.json())
        .then(data => {
            const prefs = data.preferences || {};
            
            // Set form values
            document.getElementById('emailEnabled').checked = prefs.email_enabled !== false;
            document.getElementById('teamsEnabled').checked = prefs.teams_enabled !== false;
            
            // Set priority filters
            const priorities = prefs.priority_filter || ['normal', 'high', 'urgent'];
            priorities.forEach(priority => {
                const checkbox = document.getElementById(priority + 'Priority');
                if (checkbox) checkbox.checked = true;
            });
            
            // Set quiet hours
            const quietHours = prefs.quiet_hours || {};
            document.getElementById('quietHoursEnabled').checked = quietHours.enabled || false;
            if (quietHours.start) document.getElementById('quietStart').value = quietHours.start;
            if (quietHours.end) document.getElementById('quietEnd').value = quietHours.end;
            
            toggleQuietHours();
            $('#preferencesModal').modal('show');
        })
        .catch(error => {
            showToast('Error loading preferences', 'error');
        });
}

function toggleQuietHours() {
    const enabled = document.getElementById('quietHoursEnabled').checked;
    const settings = document.getElementById('quietHoursSettings');
    settings.style.display = enabled ? 'block' : 'none';
}

function savePreferences() {
    const formData = new FormData(document.getElementById('preferencesForm'));
    const preferences = {};
    
    // Convert FormData to object
    for (let [key, value] of formData.entries()) {
        if (key.includes('[')) {
            // Handle nested objects like quiet_hours[enabled]
            const parts = key.split('[');
            const mainKey = parts[0];
            const subKey = parts[1].replace(']', '');
            
            if (!preferences[mainKey]) preferences[mainKey] = {};
            preferences[mainKey][subKey] = value;
        } else if (key === 'priority_filter[]') {
            // Handle arrays
            if (!preferences.priority_filter) preferences.priority_filter = [];
            preferences.priority_filter.push(value);
        } else {
            preferences[key] = value;
        }
    }
    
    // Handle checkboxes
    preferences.email_enabled = document.getElementById('emailEnabled').checked;
    preferences.teams_enabled = document.getElementById('teamsEnabled').checked;
    preferences.quiet_hours.enabled = document.getElementById('quietHoursEnabled').checked;
    
    fetch('{{ route("notifications.update-preferences") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(preferences)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#preferencesModal').modal('hide');
            showToast('Preferences saved successfully', 'success');
        } else {
            showToast('Error saving preferences', 'error');
        }
    })
    .catch(error => {
        showToast('Error saving preferences', 'error');
    });
}

function exportNotifications() {
    $('#exportModal').modal('show');
}

function downloadExport() {
    const format = document.querySelector('input[name="export_format"]:checked').value;
    const url = `{{ route("notifications.export") }}?format=${format}`;
    
    $('#exportModal').modal('hide');
    
    // Create a temporary link to trigger download
    const link = document.createElement('a');
    link.href = url;
    link.download = `notifications_${new Date().toISOString().split('T')[0]}.${format}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('Export started', 'info');
}

function updateUnreadCount() {
    fetch('{{ route("notifications.unread-count") }}')
        .then(response => response.json())
        .then(data => {
            // Update unread count in navbar or other UI elements
            const unreadElements = document.querySelectorAll('.unread-count');
            unreadElements.forEach(el => {
                el.textContent = data.count;
                el.style.display = data.count > 0 ? 'inline' : 'none';
            });
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

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Checkbox change listeners
    document.querySelectorAll('.notification-check').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
    
    // Quiet hours toggle
    document.getElementById('quietHoursEnabled')?.addEventListener('change', toggleQuietHours);
    
    // Real-time unread count updates
    updateUnreadCount();
    setInterval(updateUnreadCount, 60000); // Update every minute
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 'a':
                e.preventDefault();
                selectAllNotifications();
                break;
            case 'r':
                e.preventDefault();
                markSelectedAsRead();
                break;
        }
    }
});
</script>
@endpush
@endsection