@extends('layouts.app')

@section('title', 'จัดการการแจ้งเตือนทั้งหมด (Admin)')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-shield-exclamation"></i> จัดการการแจ้งเตือนทั้งหมด</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> สร้างการแจ้งเตือนใหม่
            </a>
            <a href="{{ route('admin.notifications.analytics') }}" class="btn btn-outline-info">
                <i class="bi bi-graph-up"></i> สถิติ
            </a>
        </div>
    </div>
</div>

<!-- Admin Dashboard Stats -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3>{{ $notifications->total() }}</h3>
                <small>ทั้งหมด</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                @php $draftCount = \App\Models\Notification::where('status', 'draft')->count(); @endphp
                <h3>{{ $draftCount }}</h3>
                <small>ร่าง</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                @php $scheduledCount = \App\Models\Notification::where('status', 'scheduled')->count(); @endphp
                <h3>{{ $scheduledCount }}</h3>
                <small>กำหนดการ</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                @php $processingCount = \App\Models\Notification::whereIn('status', ['queued', 'processing'])->count(); @endphp
                <h3>{{ $processingCount }}</h3>
                <small>กำลังส่ง</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                @php $sentCount = \App\Models\Notification::where('status', 'sent')->count(); @endphp
                <h3>{{ $sentCount }}</h3>
                <small>ส่งแล้ว</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                @php $failedCount = \App\Models\Notification::where('status', 'failed')->count(); @endphp
                <h3>{{ $failedCount }}</h3>
                <small>ล้มเหลว</small>
            </div>
        </div>
    </div>
</div>

<!-- Admin Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-funnel"></i> ตัวกรองขั้นสูง (Admin)</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.notifications.index') }}" class="row g-3">
            <div class="col-md-2">
                <label for="status" class="form-label">สถานะ</label>
                <select name="status" id="status" class="form-select">
                    <option value="">ทั้งหมด</option>
                    @foreach($priorities as $priority)
                        <option value="{{ $priority }}" {{ request('priority') == $priority ? 'selected' : '' }}>
                            {{ ucfirst($priority) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="template" class="form-label">เทมเพลต</label>
                <select name="template" id="template" class="form-select">
                    <option value="">ทั้งหมด</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}" {{ request('template') == $template->id ? 'selected' : '' }}>
                            {{ $template->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="creator" class="form-label">ผู้สร้าง</label>
                <select name="creator" id="creator" class="form-select">
                    <option value="">ทั้งหมด</option>
                    {{-- @foreach($creators as $creator)
                        <option value="{{ $creator->id }}" {{ request('creator') == $creator->id ? 'selected' : '' }}>
                            {{ $creator->display_name }}
                        </option>
                    @endforeach --}}
                </select>
            </div>
            <div class="col-md-2">
                <label for="search" class="form-label">ค้นหา</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="หัวข้อ หรือ UUID" value="{{ request('search') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> กรอง
                    </button>
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> ล้าง
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">ช่วงวันที่</label>
                <div class="row">
                    <div class="col">
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="จาก">
                    </div>
                    <div class="col">
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="ถึง">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">การดำเนินการ</label>
                <div class="btn-group w-100">
                    <button type="button" class="btn btn-outline-info" onclick="exportNotifications()">
                        <i class="bi bi-download"></i> Export
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="bulkActions()">
                        <i class="bi bi-check2-square"></i> Bulk Actions
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Admin Notifications List -->
<div class="row">
    @forelse($notifications as $notification)
        <div class="col-12 mb-3">
            <div class="card notification-card priority-{{ $notification->priority }}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_notifications[]" 
                                    value="{{ $notification->id }}" id="notification_{{ $notification->id }}">
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="d-flex align-items-center mb-2">
                                <span class="status-indicator bg-{{ $notification->status_badge_class }} me-2"></span>
                                <h6 class="mb-0">{{ $notification->subject }}</h6>
                                <span class="badge bg-{{ $notification->priority_badge_class }} ms-2">
                                    {{ $notification->priority_text }}
                                </span>
                                <span class="badge bg-{{ $notification->status_badge_class }} ms-2">
                                    {{ $notification->status_text }}
                                </span>
                            </div>
                            
                            <div class="text-muted small mb-2">
                                <i class="bi bi-person"></i> สร้างโดย: {{ $notification->creator->display_name ?? 'System' }} |
                                <i class="bi bi-calendar"></i> {{ $notification->created_at->format('d/m/Y H:i') }} |
                                <i class="bi bi-envelope"></i> {{ $notification->total_recipients }} ผู้รับ
                                @if($notification->template)
                                    | <i class="bi bi-file-text"></i> {{ $notification->template->name }}
                                @endif
                                @if($notification->api_key_id)
                                    | <i class="bi bi-code"></i> API
                                @endif
                            </div>
                            
                            <div class="d-flex gap-1 mb-2">
                                @foreach($notification->channels as $channel)
                                    <span class="badge bg-secondary">
                                        @if($channel == 'email')
                                            <i class="bi bi-envelope"></i> Email
                                        @elseif($channel == 'teams')
                                            <i class="bi bi-microsoft-teams"></i> Teams
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                            
                            <!-- Admin-specific delivery progress -->
                            @if($notification->total_recipients > 0)
                                <div class="progress mb-2" style="height: 6px;">
                                    @php
                                        $successRate = ($notification->delivered_count / $notification->total_recipients) * 100;
                                        $failureRate = ($notification->failed_count / $notification->total_recipients) * 100;
                                        $pendingRate = 100 - $successRate - $failureRate;
                                    @endphp
                                    <div class="progress-bar bg-success" style="width: {{ $successRate }}%"></div>
                                    <div class="progress-bar bg-danger" style="width: {{ $failureRate }}%"></div>
                                    <div class="progress-bar bg-warning" style="width: {{ $pendingRate }}%"></div>
                                </div>
                                <small class="text-muted">
                                    ส่งสำเร็จ {{ $notification->delivered_count }}/{{ $notification->total_recipients }}
                                    @if($notification->failed_count > 0)
                                        | ล้มเหลว {{ $notification->failed_count }}
                                    @endif
                                    | อัตราสำเร็จ {{ number_format($successRate, 1) }}%
                                </small>
                            @endif
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.notifications.show', $notification->uuid) }}" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye"></i> ดู
                                </a>
                                @if($notification->status == 'draft')
                                    <a href="{{ route('admin.notifications.edit', $notification->uuid) }}" 
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-pencil"></i> แก้ไข
                                    </a>
                                @endif
                                @if($notification->status == 'scheduled')
                                    <form method="POST" action="{{ route('admin.notifications.cancel', $notification->uuid) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning btn-sm" 
                                                onclick="return confirm('ยกเลิกการแจ้งเตือนนี้?')">
                                            <i class="bi bi-x-circle"></i> ยกเลิก
                                        </button>
                                    </form>
                                @endif
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" 
                                            data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <form method="POST" action="{{ route('admin.notifications.duplicate', $notification->uuid) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-files"></i> ทำสำเนา
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" onclick="viewLogs('{{ $notification->uuid }}')">
                                            <i class="bi bi-list-ul"></i> ดู Logs
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="resendFailed('{{ $notification->uuid }}')">
                                            <i class="bi bi-arrow-clockwise"></i> ส่งใหม่ (ล้มเหลว)
                                        </a></li>
                                        @if(in_array($notification->status, ['draft', 'failed', 'cancelled']))
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('admin.notifications.destroy', $notification->uuid) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger" 
                                                        onclick="return confirm('ลบการแจ้งเตือนนี้? การกระทำนี้ไม่สามารถยกเลิกได้')">
                                                    <i class="bi bi-trash"></i> ลบ
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-shield-exclamation display-1 text-muted"></i>
                <h4 class="text-muted">ไม่พบการแจ้งเตือน</h4>
                <p class="text-muted">ยังไม่มีการแจ้งเตือนในระบบ หรือลองปรับตัวกรอง</p>
                <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> สร้างการแจ้งเตือนแรก
                </a>
            </div>
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if($notifications->hasPages())
    <div class="d-flex justify-content-center">
        {{ $notifications->appends(request()->query())->links() }}
    </div>
@endif

<!-- Bulk Actions Modal -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">การดำเนินการแบบกลุ่ม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">เลือกการดำเนินการ:</label>
                    <select class="form-select" id="bulkAction">
                        <option value="">-- เลือกการดำเนินการ --</option>
                        <option value="cancel">ยกเลิกการส่ง</option>
                        <option value="resend">ส่งใหม่</option>
                        <option value="delete">ลบ</option>
                        <option value="export">Export ข้อมูล</option>
                    </select>
                </div>
                <div id="selectedCount" class="text-muted"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="executeBulkAction()">ดำเนินการ</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Auto-submit filter form on select change
document.querySelectorAll('#status, #priority, #template, #creator').forEach(select => {
    select.addEventListener('change', function() {
        this.closest('form').submit();
    });
});

// Select all checkboxes
function selectAll() {
    const checkboxes = document.querySelectorAll('input[name="selected_notifications[]"]');
    const selectAllCheckbox = document.querySelector('#selectAll');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Bulk actions
function bulkActions() {
    const selected = document.querySelectorAll('input[name="selected_notifications[]"]:checked');
    
    if (selected.length === 0) {
        alert('กรุณาเลือกการแจ้งเตือนที่ต้องการดำเนินการ');
        return;
    }
    
    document.getElementById('selectedCount').textContent = `เลือกแล้ว ${selected.length} รายการ`;
    const modal = new bootstrap.Modal(document.getElementById('bulkActionsModal'));
    modal.show();
}

function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    const selected = Array.from(document.querySelectorAll('input[name="selected_notifications[]"]:checked'))
                          .map(cb => cb.value);
    
    if (!action) {
        alert('กรุณาเลือกการดำเนินการ');
        return;
    }
    
    if (!confirm(`คุณแน่ใจหรือไม่ที่จะ ${action} การแจ้งเตือน ${selected.length} รายการ?`)) {
        return;
    }
    
    // Send bulk action request
    fetch(`{{ route('admin.notifications.index') }}/bulk-action`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.App.csrfToken
        },
        body: JSON.stringify({
            action: action,
            notifications: selected
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.AppFunctions.showNotification(data.message, 'success');
            location.reload();
        } else {
            window.AppFunctions.showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Bulk action error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาด', 'error');
    });
    
    bootstrap.Modal.getInstance(document.getElementById('bulkActionsModal')).hide();
}

function exportNotifications() {
    const params = new URLSearchParams(window.location.search);
    window.open(`{{ route('admin.notifications.index') }}/export?${params.toString()}`, '_blank');
}

function viewLogs(uuid) {
    window.open(`{{ route('admin.notifications.index') }}/${uuid}/logs`, '_blank');
}

function resendFailed(uuid) {
    if (!confirm('ส่งการแจ้งเตือนใหม่สำหรับผู้รับที่ล้มเหลว?')) {
        return;
    }
    
    fetch(`{{ route('admin.notifications.index') }}/${uuid}/resend-failed`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': window.App.csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.AppFunctions.showNotification('ส่งใหม่เรียบร้อย', 'success');
        } else {
            window.AppFunctions.showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Resend error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาด', 'error');
    });
}

// Real-time stats update
setInterval(() => {
    fetch('{{ route('admin.notifications.statistics') }}')
        .then(response => response.json())
        .then(data => {
            // Update dashboard stats if needed
        })
        .catch(error => console.log('Stats update failed:', error));
}, 30000); // Update every 30 seconds
</script>
@endpush