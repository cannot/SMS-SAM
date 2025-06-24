@extends('layouts.app')

@section('title', 'จัดการบทบาท (Roles)')

@push('styles')
<style>

</style>
@endpush

@section('content')
<div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2><i class="fas fa-user-shield me-3"></i>จัดการบทบาท (Roles Management)</h2>
                        <p class="mb-0">Manage roles</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="btn-group" role="group">
                            @can('create-roles')
                            <a href="{{ route('roles.create') }}" class="btn btn-outline-light btn-sm">
                                <i class="bi bi-plus-circle me-1"></i> สร้างบทบาทใหม่
                            </a>
                            @endcan
                            
                            @can('export-roles')
                            <a href="{{ route('roles.export') }}" class="btn btn-outline-light btn-sm">
                                <i class="bi bi-download me-1"></i> Export
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        {{-- <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">บทบาท</li>
                </ol>
            </nav>
        </div> --}}

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-3 p-3">
                                <i class="fas fa-shield-alt text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title text-muted mb-1">รวมบทบาท</h6>
                            <h4 class="mb-0">{{ $stats['total_roles'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient rounded-3 p-3">
                                <i class="fas fa-users text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title text-muted mb-1">บทบาทที่ใช้งาน</h6>
                            <h4 class="mb-0">{{ $stats['roles_with_users'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient rounded-3 p-3">
                                <i class="fas fa-key text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title text-muted mb-1">รวมสิทธิ์</h6>
                            <h4 class="mb-0">{{ $stats['total_permissions'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-gradient rounded-3 p-3">
                                <i class="fas fa-exclamation text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title text-muted mb-1">ไม่ได้ใช้งาน</h6>
                            <h4 class="mb-0">{{ $stats['unused_roles'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('roles.index') }}" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">ค้นหา</label>
                    <input type="text" 
                           class="form-control" 
                           id="search" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="ชื่อบทบาท...">
                </div>
                
                <div class="col-md-3">
                    <label for="sort_by" class="form-label">เรียงตาม</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="display_name" {{ request('sort_by') == 'display_name' ? 'selected' : '' }}>ชื่อแสดง</option>
                        <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>ชื่อระบบ</option>
                        <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>วันที่สร้าง</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="sort_direction" class="form-label">ทิศทาง</label>
                    <select class="form-select" id="sort_direction" name="sort_direction">
                        <option value="asc" {{ request('sort_direction') == 'asc' ? 'selected' : '' }}>น้อยไปมาก</option>
                        <option value="desc" {{ request('sort_direction') == 'desc' ? 'selected' : '' }}>มากไปน้อย</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> ค้นหา
                    </button>
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> ล้าง
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Roles Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">รายการบทบาท</h5>
            <small class="text-muted">ทั้งหมด {{ $roles->total() }} รายการ</small>
        </div>
        <div class="card-body p-0">
            @if($roles->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>บทบาท</th>
                            <th>คำอธิบาย</th>
                            <th class="text-center">ผู้ใช้</th>
                            <th class="text-center">สิทธิ์</th>
                            <th class="text-center">สถานะ</th>
                            <th width="150">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary bg-gradient rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
                                            <i class="fas fa-shield-alt text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">{{ $role->display_name }}</h6>
                                        <small class="text-muted">{{ $role->name }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted">{{ Str::limit($role->description, 60) ?: 'ไม่มีคำอธิบาย' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $role->users_count ?? 0 }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success">{{ $role->permissions_count ?? 0 }}</span>
                            </td>
                            <td class="text-center">
                                @if($role->users_count > 0)
                                    <span class="badge bg-success">ใช้งาน</span>
                                @else
                                    <span class="badge bg-secondary">ไม่ได้ใช้</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    @can('view-roles')
                                    <a href="{{ route('roles.show', $role) }}" 
                                       class="btn btn-outline-info" 
                                       title="ดูรายละเอียด">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @endcan
                                    
                                    @can('edit-roles')
                                    <a href="{{ route('roles.edit', $role) }}" 
                                       class="btn btn-outline-warning" 
                                       title="แก้ไข">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    
                                    @can('create-roles')
                                    <a href="{{ route('roles.clone', $role) }}" 
                                       class="btn btn-outline-secondary" 
                                       title="คัดลอก"
                                       onclick="return confirm('คุณต้องการคัดลอกบทบาทนี้หรือไม่?')">
                                        <i class="bi bi-files"></i>
                                    </a>
                                    @endcan
                                    
                                    @can('delete-roles')
                                    @if(!in_array($role->name, ['admin', 'super-admin']) && $role->users_count == 0)
                                    <form method="POST" 
                                          action="{{ route('roles.destroy', $role) }}" 
                                          class="d-inline"
                                          onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบบทบาทนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-outline-danger" 
                                                title="ลบ">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                <h5>ไม่พบข้อมูลบทบาท</h5>
                <p class="text-muted">ไม่มีบทบาทที่ตรงกับเงื่อนไขการค้นหา</p>
                @can('create-roles')
                <a href="{{ route('roles.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> สร้างบทบาทแรก
                </a>
                @endcan
            </div>
            @endif
        </div>
        
        @if($roles->hasPages())
        <div class="card-footer">
            {{ $roles->links() }}
        </div>
        @endif
    </div>
</div>
@endsection