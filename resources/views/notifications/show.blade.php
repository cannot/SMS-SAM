@extends('layouts.app')

@section('title', 'รายละเอียดการแจ้งเตือน')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-envelope-open"></i> รายละเอียดการแจ้งเตือน</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('notifications.received') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> กลับ
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Notification Content -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> เนื้อหาการแจ้งเตือน</h6>
                <div>
                    <span class="badge bg-{{ $notification->priority_badge_class }}">{{ $notification->priority_text }}</span>
                </div>
            </div>
            <div class="card-body">
                <h4 class="mb-3">{{ $notification->subject }}</h4>
                
                @if($notification->body_html)
                <div class="mb-3">
                    <div class="border rounded p-3 bg-light">
                        {!! $notification->body_html !!}
                    </div>
                </div>
                @elseif($notification->body_text)
                <div class="mb-3">
                    <pre class="border rounded p-3 bg-light">{{ $notification->body_text }}</pre>
                </div>
                @endif

                @if($notification->variables && !empty($notification->variables))
                <div class="mb-3">
                    <h6>ข้อมูลเพิ่มเติม:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            @foreach($notification->variables as $key => $value)
                            <tr>
                                <td><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong></td>
                                <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                            </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Notification Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> ข้อมูลการแจ้งเตือน</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>จาก:</strong></div>
                    <div class="col-sm-8">{{ $notification->creator->display_name ?? 'ระบบ' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>วันที่ส่ง:</strong></div>
                    <div class="col-sm-8">{{ $notification->sent_at?->format('d/m/Y H:i:s') ?? $notification->created_at->format('d/m/Y H:i:s') }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>ช่องทาง:</strong></div>
                    <div class="col-sm-8">
                        @foreach($notification->channels as $channel)
                            <span class="badge bg-secondary me-1">
                                @if($channel == 'email')
                                    <i class="bi bi-envelope"></i> Email
                                @elseif($channel == 'teams')
                                    <i class="bi bi-microsoft-teams"></i> Teams
                                @endif
                            </span>
                        @endforeach
                    </div>
                </div>
                @if($notification->template)
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>เทมเพลต:</strong></div>
                    <div class="col-sm-8">{{ $notification->template->name }}</div>
                </div>
                @endif
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>ID:</strong></div>
                    <div class="col-sm-8"><code>{{ $notification->uuid }}</code></div>
                </div>
            </div>
        </div>

        <!-- Delivery Status -->
        @php
            $userLog = $notification->logs()->where('recipient_email', Auth::user()->email)->first();
        @endphp
        @if($userLog)
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-truck"></i> สถานะการส่ง</h6>
            </div>
            <div class="card-body">
                @foreach($notification->channels as $channel)
                    @php
                        $channelLog = $notification->logs()
                            ->where('recipient_email', Auth::user()->email)
                            ->where('channel', $channel)
                            ->first();
                    @endphp
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>
                            @if($channel == 'email')
                                <i class="bi bi-envelope"></i> Email
                            @elseif($channel == 'teams')
                                <i class="bi bi-microsoft-teams"></i> Teams
                            @endif
                        </span>
                        @if($channelLog)
                            @if($channelLog->status == 'delivered' || $channelLog->status == 'read')
                                <span class="badge bg-success">ส่งสำเร็จ</span>
                            @elseif($channelLog->status == 'failed')
                                <span class="badge bg-danger">ล้มเหลว</span>
                            @else
                                <span class="badge bg-warning">{{ $channelLog->status }}</span>
                            @endif
                        @else
                            <span class="badge bg-secondary">ไม่ทราบสถานะ</span>
                        @endif
                    </div>
                @endforeach
                
                @if($userLog->sent_at)
                <small class="text-muted">
                    ส่งเมื่อ: {{ $userLog->sent_at->format('d/m/Y H:i:s') }}
                </small>
                @endif
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightning"></i> การดำเนินการ</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if($userLog && $userLog->status !== 'read')
                        <button type="button" class="btn btn-success" onclick="markAsRead()">
                            <i class="bi bi-check"></i> ทำเครื่องหมายว่าอ่านแล้ว
                        </button>
                    @endif
                    
                    <button type="button" class="btn btn-outline-info" onclick="shareNotification()">
                        <i class="bi bi-share"></i> แชร์
                    </button>
                    
                    <button type="button" class="btn btn-outline-secondary" onclick="printNotification()">
                        <i class="bi bi-printer"></i> พิมพ์
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
async function markAsRead() {
    try {
        const response = await fetch(`/notifications/received/{{ $notification->uuid }}/mark-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.App.csrfToken
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification('ทำเครื่องหมายว่าอ่านแล้ว', 'success');
            location.reload();
        }
    } catch (error) {
        console.error('Error marking as read:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาด', 'error');
    }
}

function shareNotification() {
    if (navigator.share) {
        navigator.share({
            title: '{{ $notification->subject }}',
            text: '{{ Str::limit(strip_tags($notification->body_text), 100) }}',
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            window.AppFunctions.showNotification('คัดลอกลิงก์แล้ว', 'success');
        });
    }
}

function printNotification() {
    window.print();
}
</script>
@endpush