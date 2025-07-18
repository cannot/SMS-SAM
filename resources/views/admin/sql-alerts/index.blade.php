@extends('layouts.app')

@section('title', $title ?? 'SQL Alerts')

@section('content')
<div class="container-fluid">
    
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">SQL Alerts</h1>
            <p class="mb-0 text-muted">จัดการการแจ้งเตือนแบบ SQL</p>
        </div>
        
        <div>
            <a href="{{ route('admin.sql-alerts.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> สร้างการแจ้งเตือนใหม่
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                การแจ้งเตือนทั้งหมด</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalAlerts ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                การแจ้งเตือนที่ใช้งาน</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeAlerts ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                การรันล่าสุด</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $recentExecutions->count() ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                สถานะ Draft</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $statusCounts['draft'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-edit fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.sql-alerts.index') }}" class="row g-3">
                
                <div class="col-md-3">
                    <label for="status" class="form-label">สถานะ</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">ทุกสถานะ</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ไม่ใช้งาน</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>ร่าง</option>
                        <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>ข้อผิดพลาด</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="schedule_type" class="form-label">ประเภทตารางเวลา</label>
                    <select name="schedule_type" id="schedule_type" class="form-select">
                        <option value="">ทุกประเภท</option>
                        <option value="manual" {{ request('schedule_type') === 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="once" {{ request('schedule_type') === 'once' ? 'selected' : '' }}>Once</option>
                        <option value="recurring" {{ request('schedule_type') === 'recurring' ? 'selected' : '' }}>Recurring</option>
                        <option value="cron" {{ request('schedule_type') === 'cron' ? 'selected' : '' }}>Cron</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="search" class="form-label">ค้นหา</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="ค้นหาตามชื่อหรือคำอธิบาย..." 
                           value="{{ request('search') }}">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i> ค้นหา
                        </button>
                        <a href="{{ route('admin.sql-alerts.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
                
            </form>
        </div>
    </div>

    {{-- Alerts Table --}}
    <div class="card">
        <div class="card-body">
            
            @if(isset($alerts) && $alerts->count() > 0)
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ชื่อการแจ้งเตือน</th>
                            <th>สถานะ</th>
                            <th>ประเภทตารางเวลา</th>
                            <th>การรันล่าสุด</th>
                            <th>อัตราความสำเร็จ</th>
                            <th>สร้างโดย</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $alert)
                        <tr>
                            <td>
                                <div>
                                    <h6 class="mb-0">{{ $alert->name }}</h6>
                                    @if($alert->description)
                                    <small class="text-muted">{{ Str::limit($alert->description, 50) }}</small>
                                    @endif
                                </div>
                            </td>
                            
                            <td>
                                @php
                                    $statusColors = [
                                        'active' => 'success',
                                        'inactive' => 'secondary', 
                                        'draft' => 'warning',
                                        'error' => 'danger'
                                    ];
                                    $statusColor = $statusColors[$alert->status] ?? 'secondary';
                                    $statusLabels = [
                                        'active' => 'ใช้งาน',
                                        'inactive' => 'ไม่ใช้งาน',
                                        'draft' => 'ร่าง',
                                        'error' => 'ข้อผิดพลาด'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColor }}">
                                    {{ $statusLabels[$alert->status] ?? ucfirst($alert->status) }}
                                </span>
                            </td>
                            
                            <td>
                                <div>
                                    @php
                                        $scheduleLabels = [
                                            'manual' => 'Manual',
                                            'once' => 'ครั้งเดียว',
                                            'recurring' => 'ซ้ำ',
                                            'cron' => 'Cron'
                                        ];
                                    @endphp
                                    <span class="badge bg-light text-dark">
                                        {{ $scheduleLabels[$alert->schedule_type] ?? ucfirst($alert->schedule_type) }}
                                    </span>
                                    @if($alert->next_run)
                                    <br><small class="text-muted">
                                        ถัดไป: {{ $alert->next_run->format('M j, H:i') }}
                                    </small>
                                    @endif
                                </div>
                            </td>
                            
                            <td>
                                @if($alert->last_run)
                                    <span class="text-muted">{{ $alert->last_run->diffForHumans() }}</span>
                                @else
                                    <span class="text-muted">ยังไม่เคยรัน</span>
                                @endif
                            </td>
                            
                            <td>
                                @if($alert->total_executions > 0)
                                    @php
                                        $successRate = round(($alert->successful_executions / $alert->total_executions) * 100);
                                        $rateColor = $successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger');
                                    @endphp
                                    <span class="text-{{ $rateColor }}">{{ $successRate }}%</span>
                                    <br><small class="text-muted">{{ $alert->successful_executions }}/{{ $alert->total_executions }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            
                            <td>
                                <div>
                                    <span>{{ $alert->creator->name ?? 'Unknown' }}</span>
                                    <br><small class="text-muted">{{ $alert->created_at->format('M j, Y') }}</small>
                                </div>
                            </td>
                            
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.sql-alerts.show', $alert) }}" 
                                       class="btn btn-sm btn-outline-primary" title="ดู">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <a href="{{ route('admin.sql-alerts.edit', $alert) }}" 
                                       class="btn btn-sm btn-outline-secondary" title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    @if($alert->status === 'active')
                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                            onclick="executeAlert({{ $alert->id }})" title="รันทันที">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    @endif
                                    
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteAlert({{ $alert->id }})" title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <small class="text-muted">
                        แสดง {{ $alerts->firstItem() ?? 0 }} ถึง {{ $alerts->lastItem() ?? 0 }} 
                        จากทั้งหมด {{ $alerts->total() }} รายการ
                    </small>
                </div>
                <div>
                    {{ $alerts->appends(request()->query())->links() }}
                </div>
            </div>
            
            @else
            
            {{-- Empty State --}}
            <div class="text-center py-5">
                <i class="fas fa-database fa-3x text-muted mb-3"></i>
                <h5>ยังไม่มีการแจ้งเตือน SQL</h5>
                <p class="text-muted">เริ่มต้นด้วยการสร้างการแจ้งเตือน SQL แรกของคุณ</p>
                <a href="{{ route('admin.sql-alerts.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> สร้างการแจ้งเตือนแรก
                </a>
            </div>
            
            @endif
            
        </div>
    </div>
    
</div>

{{-- Execute Alert Modal --}}
<div class="modal fade" id="executeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รันการแจ้งเตือน SQL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>คุณแน่ใจหรือไม่ที่จะรันการแจ้งเตือน SQL นี้ทันที?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    การดำเนินการนี้จะรัน SQL query และส่งการแจ้งเตือนไปยังผู้รับทั้งหมด
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-success" id="confirmExecute">รันทันที</button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Alert Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ลบการแจ้งเตือน SQL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>คุณแน่ใจหรือไม่ที่จะลบการแจ้งเตือน SQL นี้?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    การดำเนินการนี้ไม่สามารถยกเลิกได้ ประวัติการรันทั้งหมดจะถูกลบด้วย
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">ลบ</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Routes configuration
const routes = {
    execute: "{{ route('admin.sql-alerts.execute', ':id') }}",
    destroy: "{{ route('admin.sql-alerts.destroy', ':id') }}"
};

let currentAlertId = null;

function executeAlert(alertId) {
    currentAlertId = alertId;
    const modal = new bootstrap.Modal(document.getElementById('executeModal'));
    modal.show();
}

function deleteAlert(alertId) {
    currentAlertId = alertId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Execute confirmation
document.getElementById('confirmExecute')?.addEventListener('click', function() {
    if (currentAlertId) {
        const url = routes.execute.replace(':id', currentAlertId);
        
        // Show loading state
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังรัน...';
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('รันการแจ้งเตือนสำเร็จ');
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาดในการรัน: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        })
        .finally(() => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('executeModal'));
            modal?.hide();
        });
    }
});

// Delete confirmation
document.getElementById('confirmDelete')?.addEventListener('click', function() {
    if (currentAlertId) {
        const url = routes.destroy.replace(':id', currentAlertId);
        
        // Show loading state
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังลบ...';
        
        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('ลบการแจ้งเตือนสำเร็จ');
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาดในการลบ: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        })
        .finally(() => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            modal?.hide();
        });
    }
});
</script>
@endpush