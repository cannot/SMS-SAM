@extends('layouts.app')

@section('title', 'รายละเอียดการแจ้งเตือน')

@section('styles')
    <style>
        .status-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }

        .priority-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
        }

        .log-item {
            transition: all 0.2s ease;
        }

        .log-item:hover {
            background-color: #f8f9fa;
        }

        .delivery-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }

        .metric-card {
            transition: transform 0.2s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .content-preview {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .variable-tag {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            color: #1976d2;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
        }

        .auto-refresh-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            background-color: #17a2b8;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.875rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-4">
        <!-- Header -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">
                <i class="fas fa-bell me-2"></i>
                รายละเอียดการแจ้งเตือน
            </h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group me-2">
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> กลับ
                    </a>
                    <button type="button" class="btn btn-outline-info" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> รีเฟรช
                    </button>
                </div>
            </div>
        </div>

        <!-- Auto Refresh Indicator -->
        @if ($notification->status === 'processing')
            <div class="auto-refresh-indicator">
                <i class="fas fa-sync-alt fa-spin"></i>
                จะรีเฟรชอัตโนมัติใน 30 วินาที
            </div>
        @endif

        <!-- Success/Error Messages -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Main Content Row -->
        <div class="row">
            <!-- Left Column - Notification Details -->
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            ข้อมูลการแจ้งเตือน
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">UUID:</dt>
                                    <dd class="col-sm-8">
                                        <code>{{ $notification->uuid }}</code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2"
                                            onclick="copyToClipboard('{{ $notification->uuid }}')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </dd>

                                    <dt class="col-sm-4">หัวข้อ:</dt>
                                    <dd class="col-sm-8">
                                        <strong>{{ $renderedContent['subject'] ?? $notification->subject }}</strong>
                                    </dd>

                                    <dt class="col-sm-4">เทมเพลต:</dt>
                                    <dd class="col-sm-8">
                                        @if ($notification->template)
                                            <a href="#" class="text-decoration-none">
                                                {{ $notification->template->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">ไม่ได้ใช้เทมเพลต</span>
                                        @endif
                                    </dd>

                                    <dt class="col-sm-4">ช่องทางการส่ง:</dt>
                                    <dd class="col-sm-8">
                                        @foreach ($notification->channels as $channel)
                                            <span class="badge bg-info me-1">
                                                <i class="fas fa-{{ $channel === 'email' ? 'envelope' : 'users' }}"></i>
                                                {{ ucfirst($channel) }}
                                            </span>
                                        @endforeach
                                    </dd>

                                    <dt class="col-sm-4">ระดับความสำคัญ:</dt>
                                    <dd class="col-sm-8">
                                        <span class="badge bg-{{ $notification->getPriorityColor() }} priority-badge">
                                            {{ $notification->getPriorityText() }}
                                        </span>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">สร้างโดย:</dt>
                                    <dd class="col-sm-8">
                                        @if ($notification->creator)
                                            {{ $notification->creator->display_name ?? $notification->creator->username }}
                                        @else
                                            <span class="text-muted">ระบบ</span>
                                        @endif
                                    </dd>

                                    <dt class="col-sm-4">วันที่สร้าง:</dt>
                                    <dd class="col-sm-8">
                                        {{ $notification->created_at->format('d/m/Y H:i:s') }}
                                        <br>
                                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                    </dd>

                                    <dt class="col-sm-4">จำนวนผู้รับ:</dt>
                                    <dd class="col-sm-8">
                                        <strong>{{ $metrics['total_recipients'] }} คน</strong>
                                    </dd>

                                    <dt class="col-sm-4">สถานะ:</dt>
                                    <dd class="col-sm-8">
                                        <span class="badge bg-{{ $notification->getStatusColor() }} status-badge">
                                            {{ $notification->getStatusText() }}
                                        </span>
                                        @if ($notification->status === 'processing')
                                            <div class="spinner-border spinner-border-sm ms-2" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        @endif
                                    </dd>

                                    @if ($notification->scheduled_at)
                                        <dt class="col-sm-4">กำหนดส่ง:</dt>
                                        <dd class="col-sm-8">
                                            {{ $notification->scheduled_at->format('d/m/Y H:i:s') }}
                                            <br>
                                            <small
                                                class="text-muted">{{ $notification->scheduled_at->diffForHumans() }}</small>
                                        </dd>
                                    @endif

                                    @if ($notification->sent_at)
                                        <dt class="col-sm-4">ส่งเมื่อ:</dt>
                                        <dd class="col-sm-8">
                                            {{ $notification->sent_at->format('d/m/Y H:i:s') }}
                                            <br>
                                            <small class="text-muted">{{ $notification->sent_at->diffForHumans() }}</small>
                                        </dd>
                                    @endif
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rendered Content -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-eye me-2"></i>
                            เนื้อหาการแจ้งเตือน
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <h6>หัวข้อ:</h6>
                                <div class="content-preview">
                                    <strong>{{ $renderedContent['subject'] ?? $notification->subject }}</strong>
                                </div>
                            </div>

                            @if ($renderedContent['body_html'] ?? $notification->body_html)
                                <div class="col-md-12 mb-3">
                                    <h6>เนื้อหา HTML:</h6>
                                    <div class="content-preview">
                                        {!! $renderedContent['body_html'] ?? $notification->body_html !!}
                                    </div>
                                </div>
                            @endif

                            @if ($renderedContent['body_text'] ?? $notification->body_text)
                                <div class="col-md-12 mb-3">
                                    <h6>เนื้อหา Text:</h6>
                                    <div class="content-preview">
                                        <pre class="mb-0">{{ $renderedContent['body_text'] ?? $notification->body_text }}</pre>
                                    </div>
                                </div>
                            @endif

                            @if (!empty($renderedContent['variables']))
                                <div class="col-md-12">
                                    <h6>ตัวแปรที่ใช้:</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($renderedContent['variables'] as $key => $value)
                                            <span class="variable-tag" title="{{ $key }}: {{ $value }}">
                                                {{ $key }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tools me-2"></i>
                            การจัดการ
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <!-- Resend Failed Button -->
                            @if ($notification->status === 'failed' || $notification->logs->where('status', 'failed')->count() > 0)
                                <form method="POST"
                                    action="{{ route('admin.notifications.resend', $notification->uuid) }}"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-warning"
                                        onclick="return confirm('คุณต้องการส่งข้อความที่ล้มเหลวใหม่หรือไม่?')">
                                        <i class="fas fa-redo me-1"></i>
                                        ส่งใหม่ ({{ $notification->logs->where('status', 'failed')->count() }} รายการ)
                                    </button>
                                </form>
                            @endif

                            <!-- Cancel Button -->
                            @if ($notification->canBeCancelled())
                                <form method="POST"
                                    action="{{ route('admin.notifications.cancel', $notification->id) }}"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-danger"
                                        onclick="return confirm('คุณต้องการยกเลิกการแจ้งเตือนนี้หรือไม่?')">
                                        <i class="fas fa-times me-1"></i>
                                        ยกเลิก
                                    </button>
                                </form>
                            @endif

                            <!-- Preview Button -->
                            <button type="button" class="btn btn-info" data-bs-toggle="modal"
                                data-bs-target="#previewModal">
                                <i class="fas fa-eye me-1"></i>
                                ดูตัวอย่าง
                            </button>

                            <!-- Export Button -->

                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Statistics -->
            <div class="col-md-4">
                <!-- Delivery Statistics -->
                <div class="card shadow mb-4 delivery-stats">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            สถิติการส่ง
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="metric-card bg-success bg-opacity-75 p-3 rounded">
                                    <h3 class="text-white">{{ $metrics['delivery_rate'] }}%</h3>
                                    <small class="text-white-50">อัตราส่งสำเร็จ</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-card bg-danger bg-opacity-75 p-3 rounded">
                                    <h3 class="text-white">{{ $metrics['failure_rate'] }}%</h3>
                                    <small class="text-white-50">อัตราส่งล้มเหลว</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Channel Statistics -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            สถิติตามช่องทาง
                        </h6>
                    </div>
                    <div class="card-body">
                        @forelse($channelStats as $channel => $count)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <i class="fas fa-{{ $channel === 'email' ? 'envelope' : 'users' }} me-2"></i>
                                    {{ ucfirst($channel) }}
                                </div>
                                <span class="badge bg-primary">{{ $count }}</span>
                            </div>
                        @empty
                            <div class="text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>ไม่มีข้อมูลช่องทาง</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Delivery Status -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-tasks me-2"></i>
                            สถานะการส่ง
                        </h6>
                    </div>
                    <div class="card-body">
                        @forelse($deliveryStats as $status => $count)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span
                                        class="badge bg-{{ $status === 'sent' || $status === 'delivered' ? 'success' : ($status === 'failed' ? 'danger' : 'warning') }} me-2">
                                        {{ ucfirst($status) }}
                                    </span>
                                </div>
                                <span class="fw-bold">{{ $count }}</span>
                            </div>
                        @empty
                            <div class="text-center text-muted">
                                <i class="fas fa-hourglass-half fa-2x mb-2"></i>
                                <p>ไม่มีข้อมูลสถานะ</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Performance Metrics -->
                @if ($metrics['avg_delivery_time'])
                    <div class="card shadow mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                ประสิทธิภาพ
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <h4 class="text-success">{{ number_format($metrics['avg_delivery_time'], 1) }}s</h4>
                                <small class="text-muted">เวลาเฉลี่ยในการส่ง</small>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Logs -->
        <div class="card shadow mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    Log การส่งล่าสุด
                </h5>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-light" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> รีเฟรช
                    </button>
                    <a href="{{ route('admin.notifications.logs', $notification->uuid) }}"
                        class="btn btn-sm btn-outline-light">
                        <i class="fas fa-external-link-alt"></i> ดูทั้งหมด
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ผู้รับ</th>
                                <th>ช่องทาง</th>
                                <th>สถานะ</th>
                                <th>เวลา</th>
                                <th>การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $log)
                                <tr class="log-item">
                                    <td>
                                        <div>
                                            <strong>{{ $log->recipient_name ?? 'Unknown' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $log->recipient_email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <i
                                            class="fas {{ $log->channel === 'email' ? 'fa-envelope' : 'fa-users' }} me-2"></i>
                                        {{ ucfirst($log->channel) }}
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-{{ $log->status === 'sent' || $log->status === 'delivered' ? 'success' : ($log->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                        @if ($log->error_message)
                                            <br>
                                            <small class="text-danger" title="{{ $log->error_message }}">
                                                {{ Str::limit($log->error_message, 30) }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            {{ $log->created_at->format('H:i:s') }}
                                            <br>
                                            <small class="text-muted">{{ $log->created_at->format('d/m/Y') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($log->status === 'failed')
                                            <form method="POST"
                                                action="{{ route('admin.notifications.resend-log', [$notification->uuid, $log->id]) }}"
                                                class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning"
                                                    onclick="return confirm('ส่งข้อความนี้ใหม่?')" title="ส่งใหม่">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            </form>
                                        @elseif($log->status === 'sent' || $log->status === 'delivered')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i>
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock"></i>
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <br>
                                        ไม่มีข้อมูล Log
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">
                        <i class="fas fa-eye me-2"></i>
                        ตัวอย่างการแจ้งเตือน
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>หัวข้อ:</h6>
                        <div class="border p-3 bg-light rounded">
                            <strong>{{ $renderedContent['subject'] ?? $notification->subject }}</strong>
                        </div>
                    </div>

                    @if ($renderedContent['body_html'] ?? $notification->body_html)
                        <div class="mb-3">
                            <h6>เนื้อหา HTML:</h6>
                            <div class="border p-3 bg-light rounded">
                                {!! $renderedContent['body_html'] ?? $notification->body_html !!}
                            </div>
                        </div>
                    @endif

                    @if ($renderedContent['body_text'] ?? $notification->body_text)
                        <div class="mb-3">
                            <h6>เนื้อหา Text:</h6>
                            <div class="border p-3 bg-light rounded">
                                <pre class="mb-0">{{ $renderedContent['body_text'] ?? $notification->body_text }}</pre>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Auto refresh for processing notifications
        @if ($notification->status === 'processing')
            setTimeout(function() {
                location.reload();
            }, 30000);
        @endif

        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success toast
                const toast = document.createElement('div');
                toast.className = 'toast-notification';
                toast.innerHTML = '<i class="fas fa-check me-2"></i>คัดลอกแล้ว!';
                toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #28a745;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 5px;
            z-index: 1060;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        `;
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.remove();
                }, 3000);
            });
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Show full error message
        function showErrorDetails(message) {
            const modal = new bootstrap.Modal(document.getElementById('errorModal'));
            document.getElementById('errorMessage').textContent = message;
            modal.show();
        }
    </script>
@endsection
