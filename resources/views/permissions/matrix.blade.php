@extends('layouts.app')

@section('title', 'Permission Matrix')

@push('styles')
<style>
.permission-matrix {
    font-size: 0.85rem;
}

.table th {
    background-color: #f8f9fa;
    border-top: 2px solid #dee2e6;
    vertical-align: middle;
    text-align: center;
    font-weight: 600;
}

.table td {
    text-align: center;
    vertical-align: middle;
}

.permission-granted {
    color: #198754;
    font-size: 1.2rem;
}

.permission-denied {
    color: #dc3545;
    font-size: 1.2rem;
}

.permission-limited {
    color: #fd7e14;
    font-size: 1.2rem;
}

.module-header {
    background-color: #e9ecef;
    font-weight: bold;
}

.role-header {
    writing-mode: vertical-rl;
    text-orientation: mixed;
    min-width: 120px;
    font-size: 0.8rem;
}

.feature-name {
    text-align: left;
    padding-left: 20px;
}

.legend {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.api-section {
    margin-top: 2rem;
}

.table-responsive {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.375rem;
}

.sticky-header {
    position: sticky;
    top: 0;
    z-index: 10;
}

.role-description {
    background-color: #f8f9fa;
    border-left: 4px solid #0d6efd;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    border-radius: 0 0.375rem 0.375rem 0;
}

.role-description h6 {
    margin-bottom: 0.25rem;
    color: #0d6efd;
}

.role-description small {
    color: #6c757d;
}

.matrix-nav {
    background: rgba(101, 209, 181, 0.1);
    border: 1px solid var(--light-green);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.section-anchor {
    scroll-margin-top: 100px;
}

/* Print styles */
@media print {
    .btn, .modal, .card-header, .matrix-nav {
        display: none !important;
    }
    
    .table {
        font-size: 10px !important;
    }
    
    .permission-granted, .permission-denied, .permission-limited {
        font-size: 14px !important;
    }
    
    @page {
        size: A4 landscape;
        margin: 1cm;
    }
    
    body {
        font-size: 12px;
    }
    
    .container-fluid {
        padding: 0 !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .table-responsive {
        overflow: visible !important;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item active">Permission Matrix</li>
                </ol>
            </nav>
            <h2><i class="fas fa-shield-alt text-primary"></i> Permission Matrix</h2>
            <p class="text-muted">Smart Notification System - ระบบจัดการสิทธิ์การเข้าถึง</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> พิมพ์
                </button>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#roleModal">
                    <i class="fas fa-info-circle me-1"></i> คำอธิบายบทบาท
                </button>
                <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> กลับ
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Navigation -->
    <div class="matrix-nav">
        <h6 class="mb-3"><i class="fas fa-compass me-2"></i>เมนูด่วน</h6>
        <div class="row">
            <div class="col-md-6">
                <div class="list-group list-group-horizontal-md">
                    <a href="#matrix-table" class="list-group-item list-group-item-action">
                        <i class="fas fa-table me-1"></i> ตาราง Matrix
                    </a>
                    <a href="#api-permissions" class="list-group-item list-group-item-action">
                        <i class="fas fa-plug me-1"></i> API Permissions
                    </a>
                    <a href="#security" class="list-group-item list-group-item-action">
                        <i class="fas fa-lock me-1"></i> Security
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end align-items-center">
                    <small class="text-muted me-3">
                        <i class="fas fa-calendar me-1"></i>
                        อัพเดทล่าสุด: {{ now()->format('d M Y H:i') }}
                    </small>
                    @can('edit-permissions')
                        <a href="{{ route('permissions.create') }}" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i> สร้าง Permission
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="legend">
        <h6 class="mb-3"><i class="fas fa-key me-2"></i>สัญลักษณ์</h6>
        <div class="row">
            <div class="col-md-4">
                <span class="permission-granted"><i class="fas fa-check-circle"></i></span>
                <span class="ms-2">เข้าถึงได้เต็มสิทธิ์</span>
            </div>
            <div class="col-md-4">
                <span class="permission-denied"><i class="fas fa-times-circle"></i></span>
                <span class="ms-2">ไม่สามารถเข้าถึงได้</span>
            </div>
            <div class="col-md-4">
                <span class="permission-limited"><i class="fas fa-lock"></i></span>
                <span class="ms-2">เข้าถึงได้เฉพาะข้อมูลของตนเอง</span>
            </div>
        </div>
    </div>

    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-2">
                    <h5 class="mb-0">{{ \Spatie\Permission\Models\Role::count() }}</h5>
                    <small>Roles</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-2">
                    <h5 class="mb-0">{{ \Spatie\Permission\Models\Permission::count() }}</h5>
                    <small>Permissions</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center py-2">
                    <h5 class="mb-0">{{ \App\Models\User::count() }}</h5>
                    <small>Users</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
            <div class="card bg-warning text-white">
                <div class="card-body text-center py-2">
                    <h5 class="mb-0">{{ \App\Models\ApiKey::count() }}</h5>
                    <small>API Keys</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
            <div class="card text-white" style="background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);">
                <div class="card-body text-center py-2">
                    <h5 class="mb-0">7</h5>
                    <small>Modules</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center py-2">
                    <h5 class="mb-0">{{ \Spatie\Permission\Models\Permission::whereHas('roles')->count() }}</h5>
                    <small>Assigned</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Permission Matrix Table -->
    <div id="matrix-table" class="section-anchor">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>ตาราง Permission Matrix</h5>
                    <span class="badge bg-light text-primary">{{ \Spatie\Permission\Models\Permission::count() }} permissions</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover permission-matrix mb-0">
                        <thead class="sticky-header">
                            <tr>
                                <th rowspan="2" class="align-middle" style="min-width: 250px;">
                                    <i class="fas fa-cog me-2"></i>โมดูล/ฟีเจอร์
                                </th>
                                <th colspan="5" class="bg-primary text-white">บทบาทผู้ใช้งาน</th>
                            </tr>
                            <tr>
                                <th class="role-header bg-danger text-white">
                                    <i class="fas fa-user-shield"></i><br>System<br>Admin
                                </th>
                                <th class="role-header bg-warning text-dark">
                                    <i class="fas fa-key"></i><br>API<br>Admin
                                </th>
                                <th class="role-header bg-info text-white">
                                    <i class="fas fa-bell"></i><br>Notification<br>Manager
                                </th>
                                <th class="role-header bg-success text-white">
                                    <i class="fas fa-tools"></i><br>IT<br>Support
                                </th>
                                <th class="role-header bg-secondary text-white">
                                    <i class="fas fa-user"></i><br>End<br>User
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- User Management -->
                            <tr class="module-header">
                                <td colspan="6">
                                    <i class="fas fa-users me-2"></i><strong>การจัดการผู้ใช้ (User Management)</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดูข้อมูลผู้ใช้ทั้งหมด</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">สร้าง/แก้ไข/ลบผู้ใช้</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ซิงค์ข้อมูลจาก LDAP</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดูการตั้งค่าผู้ใช้</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-lock permission-limited"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">แก้ไขการตั้งค่าผู้ใช้</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-lock permission-limited"></i></td>
                            </tr>

                            <!-- Notification Management -->
                            <tr class="module-header">
                                <td colspan="6">
                                    <i class="fas fa-bell me-2"></i><strong>การจัดการการแจ้งเตือน (Notification Management)</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดูเทมเพลตการแจ้งเตือน</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">สร้างเทมเพลตการแจ้งเตือน</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">แก้ไขเทมเพลตการแจ้งเตือน</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ลบเทมเพลตการแจ้งเตือน</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ส่งการแจ้งเตือน</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">กำหนดตารางเวลาการแจ้งเตือน</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดูประวัติการแจ้งเตือน</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-lock permission-limited"></i></td>
                            </tr>

                            <!-- Group Management -->
                            <tr class="module-header">
                                <td colspan="6">
                                    <i class="fas fa-layer-group me-2"></i><strong>การจัดการกลุ่ม (Group Management)</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดูกลุ่มการแจ้งเตือน</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">สร้างกลุ่มการแจ้งเตือน</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">แก้ไขกลุ่มการแจ้งเตือน</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ลบกลุ่มการแจ้งเตือน</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">จัดการสมาชิกในกลุ่ม</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>

                            <!-- API Key Management -->
                            <tr class="module-header">
                                <td colspan="6">
                                    <i class="fas fa-key me-2"></i><strong>การจัดการ API Key (API Key Management)</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดู API Keys</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">สร้าง API Keys</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">แก้ไข API Keys</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ลบ/เพิกถอน API Keys</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">กำหนด API Rate Limits</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดู API Usage Logs</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>

                            <!-- System Monitoring -->
                            <tr class="module-header">
                                <td colspan="6">
                                    <i class="fas fa-chart-line me-2"></i><strong>การตรวจสอบระบบ (System Monitoring)</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดู System Logs</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดู Activity Logs</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดู Delivery Logs</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดู Failed Jobs</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">Retry Failed Jobs</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>

                            <!-- Reports & Analytics -->
                            <tr class="module-header">
                                <td colspan="6">
                                    <i class="fas fa-chart-bar me-2"></i><strong>รายงานและสถิติ (Reports & Analytics)</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดูรายงานการส่ง</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดูรายงานการใช้งาน API</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดูสถิติระบบ</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">Export รายงาน</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">ดู Dashboard</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                            </tr>

                            <!-- System Configuration -->
                            <tr class="module-header">
                                <td colspan="6">
                                    <i class="fas fa-cogs me-2"></i><strong>การตั้งค่าระบบ (System Configuration)</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="feature-name">จัดการการตั้งค่า LDAP</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">จัดการการตั้งค่า Email</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">จัดการการตั้งค่า Teams</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">จัดการการตั้งค่า Queue</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">บำรุงรักษาระบบ</td>
                                <td><i class="fas fa-check-circle permission-granted"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                                <td><i class="fas fa-times-circle permission-denied"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- API Permissions Section -->
    <div id="api-permissions" class="section-anchor api-section">
        <div class="card">
            <div class="card-header bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-plug me-2"></i>API Permissions สำหรับระบบภายนอก</h5>
                    <span class="badge bg-light text-success">7 endpoints</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 40%;">
                                    <i class="fas fa-link me-2"></i>API Endpoint
                                </th>
                                <th style="width: 25%;">
                                    <i class="fas fa-shield-alt me-2"></i>Required Permission
                                </th>
                                <th style="width: 35%;">
                                    <i class="fas fa-info-circle me-2"></i>คำอธิบาย
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code class="text-primary">POST /api/v1/notifications/send</code></td>
                                <td><span class="badge bg-warning text-dark">notifications:send</span></td>
                                <td>ส่งการแจ้งเตือนผ่าน API</td>
                            </tr>
                            <tr>
                                <td><code class="text-primary">POST /api/v1/notifications/bulk</code></td>
                                <td><span class="badge bg-warning text-dark">notifications:bulk</span></td>
                                <td>ส่งการแจ้งเตือนหลายรายการ</td>
                            </tr>
                            <tr>
                                <td><code class="text-primary">GET /api/v1/notifications/{id}/status</code></td>
                                <td><span class="badge bg-info">notifications:read</span></td>
                                <td>ตรวจสอบสถานะการส่ง</td>
                            </tr>
                            <tr>
                                <td><code class="text-primary">GET /api/v1/notifications/history</code></td>
                                <td><span class="badge bg-info">notifications:read</span></td>
                                <td>ดูประวัติการแจ้งเตือน</td>
                            </tr>
                            <tr>
                                <td><code class="text-primary">GET /api/v1/users</code></td>
                                <td><span class="badge bg-secondary">users:read</span></td>
                                <td>ดูรายการผู้ใช้จาก LDAP</td>
                            </tr>
                            <tr>
                                <td><code class="text-primary">GET /api/v1/groups</code></td>
                                <td><span class="badge bg-secondary">groups:read</span></td>
                                <td>ดูรายการกลุ่มการแจ้งเตือน</td>
                            </tr>
                            <tr>
                                <td><code class="text-primary">POST /api/v1/groups</code></td>
                                <td><span class="badge bg-success">groups:write</span></td>
                                <td>สร้างกลุ่มการแจ้งเตือน</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Implementation -->
    <div id="security" class="section-anchor mt-4">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-lock me-2"></i>การนำไปใช้งานด้านความปลอดภัย</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-user-check me-2"></i>Authentication
                        </h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-desktop text-info me-2"></i>
                                <strong>Web Interface:</strong> LDAP Authentication + JWT Token
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-code text-warning me-2"></i>
                                <strong>API:</strong> API Key Authentication + Rate Limiting
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-clock text-secondary me-2"></i>
                                <strong>Session Timeout:</strong> 8 ชั่วโมง
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-shield-alt me-2"></i>Authorization
                        </h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-users-cog text-success me-2"></i>
                                Role-based Access Control (RBAC)
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-key text-warning me-2"></i>
                                Permission-based Restrictions
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-code-branch text-info me-2"></i>
                                API Scope Limitations
                            </li>
                        </ul>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-clipboard-list me-2"></i>Audit Trail
                        </h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-history text-primary me-2"></i>
                                บันทึกทุกการกระทำที่สำคัญ
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-database text-secondary me-2"></i>
                                Log การเข้าถึงข้อมูล
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-chart-line text-info me-2"></i>
                                Track การใช้งาน API
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-lock me-2"></i>Data Security
                        </h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-encrypt text-success me-2"></i>
                                เข้ารหัสข้อมูลสำคัญใน Database
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-certificate text-warning me-2"></i>
                                HTTPS สำหรับการสื่อสาร
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-key text-danger me-2"></i>
                                API Key Encryption
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Implementation Notes -->
    <div class="mt-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-code me-2"></i>การนำไปใช้งานทางเทคนิค</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-layer-group me-2"></i>Middleware
                        </h6>
                        <div class="bg-light p-3 rounded">
                            <code class="text-dark d-block mb-2">LdapAuthMiddleware</code>
                            <small class="text-muted">สำหรับ Web Interface</small>
                            <hr>
                            <code class="text-dark d-block mb-2">ApiKeyMiddleware</code>
                            <small class="text-muted">สำหรับ API Authentication</small>
                            <hr>
                            <code class="text-dark d-block mb-2">RateLimitMiddleware</code>
                            <small class="text-muted">สำหรับ API Rate Limiting</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-database me-2"></i>Database Tables
                        </h6>
                        <div class="bg-light p-3 rounded">
                            <code class="text-dark d-block mb-1">roles</code>
                            <code class="text-dark d-block mb-1">permissions</code>
                            <code class="text-dark d-block mb-1">role_has_permissions</code>
                            <code class="text-dark d-block mb-1">model_has_roles</code>
                            <code class="text-dark d-block mb-1">api_keys</code>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-shield-alt me-2"></i>Guard Configuration
                        </h6>
                        <div class="bg-light p-3 rounded">
                            <code class="text-dark d-block mb-2">'web' => ['driver' => 'session']</code>
                            <small class="text-muted">LDAP Provider</small>
                            <hr>
                            <code class="text-dark d-block mb-2">'api' => ['driver' => 'api-key']</code>
                            <small class="text-muted">API Keys Provider</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Role Description Modal -->
<div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="roleModalLabel">
                    <i class="fas fa-users me-2"></i>คำอธิบายบทบาทผู้ใช้งาน
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="role-description">
                    <h6><i class="fas fa-user-shield text-danger me-2"></i>System Administrator</h6>
                    <small>ผู้ดูแลระบบที่มีสิทธิ์สูงสุดในการจัดการระบบทั้งหมด รวมถึงการตั้งค่าระบบ การจัดการผู้ใช้ และการบำรุงรักษา</small>
                </div>
                
                <div class="role-description">
                    <h6><i class="fas fa-key text-warning me-2"></i>API Administrator</h6>
                    <small>ผู้จัดการ API Keys และสิทธิการเข้าถึง API สำหรับระบบภายนอก รวมถึงการติดตามการใช้งาน API</small>
                </div>
                
                <div class="role-description">
                    <h6><i class="fas fa-bell text-info me-2"></i>Notification Manager</h6>
                    <small>ผู้จัดการสร้างและจัดการรูปแบบการแจ้งเตือน การส่งข้อความ และการจัดการกลุ่มผู้รับ</small>
                </div>
                
                <div class="role-description">
                    <h6><i class="fas fa-tools text-success me-2"></i>IT Support</h6>
                    <small>ผู้ตรวจสอบ logs แก้ไขปัญหา และให้การสนับสนุนทางเทคนิค รวมถึงการจัดการ failed jobs</small>
                </div>
                
                <div class="role-description">
                    <h6><i class="fas fa-user text-secondary me-2"></i>End User</h6>
                    <small>ผู้ใช้ทั่วไปที่รับการแจ้งเตือนและสามารถปรับแต่งการตั้งค่าส่วนตัวได้ เข้าถึงได้เฉพาะข้อมูลของตนเอง</small>
                </div>
                
                <div class="role-description">
                    <h6><i class="fas fa-code text-dark me-2"></i>API Consumer</h6>
                    <small>ระบบภายนอกที่เรียกใช้งานผ่าน API โดยไม่มีสิทธิ์ในหน้า Web Interface ต้องใช้ API Key ในการเข้าถึง</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Print functionality
window.addEventListener('beforeprint', function() {
    document.body.classList.add('printing');
});

window.addEventListener('afterprint', function() {
    document.body.classList.remove('printing');
});

// Smooth scrolling for internal links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add some interactivity
document.addEventListener('DOMContentLoaded', function() {
    // Highlight current section in navigation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                const navLink = document.querySelector(`a[href="#${id}"]`);
                if (navLink) {
                    // Remove active class from all nav links
                    document.querySelectorAll('.list-group-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    // Add active class to current nav link
                    navLink.classList.add('active');
                }
            }
        });
    }, {
        rootMargin: '-20% 0px -80% 0px'
    });

    // Observe all sections
    document.querySelectorAll('.section-anchor').forEach(section => {
        observer.observe(section);
    });

    // Permission icon hover effects
    document.querySelectorAll('.permission-granted, .permission-denied, .permission-limited').forEach(icon => {
        icon.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.2)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        icon.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Add tooltip to permission icons
    document.querySelectorAll('.permission-granted').forEach(icon => {
        icon.setAttribute('title', 'เข้าถึงได้เต็มสิทธิ์');
    });
    
    document.querySelectorAll('.permission-denied').forEach(icon => {
        icon.setAttribute('title', 'ไม่สามารถเข้าถึงได้');
    });
    
    document.querySelectorAll('.permission-limited').forEach(icon => {
        icon.setAttribute('title', 'เข้าถึงได้เฉพาะข้อมูลของตนเอง');
    });

    // Initialize Bootstrap tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// Export functionality (if needed)
function exportMatrix(format) {
    if (format === 'pdf') {
        window.print();
    } else if (format === 'excel') {
        // Implementation for Excel export
        console.log('Excel export functionality to be implemented');
    }
}

// Search functionality for the matrix (optional enhancement)
function searchMatrix(searchTerm) {
    const rows = document.querySelectorAll('.permission-matrix tbody tr');
    searchTerm = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm) || searchTerm === '') {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
@endpush