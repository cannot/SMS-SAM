@extends('layouts.app')

@section('title', 'รายละเอียดบทบาท: ' . $role->display_name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">รายละเอียดบทบาท: {{ $role->display_name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">บทบาท</a></li>
                    <li class="breadcrumb-item active">{{ $role->display_name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('edit-roles')
            <a href="{{ route('roles.edit', $role) }}" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> แก้ไข
            </a>
            @endcan
            
            @can('create-roles')
            <a href="{{ route('roles.clone', $role) }}" 
               class="btn btn-outline-secondary"
               onclick="return confirm('คุณต้องการคัดลอกบทบาทนี้หรือไม่?')">
                <i class="bi bi-files me-1"></i> คัดลอก
            </a>
            @endcan
            
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> กลับ
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Role Information -->
        <div class="col-lg-4">
            <!-- Basic Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลพื้นฐาน</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="bg-primary bg-gradient rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-shield-alt text-white fa-2x"></i>
                        </div>
                        <h4 class="mt-3 mb-1">{{ $role->display_name }}</h4>
                        <p class="text-muted">{{ $role->name }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">คำอธิบาย</label>
                        <p>{{ $role->description ?: 'ไม่มีคำอธิบาย' }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">สร้างเมื่อ</label>
                        <p>{{ $role->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">แก้ไขล่าสุด</label>
                        <p>{{ $role->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">สถิติ</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="border-end">
                                <h3 class="text-primary mb-1">{{ $stats['total_users'] }}</h3>
                                <small class="text-muted">ผู้ใช้ทั้งหมด</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <h3 class="text-success mb-1">{{ $stats['active_users'] }}</h3>
                            <small class="text-muted">ผู้ใช้ที่ใช้งาน</small>
                        </div>
                        <div class="col-6">
                            <div class="border-end">
                                <h3 class="text-info mb-1">{{ $stats['permissions_count'] }}</h3>
                                <small class="text-muted">สิทธิ์</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h3 class="text-warning mb-1">{{ $stats['recent_assignments'] }}</h3>
                            <small class="text-muted">ใหม่ (30 วัน)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            @can('delete-roles')
            @if(!in_array($role->name, ['admin', 'super-admin']))
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-1"></i> Danger Zone</h5>
                </div>
                <div class="card-body">
                    @if($stats['total_users'] > 0)
                    <p class="text-muted mb-3">ไม่สามารถลบบทบาทที่มีผู้ใช้งานได้</p>
                    <button class="btn btn-outline-danger" disabled>
                        <i class="bi bi-trash me-1"></i> ลบบทบาท
                    </button>
                    @else
                    <p class="text-muted mb-3">การลบบทบาทนี้จะไม่สามารถกู้คืนได้</p>
                    <form method="POST" 
                          action="{{ route('roles.destroy', $role) }}" 
                          onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบบทบาทนี้? การดำเนินการนี้ไม่สามารถยกเลิกได้')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> ลบบทบาท
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endif
            @endcan
        </div>

        <!-- Permissions and Users -->
        <div class="col-lg-8">
            <!-- Permissions -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">สิทธิ์การเข้าถึง</h5>
                    <span class="badge bg-primary">{{ $role->permissions->count() }} สิทธิ์</span>
                </div>
                <div class="card-body">
                    @if($role->permissions->count() > 0)
                        @php
                            $groupedPermissions = $role->permissions->groupBy(function($permission) {
                                return explode('-', $permission->name)[0];
                            });
                        @endphp

                        @foreach($groupedPermissions as $group => $permissions)
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-key me-1"></i> {{ ucfirst($group) }} Permissions
                                <span class="badge bg-info ms-2">{{ $permissions->count() }}</span>
                            </h6>
                            <div class="row">
                                @foreach($permissions as $permission)
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        <span>{{ $permission->display_name ?? $permission->name }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-key fa-3x text-muted mb-3"></i>
                            <h5>ไม่มีสิทธิ์</h5>
                            <p class="text-muted">บทบาทนี้ยังไม่มีสิทธิ์การเข้าถึงใดๆ</p>
                            @can('edit-roles')
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> เพิ่มสิทธิ์
                            </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>

            <!-- Users with this role -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">ผู้ใช้ที่มีบทบาทนี้</h5>
                    <span class="badge bg-info">{{ $role->users->count() }} คน</span>
                </div>
                <div class="card-body">
                    @if($role->users->count() > 0)
                        <div class="row">
                            @foreach($role->users->take(12) as $user)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="d-flex align-items-center p-2 border rounded">
                                    <div class="flex-shrink-0">
                                        <div class="user-avatar bg-primary">
                                            {{ substr($user->display_name, 0, 2) }}
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">{{ $user->display_name }}</h6>
                                        <small class="text-muted">{{ $user->email }}</small>
                                        @if(!$user->is_active)
                                            <br><span class="badge bg-secondary">ไม่ใช้งาน</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        @if($role->users->count() > 12)
                        <div class="text-center mt-3">
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#allUsersModal">
                                <i class="bi bi-eye me-1"></i> ดูทั้งหมด ({{ $role->users->count() }} คน)
                            </button>
                        </div>
                        @endif

                        <!-- Bulk Actions -->
                        @can('bulk-manage-users')
                        <div class="mt-4 pt-3 border-top">
                            <h6>การจัดการแบบกลุ่ม</h6>
                            <div class="btn-group" role="group">
                                <button type="button" 
                                        class="btn btn-outline-success btn-sm" 
                                        onclick="bulkAssignRole()">
                                    <i class="bi bi-person-plus me-1"></i> เพิ่มผู้ใช้
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-warning btn-sm" 
                                        onclick="bulkRemoveRole()">
                                    <i class="bi bi-person-dash me-1"></i> ลบผู้ใช้
                                </button>
                            </div>
                        </div>
                        @endcan
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>ไม่มีผู้ใช้</h5>
                            <p class="text-muted">ยังไม่มีผู้ใช้ที่ได้รับบทบาทนี้</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- All Users Modal -->
@if($role->users->count() > 12)
<div class="modal fade" id="allUsersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ผู้ใช้ทั้งหมดที่มีบทบาท: {{ $role->display_name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ผู้ใช้</th>
                                <th>อีเมล</th>
                                <th>สถานะ</th>
                                <th>เพิ่มเมื่อ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($role->users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar bg-primary me-2">
                                            {{ substr($user->display_name, 0, 2) }}
                                        </div>
                                        {{ $user->display_name }}
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge bg-success">ใช้งาน</span>
                                    @else
                                        <span class="badge bg-secondary">ไม่ใช้งาน</span>
                                    @endif
                                </td>
                                <td>{{ $user->pivot->created_at->format('d/m/Y') ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<script>
function bulkAssignRole() {
    // Implement bulk assign functionality
    alert('ฟีเจอร์กำลังพัฒนา');
}

function bulkRemoveRole() {
    // Implement bulk remove functionality  
    alert('ฟีเจอร์กำลังพัฒนา');
}
</script>
@endsection }}" 
                                               id="permission_{{ $permission->id }}"
                                               {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                            {{ $permission->display_name ?? $permission->name }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <hr>
                        @endforeach

                        @error('permissions')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function selectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function clearAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function toggleGroup(groupName) {
    const groupCheckboxes = document.querySelectorAll(`[data-group="${groupName}"] .permission-checkbox`);
    const allChecked = Array.from(groupCheckboxes).every(cb => cb.checked);
    
    groupCheckboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
}

// Auto-generate system name from display name
document.getElementById('display_name').addEventListener('input', function() {
    const displayName = this.value;
    const systemName = displayName
        .toLowerCase()
        .replace(/[^a-z0-9\s]/g, '')
        .replace(/\s+/g, '-')
        .trim();
    
    document.getElementById('name').value = systemName;
});
</script>
@endsection