@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">ยินดีต้อนรับ</h5>
            </div>
            <div class="card-body">
                <p>สวัสดี <strong>{{ $user->display_name ?? $user->username }}</strong></p>
                <p>ยินดีต้อนรับสู่ Smart Notification System</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">ข้อมูลผู้ใช้</h5>
            </div>
            <div class="card-body">
                <p><strong>อีเมล:</strong> {{ $user->email ?? 'ไม่ระบุ' }}</p>
                <p><strong>แผนก:</strong> {{ $user->department ?? 'ไม่ระบุ' }}</p>
                <p><strong>ซิงค์ล่าสุด:</strong> {{ $user->ldap_synced_at ? $user->ldap_synced_at->format('d/m/Y H:i') : 'ไม่ระบุ' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection