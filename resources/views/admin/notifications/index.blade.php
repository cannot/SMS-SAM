@extends('layouts.app')

@section('title', 'จัดการการแจ้งเตือนทั้งหมด (Admin)')

@section('styles')
<style>
    .status-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 5px;
    }
    .priority-urgent { color: #dc3545; font-weight: bold; }
    .priority-high { color: #fd7e14; font-weight: bold; }
    .priority-medium { color: #ffc107; font-weight: bold; }
    .priority-normal { color: #17a2b8; }
    .priority-low { color: #28a745; }
    
    .progress-mini {
        height: 4px;
        background-color: #e9ecef;
        border-radius: 2px;
        overflow: hidden;
    }
    .progress-mini .progress-bar {
        height: 100%;
    }
    
    .table th {
        border-top: none;
        font-weight: 600;
        background-color: #f8f9fa;
    }
    .table-hover tbody tr:hover {
        background-color: #f5f5f5;
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .text-truncate-custom {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-shield-alt me-2"></i> 
            จัดการการแจ้งเตือนทั้งหมด
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> สร้างใหม่
                </a>
                <a href="{{ route('admin.notifications.analytics') }}" class="btn btn-outline-info">
                    <i class="fas fa-chart-line"></i> สถิติ
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <h4 class="text-primary">{{ $notifications->total() }}</h4>
                            <small class="text-muted">ทั้งหมด</small>
                        </div>
                        <div class="col-md-2">
                            @php $draftCount = \App\Models\Notification::where('status', 'draft')->count(); @endphp
                            <h4 class="text-secondary">{{ $draftCount }}</h4>
                            <small class="text-muted">ร่าง</small>
                        </div>
                        <div class="col-md-2">
                            @php $scheduledCount = \App\Models\Notification::where('status', 'scheduled')->count(); @endphp
                            <h4 class="text-warning">{{ $scheduledCount }}</h4>
                            <small class="text-muted">กำหนดการ</small>
                        </div>
                        <div class="col-md-2">
                            @php $processingCount = \App\Models\Notification::whereIn('status', ['queued', 'processing'])->count(); @endphp
                            <h4 class="text-info">{{ $processingCount }}</h4>
                            <small class="text-muted">กำลังส่ง</small>
                        </div>
                        <div class="col-md-2">
                            @php $sentCount = \App\Models\Notification::where('status', 'sent')->count(); @endphp
                            <h4 class="text-success">{{ $sentCount }}</h4>
                            <small class="text-muted">ส่งแล้ว</small>
                        </div>
                        <div class="col-md-2">
                            @php $failedCount = \App\Models\Notification::where('status', 'failed')->count(); @endphp
                            <h4 class="text-danger">{{ $failedCount }}</h4>
                            <small class="text-muted">ล้มเหลว</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-filter me-2"></i> 
                ตัวกรอง
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.notifications.index') }}" class="row g-3">
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">สถานะทั้งหมด</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>ร่าง</option>
                        <option value="queued" {{ request('status') == 'queued' ? 'selected' : '' }}>รอส่ง</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>กำหนดการ</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>กำลังส่ง</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>ส่งแล้ว</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-select form-select-sm">
                        <option value="">ความสำคัญทั้งหมด</option>
                        @foreach($priorities as $priority)
                            <option value="{{ $priority }}" {{ request('priority') == $priority ? 'selected' : '' }}>
                                {{ ucfirst($priority) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="channel" class="form-select form-select-sm">
                        <option value="">ช่องทางทั้งหมด</option>
                        <option value="email" {{ request('channel') == 'email' ? 'selected' : '' }}>อีเมล</option>
                        <option value="teams" {{ request('channel') == 'teams' ? 'selected' : '' }}>Teams</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" 
                           placeholder="ค้นหาหัวข้อหรือ UUID..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <div class="btn-group w-100">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> ค้นหา
                        </button>
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i> ล้าง
                        </a>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="bulkActions()">
                            <i class="fas fa-check-square"></i> Bulk
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <input type="checkbox" id="selectAll" class="form-check-input me-2" onchange="toggleSelectAll()">
                <strong>รายการการแจ้งเตือน</strong>
                <span class="text-muted ms-2">({{ $notifications->count() }} จาก {{ $notifications->total() }} รายการ)</span>
            </div>
            <div id="bulkActions" class="d-none">
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-warning" onclick="bulkResend()">
                        <i class="fas fa-redo"></i> ส่งใหม่
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="bulkCancel()">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="bulkExport()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="40">#</th>
                            <th width="200">หัวข้อ</th>
                            <th width="100">สถานะ</th>
                            <th width="80">ความสำคัญ</th>
                            <th width="100">ช่องทาง</th>
                            <th width="80">ผู้รับ</th>
                            <th width="120">ความคืบหน้า</th>
                            <th width="120">วันที่สร้าง</th>
                            <th width="100">ผู้สร้าง</th>
                            <th width="120">การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $notification)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input notification-checkbox" 
                                           value="{{ $notification->id }}" onchange="updateBulkActions()">
                                </td>
                                <td>
                                    <div class="text-truncate-custom" title="{{ $notification->subject }}">
                                        <span class="status-dot bg-{{ $notification->getStatusColor() }}"></span>
                                        <strong>{{ $notification->subject }}</strong>
                                    </div>
                                    @if($notification->template)
                                        <small class="text-muted d-block">
                                            <i class="fas fa-file-text"></i> {{ $notification->template->name }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $notification->getStatusColor() }}">
                                        {{ $notification->getStatusText() }}
                                    </span>
                                    @if($notification->status === 'processing')
                                        <div class="spinner-border spinner-border-sm ms-1" role="status"></div>
                                    @endif
                                </td>
                                <td>
                                    <span class="priority-{{ $notification->priority }}">
                                        {{ ucfirst($notification->priority) }}
                                    </span>
                                </td>
                                <td>
                                    @foreach($notification->channels as $channel)
                                        <span class="badge bg-secondary me-1">
                                            <i class="fas fa-{{ $channel === 'email' ? 'envelope' : 'users' }}"></i>
                                        </span>
                                    @endforeach
                                </td>
                                <td class="text-center">
                                    <strong>{{ $notification->logs->count() }}</strong>
                                </td>
                                <td>
                                    @if($notification->logs->count() > 0)
                                        @php
                                            $totalLogs = $notification->logs->count();
                                            $sentLogs = $notification->logs->whereIn('status', ['sent', 'delivered'])->count();
                                            $failedLogs = $notification->logs->where('status', 'failed')->count();
                                            $pendingLogs = $notification->logs->where('status', 'pending')->count();
                                            
                                            $successRate = ($sentLogs / $totalLogs) * 100;
                                            $failureRate = ($failedLogs / $totalLogs) * 100;
                                            $pendingRate = ($pendingLogs / $totalLogs) * 100;
                                        @endphp
                                        <div class="progress-mini mb-1">
                                            <div class="progress-bar bg-success" style="width: {{ $successRate }}%"></div>
                                            <div class="progress-bar bg-danger" style="width: {{ $failureRate }}%"></div>
                                            <div class="progress-bar bg-warning" style="width: {{ $pendingRate }}%"></div>
                                        </div>
                                        <small class="text-muted">
                                            {{ $sentLogs }}/{{ $totalLogs }}
                                            @if($failedLogs > 0)
                                                <span class="text-danger">({{ $failedLogs }} ล้มเหลว)</span>
                                            @endif
                                        </small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $notification->created_at->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $notification->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    <div class="text-truncate-custom">
                                        {{ $notification->creator->display_name ?? $notification->creator->username ?? 'System' }}
                                    </div>
                                    @if($notification->api_key_id)
                                        <small class="text-muted">
                                            <i class="fas fa-robot"></i> API
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.notifications.show', $notification->uuid) }}" 
                                           class="btn btn-outline-primary" title="ดูรายละเอียด">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        @if($notification->logs->where('status', 'failed')->count() > 0)
                                            <form method="POST" action="{{ route('admin.notifications.resend', $notification->uuid) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning" 
                                                        title="ส่งใหม่" onclick="return confirm('ส่งใหม่?')">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        @if($notification->canBeCancelled())
                                            <form method="POST" action="{{ route('admin.notifications.cancel', $notification->uuid) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger" 
                                                        title="ยกเลิก" onclick="return confirm('ยกเลิก?')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" 
                                                    data-bs-toggle="dropdown" title="เพิ่มเติม">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.notifications.logs', $notification->uuid) }}">
                                                        <i class="fas fa-list me-2"></i> ดู Logs
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.notifications.preview', $notification->uuid) }}">
                                                        <i class="fas fa-eye me-2"></i> ดูตัวอย่าง
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                @if(in_array($notification->status, ['draft', 'failed', 'cancelled']))
                                                    <li>
                                                        <form method="POST" action="{{ route('admin.notifications.destroy', $notification->uuid) }}" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger" 
                                                                    onclick="return confirm('ลบการแจ้งเตือนนี้?')">
                                                                <i class="fas fa-trash me-2"></i> ลบ
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-bell-slash fa-2x mb-2"></i>
                                        <h5>ไม่พบการแจ้งเตือน</h5>
                                        <p>ยังไม่มีการแจ้งเตือนในระบบ หรือลองปรับตัวกรอง</p>
                                        <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus-circle"></i> สร้างการแจ้งเตือนแรก
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        @if($notifications->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-center">
                    {{ $notifications->appends(request()->query())->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Bulk Actions Modal -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">การดำเนินการแบบกลุ่ม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>เลือกแล้ว <span id="selectedCount">0</span> รายการ</p>
                <div class="mb-3">
                    <label class="form-label">เลือกการดำเนินการ:</label>
                    <select class="form-select" id="bulkAction">
                        <option value="">-- เลือกการดำเนินการ --</option>
                        <option value="resend">ส่งใหม่ (เฉพาะที่ล้มเหลว)</option>
                        <option value="cancel">ยกเลิกการส่ง</option>
                        <option value="export">Export ข้อมูล</option>
                        <option value="delete">ลบ (เฉพาะร่าง/ล้มเหลว)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="executeBulkAction()">ดำเนินการ</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Auto-submit filter form on select change
document.querySelectorAll('select[name="status"], select[name="priority"], select[name="channel"]').forEach(select => {
    select.addEventListener('change', function() {
        this.closest('form').submit();
    });
});

// Toggle select all
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

// Update bulk actions visibility
function updateBulkActions() {
    const selected = document.querySelectorAll('.notification-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    
    if (selected.length > 0) {
        bulkActions.classList.remove('d-none');
    } else {
        bulkActions.classList.add('d-none');
    }
}

// Bulk actions
function bulkActions() {
    const selected = document.querySelectorAll('.notification-checkbox:checked');
    
    if (selected.length === 0) {
        alert('กรุณาเลือกการแจ้งเตือน');
        return;
    }
    
    document.getElementById('selectedCount').textContent = selected.length;
    const modal = new bootstrap.Modal(document.getElementById('bulkActionsModal'));
    modal.show();
}

function bulkResend() {
    const selected = Array.from(document.querySelectorAll('.notification-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('กรุณาเลือกการแจ้งเตือน');
        return;
    }
    if (!confirm(`ส่งใหม่ ${selected.length} รายการ?`)) return;
    performBulkAction('resend', selected);
}

function bulkCancel() {
    const selected = Array.from(document.querySelectorAll('.notification-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('กรุณาเลือกการแจ้งเตือน');
        return;
    }
    if (!confirm(`ยกเลิก ${selected.length} รายการ?`)) return;
    performBulkAction('cancel', selected);
}

function bulkExport() {
    const selected = Array.from(document.querySelectorAll('.notification-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('กรุณาเลือกการแจ้งเตือน');
        return;
    }
    const params = new URLSearchParams();
    params.append('ids', selected.join(','));
    window.open(`{{ route('admin.notifications.export') }}?${params.toString()}`, '_blank');
}

function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    const selected = Array.from(document.querySelectorAll('.notification-checkbox:checked')).map(cb => cb.value);
    
    if (!action) {
        alert('กรุณาเลือกการดำเนินการ');
        return;
    }
    
    if (!confirm(`ดำเนินการ ${selected.length} รายการ?`)) return;
    
    performBulkAction(action, selected);
    bootstrap.Modal.getInstance(document.getElementById('bulkActionsModal')).hide();
}

function performBulkAction(action, notifications) {
    fetch(`{{ route('admin.notifications.bulk-action') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            action: action,
            notifications: notifications
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
    });
}

// Auto refresh for processing notifications
setInterval(() => {
    if (document.querySelector('.spinner-border')) {
        location.reload();
    }
}, 30000);
</script>
@endsection