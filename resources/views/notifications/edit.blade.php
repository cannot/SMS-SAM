@extends('layouts.app')

@section('title', 'แก้ไขการแจ้งเตือน')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-pencil"></i> แก้ไขการแจ้งเตือน</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('notifications.show', $notification->uuid) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> กลับ
        </a>
    </div>
</div>

@if($notification->status !== 'draft')
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>คำเตือน:</strong> สามารถแก้ไขได้เฉพาะการแจ้งเตือนที่มีสถานะ "ร่าง" เท่านั้น
</div>
@endif

<form method="POST" action="{{ route('notifications.update', $notification->uuid) }}" id="notificationForm">
    @csrf
    @method('PUT')
    
    <!-- Same form structure as create.blade.php but with pre-filled values -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Content Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-file-text"></i> เนื้อหาการแจ้งเตือน</h6>
                </div>
                <div class="card-body">
                    <!-- Template Selection -->
                    <div class="mb-3">
                        <label for="template_id" class="form-label">เทมเพลต (ไม่บังคับ)</label>
                        <select name="template_id" id="template_id" class="form-select" {{ $notification->status !== 'draft' ? 'disabled' : '' }}>
                            <option value="">เลือกเทมเพลต หรือสร้างเองแบบกำหนดเอง</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ old('template_id', $notification->template_id) == $template->id ? 'selected' : '' }}>
                                    {{ $template->name }} ({{ implode(', ', $template->supported_channels) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Custom Content -->
                    <div id="customContent">
                        <div class="mb-3">
                            <label for="subject" class="form-label">หัวข้อ *</label>
                            <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror" 
                                value="{{ old('subject', $notification->subject) }}" maxlength="255" {{ $notification->status !== 'draft' ? 'readonly' : '' }}>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="body_html" class="form-label">เนื้อหา HTML</label>
                                    <textarea name="body_html" id="body_html" class="form-control @error('body_html') is-invalid @enderror" 
                                        rows="8" {{ $notification->status !== 'draft' ? 'readonly' : '' }}>{{ old('body_html', $notification->body_html) }}</textarea>
                                    @error('body_html')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="body_text" class="form-label">เนื้อหา Text</label>
                                    <textarea name="body_text" id="body_text" class="form-control @error('body_text') is-invalid @enderror" 
                                        rows="8" {{ $notification->status !== 'draft' ? 'readonly' : '' }}>{{ old('body_text', $notification->body_text) }}</textarea>
                                    @error('body_text')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recipients Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-people"></i> ผู้รับการแจ้งเตือน</h6>
                </div>
                <div class="card-body">
                    <!-- Pre-populate recipient type based on existing data -->
                    @php
                        $recipientType = 'manual';
                        if (!empty($notification->recipient_groups)) {
                            $recipientType = 'groups';
                        } elseif (!empty($notification->recipients) && count($notification->recipients) == $users->count()) {
                            $recipientType = 'all_users';
                        }
                    @endphp

                    <div class="mb-3">
                        <label class="form-label">ประเภทผู้รับ *</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="recipient_type" id="manual" value="manual" 
                                        {{ old('recipient_type', $recipientType) == 'manual' ? 'checked' : '' }}
                                        {{ $notification->status !== 'draft' ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="manual">
                                        <i class="bi bi-person-plus"></i> เลือกเอง
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="recipient_type" id="groups" value="groups" 
                                        {{ old('recipient_type', $recipientType) == 'groups' ? 'checked' : '' }}
                                        {{ $notification->status !== 'draft' ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="groups">
                                        <i class="bi bi-people"></i> ตามกลุ่ม
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="recipient_type" id="all_users" value="all_users" 
                                        {{ old('recipient_type', $recipientType) == 'all_users' ? 'checked' : '' }}
                                        {{ $notification->status !== 'draft' ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="all_users">
                                        <i class="bi bi-globe"></i> ผู้ใช้ทั้งหมด
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Manual Recipients -->
                    <div id="manualRecipients" class="recipient-section" style="{{ $recipientType == 'manual' ? 'display: block;' : 'display: none;' }}">
                        <label for="recipients" class="form-label">อีเมลผู้รับ *</label>
                        <textarea name="recipients[]" id="recipients" class="form-control @error('recipients') is-invalid @enderror" 
                            rows="4" {{ $notification->status !== 'draft' ? 'readonly' : '' }}>{{ old('recipients') ? implode("\n", old('recipients')) : (!empty($notification->recipients) ? implode("\n", $notification->recipients) : '') }}</textarea>
                        <div class="form-text">กรอกอีเมลผู้รับ แยกด้วย Enter หรือ comma</div>
                        @error('recipients')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Group Recipients -->
                    <div id="groupRecipients" class="recipient-section" style="{{ $recipientType == 'groups' ? 'display: block;' : 'display: none;' }}">
                        <label class="form-label">เลือกกลุ่ม *</label>
                        <div class="row">
                            @foreach($groups->chunk(3) as $groupChunk)
                                <div class="col-md-4">
                                    @foreach($groupChunk as $group)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="recipient_groups[]" 
                                                id="group_{{ $group->id }}" value="{{ $group->id }}"
                                                {{ in_array($group->id, old('recipient_groups', $notification->recipient_groups ?? [])) ? 'checked' : '' }}
                                                {{ $notification->status !== 'draft' ? 'disabled' : '' }}>
                                            <label class="form-check-label" for="group_{{ $group->id }}">
                                                {{ $group->name }} ({{ $group->member_count }} คน)
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        @error('recipient_groups')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- All Users -->
                    <div id="allUsersRecipients" class="recipient-section" style="{{ $recipientType == 'all_users' ? 'display: block;' : 'display: none;' }}">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>ส่งให้ผู้ใช้ทั้งหมด ({{ $users->count() }} คน)</strong>
                            <br>การแจ้งเตือนจะถูกส่งไปยังผู้ใช้ทุกคนในระบบ
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Settings Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-gear"></i> การตั้งค่า</h6><!-- 1. resources/views/notifications/index.blade.php -->
@extends('layouts.app')

@section('title', 'การจัดการการแจ้งเตือน')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-bell"></i> การจัดการการแจ้งเตือน</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('notifications.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> สร้างการแจ้งเตือนใหม่
            </a>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-funnel"></i> ตัวกรอง</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('notifications.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">สถานะ</label>
                <select name="status" id="status" class="form-select">
                    <option value="">ทั้งหมด</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="priority" class="form-label">ระดับความสำคัญ</label>
                <select name="priority" id="priority" class="form-select">
                    <option value="">ทั้งหมด</option>
                    @foreach($priorities as $priority)
                        <option value="{{ $priority }}" {{ request('priority') == $priority ? 'selected' : '' }}>
                            {{ ucfirst($priority) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
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
            <div class="col-md-3">
                <label for="search" class="form-label">ค้นหา</label>
                <div class="input-group">
                    <input type="text" name="search" id="search" class="form-control" placeholder="หัวข้อ หรือ UUID" value="{{ request('search') }}">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-search"></i>
                    </button>
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
            <div class="col-md-6 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> กรอง
                    </button>
                    <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> ล้างตัวกรอง
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Notifications List -->
<div class="row">
    @forelse($notifications as $notification)
        <div class="col-12 mb-3">
            <div class="card notification-card priority-{{ $notification->priority }}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-2">
                                <span class="status-indicator bg-{{ $notification->status_badge_class }} me-2"></span>
                                <h6 class="mb-0">{{ $notification->subject }}</h6>
                                <span class="badge bg-{{ $notification->priority_badge_class }} ms-2">
                                    {{ $notification->priority_text }}
                                </span>
                            </div>
                            <div class="text-muted small mb-2">
                                <i class="bi bi-person"></i> {{ $notification->creator->display_name ?? 'System' }} |
                                <i class="bi bi-calendar"></i> {{ $notification->created_at->format('d/m/Y H:i') }} |
                                <i class="bi bi-envelope"></i> {{ $notification->total_recipients }} ผู้รับ
                                @if($notification->template)
                                    | <i class="bi bi-file-text"></i> {{ $notification->template->name }}
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
                            @if($notification->delivered_count > 0 || $notification->failed_count > 0)
                                <div class="progress" style="height: 5px;">
                                    @php
                                        $successRate = $notification->total_recipients > 0 ? ($notification->delivered_count / $notification->total_recipients) * 100 : 0;
                                        $failureRate = $notification->total_recipients > 0 ? ($notification->failed_count / $notification->total_recipients) * 100 : 0;
                                    @endphp
                                    <div class="progress-bar bg-success" style="width: {{ $successRate }}%"></div>
                                    <div class="progress-bar bg-danger" style="width: {{ $failureRate }}%"></div>
                                </div>
                                <small class="text-muted">
                                    ส่งสำเร็จ {{ $notification->delivered_count }}/{{ $notification->total_recipients }}
                                    @if($notification->failed_count > 0)
                                        | ล้มเหลว {{ $notification->failed_count }}
                                    @endif
                                </small>
                            @endif
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="btn-group" role="group">
                                <a href="{{ route('notifications.show', $notification->uuid) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($notification->status == 'draft')
                                    <a href="{{ route('notifications.edit', $notification->uuid) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                                @if($notification->status == 'scheduled')
                                    <form method="POST" action="{{ route('notifications.cancel', $notification->uuid) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning btn-sm" onclick="return confirm('ยกเลิกการแจ้งเตือนนี้?')">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('notifications.duplicate', $notification->uuid) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-info btn-sm" title="ทำสำเนา">
                                        <i class="bi bi-files"></i>
                                    </button>
                                </form>
                                @if(in_array($notification->status, ['draft', 'failed', 'cancelled']))
                                    <form method="POST" action="{{ route('notifications.destroy', $notification->uuid) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('ลบการแจ้งเตือนนี้?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
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
                <i class="bi bi-bell-slash display-1 text-muted"></i>
                <h4 class="text-muted">ไม่พบการแจ้งเตือน</h4>
                <p class="text-muted">ยังไม่มีการแจ้งเตือนในระบบ หรือลองปรับตัวกรอง</p>
                <a href="{{ route('notifications.create') }}" class="btn btn-primary">
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

@endsection

@push('scripts')
<script>
// Auto-submit filter form on select change
document.querySelectorAll('#status, #priority, #template').forEach(select => {
    select.addEventListener('change', function() {
        this.closest('form').submit();
    });
});
</script>
@endpush