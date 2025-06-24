@extends('layouts.app')

@section('title', 'การแจ้งเตือนที่ได้รับ')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-envelope"></i> การแจ้งเตือนที่ได้รับ</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-outline-primary" onclick="markAllAsRead()">
                <i class="bi bi-check2-all"></i> อ่านทั้งหมด
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="refreshNotifications()">
                <i class="bi bi-arrow-clockwise"></i> รีเฟรช
            </button>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">ทั้งหมด</h6>
                        <h3>{{ $notifications->total() }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-envelope display-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">ยังไม่ได้อ่าน</h6>
                        @php
                            $unreadCount = \App\Models\Notification::whereJsonContains('recipients', Auth::user()->email)
                                ->whereDoesntHave('logs', function($q) {
                                    $q->where('recipient_email', Auth::user()->email)->where('status', 'read');
                                })->count();
                        @endphp
                        <h3>{{ $unreadCount }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-envelope-open display-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">เร่งด่วน</h6>
                        @php
                            $urgentCount = \App\Models\Notification::whereJsonContains('recipients', Auth::user()->email)
                                ->where('priority', 'urgent')->count();
                        @endphp
                        <h3>{{ $urgentCount }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-exclamation-triangle display-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">วันนี้</h6>
                        @php
                            $todayCount = \App\Models\Notification::whereJsonContains('recipients', Auth::user()->email)
                                ->whereDate('created_at', today())->count();
                        @endphp
                        <h3>{{ $todayCount }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-day display-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-funnel"></i> ตัวกรอง</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('notifications.received') }}" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">สถานะ</label>
                <select name="status" id="status" class="form-select">
                    <option value="">ทั้งหมด</option>
                    <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>ยังไม่ได้อ่าน</option>
                    <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>อ่านแล้ว</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="priority" class="form-label">ระดับความสำคัญ</label>
                <select name="priority" id="priority" class="form-select">
                    <option value="">ทั้งหมด</option>
                    <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>สูง</option>
                    <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>ปกติ</option>
                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>ต่ำ</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="search" class="form-label">ค้นหา</label>
                <div class="input-group">
                    <input type="text" name="search" id="search" class="form-control" 
                        placeholder="ค้นหาในหัวข้อ หรือเนื้อหา" value="{{ request('search') }}">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('notifications.received') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle"></i> ล้าง
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Notifications List -->
<div class="row">
    @forelse($notifications as $notification)
        @php
            $isRead = $notification->logs()->where('recipient_email', Auth::user()->email)->where('status', 'read')->exists();
        @endphp
        <div class="col-12 mb-3">
            <div class="card notification-card priority-{{ $notification->priority }} {{ $isRead ? '' : 'border-warning' }}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-10">
                            <div class="d-flex align-items-center mb-2">
                                @if(!$isRead)
                                    <span class="badge bg-warning me-2">ใหม่</span>
                                @endif
                                <span class="status-indicator bg-{{ $notification->priority_badge_class }} me-2"></span>
                                <h6 class="mb-0 {{ $isRead ? 'text-muted' : '' }}">{{ $notification->subject }}</h6>
                                <span class="badge bg-{{ $notification->priority_badge_class }} ms-2">
                                    {{ $notification->priority_text }}
                                </span>
                            </div>
                            
                            <div class="text-muted small mb-2">
                                <i class="bi bi-person"></i> จาก: {{ $notification->creator->display_name ?? 'ระบบ' }} |
                                <i class="bi bi-calendar"></i> {{ $notification->created_at->format('d/m/Y H:i') }} |
                                @if($notification->template)
                                    <i class="bi bi-file-text"></i> {{ $notification->template->name }}
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
                            
                            @if($notification->body_text)
                                <p class="text-muted mb-2 text-truncate-2">
                                    {{ Str::limit(strip_tags($notification->body_text), 150) }}
                                </p>
                            @endif
                        </div>
                        <div class="col-md-2 text-end">
                            <div class="btn-group-vertical" role="group">
                                <a href="{{ route('notifications.received.show', $notification->uuid) }}" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye"></i> ดู
                                </a>
                                @if(!$isRead)
                                    <button type="button" class="btn btn-outline-success btn-sm" 
                                            onclick="markAsRead('{{ $notification->uuid }}')">
                                        <i class="bi bi-check"></i> อ่านแล้ว
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-envelope-slash display-1 text-muted"></i>
                <h4 class="text-muted">ไม่มีการแจ้งเตือน</h4>
                <p class="text-muted">คุณยังไม่ได้รับการแจ้งเตือนใดๆ หรือลองปรับตัวกรอง</p>
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

@endsection

@push('scripts')
<script>
// Mark single notification as read
async function markAsRead(uuid) {
    try {
        const response = await fetch(`/notifications/received/${uuid}/mark-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.App.csrfToken
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Error marking as read:', error);
    }
}

// Mark all notifications as read
async function markAllAsRead() {
    if (!confirm('ทำเครื่องหมายการแจ้งเตือนทั้งหมดว่าอ่านแล้ว?')) {
        return;
    }
    
    try {
        const response = await fetch('/notifications/received/mark-all-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.App.csrfToken
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification('ทำเครื่องหมายทั้งหมดเรียบร้อย', 'success');
            location.reload();
        }
    } catch (error) {
        console.error('Error marking all as read:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาด', 'error');
    }
}

// Refresh notifications
function refreshNotifications() {
    location.reload();
}

// Auto-submit filter form on select change
document.querySelectorAll('#status, #priority').forEach(select => {
    select.addEventListener('change', function() {
        this.closest('form').submit();
    });
});
</script>
@endpush