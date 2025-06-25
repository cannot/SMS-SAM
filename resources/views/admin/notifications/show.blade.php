@extends('layouts.app')

@section('title', 'รายละเอียดการแจ้งเตือน')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-eye"></i> รายละเอียดการแจ้งเตือน
        <span class="badge bg-{{ $notification->status == 'sent' ? 'success' : ($notification->status == 'failed' ? 'danger' : 'warning') }}">
            {{ ucfirst($notification->status) }}
        </span>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> กลับ
            </a>
            @if($notification->status == 'scheduled')
                <form method="POST" action="{{ route('admin.notifications.cancel', $notification->uuid) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning" onclick="return confirm('คุณต้องการยกเลิกการแจ้งเตือนนี้หรือไม่?')">
                        <i class="bi bi-x-circle"></i> ยกเลิก
                    </button>
                </form>
            @endif
            @if($notification->status == 'failed')
                <form method="POST" action="{{ route('admin.notifications.retry', $notification->uuid) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-info">
                        <i class="bi bi-arrow-clockwise"></i> ลองใหม่
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Notification Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> ข้อมูลการแจ้งเตือน</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th style="width: 40%;">UUID:</th>
                                <td><code>{{ $notification->uuid }}</code></td>
                            </tr>
                            <tr>
                                <th>หัวข้อ:</th>
                                <td>{{ $notification->subject }}</td>
                            </tr>
                            <tr>
                                <th>เทมเพลต:</th>
                                <td>
                                    @if($notification->template)
                                        <a href="{{ route('templates.show', $notification->template) }}">
                                            {{ $notification->template->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">ไม่ใช้เทมเพลต</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>ช่องทางการส่ง:</th>
                                <td>
                                    @foreach($notification->channels as $channel)
                                        <span class="badge bg-secondary me-1">
                                            @if($channel == 'email')
                                                <i class="bi bi-envelope"></i> อีเมล
                                            @elseif($channel == 'teams')
                                                <i class="bi bi-microsoft-teams"></i> Teams
                                            @endif
                                        </span>
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <th>ระดับความสำคัญ:</th>
                                <td>
                                    <span class="badge bg-{{ $notification->priority == 'urgent' ? 'danger' : ($notification->priority == 'high' ? 'warning' : 'info') }}">
                                        {{ ucfirst($notification->priority) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th style="width: 40%;">สร้างโดย:</th>
                                <td>
                                    @if($notification->creator)
                                        {{ $notification->creator->display_name }}
                                    @else
                                        <span class="text-muted">ไม่ทราบ</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>วันที่สร้าง:</th>
                                <td>{{ $notification->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @if($notification->scheduled_at)
                            <tr>
                                <th>กำหนดการส่ง:</th>
                                <td>{{ $notification->scheduled_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endif
                            @if($notification->sent_at)
                            <tr>
                                <th>วันที่ส่ง:</th>
                                <td>{{ $notification->sent_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th>จำนวนผู้รับ:</th>
                                <td>{{ $notification->total_recipients }} คน</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Preview -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-eye"></i> เนื้อหาการแจ้งเตือน</h6>
            </div>
            <div class="card-body">
                @if($notification->body_html)
                    <h6>เนื้อหา HTML:</h6>
                    <div class="border rounded p-3 mb-3" style="max-height: 300px; overflow-y: auto;">
                        {!! $notification->body_html !!}
                    </div>
                @endif

                @if($notification->body_text)
                    <h6>เนื้อหา Text:</h6>
                    <pre class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">{{ $notification->body_text }}</pre>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Statistics -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-graph-up"></i> สถิติการส่ง</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="bg-success text-white p-2 rounded mb-2">
                            <h4 class="mb-0">{{ $metrics['delivery_rate'] }}%</h4>
                            <small>อัตราส่งสำเร็จ</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-danger text-white p-2 rounded mb-2">
                            <h4 class="mb-0">{{ $metrics['failure_rate'] }}%</h4>
                            <small>อัตราล้มเหลว</small>
                        </div>
                    </div>
                </div>
                
                @if($metrics['avg_delivery_time'])
                <div class="mt-3">
                    <strong>เวลาส่งเฉลี่ย:</strong> {{ number_format($metrics['avg_delivery_time']) }} วินาที
                </div>
                @endif

                <hr>
                
                <h6>สถิติตามช่องทาง:</h6>
                @forelse($channelStats as $channel => $count)
                    <div class="d-flex justify-content-between">
                        <span>
                            @if($channel == 'email')
                                <i class="bi bi-envelope"></i> อีเมล
                            @elseif($channel == 'teams')
                                <i class="bi bi-microsoft-teams"></i> Teams
                            @endif
                        </span>
                        <span class="badge bg-secondary">{{ $count }}</span>
                    </div>
                @empty
                    <p class="text-muted">ยังไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Logs -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-list"></i> Log ล่าสุด</h6>
                <a href="{{ route('admin.notifications.logs', $notification->uuid) }}" class="btn btn-sm btn-outline-primary">
                    ดูทั้งหมด
                </a>
            </div>
            <div class="card-body">
                @forelse($recentLogs->take(10) as $log)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <div class="small text-muted">{{ $log->recipient_email }}</div>
                            <div class="small">
                                <span class="badge bg-{{ $log->status == 'delivered' ? 'success' : ($log->status == 'failed' ? 'danger' : 'warning') }}">
                                    {{ $log->status }}
                                </span>
                                via {{ $log->channel }}
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">{{ $log->created_at->format('H:i') }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">ยังไม่มี log</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection