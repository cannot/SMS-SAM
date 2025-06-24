@extends('layouts.app')

@section('title', 'Notification Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Notification Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('user.notifications.received') }}">My Notifications</a></li>
                    <li class="breadcrumb-item active">{{ Str::limit($notificationLog->notification->title, 30) }}</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            <a href="{{ route('user.notifications.received') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Notifications
            </a>
            @if(!$notificationLog->read_at)
                <button class="btn btn-outline-primary" onclick="markAsRead()">
                    <i class="fas fa-check"></i> Mark as Read
                </button>
            @else
                <button class="btn btn-outline-secondary" onclick="markAsUnread()">
                    <i class="fas fa-undo"></i> Mark as Unread
                </button>
            @endif
            <button class="btn btn-outline-danger" onclick="archiveNotification()">
                <i class="fas fa-archive"></i> Archive
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Notification Content -->
            <div class="card shadow mb-4 {{ !$notificationLog->read_at ? 'border-warning' : '' }}">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1 text-primary">
                                {{ $notificationLog->notification->title }}
                                @if(!$notificationLog->read_at)
                                    <span class="badge badge-warning ml-2">Unread</span>
                                @endif
                            </h5>
                            <div class="text-muted">
                                <small>
                                    <i class="fas fa-user mr-1"></i>
                                    From: {{ $notificationLog->notification->creator->name ?? 'System' }}
                                    <span class="ml-3">
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ $notificationLog->created_at->format('M d, Y \a\t H:i') }}
                                    </span>
                                </small>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="badge badge-{{ $notificationLog->notification->priority == 'urgent' ? 'danger' : ($notificationLog->notification->priority == 'high' ? 'warning' : ($notificationLog->notification->priority == 'normal' ? 'primary' : 'secondary')) }} badge-lg">
                                {{ ucfirst($notificationLog->notification->priority) }} Priority
                            </span>
                            <br>
                            <span class="badge badge-outline-{{ $notificationLog->channel == 'teams' ? 'primary' : 'info' }} mt-1">
                                <i class="fas fa-{{ $notificationLog->channel == 'teams' ? 'users' : 'envelope' }}"></i>
                                {{ ucfirst($notificationLog->channel) }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Notification Content -->
                    <div class="notification-content-display">
                        <div class="content-body">
                            {!! nl2br(e($notificationLog->notification->content)) !!}
                        </div>
                    </div>

                    <!-- Custom Data (if available) -->
                    @if($notificationLog->notification->data)
                        <div class="mt-4">
                            <h6 class="text-muted mb-2">
                                <i class="fas fa-info-circle"></i> Additional Information
                            </h6>
                            <div class="bg-light border rounded p-3">
                                @foreach($notificationLog->notification->data as $key => $value)
                                    <div class="row mb-2">
                                        <div class="col-md-3">
                                            <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                        </div>
                                        <div class="col-md-9">
                                            @if(is_array($value) || is_object($value))
                                                <code>{{ json_encode($value, JSON_PRETTY_PRINT) }}</code>
                                            @else
                                                {{ $value }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="mt-4 text-center">
                        <div class="btn-group">
                            @if(!$notificationLog->read_at)
                                <button class="btn btn-primary" onclick="markAsRead()">
                                    <i class="fas fa-check"></i> Mark as Read
                                </button>
                            @else
                                <button class="btn btn-outline-secondary" onclick="markAsUnread()">
                                    <i class="fas fa-undo"></i> Mark as Unread
                                </button>
                            @endif
                            <button class="btn btn-outline-info" onclick="printNotification()">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button class="btn btn-outline-success" onclick="shareNotification()">
                                <i class="fas fa-share"></i> Share
                            </button>
                            <button class="btn btn-outline-danger" onclick="archiveNotification()">
                                <i class="fas fa-archive"></i> Archive
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Notifications -->
            @if($relatedNotifications->count() > 0)
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-link"></i> Related Notifications
                        </h6>
                    </div>
                    <div class="card-body">
                        @foreach($relatedNotifications as $related)
                            <div class="d-flex align-items-center mb-3 p-2 border rounded">
                                <div class="flex-grow-1">
                                    <div class="font-weight-bold">{{ $related->notification->title }}</div>
                                    <small class="text-muted">
                                        {{ $related->created_at->format('M d, Y H:i') }} via {{ ucfirst($related->channel) }}
                                    </small>
                                </div>
                                <div>
                                    <a href="{{ route('user.notifications.show', $related) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Delivery Information -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Delivery Information
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-{{ $notificationLog->status == 'delivered' ? 'success' : ($notificationLog->status == 'failed' ? 'danger' : 'warning') }}">
                                {{ ucfirst($notificationLog->status) }}
                            </span>
                        </dd>

                        <dt class="col-sm-5">Channel:</dt>
                        <dd class="col-sm-7">
                            <i class="fas fa-{{ $notificationLog->channel == 'teams' ? 'users' : 'envelope' }} mr-1"></i>
                            {{ ucfirst($notificationLog->channel) }}
                        </dd>

                        <dt class="col-sm-5">Sent At:</dt>
                        <dd class="col-sm-7">
                            {{ $notificationLog->created_at->format('M d, Y') }}
                            <small class="text-muted d-block">{{ $notificationLog->created_at->format('H:i:s') }}</small>
                        </dd>

                        @if($notificationLog->delivered_at)
                            <dt class="col-sm-5">Delivered At:</dt>
                            <dd class="col-sm-7">
                                {{ $notificationLog->delivered_at->format('M d, Y') }}
                                <small class="text-muted d-block">{{ $notificationLog->delivered_at->format('H:i:s') }}</small>
                            </dd>

                            <dt class="col-sm-5">Delivery Time:</dt>
                            <dd class="col-sm-7">
                                @php
                                    $deliveryTime = $notificationLog->delivered_at->diffInSeconds($notificationLog->created_at);
                                @endphp
                                <span class="badge badge-{{ $deliveryTime < 5 ? 'success' : ($deliveryTime < 30 ? 'warning' : 'danger') }}">
                                    {{ $deliveryTime }}s
                                </span>
                            </dd>
                        @endif

                        @if($notificationLog->read_at)
                            <dt class="col-sm-5">Read At:</dt>
                            <dd class="col-sm-7">
                                {{ $notificationLog->read_at->format('M d, Y') }}
                                <small class="text-muted d-block">{{ $notificationLog->read_at->format('H:i:s') }}</small>
                                <small class="text-muted d-block">{{ $notificationLog->read_at->diffForHumans() }}</small>
                            </dd>
                        @endif

                        @if($notificationLog->error_message)
                            <dt class="col-sm-5">Error:</dt>
                            <dd class="col-sm-7">
                                <span class="text-danger" title="{{ $notificationLog->error_message }}">
                                    {{ Str::limit($notificationLog->error_message, 50) }}
                                </span>
                                @if(strlen($notificationLog->error_message) > 50)
                                    <button class="btn btn-sm btn-outline-danger mt-1" onclick="showErrorDetails()">
                                        View Full Error
                                    </button>
                                @endif
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Notification Template -->
            @if($notificationLog->notification->template)
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-template"></i> Template Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">Name:</dt>
                            <dd class="col-sm-8">{{ $notificationLog->notification->template->name }}</dd>

                            @if($notificationLog->notification->template->description)
                                <dt class="col-sm-4">Description:</dt>
                                <dd class="col-sm-8">{{ $notificationLog->notification->template->description }}</dd>
                            @endif

                            @if($notificationLog->notification->template->category)
                                <dt class="col-sm-4">Category:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge badge-secondary">{{ $notificationLog->notification->template->category }}</span>
                                </dd>
                            @endif
                        </dl>
                    </div>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if(!$notificationLog->read_at)
                            <button class="btn btn-outline-primary" onclick="markAsRead()">
                                <i class="fas fa-check"></i> Mark as Read
                            </button>
                        @else
                            <button class="btn btn-outline-secondary" onclick="markAsUnread()">
                                <i class="fas fa-undo"></i> Mark as Unread
                            </button>
                        @endif

                        <button class="btn btn-outline-info" onclick="printNotification()">
                            <i class="fas fa-print"></i> Print Notification
                        </button>

                        <button class="btn btn-outline-success" onclick="shareNotification()">
                            <i class="fas fa-share"></i> Share
                        </button>

                        <button class="btn btn-outline-warning" onclick="reportIssue()">
                            <i class="fas fa-exclamation-triangle"></i> Report Issue
                        </button>

                        <hr>

                        <button class="btn btn-outline-danger" onclick="archiveNotification()">
                            <i class="fas fa-archive"></i> Archive Notification
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-navigation"></i> Navigation
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('user.notifications.received') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list"></i> All Notifications
                        </a>
                        <a href="{{ route('user.notifications.received', ['read_status' => 'unread']) }}" class="btn btn-outline-warning">
                            <i class="fas fa-envelope"></i> Unread Only
                        </a>
                        <a href="{{ route('user.notifications.received', ['priority' => 'urgent']) }}" class="btn btn-outline-danger">
                            <i class="fas fa-exclamation-circle"></i> Urgent Notifications
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Error Details Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Delivery Error Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>Error Message:</strong>
                    <pre id="errorContent" class="mt-2 mb-0">{{ $notificationLog->error_message }}</pre>
                </div>
                <p class="mb-0">
                    If this error persists, please contact your system administrator or report this issue using the "Report Issue" button.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" onclick="reportIssue()">
                    <i class="fas fa-exclamation-triangle"></i> Report Issue
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-share"></i> Share Notification
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Share Link</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="shareLink" value="{{ request()->url() }}" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" onclick="copyShareLink()">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">Share this link with others to show them this notification.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Quick Share</label>
                    <div class="btn-group btn-group-block">
                        <button class="btn btn-outline-primary" onclick="shareViaEmail()">
                            <i class="fas fa-envelope"></i> Email
                        </button>
                        <button class="btn btn-outline-info" onclick="shareViaTeams()">
                            <i class="fab fa-microsoft"></i> Teams
                        </button>
                        <button class="btn btn-outline-secondary" onclick="shareToClipboard()">
                            <i class="fas fa-clipboard"></i> Copy Text
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Report Issue Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Report Issue
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="reportForm">
                    <div class="form-group">
                        <label class="form-label">Issue Type</label>
                        <select class="form-control" name="issue_type" required>
                            <option value="">Select issue type</option>
                            <option value="delivery_failed">Delivery Failed</option>
                            <option value="content_incorrect">Content Incorrect</option>
                            <option value="spam_unwanted">Spam/Unwanted</option>
                            <option value="technical_error">Technical Error</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4" 
                                  placeholder="Please describe the issue in detail..." required></textarea>
                    </div>
                    <input type="hidden" name="notification_log_id" value="{{ $notificationLog->id }}">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="submitReport()">
                    <i class="fas fa-paper-plane"></i> Submit Report
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.badge-lg {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
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

.notification-content-display {
    padding: 1.5rem;
    background: #f8f9fc;
    border-radius: 0.375rem;
    border: 1px solid #e3e6f0;
}

.content-body {
    font-size: 1rem;
    line-height: 1.6;
    color: #5a5c69;
}

.card.border-warning {
    border-left: 4px solid #f6c23e !important;
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}

.btn-group-block {
    display: flex;
    width: 100%;
}

.btn-group-block .btn {
    flex: 1;
}

@media print {
    .btn, .card-header, .breadcrumb, nav, .modal {
        display: none !important;
    }
    
    .notification-content-display {
        background: white !important;
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
function markAsRead() {
    fetch(`{{ route("user.notifications.mark-read", $notificationLog) }}`, {
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
            location.reload();
        }
    })
    .catch(error => {
        showToast('Error marking notification as read', 'error');
    });
}

function markAsUnread() {
    fetch(`{{ route("user.notifications.mark-unread", $notificationLog) }}`, {
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
            location.reload();
        }
    })
    .catch(error => {
        showToast('Error marking notification as unread', 'error');
    });
}

function archiveNotification() {
    if (confirm('Are you sure you want to archive this notification?')) {
        fetch(`{{ route("user.notifications.delete", $notificationLog) }}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Notification archived successfully', 'success');
                setTimeout(() => {
                    window.location.href = '{{ route("user.notifications.received") }}';
                }, 2000);
            }
        })
        .catch(error => {
            showToast('Error archiving notification', 'error');
        });
    }
}

function printNotification() {
    window.print();
}

function shareNotification() {
    $('#shareModal').modal('show');
}

function copyShareLink() {
    const shareLink = document.getElementById('shareLink');
    shareLink.select();
    shareLink.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(shareLink.value).then(() => {
        showToast('Link copied to clipboard', 'success');
    });
}

function shareViaEmail() {
    const subject = encodeURIComponent('Notification: ' + '{{ addslashes($notificationLog->notification->title) }}');
    const body = encodeURIComponent(`I'm sharing this notification with you:\n\n${window.location.href}`);
    window.open(`mailto:?subject=${subject}&body=${body}`);
}

function shareViaTeams() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent('Check out this notification: ' + '{{ addslashes($notificationLog->notification->title) }}');
    window.open(`https://teams.microsoft.com/share?href=${url}&msgText=${text}`, '_blank');
}

function shareToClipboard() {
    const text = `Notification: {{ addslashes($notificationLog->notification->title) }}\n\n{{ addslashes(strip_tags($notificationLog->notification->content)) }}\n\nView details: ${window.location.href}`;
    
    navigator.clipboard.writeText(text).then(() => {
        showToast('Notification text copied to clipboard', 'success');
        $('#shareModal').modal('hide');
    });
}

function showErrorDetails() {
    $('#errorModal').modal('show');
}

function reportIssue() {
    $('#reportModal').modal('show');
}

function submitReport() {
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    
    // Convert FormData to JSON
    const data = {};
    formData.forEach((value, key) => data[key] = value);
    
    fetch('{{ route("user.notifications.report-issue") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#reportModal').modal('hide');
            showToast('Issue reported successfully. Thank you for your feedback!', 'success');
            form.reset();
        } else {
            showToast('Error submitting report. Please try again.', 'error');
        }
    })
    .catch(error => {
        showToast('Error submitting report. Please try again.', 'error');
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

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 'p':
                e.preventDefault();
                printNotification();
                break;
            case 'm':
                e.preventDefault();
                @if(!$notificationLog->read_at)
                    markAsRead();
                @else
                    markAsUnread();
                @endif
                break;
        }
    }
    
    // ESC key to go back
    if (e.key === 'Escape') {
        window.history.back();
    }
});

// Auto-mark as read after viewing for 5 seconds
@if(!$notificationLog->read_at)
    setTimeout(() => {
        markAsRead();
    }, 5000);
@endif
</script>
@endpush
@endsection