{{-- resources/views/roles/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'แก้ไขบทบาท: ' . $role->display_name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">แก้ไขบทบาท: {{ $role->display_name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">บทบาท</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('roles.show', $role) }}">{{ $role->display_name }}</a></li>
                    <li class="breadcrumb-item active">แก้ไข</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('roles.show', $role) }}" class="btn btn-outline-info">
                <i class="bi bi-eye me-1"></i> ดูรายละเอียด
            </a>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> กลับ
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('roles.update', $role) }}">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Basic Information -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">ข้อมูลพื้นฐาน</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">ชื่อระบบ</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   value="{{ $role->name }}" 
                                   disabled>
                            <div class="form-text">ไม่สามารถแก้ไขชื่อระบบได้</div>
                        </div>

                        <div class="mb-3">
                            <label for="display_name" class="form-label">ชื่อแสดง <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('display_name') is-invalid @enderror" 
                                   id="display_name" 
                                   name="display_name" 
                                   value="{{ old('display_name', $role->display_name) }}" 
                                   required>
                            @error('display_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">คำอธิบาย</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3">{{ old('description', $role->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Role Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">ข้อมูลเพิ่มเติม</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">จำนวนผู้ใช้</label>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-info me-2">{{ $role->users()->count() }}</span>
                                <span class="text-muted">ผู้ใช้</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">สร้างเมื่อ</label>
                            <div class="text-muted">{{ $role->created_at->format('d/m/Y H:i') }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">แก้ไขล่าสุด</label>
                            <div class="text-muted">{{ $role->updated_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> บันทึกการแก้ไข
                            </button>
                            <a href="{{ route('roles.show', $role) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> ยกเลิก
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">สิทธิ์การเข้าถึง</h5>
                        <div>
                            <span class="badge bg-success me-2" id="selected-count">{{ $role->permissions()->count() }} สิทธิ์ที่เลือก</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllPermissions()">
                                <i class="bi bi-check-all me-1"></i> เลือกทั้งหมด
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllPermissions()">
                                <i class="bi bi-x-square me-1"></i> ล้างทั้งหมด
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @php
                            $rolePermissions = $role->permissions->pluck('name')->toArray();
                            
                            // Group permissions by module/category instead of action type
                            $modulePermissions = [];
                            foreach($permissions->flatten() as $permission) {
                                // Extract module from permission name (e.g., 'view-users' -> 'users')
                                $parts = explode('-', $permission->name);
                                if (count($parts) >= 2) {
                                    $module = $parts[1]; // Get the second part as module
                                    if (!isset($modulePermissions[$module])) {
                                        $modulePermissions[$module] = collect();
                                    }
                                    $modulePermissions[$module]->push($permission);
                                }
                            }
                            
                            // Define module display names and icons
                            $moduleConfig = [
                                'users' => ['name' => 'ผู้ใช้งาน', 'icon' => 'bi-people'],
                                'notifications' => ['name' => 'การแจ้งเตือน', 'icon' => 'bi-bell'],
                                'templates' => ['name' => 'เทมเพลต', 'icon' => 'bi-file-text'],
                                'groups' => ['name' => 'กลุ่มผู้รับ', 'icon' => 'bi-collection'],
                                'roles' => ['name' => 'บทบาท', 'icon' => 'bi-shield'],
                                'permissions' => ['name' => 'สิทธิ์', 'icon' => 'bi-key'],
                                'api-keys' => ['name' => 'API Keys', 'icon' => 'bi-key-fill'],
                                'api' => ['name' => 'API Management', 'icon' => 'bi-code'],
                                'reports' => ['name' => 'รายงาน', 'icon' => 'bi-graph-up'],
                                'analytics' => ['name' => 'การวิเคราะห์', 'icon' => 'bi-pie-chart'],
                                'system' => ['name' => 'ระบบ', 'icon' => 'bi-gear'],
                                'settings' => ['name' => 'การตั้งค่า', 'icon' => 'bi-sliders'],
                                'admin' => ['name' => 'ผู้ดูแลระบบ', 'icon' => 'bi-shield-check'],
                                'dashboard' => ['name' => 'แดชบอร์ด', 'icon' => 'bi-speedometer2'],
                                'logs' => ['name' => 'บันทึกระบบ', 'icon' => 'bi-journal-text'],
                                'data' => ['name' => 'ข้อมูล', 'icon' => 'bi-database'],
                                'ldap' => ['name' => 'LDAP', 'icon' => 'bi-diagram-3'],
                            ];
                            
                            // Sort modules by importance
                            $moduleOrder = ['users', 'notifications', 'templates', 'groups', 'roles', 'permissions', 'api-keys', 'api', 'reports', 'analytics', 'dashboard', 'system', 'settings', 'admin', 'logs', 'data', 'ldap'];
                            $sortedModules = [];
                            
                            foreach($moduleOrder as $module) {
                                if (isset($modulePermissions[$module])) {
                                    $sortedModules[$module] = $modulePermissions[$module];
                                }
                            }
                            
                            // Add any remaining modules
                            foreach($modulePermissions as $module => $perms) {
                                if (!in_array($module, $moduleOrder)) {
                                    $sortedModules[$module] = $perms;
                                }
                            }
                        @endphp

                        @foreach($sortedModules as $module => $groupPermissions)
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                @php
                                    $moduleInfo = $moduleConfig[$module] ?? ['name' => ucfirst($module), 'icon' => 'bi-folder'];
                                @endphp
                                <i class="{{ $moduleInfo['icon'] }} text-primary me-2"></i>
                                <h6 class="mb-0 text-primary">{{ $moduleInfo['name'] }}</h6>
                                <div class="ms-auto">
                                    @php
                                        $groupSelected = $groupPermissions->filter(function($p) use ($rolePermissions) {
                                            return in_array($p->name, $rolePermissions);
                                        })->count();
                                        $groupTotal = $groupPermissions->count();
                                    @endphp
                                    <span class="badge bg-info me-2" data-group-counter="{{ $module }}">{{ $groupSelected }}/{{ $groupTotal }}</span>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-info" 
                                            onclick="toggleGroup('{{ $module }}')">
                                        <i class="bi bi-toggles me-1"></i> สลับกลุ่ม
                                    </button>
                                </div>
                            </div>
                            
                            <div class="row" data-group="{{ $module }}">
                                @php
                                    // Sort permissions within module by action type
                                    $sortedPermissions = $groupPermissions->sortBy(function($permission) {
                                        $actionOrder = ['view', 'create', 'update', 'edit', 'delete', 'manage', 'export', 'import', 'bulk', 'sync', 'access'];
                                        $action = explode('-', $permission->name)[0];
                                        $index = array_search($action, $actionOrder);
                                        return $index !== false ? $index : 999;
                                    });
                                @endphp
                                @foreach($sortedPermissions as $permission)
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input permission-checkbox" 
                                               type="checkbox" 
                                               name="permissions[]" 
                                               value="{{ $permission->name }}" 
                                               id="permission_{{ $permission->id }}"
                                               data-group="{{ $module }}"
                                               {{ in_array($permission->name, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                            @php
                                                // Create readable permission name
                                                $parts = explode('-', $permission->name);
                                                $action = $parts[0];
                                                $actionNames = [
                                                    'view' => 'ดู',
                                                    'create' => 'สร้าง', 
                                                    'update' => 'แก้ไข',
                                                    'edit' => 'แก้ไข',
                                                    'delete' => 'ลบ',
                                                    'manage' => 'จัดการ',
                                                    'export' => 'ส่งออก',
                                                    'import' => 'นำเข้า',
                                                    'bulk' => 'จัดการกลุ่ม',
                                                    'sync' => 'ซิงค์',
                                                    'access' => 'เข้าถึง',
                                                    'send' => 'ส่ง',
                                                    'toggle' => 'เปิด/ปิด',
                                                    'reset' => 'รีเซ็ต',
                                                    'merge' => 'รวม',
                                                    'impersonate' => 'แสดงตัวแทน',
                                                    'generate' => 'สร้าง'
                                                ];
                                                $displayAction = $actionNames[$action] ?? ucfirst($action);
                                                
                                                // If permission has display_name, use it, otherwise create one
                                                if ($permission->display_name) {
                                                    echo $permission->display_name;
                                                } else {
                                                    echo $displayAction . ' ' . $moduleInfo['name'];
                                                }
                                            @endphp
                                            <small class="text-muted d-block">{{ $permission->name }}</small>
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
    updateSelectedCount();
    updateGroupCounters();
}

function clearAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    updateSelectedCount();
    updateGroupCounters();
}

function toggleGroup(groupName) {
    const groupCheckboxes = document.querySelectorAll(`[data-group="${groupName}"] .permission-checkbox`);
    const allChecked = Array.from(groupCheckboxes).every(cb => cb.checked);
    
    groupCheckboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
    updateSelectedCount();
    updateGroupCounters();
}

function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.permission-checkbox:checked').length;
    const badge = document.getElementById('selected-count');
    if (badge) {
        badge.textContent = `${selectedCount} สิทธิ์ที่เลือก`;
    }
}

function updateGroupCounters() {
    // Update counters for each group
    const groups = [...new Set(Array.from(document.querySelectorAll('.permission-checkbox')).map(cb => cb.dataset.group))];
    
    groups.forEach(group => {
        const groupCheckboxes = document.querySelectorAll(`[data-group="${group}"] .permission-checkbox`);
        const selectedInGroup = Array.from(groupCheckboxes).filter(cb => cb.checked).length;
        const totalInGroup = groupCheckboxes.length;
        
        const counter = document.querySelector(`[data-group-counter="${group}"]`);
        if (counter) {
            counter.textContent = `${selectedInGroup}/${totalInGroup}`;
            
            // Update counter color based on selection
            counter.className = 'badge me-2';
            if (selectedInGroup === 0) {
                counter.classList.add('bg-secondary');
            } else if (selectedInGroup === totalInGroup) {
                counter.classList.add('bg-success');
            } else {
                counter.classList.add('bg-warning', 'text-dark');
            }
        }
    });
}

// Update counts when checkboxes change
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();
            updateGroupCounters();
        });
    });
    
    // Initial update
    updateSelectedCount();
    updateGroupCounters();
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + A to select all
    if (e.ctrlKey && e.key === 'a' && e.target.type !== 'text' && e.target.type !== 'textarea') {
        e.preventDefault();
        selectAllPermissions();
    }
    
    // Ctrl + D to clear all
    if (e.ctrlKey && e.key === 'd') {
        e.preventDefault();
        clearAllPermissions();
    }
});

// Auto-save indicator (optional)
let hasChanges = false;
document.querySelectorAll('input, textarea, select').forEach(input => {
    input.addEventListener('change', function() {
        hasChanges = true;
    });
});

// Warn before leaving if there are unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Reset changes flag on form submission
document.querySelector('form').addEventListener('submit', function() {
    hasChanges = false;
});
</script>

<style>
/* Custom styles for better UX */
.permission-checkbox:checked + label {
    font-weight: 500;
    color: var(--bs-primary);
}

.form-check:hover {
    background-color: rgba(0, 123, 255, 0.05);
    border-radius: 4px;
}

.badge[data-group-counter] {
    transition: all 0.3s ease;
}

/* Highlight changed permissions */
.permission-checkbox:checked:not(:disabled) + label::after {
    content: " ✓";
    color: var(--bs-success);
    font-weight: bold;
}

/* Group headers */
.text-primary {
    position: relative;
}

.text-primary::before {
    content: "";
    position: absolute;
    left: -10px;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 20px;
    background: var(--bs-primary);
    border-radius: 2px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .col-md-6.col-lg-4 {
        margin-bottom: 0.75rem !important;
    }
}
</style>
@endsection