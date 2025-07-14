@extends('layouts.app')

@section('title', 'การแจ้งเตือนแบบ SQL')

@push('styles')
<style>
.alert-card {
    transition: all 0.3s ease;
    border-left: 4px solid #e5e7eb;
}

.alert-card.active {
    border-left-color: #10b981;
}

.alert-card.inactive {
    border-left-color: #6b7280;
}

.alert-card.draft {
    border-left-color: #f59e0b;
}

.alert-card.error {
    border-left-color: #ef4444;
}

.alert-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.status-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
}

.status-active {
    background-color: #d1fae5;
    color: #065f46;
}

.status-inactive {
    background-color: #f3f4f6;
    color: #374151;
}

.status-draft {
    background-color: #fef3c7;
    color: #92400e;
}

.status-error {
    background-color: #fee2e2;
    color: #991b1b;
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
}

.filter-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-database text-primary me-2"></i>
                การแจ้งเตือนแบบ SQL
            </h1>
            <p class="text-muted">จัดการและติดตามการแจ้งเตือนอัตโนมัติจากฐานข้อมูล</p>
        </div>
        <div>
            <a href="{{ route('admin.sql-alerts.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                สร้างการแจ้งเตือนใหม่
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-bell fa-2x opacity-75"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="fs-4 fw-bold">{{ $alerts->total() }}</div>
                        <div class="opacity-75">การแจ้งเตือนทั้งหมด</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-play-circle fa-2x opacity-75"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="fs-4 fw-bold">{{ $alerts->where('status', 'active')->count() }}</div>
                        <div class="opacity-75">กำลังทำงาน</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock fa-2x opacity-75"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="fs-4 fw-bold">{{ $alerts->where('schedule_type', '!=', 'manual')->count() }}</div>
                        <div class="opacity-75">รันตามกำหนดเวลา</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="fs-4 fw-bold">{{ $alerts->where('status', 'error')->count() }}</div>
                        <div class="opacity-75">มีข้อผิดพลาด</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.sql-alerts.index') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">สถานะ</label>
                    <select name="status" class="form-select">
                        <option value="">ทั้งหมด</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>ร่าง</option>
                        <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>ข้อผิดพลาด</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">ประเภทการรัน</label>
                    <select name="schedule_type" class="form-select">
                        <option value="">ทั้งหมด</option>
                        <option value="manual" {{ request('schedule_type') == 'manual' ? 'selected' : '' }}>รันเอง</option>
                        <option value="once" {{ request('schedule_type') == 'once' ? 'selected' : '' }}>รันครั้งเดียว</option>
                        <option value="recurring" {{ request('schedule_type') == 'recurring' ? 'selected' : '' }}>รันซ้ำ</option>
                        <option value="cron" {{ request('schedule_type') == 'cron' ? 'selected' : '' }}>กำหนดเอง</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ค้นหา</label>
                    <input type="text" name="search" class="form-control" placeholder="ชื่อหรือคำอธิบาย" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search me-1"></i>
                            ค้นหา
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Alerts List -->
    <div class="row">
        @forelse($alerts as $alert)
        <div class="col-lg-6 mb-4">
            <div class="card alert-card {{ $alert->status }} h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="card-title mb-1">
                                <a href="{{ route('admin.sql-alerts.show', $alert) }}" class="text-decoration-none">
                                    {{ $alert->name }}
                                </a>
                            </h5>
                            <span class="status-badge status-{{ $alert->status }}">
                                {{ $alert->status_display }}
                            </span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('admin.sql-alerts.show', $alert) }}">
                                    <i class="fas fa-eye me-2"></i>ดูรายละเอียด
                                </a></li>
                                @if($alert->canExecute())
                                <li><a class="dropdown-item" href="#" onclick="executeAlert({{ $alert->id }})">
                                    <i class="fas fa-play me-2"></i>รันทันที
                                </a></li>
                                @endif
                                <li><a class="dropdown-item" href="{{ route('admin.sql-alerts.edit', $alert) }}">
                                    <i class="fas fa-edit me-2"></i>แก้ไข
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteAlert({{ $alert->id }})">
                                    <i class="fas fa-trash me-2"></i>ลบ
                                </a></li>
                            </ul>
                        </div>
                    </div>

                    <p class="card-text text-muted small">{{ $alert->description ?: 'ไม่มีคำอธิบาย' }}</p>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="d-flex align-items-center text-muted small">
                                <i class="fas fa-database me-2"></i>
                                {{ $alert->database_type }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center text-muted small">
                                <i class="fas fa-clock me-2"></i>
                                {{ $alert->schedule_type_display }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center text-muted small">
                                <i class="fas fa-users me-2"></i>
                                {{ $alert->getRecipientCount() }} ผู้รับ
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center text-muted small">
                                <i class="fas fa-chart-line me-2"></i>
                                {{ $alert->success_rate }}% สำเร็จ
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="fs-6 fw-bold text-primary">{{ $alert->total_executions }}</div>
                            <div class="small text-muted">รันทั้งหมด</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-6 fw-bold text-success">{{ $alert->successful_executions }}</div>
                            <div class="small text-muted">สำเร็จ</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-6 fw-bold text-danger">{{ $alert->total_executions - $alert->successful_executions }}</div>
                            <div class="small text-muted">ล้มเหลว</div>
                        </div>
                    </div>

                    @if($alert->next_run)
                    <div class="mt-3 p-2 bg-light rounded">
                        <div class="d-flex align-items-center justify-content-between">
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i>
                                รันครั้งถัดไป:
                            </small>
                            <small class="fw-bold {{ $alert->is_overdue ? 'text-danger' : 'text-primary' }}">
                                {{ $alert->next_run_human }}
                            </small>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="card-footer bg-transparent border-top-0">
                    <div class="d-flex align-items-center justify-content-between text-muted small">
                        <div>
                            <i class="fas fa-user me-1"></i>
                            {{ $alert->creator->name }}
                        </div>
                        <div>
                            <i class="fas fa-calendar me-1"></i>
                            {{ $alert->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-database fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">ยังไม่มีการแจ้งเตือน SQL</h4>
                <p class="text-muted">เริ่มต้นสร้างการแจ้งเตือนอัตโนมัติจากฐานข้อมูลของคุณ</p>
                <a href="{{ route('admin.sql-alerts.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    สร้างการแจ้งเตือนแรก
                </a>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($alerts->hasPages())
    <div class="d-flex justify-content-center">
        {{ $alerts->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function executeAlert(alertId) {
    if (!confirm('ต้องการรันการแจ้งเตือนนี้ทันทีหรือไม่?')) {
        return;
    }

    fetch(`/admin/sql-alerts/${alertId}/execute`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('รันการแจ้งเตือนสำเร็จ\n' + 
                  `ข้อมูล: ${data.data.rows_returned} แถว\n` +
                  `ส่งแจ้งเตือน: ${data.data.notifications_sent} ฉบับ\n` +
                  `เวลาที่ใช้: ${data.data.execution_time}`);
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการส่งคำขอ');
    });
}

function deleteAlert(alertId) {
    if (!confirm('ต้องการลบการแจ้งเตือนนี้หรือไม่? การดำเนินการนี้ไม่สามารถยกเลิกได้')) {
        return;
    }

    fetch(`/admin/sql-alerts/${alertId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('ลบการแจ้งเตือนสำเร็จ');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการส่งคำขอ');
    });
}
</script>
@endpush

<?php
// ===== resources/views/admin/sql-alerts/steps/generic-step.blade.php =====
?>

<style>
.wizard-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.wizard-header {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    padding: 30px;
}

.wizard-title {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 10px;
}

.wizard-subtitle {
    opacity: 0.9;
    font-size: 1.1rem;
}

.step-indicator {
    display: flex;
    gap: 8px;
    margin-top: 25px;
}

.step {
    flex: 1;
    height: 4px;
    background: rgba(255,255,255,0.3);
    border-radius: 2px;
    transition: background 0.3s ease;
}

.step.active {
    background: #fbbf24;
}

.step.completed {
    background: #10b981;
}

.wizard-content {
    padding: 40px;
}

.section-title {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 25px;
    color: #4f46e5;
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-icon {
    width: 32px;
    height: 32px;
    background: #4f46e5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.wizard-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 40px;
    padding-top: 25px;
    border-top: 1px solid #e5e7eb;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    color: #92400e;
    background: #fef3c7;
}

.coming-soon {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 12px;
    margin: 20px 0;
}

.coming-soon-icon {
    width: 80px;
    height: 80px;
    background: #e5e7eb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 32px;
    color: #6b7280;
}
</style>

<!-- Wizard Container -->
<div class="wizard-container">
    <!-- Wizard Header -->
    <div class="wizard-header">
        <div class="wizard-title">🚀 {{ $title }}</div>
        <div class="wizard-subtitle">ขั้นตอนที่ {{ $step }} ของการสร้างการแจ้งเตือน SQL</div>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            @for($i = 1; $i <= $totalSteps; $i++)
                <div class="step {{ $i < $step ? 'completed' : ($i == $step ? 'active' : '') }}"></div>
            @endfor
        </div>
    </div>

    <!-- Wizard Content -->
    <div class="wizard-content">
        <!-- Step Content -->
        <div class="section-title">
            <div class="section-icon">{{ $step }}</div>
            {{ $title }}
        </div>
        
        <!-- Coming Soon Content -->
        <div class="coming-soon">
            <div class="coming-soon-icon">
                <i class="fas fa-cog"></i>
            </div>
            <h4>ขั้นตอนนี้กำลังพัฒนา</h4>
            <p class="text-muted">
                ขั้นตอน "{{ $title }}" กำลังอยู่ระหว่างการพัฒนา<br>
                กรุณาดำเนินการต่อไปยังขั้นตอนถัดไป
            </p>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>หมายเหตุ:</strong> ในเวอร์ชันปัจจุบันคุณสามารถข้ามขั้นตอนนี้ได้
            </div>
        </div>

        <!-- Navigation -->
        <div class="wizard-navigation">
            <button type="button" class="btn btn-secondary" onclick="previousStep()">
                <i class="fas fa-arrow-left"></i>
                ย้อนกลับ
            </button>
            
            <div class="status-indicator">
                <i class="fas fa-info-circle"></i>
                ขั้นตอนที่ {{ $step }} จาก {{ $totalSteps }}
            </div>
            
            <button type="button" class="btn btn-primary" onclick="nextStep()">
                @if($step < $totalSteps)
                    ถัดไป
                    <i class="fas fa-arrow-right"></i>
                @else
                    เสร็จสิ้น
                    <i class="fas fa-check"></i>
                @endif
            </button>
        </div>
    </div>
</div>

<script>
function previousStep() {
    const currentStep = {{ $step }};
    if (currentStep > 1) {
        window.SqlAlertWizard.goToStep(currentStep - 1);
    }
}

function nextStep() {
    const currentStep = {{ $step }};
    const totalSteps = {{ $totalSteps }};
    
    if (currentStep < totalSteps) {
        window.SqlAlertWizard.goToStep(currentStep + 1);
    } else {
        // Final step - redirect to summary or save
        alert('การสร้างการแจ้งเตือน SQL เสร็จสิ้น!');
        window.location.href = '{{ route("admin.sql-alerts.index") }}';
    }
}
</script>

<?php
// ===== resources/views/admin/sql-alerts/show.blade.php =====
?>

@extends('layouts.app')

@section('title', 'รายละเอียดการแจ้งเตือน SQL - ' . $sqlAlert->name)

@push('styles')
<style>
.alert-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    padding: 2rem;
}

.status-badge {
    font-size: 0.875rem;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    border: 2px solid rgba(255,255,255,0.3);
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border-left: 4px solid #e5e7eb;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.stat-card.success {
    border-left-color: #10b981;
}

.stat-card.danger {
    border-left-color: #ef4444;
}

.stat-card.warning {
    border-left-color: #f59e0b;
}

.stat-card.info {
    border-left-color: #3b82f6;
}

.execution-timeline {
    position: relative;
    padding-left: 2rem;
}

.execution-timeline::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #10b981, #e5e7eb);
}

.execution-item {
    position: relative;
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #e5e7eb;
}

.execution-item::before {
    content: '';
    position: absolute;
    left: -2.25rem;
    top: 1.5rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #e5e7eb;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #e5e7eb;
}

.execution-item.success {
    border-left-color: #10b981;
}

.execution-item.success::before {
    background: #10b981;
    box-shadow: 0 0 0 2px #10b981;
}

.execution-item.failed {
    border-left-color: #ef4444;
}

.execution-item.failed::before {
    background: #ef4444;
    box-shadow: 0 0 0 2px #ef4444;
}

.execution-item.running {
    border-left-color: #f59e0b;
    animation: pulse 2s infinite;
}

.execution-item.running::before {
    background: #f59e0b;
    box-shadow: 0 0 0 2px #f59e0b;
    animation: pulse 2s infinite;
}

.config-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.config-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.config-item:last-child {
    border-bottom: none;
}

.config-label {
    font-weight: 600;
    color: #374151;
}

.config-value {
    color: #6b7280;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

.sql-query-display {
    background: #1f2937;
    color: #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    line-height: 1.5;
    max-height: 300px;
    overflow-y: auto;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Alert Header -->
    <div class="alert-header mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1 class="h2 mb-2">{{ $sqlAlert->name }}</h1>
                <p class="mb-3 opacity-90">{{ $sqlAlert->description ?: 'ไม่มีคำอธิบาย' }}</p>
                <div class="d-flex align-items-center gap-3">
                    <span class="status-badge">
                        <i class="fas fa-circle me-2"></i>
                        {{ $sqlAlert->status_display }}
                    </span>
                    <span class="text-white-50">
                        <i class="fas fa-clock me-1"></i>
                        {{ $sqlAlert->schedule_type_display }}
                    </span>
                    <span class="text-white-50">
                        <i class="fas fa-database me-1"></i>
                        {{ $sqlAlert->database_type }}
                    </span>
                </div>
            </div>
            <div class="text-end">
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-2"></i>
                        จัดการ
                    </button>
                    <ul class="dropdown-menu">
                        @if($sqlAlert->canExecute())
                        <li><a class="dropdown-item" href="#" onclick="executeAlert()">
                            <i class="fas fa-play me-2 text-success"></i>รันทันที
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        @endif
                        <li><a class="dropdown-item" href="{{ route('admin.sql-alerts.edit', $sqlAlert) }}">
                            <i class="fas fa-edit me-2 text-primary"></i>แก้ไข
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="duplicateAlert()">
                            <i class="fas fa-copy me-2 text-info"></i>ทำสำเนา
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportConfig()">
                            <i class="fas fa-download me-2 text-warning"></i>ส่งออกการตั้งค่า
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteAlert()">
                            <i class="fas fa-trash me-2"></i>ลบ
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">รันทั้งหมด</div>
                        <div class="h3 mb-0">{{ $statistics['total_executions'] }}</div>
                    </div>
                    <i class="fas fa-play-circle fa-2x text-info opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">สำเร็จ</div>
                        <div class="h3 mb-0">{{ $statistics['successful_executions'] }}</div>
                        <div class="small text-success">{{ $statistics['success_rate'] }}%</div>
                    </div>
                    <i class="fas fa-check-circle fa-2x text-success opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">ล้มเหลว</div>
                        <div class="h3 mb-0">{{ $statistics['failed_executions'] }}</div>
                    </div>
                    <i class="fas fa-times-circle fa-2x text-danger opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">เวลาเฉลี่ย</div>
                        <div class="h3 mb-0">{{ $statistics['avg_execution_time'] }}</div>
                    </div>
                    <i class="fas fa-stopwatch fa-2x text-warning opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Configuration -->
        <div class="col-md-8">
            <!-- SQL Query -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-code me-2"></i>
                        SQL Query
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="sql-query-display">{{ $sqlAlert->sql_query }}</div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            ตัวแปรที่ใช้: 
                            @forelse($sqlAlert->getUsedVariables() as $variable)
                                <code>{{{{ $variable }}}}</code>{{ !$loop->last ? ', ' : '' }}
                            @empty
                                ไม่มี
                            @endforelse
                        </small>
                        <button class="btn btn-sm btn-outline-primary" onclick="testQuery()">
                            <i class="fas fa-play me-1"></i>
                            ทดสอบ Query
                        </button>
                    </div>
                </div>
            </div>

            <!-- Database Configuration -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-database me-2"></i>
                        การเชื่อมต่อฐานข้อมูล
                    </h5>
                </div>
                <div class="card-body">
                    <div class="config-section">
                        <div class="config-item">
                            <span class="config-label">ประเภท</span>
                            <span class="config-value">{{ $sqlAlert->database_config['type'] ?? 'Unknown' }}</span>
                        </div>
                        <div class="config-item">
                            <span class="config-label">เซิร์ฟเวอร์</span>
                            <span class="config-value">{{ $sqlAlert->database_config['host'] ?? 'localhost' }}</span>
                        </div>
                        <div class="config-item">
                            <span class="config-label">พอร์ต</span>
                            <span class="config-value">{{ $sqlAlert->database_config['port'] ?? 'Default' }}</span>
                        </div>
                        <div class="config-item">
                            <span class="config-label">ฐานข้อมูล</span>
                            <span class="config-value">{{ $sqlAlert->database_config['database'] ?? 'Unknown' }}</span>
                        </div>
                        <div class="config-item">
                            <span class="config-label">ผู้ใช้</span>
                            <span class="config-value">{{ $sqlAlert->database_config['username'] ?? 'Unknown' }}</span>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-success" onclick="testConnection()">
                        <i class="fas fa-plug me-1"></i>
                        ทดสอบการเชื่อมต่อ
                    </button>
                </div>
            </div>

            <!-- Email Configuration -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope me-2"></i>
                        การตั้งค่าอีเมล
                    </h5>
                </div>
                <div class="card-body">
                    <div class="config-section">
                        <div class="config-item">
                            <span class="config-label">หัวข้อ</span>
                            <span class="config-value">{{ $sqlAlert->email_config['subject'] ?? 'ไม่ระบุ' }}</span>
                        </div>
                        <div class="config-item">
                            <span class="config-label">ผู้รับ</span>
                            <span class="config-value">{{ $sqlAlert->getRecipientCount() }} คน</span>
                        </div>
                        <div class="config-item">
                            <span class="config-label">รูปแบบ</span>
                            <span class="config-value">HTML + Text</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-bold">เทมเพลตอีเมล:</label>
                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                            {!! nl2br(e($sqlAlert->email_config['body_template'] ?? 'ไม่มีเทมเพลต')) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Schedule & Executions -->
        <div class="col-md-4">
            <!-- Schedule Configuration -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        ตารางเวลา
                    </h5>
                </div>
                <div class="card-body">
                    <div class="config-section">
                        <div class="config-item">
                            <span class="config-label">ประเภท</span>
                            <span class="config-value">{{ $sqlAlert->schedule_type_display }}</span>
                        </div>
                        @if($sqlAlert->last_run)
                        <div class="config-item">
                            <span class="config-label">รันล่าสุด</span>
                            <span class="config-value">{{ $sqlAlert->last_run->diffForHumans() }}</span>
                        </div>
                        @endif
                        @if($sqlAlert->next_run)
                        <div class="config-item">
                            <span class="config-label">รันครั้งถัดไป</span>
                            <span class="config-value {{ $sqlAlert->is_overdue ? 'text-danger' : '' }}">
                                {{ $sqlAlert->next_run->diffForHumans() }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recent Executions -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        ประวัติการรัน
                    </h5>
                    <a href="{{ route('admin.sql-alerts.executions', $sqlAlert) }}" class="btn btn-sm btn-outline-primary">
                        ดูทั้งหมด
                    </a>
                </div>
                <div class="card-body">
                    @forelse($recentExecutions->take(5) as $execution)
                    <div class="execution-item {{ $execution->status }}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-{{ $execution->status === 'success' ? 'success' : ($execution->status === 'failed' ? 'danger' : 'warning') }}">
                                    {{ $execution->status_display }}
                                </span>
                                <small class="text-muted ms-2">
                                    {{ $execution->trigger_type_display }}
                                </small>
                            </div>
                            <small class="text-muted">
                                {{ $execution->created_at->diffForHumans() }}
                            </small>
                        </div>
                        
                        @if($execution->status === 'success')
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="small text-muted">ข้อมูล</div>
                                <div class="fw-bold">{{ $execution->rows_returned ?? 0 }}</div>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">อีเมล</div>
                                <div class="fw-bold">{{ $execution->notifications_sent ?? 0 }}</div>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">เวลา</div>
                                <div class="fw-bold">{{ $execution->execution_time_human }}</div>
                            </div>
                        </div>
                        @elseif($execution->status === 'failed')
                        <div class="text-danger small">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            {{ Str::limit($execution->error_message, 100) }}
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="text-center py-3">
                        <i class="fas fa-history fa-2x text-muted mb-2"></i>
                        <p class="text-muted small">ยังไม่มีประวัติการรัน</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function executeAlert() {
    if (!confirm('ต้องการรันการแจ้งเตือนนี้ทันทีหรือไม่?')) {
        return;
    }

    fetch(`{{ route('admin.sql-alerts.execute', $sqlAlert) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('รันการแจ้งเตือนสำเร็จ!');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการส่งคำขอ');
    });
}

function testQuery() {
    // Implementation for testing SQL query
    alert('ฟีเจอร์ทดสอบ Query กำลังพัฒนา');
}

function testConnection() {
    // Implementation for testing database connection
    alert('ฟีเจอร์ทดสอบการเชื่อมต่อกำลังพัฒนา');
}

function duplicateAlert() {
    if (confirm('ต้องการทำสำเนาการแจ้งเตือนนี้หรือไม่?')) {
        alert('ฟีเจอร์ทำสำเนากำลังพัฒนา');
    }
}

function exportConfig() {
    alert('ฟีเจอร์ส่งออกการตั้งค่ากำลังพัฒนา');
}

function deleteAlert() {
    if (!confirm('ต้องการลบการแจ้งเตือนนี้หรือไม่? การดำเนินการนี้ไม่สามารถยกเลิกได้')) {
        return;
    }

    fetch(`{{ route('admin.sql-alerts.destroy', $sqlAlert) }}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('ลบการแจ้งเตือนสำเร็จ');
            window.location.href = '{{ route("admin.sql-alerts.index") }}';
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการส่งคำขอ');
    });
}

// Auto-refresh executions every 30 seconds
setInterval(function() {
    // Only refresh if there are running executions
    if (document.querySelector('.execution-item.running')) {
        location.reload();
    }
}, 30000);
</script>
@endpush