@extends('layouts.app')

@section('title', 'รายละเอียดกลุ่ม: ' . $group->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center mb-2">
                <h2 class="mb-0 me-3">{{ $group->name }}</h2>
                @if($group->is_active)
                    <span class="badge bg-success">ใช้งาน</span>
                @else
                    <span class="badge bg-danger">ไม่ใช้งาน</span>
                @endif
                
                @switch($group->type)
                    @case('manual')
                        <span class="badge bg-info ms-2">กำหนดเอง</span>
                        @break
                    @case('department')
                        <span class="badge bg-warning ms-2">แผนก</span>
                        @break
                    @case('role')
                        <span class="badge bg-secondary ms-2">ตำแหน่ง</span>
                        @break
                    @case('ldap_group')
                        <span class="badge bg-primary ms-2">LDAP</span>
                        @break
                    @case('dynamic')
                        <span class="badge bg-success ms-2">Dynamic</span>
                        @break
                @endswitch
            </div>
            @if($group->description)
            <p class="text-muted mb-0">{{ $group->description }}</p>
            @endif
        </div>
        <div class="btn-group">
            <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>กลับ
            </a>
            @can('edit-groups')
            <a href="{{ route('groups.edit', $group) }}" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i>แก้ไข
            </a>
            @endcan
            
            @if(in_array($group->type, ['department', 'ldap_group', 'dynamic']))
            @can('sync-groups')
            <button type="button" class="btn btn-info" onclick="syncGroupMembers()">
                <i class="bi bi-arrow-clockwise me-1"></i>ซิงค์สมาชิก
            </button>
            @endcan
            @endif
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-1">{{ $stats['member_count'] }}</h3>
                    <small class="text-muted">สมาชิกทั้งหมด</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h3 class="text-success mb-1">{{ $stats['active_member_count'] }}</h3>
                    <small class="text-muted">สมาชิกที่ใช้งาน</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h3 class="text-info mb-1">{{ $stats['total_notifications'] }}</h3>
                    <small class="text-muted">การแจ้งเตือนทั้งหมด</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-1">{{ $stats['this_month'] }}</h3>
                    <small class="text-muted">การแจ้งเตือนเดือนนี้</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Group Details -->
        <div class="col-lg-8">
            <!-- Group Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>ข้อมูลกลุ่ม
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="30%"><strong>ชื่อกลุ่ม:</strong></td>
                                    <td>{{ $group->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>ประเภท:</strong></td>
                                    <td>
                                        @switch($group->type)
                                            @case('manual')
                                                <span class="badge bg-info">กำหนดเอง</span>
                                                @break
                                            @case('department')
                                                <span class="badge bg-warning">แผนก</span>
                                                @break
                                            @case('role')
                                                <span class="badge bg-secondary">ตำแหน่ง</span>
                                                @break
                                            @case('ldap_group')
                                                <span class="badge bg-primary">LDAP Group</span>
                                                @break
                                            @case('dynamic')
                                                <span class="badge bg-success">Dynamic</span>
                                                @break
                                        @endswitch
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>สถานะ:</strong></td>
                                    <td>
                                        @if($group->is_active)
                                            <span class="badge bg-success">ใช้งาน</span>
                                        @else
                                            <span class="badge bg-danger">ไม่ใช้งาน</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($group->criteria)
                                <tr>
                                    <td><strong>เงื่อนไข:</strong></td>
                                    <td>
                                        @if(isset($group->criteria['department']))
                                            <span class="badge bg-light text-dark">แผนก: {{ $group->criteria['department'] }}</span>
                                        @endif
                                        @if(isset($group->criteria['title']))
                                            <span class="badge bg-light text-dark">ตำแหน่ง: {{ $group->criteria['title'] }}</span>
                                        @endif
                                        @if(isset($group->criteria['ldap_group']))
                                            <span class="badge bg-light text-dark">LDAP: {{ $group->criteria['ldap_group'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="30%"><strong>สร้างเมื่อ:</strong></td>
                                    <td>{{ $group->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>สร้างโดย:</strong></td>
                                    <td>{{ $group->creator->display_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>อัปเดตล่าสุด:</strong></td>
                                    <td>{{ $group->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>จำนวนสมาชิก:</strong></td>
                                    <td>
                                        <span class="badge bg-primary">{{ $group->users->count() }} คน</span>
                                        <span class="badge bg-success">{{ $group->users->where('is_active', true)->count() }} คนใช้งาน</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($group->description)
                    <hr>
                    <div>
                        <strong>คำอธิบาย:</strong>
                        <p class="mb-0 mt-2">{{ $group->description }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Members List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>สมาชิกในกลุ่ม ({{ $group->users->count() }} คน)
                    </h5>
                    @if($group->type === 'manual')
                    @can('edit-groups')
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                        <i class="bi bi-person-plus me-1"></i>เพิ่มสมาชิก
                    </button>
                    @endcan
                    @endif
                </div>
                <div class="card-body">
                    @if($group->users->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ชื่อ</th>
                                    <th>อีเมล</th>
                                    <th>แผนก</th>
                                    <th>ตำแหน่ง</th>
                                    <th>สถานะ</th>
                                    <th>เข้าร่วมเมื่อ</th>
                                    @if($group->type === 'manual')
                                    <th>การดำเนินการ</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group->users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-2">
                                                {{ $user->initials }}
                                            </div>
                                            <strong>{{ $user->display_name }}</strong>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->department)
                                            <span class="badge bg-light text-dark">{{ $user->department }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->title)
                                            <span class="badge bg-light text-dark">{{ $user->title }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->is_active)
                                            <span class="badge bg-success">ใช้งาน</span>
                                        @else
                                            <span class="badge bg-danger">ไม่ใช้งาน</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $user->pivot->joined_at ? \Carbon\Carbon::parse($user->pivot->joined_at)->format('d/m/Y H:i') : '-' }}
                                        </small>
                                    </td>
                                    @if($group->type === 'manual')
                                    <td>
                                        @can('edit-groups')
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="removeMember({{ $user->id }}, '{{ $user->display_name }}')"
                                                title="ลบออกจากกลุ่ม">
                                            <i class="bi bi-person-dash"></i>
                                        </button>
                                        @endcan
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="bi bi-people display-4 text-muted mb-3"></i>
                        <h5>ไม่มีสมาชิกในกลุ่ม</h5>
                        <p class="text-muted">
                            @if($group->type === 'manual')
                                เพิ่มสมาชิกแรกของกลุ่มนี้
                            @else
                                ระบบจะซิงค์สมาชิกอัตโนมัติตามเงื่อนไขที่กำหนด
                            @endif
                        </p>
                        @if($group->type === 'manual')
                        @can('edit-groups')
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                            <i class="bi bi-person-plus me-1"></i>เพิ่มสมาชิก
                        </button>
                        @endcan
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Recent Notifications -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bell me-2"></i>การแจ้งเตือนล่าสุด
                    </h5>
                </div>
                <div class="card-body">
                    @if($group->notifications->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($group->notifications as $notification)
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="me-auto">
                                    <h6 class="mb-1">{{ $notification->title }}</h6>
                                    <p class="mb-1 small text-muted">{{ Str::limit($notification->message, 60) }}</p>
                                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                                <span class="badge bg-{{ $notification->status === 'sent' ? 'success' : ($notification->status === 'failed' ? 'danger' : 'warning') }}">
                                    {{ $notification->status }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-3">
                        <i class="bi bi-bell-slash text-muted mb-2"></i>
                        <p class="text-muted mb-0">ยังไม่มีการแจ้งเตือน</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Actions Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-gear me-2"></i>การดำเนินการ
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @can('create-notifications')
                        <a href="{{ route('admin.notifications.create', ['group' => $group->id]) }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>
                            สร้างการแจ้งเตือน
                        </a>
                        @endcan
                        
                        @can('view-notifications')
                        <a href="{{ route('admin.notifications.index', ['group' => $group->id]) }}" class="btn btn-outline-info">
                            <i class="bi bi-list me-1"></i>
                            ดูการแจ้งเตือนทั้งหมด
                        </a>
                        @endcan
                        
                        @if(in_array($group->type, ['department', 'ldap_group', 'dynamic']))
                        @can('sync-groups')
                        <button type="button" class="btn btn-outline-secondary" onclick="syncGroupMembers()">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            ซิงค์สมาชิกใหม่
                        </button>
                        @endcan
                        @endif
                        
                        @can('export-reports')
                        <button type="button" class="btn btn-outline-success" onclick="exportGroupReport()">
                            <i class="bi bi-download me-1"></i>
                            Export รายงาน
                        </button>
                        @endcan
                        
                        @can('edit-groups')
                        <a href="{{ route('groups.edit', $group) }}" class="btn btn-warning">
                            <i class="bi bi-pencil me-1"></i>
                            แก้ไขกลุ่ม
                        </a>
                        @endcan
                        
                        @can('delete-groups')
                        <button type="button" class="btn btn-outline-danger" onclick="deleteGroup()">
                            <i class="bi bi-trash me-1"></i>
                            ลบกลุ่ม
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
@if($group->type === 'manual')
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มสมาชิกใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="memberSearch" class="form-label">ค้นหาผู้ใช้</label>
                    <input type="text" class="form-control" id="memberSearch" 
                           placeholder="พิมพ์ชื่อหรืออีเมลเพื่อค้นหา...">
                </div>
                <div id="memberSearchResults" style="display: none;">
                    <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                        <!-- Results will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>คุณแน่ใจหรือไม่ที่จะลบกลุ่ม <strong>{{ $group->name }}</strong>?</p>
                <p class="text-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    การดำเนินการนี้ไม่สามารถย้อนกลับได้
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <form method="POST" action="{{ route('groups.destroy', $group) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">ลบ</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Sync group members
async function syncGroupMembers() {
    if (!confirm('คุณต้องการซิงค์สมาชิกของกลุ่มนี้หรือไม่?')) {
        return;
    }

    try {
        const button = event.target;
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i>ซิงค์...';
        button.disabled = true;

        const response = await fetch(`/groups/{{ $group->id }}/sync`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            window.AppFunctions.showNotification(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            window.AppFunctions.showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Sync error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการซิงค์', 'error');
    } finally {
        button.innerHTML = originalContent;
        button.disabled = false;
    }
}

// Remove member from group
async function removeMember(userId, userName) {
    if (!confirm(`คุณต้องการลบ ${userName} ออกจากกลุ่มหรือไม่?`)) {
        return;
    }

    try {
        const response = await fetch(`/groups/{{ $group->id }}/remove-user`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        });

        const result = await response.json();

        if (result.success) {
            window.AppFunctions.showNotification(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            window.AppFunctions.showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Remove member error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการลบสมาชิก', 'error');
    }
}

// Add member to group
async function addMember(userId, userName) {
    try {
        const response = await fetch(`/groups/{{ $group->id }}/add-user`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        });

        const result = await response.json();

        if (result.success) {
            window.AppFunctions.showNotification(result.message, 'success');
            document.getElementById('memberSearch').value = '';
            document.getElementById('memberSearchResults').style.display = 'none';
            setTimeout(() => location.reload(), 1000);
        } else {
            window.AppFunctions.showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Add member error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการเพิ่มสมาชิก', 'error');
    }
}

// Search members functionality
let memberSearchTimeout;

async function searchMembers() {
    const query = document.getElementById('memberSearch').value.trim();
    if (query.length < 2) {
        document.getElementById('memberSearchResults').style.display = 'none';
        return;
    }

    try {
        const response = await fetch(`/groups/users?q=${encodeURIComponent(query)}`);
        const users = await response.json();
        
        let resultsHtml = '';
        users.forEach(user => {
            resultsHtml += `
                <div class="d-flex justify-content-between align-items-center p-2 border-bottom user-item" 
                     style="cursor: pointer;" onclick="addMember(${user.id}, '${user.text}')">
                    <div>
                        <strong>${user.text}</strong>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
            `;
        });

        if (resultsHtml === '') {
            resultsHtml = '<div class="text-center text-muted p-3">ไม่พบผู้ใช้</div>';
        }

        document.getElementById('memberSearchResults').innerHTML = resultsHtml;
        document.getElementById('memberSearchResults').style.display = 'block';
    } catch (error) {
        console.error('Search error:', error);
    }
}

// Delete group
function deleteGroup() {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Export group report
async function exportGroupReport() {
    try {
        window.AppFunctions.showNotification('กำลังสร้างรายงาน...', 'info');
        
        const response = await fetch(`/groups/{{ $group->id }}/export`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `group_${{{ $group->id }}}_report.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            window.AppFunctions.showNotification('ดาวน์โหลดรายงานเรียบร้อยแล้ว', 'success');
        } else {
            window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการสร้างรายงาน', 'error');
        }
    } catch (error) {
        console.error('Export error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการ Export', 'error');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Member search functionality
    const memberSearchInput = document.getElementById('memberSearch');
    if (memberSearchInput) {
        memberSearchInput.addEventListener('input', function() {
            clearTimeout(memberSearchTimeout);
            memberSearchTimeout = setTimeout(searchMembers, 300);
        });
    }
});
</script>
@endpush