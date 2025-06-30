@extends('layouts.app')

@section('title', 'จัดการกลุ่มการแจ้งเตือน')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="bi bi-people-fill text-primary me-2"></i>
                จัดการกลุ่มการแจ้งเตือน
            </h2>
            <p class="text-muted mb-0">สร้างและจัดการกลุ่มผู้รับการแจ้งเตือน</p>
        </div>
        <div>
            @can('create-notification-groups')
            <a href="{{ route('groups.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>
                สร้างกลุ่มใหม่
            </a>
            @endcan
            
            @can('sync-groups')
            <button type="button" class="btn btn-outline-secondary ms-2" onclick="bulkSyncGroups()">
                <i class="bi bi-arrow-clockwise me-1"></i>
                ซิงค์ทั้งหมด
            </button>
            @endcan
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-1">{{ $stats['total'] }}</h3>
                    <small class="text-muted">กลุ่มทั้งหมด</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h3 class="text-success mb-1">{{ $stats['active'] }}</h3>
                    <small class="text-muted">กลุ่มที่ใช้งาน</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h3 class="text-info mb-1">{{ $stats['manual'] }}</h3>
                    <small class="text-muted">กลุ่มแบบกำหนดเอง</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-1">{{ $stats['department'] }}</h3>
                    <small class="text-muted">กลุ่มแผนก</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-secondary">
                <div class="card-body text-center">
                    <h3 class="text-secondary mb-1">{{ $stats['ldap'] }}</h3>
                    <small class="text-muted">กลุ่ม LDAP</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-dark">
                <div class="card-body text-center">
                    <h3 class="text-dark mb-1">{{ $stats['dynamic'] ?? 0 }}</h3>
                    <small class="text-muted">กลุ่ม Dynamic</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">ค้นหา</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="ชื่อกลุ่มหรือคำอธิบาย...">
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">ประเภทกลุ่ม</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">ทั้งหมด</option>
                        <option value="manual" {{ request('type') === 'manual' ? 'selected' : '' }}>แบบกำหนดเอง</option>
                        <option value="department" {{ request('type') === 'department' ? 'selected' : '' }}>แผนก</option>
                        <option value="role" {{ request('type') === 'role' ? 'selected' : '' }}>ตำแหน่ง</option>
                        <option value="ldap_group" {{ request('type') === 'ldap_group' ? 'selected' : '' }}>LDAP Group</option>
                        <option value="dynamic" {{ request('type') === 'dynamic' ? 'selected' : '' }}>Dynamic</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">สถานะ</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">ทั้งหมด</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ไม่ใช้งาน</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>ค้นหา
                        </button>
                        <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i>ล้าง
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Groups Table -->
    <div class="card">
        <div class="card-body">
            @if($groups->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ชื่อกลุ่ม</th>
                            <th>ประเภท</th>
                            <th>สมาชิก</th>
                            <th>การแจ้งเตือน</th>
                            <th>สถานะ</th>
                            <th>สร้างเมื่อ</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groups as $group)
                        <tr>
                            <td>
                                <div>
                                    <strong>{{ $group->name }}</strong>
                                    @if($group->description)
                                    <br><small class="text-muted">{{ Str::limit($group->description, 60) }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @switch($group->type)
                                    @case('manual')
                                        <span class="badge bg-info">กำหนดเอง</span>
                                        @break
                                    @case('department')
                                        <span class="badge bg-warning">แผนก</span>
                                        @if(isset($group->criteria['department']))
                                            <br><small class="text-muted">{{ $group->criteria['department'] }}</small>
                                        @endif
                                        @break
                                    @case('role')
                                        <span class="badge bg-secondary">ตำแหน่ง</span>
                                        @if(isset($group->criteria['title']))
                                            <br><small class="text-muted">{{ $group->criteria['title'] }}</small>
                                        @endif
                                        @break
                                    @case('ldap_group')
                                        <span class="badge bg-primary">LDAP</span>
                                        @if(isset($group->criteria['ldap_group']))
                                            <br><small class="text-muted">{{ $group->criteria['ldap_group'] }}</small>
                                        @endif
                                        @break
                                    @case('dynamic')
                                        <span class="badge bg-success">Dynamic</span>
                                        @if($group->criteria)
                                            <br><small class="text-muted">
                                                @foreach($group->criteria as $key => $value)
                                                    @if($value)
                                                        {{ ucfirst($key) }}: {{ $value }}
                                                        @if(!$loop->last), @endif
                                                    @endif
                                                @endforeach
                                            </small>
                                        @endif
                                        @break
                                    @default
                                        <span class="badge bg-light text-dark">{{ $group->type }}</span>
                                @endswitch
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">{{ $group->users_count }} คน</span>
                                @if($group->users_count > 0)
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            <i class="bi bi-check-circle text-success"></i>
                                            ใช้งาน: {{ $group->users()->where('users.is_active', true)->count() }}
                                        </small>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">{{ $group->notifications_count }} ครั้ง</span>
                                @if($group->notifications_count > 0)
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            ล่าสุด: {{ $group->notifications()->latest()->first()?->created_at?->diffForHumans() ?? 'N/A' }}
                                        </small>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($group->is_active)
                                    <span class="badge bg-success">ใช้งาน</span>
                                @else
                                    <span class="badge bg-danger">ไม่ใช้งาน</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $group->created_at->format('d/m/Y H:i') }}
                                    <br>โดย {{ $group->creator->display_name ?? 'N/A' }}
                                </small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    @can('view-notification-groups')
                                    <a href="{{ route('groups.show', $group) }}" 
                                       class="btn btn-sm btn-outline-primary"
                                       title="ดูรายละเอียด">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @endcan
                                    
                                    @can('edit-notification-groups')
                                    <a href="{{ route('groups.edit', $group) }}" 
                                       class="btn btn-sm btn-outline-warning"
                                       title="แก้ไข">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    
                                    @if(in_array($group->type, ['department', 'ldap_group', 'dynamic']))
                                    @can('sync-groups')
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-secondary"
                                            onclick="syncGroupMembers({{ $group->id }})"
                                            title="ซิงค์สมาชิก">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                    @endcan
                                    @endif
                                    
                                    @can('delete-groups')
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="deleteGroup({{ $group->id }}, '{{ $group->name }}')"
                                            title="ลบ">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    แสดง {{ $groups->firstItem() }} - {{ $groups->lastItem() }} 
                    จาก {{ $groups->total() }} รายการ
                </div>
                {{ $groups->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="bi bi-people display-1 text-muted"></i>
                </div>
                <h5>ไม่พบกลุ่มการแจ้งเตือน</h5>
                <p class="text-muted">
                    @if(request()->hasAny(['search', 'type', 'status']))
                        ไม่พบกลุ่มที่ตรงกับเงื่อนไขการค้นหา ลองเปลี่ยนเงื่อนไขการค้นหา
                    @else
                        เริ่มต้นสร้างกลุ่มแรกของคุณ
                    @endif
                </p>
                @can('create-notification-groups')
                <a href="{{ route('groups.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>สร้างกลุ่มใหม่
                </a>
                @endcan
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>คุณแน่ใจหรือไม่ที่จะลบกลุ่ม <strong id="groupNameToDelete"></strong>?</p>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>คำเตือน:</strong> การดำเนินการนี้จะ:
                    <ul class="mb-0 mt-2">
                        <li>ลบกลุ่มและสมาชิกทั้งหมดออกจากระบบ</li>
                        <li>ไม่สามารถย้อนกลับได้</li>
                        <li>ประวัติการแจ้งเตือนที่ส่งไปแล้วจะยังคงอยู่</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>ลบกลุ่ม
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Sync Progress Modal -->
<div class="modal fade" id="bulkSyncModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">กำลังซิงค์กลุ่มทั้งหมด</h5>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">กำลังซิงค์...</span>
                    </div>
                    <p class="mb-0">กรุณารอสักครู่...</p>
                    <small class="text-muted">การซิงค์อาจใช้เวลาหลายนาที</small>
                </div>
                <div id="syncProgress" class="mt-3" style="display: none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%"></div>
                    </div>
                    <div id="syncStatus" class="text-center mt-2">
                        <small class="text-muted">เริ่มการซิงค์...</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deleteGroup(groupId, groupName) {
    document.getElementById('groupNameToDelete').textContent = groupName;
    document.getElementById('deleteForm').action = `/groups/${groupId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

async function syncGroupMembers(groupId) {
    try {
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i>ซิงค์...';
        button.disabled = true;

        const response = await fetch(`/groups/${groupId}/sync`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Sync error:', error);
        showNotification('เกิดข้อผิดพลาดในการซิงค์', 'error');
    } finally {
        if (event.target.closest('button')) {
            event.target.closest('button').innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
            event.target.closest('button').disabled = false;
        }
    }
}

async function bulkSyncGroups() {
    if (!confirm('คุณต้องการซิงค์สมาชิกของกลุ่มทั้งหมดหรือไม่? การดำเนินการนี้อาจใช้เวลาหลายนาที')) {
        return;
    }

    try {
        // Show progress modal
        const modal = new bootstrap.Modal(document.getElementById('bulkSyncModal'));
        modal.show();

        const response = await fetch('/groups/bulk-sync', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();

        // Hide modal
        modal.hide();

        if (result.success) {
            showNotification(result.message, 'success');
            
            // Show detailed results if available
            if (result.details && result.details.length > 0) {
                let detailsHtml = '<div class="mt-3"><h6>รายละเอียดการซิงค์:</h6><ul>';
                result.details.forEach(detail => {
                    detailsHtml += `<li>${detail.group}: อัปเดต ${detail.updated} รายการ</li>`;
                });
                detailsHtml += '</ul></div>';
                
                // You can show this in a separate modal or notification
                console.log('Sync details:', result.details);
            }
            
            setTimeout(() => location.reload(), 2000);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        // Hide modal on error
        const modal = bootstrap.Modal.getInstance(document.getElementById('bulkSyncModal'));
        if (modal) modal.hide();
        
        console.error('Bulk sync error:', error);
        showNotification('เกิดข้อผิดพลาดในการซิงค์', 'error');
    }
}

// Notification function
function showNotification(message, type = 'info') {
    // สร้าง notification element
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Auto-refresh stats every 30 seconds
setInterval(function() {
    updateGroupStats();
}, 30000);

function updateGroupStats() {
    fetch('/api/groups/stats')
        .then(response => response.json())
        .then(data => {
            // Update badge numbers if API exists
            document.querySelectorAll('[data-stat]').forEach(element => {
                const stat = element.dataset.stat;
                if (data[stat] !== undefined) {
                    element.textContent = data[stat];
                }
            });
        })
        .catch(error => {
            console.log('Failed to update group stats:', error);
        });
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-submit search form on Enter
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+N = New group
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        const createBtn = document.querySelector('a[href="{{ route("groups.create") }}"]');
        if (createBtn) {
            window.location.href = createBtn.href;
        }
    }
    
    // Ctrl+R = Refresh/Sync all
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        bulkSyncGroups();
    }
});
</script>
@endpush

@push('styles')
<style>
/* Custom styles for groups index */
.user-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: linear-gradient(45deg, #007bff, #6c757d);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.badge {
    font-size: 0.75em;
}

.btn-group .btn {
    border-radius: 0.25rem !important;
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Loading animation */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.loading {
    animation: pulse 1.5s infinite;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
        margin-right: 0;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .stats-card {
        margin-bottom: 1rem;
    }
}

/* Status indicators */
.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}

.status-active {
    background-color: #28a745;
}

.status-inactive {
    background-color: #dc3545;
}

/* Enhanced notifications */
.alert {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.alert-success {
    background-color: #d1edff;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background-color: #f8d7da;
    border-left: 4px solid #dc3545;
}

.alert-warning {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}

.alert-info {
    background-color: #cce7ff;
    border-left: 4px solid #17a2b8;
}
</style>
@endpush