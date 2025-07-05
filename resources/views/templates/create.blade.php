@extends('layouts.app')

@section('title', 'สร้างเทมเพลตใหม่')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
.variable-badge {
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.variable-badge:hover {
    transform: scale(1.05);
    background-color: var(--bs-primary) !important;
    color: white !important;
}
.variable-badge:active {
    transform: scale(0.95);
}
.template-preview {
    max-height: 400px;
    overflow-y: auto;
}
.variable-helper {
    position: sticky;
    top: 20px;
}
.step-indicator {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
}
.step {
    flex: 1;
    text-align: center;
    position: relative;
}
.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 15px;
    right: -50%;
    width: 100%;
    height: 2px;
    background-color: #dee2e6;
    z-index: 0;
}
.step.active:not(:last-child)::after {
    background-color: #0d6efd;
}
.step-circle {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #dee2e6;
    color: #6c757d;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    position: relative;
    z-index: 1;
}
.step.active .step-circle {
    background-color: #0d6efd;
    color: white;
}
.step.completed .step-circle {
    background-color: #198754;
    color: white;
}
.quick-start-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.template-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}
.template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.border-dashed {
    border: 2px dashed #dee2e6 !important;
}
.content-tabs .nav-link {
    border-bottom: 2px solid transparent;
}
.content-tabs .nav-link.active {
    border-bottom-color: #0d6efd;
    background-color: transparent;
}
.syntax-highlight {
    background-color: #f8f9fa;
    border-left: 4px solid #0d6efd;
    padding: 0.75rem;
    border-radius: 0 0.25rem 0.25rem 0;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}
#content-preview {
    transition: all 0.3s ease;
}

/* Hide sections initially */
#templateGallery {
    display: none;
}
#mainForm {
    display: none;
}
#stepIndicator {
    display: none;
}

/* Thai font support */
body {
    font-family: 'Sarabun', 'Prompt', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.template-alert {
    font-family: 'Sarabun', sans-serif;
}

/* Channel indicators */
.channel-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.5rem;
    border-radius: 0.75rem;
    font-size: 0.75rem;
    font-weight: 500;
}
.channel-email {
    background-color: #e3f2fd;
    color: #1976d2;
}
.channel-teams {
    background-color: #f3e5f5;
    color: #7b1fa2;
}
.channel-sms {
    background-color: #e8f5e8;
    color: #388e3c;
}
</style>
@endpush

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        สร้างเทมเพลตใหม่
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('templates.index') }}">เทมเพลต</a></li>
                            <li class="breadcrumb-item active">สร้างใหม่</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>กลับสู่รายการเทมเพลต
                    </a>
                </div>
            </div>

            <!-- Quick Start Options -->
            <div id="quickStart">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card quick-start-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-rocket me-2"></i>เริ่มต้นอย่างรวดเร็ว
                                </h5>
                                <p class="card-text mb-3">เลือกวิธีที่คุณต้องการสร้างเทมเพลต:</p>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <button class="btn btn-light w-100" onclick="startFromScratch()">
                                            <i class="fas fa-file-plus text-primary me-2"></i>
                                            <div>
                                                <div class="fw-bold">เริ่มจากต้น</div>
                                                <small class="text-muted">สร้างเทมเพลตใหม่ทั้งหมด</small>
                                            </div>
                                        </button>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <button class="btn btn-light w-100" onclick="showTemplateGallery()">
                                            <i class="fas fa-th-large text-success me-2"></i>
                                            <div>
                                                <div class="fw-bold">ใช้เทมเพลตสำเร็จรูป</div>
                                                <small class="text-muted">เริ่มจากเทมเพลตที่กำหนดไว้</small>
                                            </div>
                                        </button>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <button class="btn btn-light w-100" onclick="importTemplate()">
                                            <i class="fas fa-upload text-info me-2"></i>
                                            <div>
                                                <div class="fw-bold">นำเข้าเทมเพลต</div>
                                                <small class="text-muted">อัปโหลดไฟล์เทมเพลตที่มีอยู่</small>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator" id="stepIndicator">
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label small mt-1">ข้อมูลพื้นฐาน</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label small mt-1">เนื้อหา</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label small mt-1">ตัวแปร</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-label small mt-1">ตัวอย่าง</div>
                </div>
            </div>

<!-- Template Gallery -->
<div id="templateGallery">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-th-large me-2"></i>แกลลอรี่เทมเพลต
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- System Alert Template -->
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card template-card h-100" onclick="useTemplate('system_alert')">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">การแจ้งเตือนระบบ</h6>
                                        <span class="badge bg-danger">ระบบ</span>
                                    </div>
                                    <p class="card-text small text-muted mb-3">การแจ้งเตือนระบบที่สำคัญและเหตุการณ์ฉุกเฉิน</p>
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <span class="channel-indicator channel-email">
                                            <i class="fas fa-envelope"></i>อีเมล
                                        </span>
                                        <span class="channel-indicator channel-teams">
                                            <i class="fab fa-microsoft"></i>Teams
                                        </span>
                                    </div>
                                    <div class="syntax-highlight small">
                                        หัวข้อ: [@{{priority}}] การแจ้งเตือนระบบ<br>
                                        ตัวแปร: priority, subject, message
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Marketing Email Template -->
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card template-card h-100" onclick="useTemplate('marketing_email')">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">อีเมลการตลาด</h6>
                                        <span class="badge bg-info">การตลาด</span>
                                    </div>
                                    <p class="card-text small text-muted mb-3">อีเมลส่งเสริมการขายและจดหมายข่าวสารที่มีดีไซน์สวยงาม</p>
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <span class="channel-indicator channel-email">
                                            <i class="fas fa-envelope"></i>อีเมล
                                        </span>
                                    </div>
                                    <div class="syntax-highlight small">
                                        หัวข้อ: @{{subject}} - @{{company}}<br>
                                        ตัวแปร: subject, message, company
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Meeting Reminder Template -->
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card template-card h-100" onclick="useTemplate('meeting_reminder')">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">เตือนการประชุม</h6>
                                        <span class="badge bg-success">ปฏิบัติการ</span>
                                    </div>
                                    <p class="card-text small text-muted mb-3">การแจ้งเตือนประชุมและการนัดหมาย</p>
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <span class="channel-indicator channel-email">
                                            <i class="fas fa-envelope"></i>อีเมล
                                        </span>
                                        <span class="channel-indicator channel-teams">
                                            <i class="fab fa-microsoft"></i>Teams
                                        </span>
                                    </div>
                                    <div class="syntax-highlight small">
                                        หัวข้อ: เตือนความจำ: @{{meeting_title}}<br>
                                        ตัวแปร: meeting_title, meeting_date
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Update Template -->
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card template-card h-100" onclick="useTemplate('status_update')">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">อัปเดตสถานะ</h6>
                                        <span class="badge bg-warning">ปฏิบัติการ</span>
                                    </div>
                                    <p class="card-text small text-muted mb-3">การอัปเดตสถานะโครงการและระบบ</p>
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <span class="channel-indicator channel-email">
                                            <i class="fas fa-envelope"></i>อีเมล
                                        </span>
                                        <span class="channel-indicator channel-teams">
                                            <i class="fab fa-microsoft"></i>Teams
                                        </span>
                                    </div>
                                    <div class="syntax-highlight small">
                                        หัวข้อ: อัปเดตสถานะ: @{{project_name}}<br>
                                        ตัวแปร: project_name, status, message
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Welcome Message Template -->
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card template-card h-100" onclick="useTemplate('welcome_message')">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">ข้อความต้อนรับ</h6>
                                        <span class="badge bg-primary">ปฏิบัติการ</span>
                                    </div>
                                    <p class="card-text small text-muted mb-3">ข้อความต้อนรับผู้ใช้ใหม่และการปฐมนิเทศ</p>
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <span class="channel-indicator channel-email">
                                            <i class="fas fa-envelope"></i>อีเมล
                                        </span>
                                        <span class="channel-indicator channel-teams">
                                            <i class="fab fa-microsoft"></i>Teams
                                        </span>
                                    </div>
                                    <div class="syntax-highlight small">
                                        หัวข้อ: ยินดีต้อนรับ @{{user_name}}!<br>
                                        ตัวแปร: user_name, welcome_message
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Template -->
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card template-card h-100 border-dashed" onclick="startFromScratch()">
                                <div class="card-body text-center d-flex flex-column justify-content-center">
                                    <i class="fas fa-plus-circle fa-3x text-muted mb-3"></i>
                                    <h6 class="card-title">สร้างเอง</h6>
                                    <p class="card-text small text-muted mb-0">เริ่มด้วยเทมเพลตเปล่า</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <button class="btn btn-secondary" onclick="hideTemplateGallery()">
                            <i class="fas fa-times me-2"></i>ยกเลิก
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Form -->
<div id="mainForm">
    <form action="{{ route('templates.store') }}" method="POST" id="templateForm">
        @csrf
        
        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
                <!-- Step 1: Basic Information -->
                <div class="form-step" data-step="1">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>ข้อมูลพื้นฐาน
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">ชื่อเทมเพลต <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required
                                           placeholder="ระบุชื่อเทมเพลต">
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">ชื่อที่จะใช้ในการอ้างอิงเทมเพลต</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                           id="slug" name="slug" value="{{ old('slug') }}"
                                           placeholder="สร้างอัตโนมัติจากชื่อ">
                                    <div class="form-text">ตัวระบุ URL (จะสร้างอัตโนมัติถ้าไม่ระบุ)</div>
                                    @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category') is-invalid @enderror" 
                                            id="category" name="category" required>
                                        <option value="">เลือกหมวดหมู่</option>
                                        <option value="system" {{ old('category') === 'system' ? 'selected' : '' }}>ระบบ</option>
                                        <option value="marketing" {{ old('category') === 'marketing' ? 'selected' : '' }}>การตลาด</option>
                                        <option value="operational" {{ old('category') === 'operational' ? 'selected' : '' }}>ปฏิบัติการ</option>
                                        <option value="emergency" {{ old('category') === 'emergency' ? 'selected' : '' }}>ฉุกเฉิน</option>
                                    </select>
                                    @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label">ระดับความสำคัญ <span class="text-danger">*</span></label>
                                    <select class="form-select @error('priority') is-invalid @enderror" 
                                            id="priority" name="priority" required>
                                        <option value="">เลือกระดับความสำคัญ</option>
                                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>ต่ำ</option>
                                        <option value="medium" {{ old('priority') === 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                        <option value="normal" {{ old('priority') === 'normal' ? 'selected' : '' }}>ปกติ</option>
                                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>สูง</option>
                                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                                    </select>
                                    @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">คำอธิบาย</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="3" 
                                          placeholder="คำอธิบายสั้นๆ เกี่ยวกับการใช้งานเทมเพลตนี้">{{ old('description') }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">ช่องทางที่รองรับ <span class="text-danger">*</span></label>
                                    <div class="mt-2">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input @error('supported_channels') is-invalid @enderror" 
                                                   type="checkbox" id="channel_email" name="supported_channels[]" value="email"
                                                   {{ in_array('email', old('supported_channels', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_email">
                                                <i class="fas fa-envelope me-1"></i>อีเมล
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input @error('supported_channels') is-invalid @enderror" 
                                                   type="checkbox" id="channel_teams" name="supported_channels[]" value="teams"
                                                   {{ in_array('teams', old('supported_channels', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_teams">
                                                <i class="fab fa-microsoft me-1"></i>Microsoft Teams
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input @error('supported_channels') is-invalid @enderror" 
                                                   type="checkbox" id="channel_sms" name="supported_channels[]" value="sms"
                                                   {{ in_array('sms', old('supported_channels', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_sms">
                                                <i class="fas fa-sms me-1"></i>SMS
                                            </label>
                                        </div>
                                    </div>
                                    @error('supported_channels')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">เลือกช่องทางการแจ้งเตือนอย่างน้อย 1 ช่องทาง</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" 
                                               id="is_active" name="is_active" value="1" 
                                               {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            <i class="fas fa-toggle-on me-1"></i>เทมเพลตที่ใช้งานได้
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

<!-- Step 2: Content -->
<div class="form-step" data-step="2" style="display: none;">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-file-text me-2"></i>เนื้อหาเทมเพลต
            </h5>
        </div>
        
        <div class="card-body">
            <!-- Subject Template -->
            <div class="mb-4">
                <label for="subject_template" class="form-label">หัวข้อเทมเพลต <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('subject_template') is-invalid @enderror" 
                       id="subject_template" name="subject_template" 
                       value="{{ old('subject_template') }}" 
                       placeholder="ระบุหัวข้อการแจ้งเตือน" required>
                <div class="form-text">ใช้ตัวแปรเช่น @{{user_name}} หรือ @{{message}} สำหรับเนื้อหาแบบไดนามิก</div>
                @error('subject_template')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Channel-specific content tabs -->
            <div class="mb-3">
                <ul class="nav nav-tabs content-tabs" id="contentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="html-tab" data-bs-toggle="tab" data-bs-target="#html-content" type="button" role="tab">
                            <i class="fas fa-code me-1"></i>เนื้อหา HTML
                            <span class="badge bg-primary ms-1">อีเมลเท่านั้น</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="text-tab" data-bs-toggle="tab" data-bs-target="#text-content" type="button" role="tab">
                            <i class="fas fa-file-text me-1"></i>เนื้อหาข้อความธรรมดา
                            <span class="badge bg-success ms-1">SMS & Teams & สำรอง</span>
                        </button>
                    </li>
                </ul>
                <div class="tab-content border border-top-0 p-3">
                    <!-- HTML Content -->
                    <div class="tab-pane fade show active" id="html-content" role="tabpanel">
                        <label for="body_html_template" class="form-label">เทมเพลต HTML</label>
                        <textarea class="form-control @error('body_html_template') is-invalid @enderror" 
                                  id="body_html_template" name="body_html_template" rows="15">{{ old('body_html_template') }}</textarea>
                        <div class="form-text">
                            สำหรับอีเมลเท่านั้น สามารถใช้ HTML tags และ inline CSS ได้
                            <a href="#" onclick="showHtmlExamples()" class="ms-2">ดูตัวอย่าง</a>
                        </div>
                        @error('body_html_template')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Text Content -->
                    <div class="tab-pane fade" id="text-content" role="tabpanel">
                        <label for="body_text_template" class="form-label">เทมเพลตข้อความธรรมดา</label>
                        <textarea class="form-control @error('body_text_template') is-invalid @enderror" 
                                  id="body_text_template" name="body_text_template" rows="12" 
                                  placeholder="เนื้อหาข้อความธรรมดาสำหรับ SMS, Teams และการสำรอง">{{ old('body_text_template') }}</textarea>
                        <div class="form-text">
                            สำหรับ SMS และข้อความธรรมดา ใช้ข้อความสั้นๆ สำหรับ SMS (แนะนำ 160 ตัวอักษร)
                            <span id="text-char-count" class="badge bg-secondary ms-2">0 ตัวอักษร</span>
                        </div>
                        @error('body_text_template')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Content Tools -->
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-outline-info btn-sm" onclick="validateTemplate()">
                    <i class="fas fa-check-circle me-1"></i>ตรวจสอบ Syntax
                </button>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="generateSampleContent()">
                    <i class="fas fa-magic me-1"></i>สร้างตัวอย่าง
                </button>
                <button type="button" class="btn btn-outline-warning btn-sm" onclick="previewContent()">
                    <i class="fas fa-eye me-1"></i>ดูตัวอย่างด่วน
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearContent()">
                    <i class="fas fa-eraser me-1"></i>ล้างทั้งหมด
                </button>
            </div>
            <div id="validation-result" class="mt-2"></div>
            <div id="content-preview" class="mt-2"></div>
        </div>
    </div>
</div>

<!-- Step 3: Variables -->
<div class="form-step" data-step="3" style="display: none;">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-tags me-2"></i>การกำหนดค่าตัวแปร
            </h5>
        </div>
        <div class="card-body">
            <!-- Auto-detected Variables -->
            <div id="detected-variables-section" class="mb-4" style="display: none;">
                <h6 class="text-info">
                    <i class="fas fa-search me-1"></i>ตัวแปรที่ตรวจพบ
                </h6>
                <div id="detected-variables-list" class="d-flex flex-wrap gap-1 mb-2"></div>
                <small class="text-muted">ตัวแปรที่พบในเนื้อหาเทมเพลต คลิกเพื่อเพิ่มไปยังตัวแปรที่จำเป็น</small>
            </div>

            <!-- Required Variables -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">ตัวแปรที่จำเป็น</label>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addVariable()">
                        <i class="fas fa-plus me-1"></i>เพิ่มตัวแปร
                    </button>
                </div>
                <div id="variables-container">
                    <!-- Variables will be added here dynamically -->
                </div>
                <div class="form-text">ระบุตัวแปรที่ต้องมีข้อมูลเมื่อส่งการแจ้งเตือน</div>
            </div>

            <!-- Default Variables -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label for="default_variables_json" class="form-label mb-0">ตัวแปรเริ่มต้น (รูปแบบ JSON)</label>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="generateDefaultVariablesJSON()">
                            <i class="fas fa-magic me-1"></i>สร้างอัตโนมัติ
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="formatJSON()">
                            <i class="fas fa-code me-1"></i>จัดรูปแบบ JSON
                        </button>
                    </div>
                </div>
                <textarea class="form-control @error('default_variables') is-invalid @enderror" 
                          id="default_variables_json" name="default_variables_json" rows="8" 
                          placeholder='{"ชื่อตัวแปร": "ค่าเริ่มต้น", "user_name": "ผู้ใช้ตัวอย่าง", "message": "ข้อความตัวอย่าง"}'>{{ old('default_variables_json') }}</textarea>
                @error('default_variables')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">JSON object (ไม่ใช่ array) สำหรับค่าเริ่มต้นของตัวแปร จะใช้เมื่อไม่มีการระบุตัวแปร</div>
            </div>

            <!-- Variable Validation -->
            <div class="mb-3">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="validateVariables()">
                    <i class="fas fa-check-circle me-1"></i>ตรวจสอบตัวแปร
                </button>
                <div id="variable-validation-result" class="mt-2"></div>
            </div>
        </div>
    </div>
</div>

<!-- Step 4: Preview & Save -->
<div class="form-step" data-step="4" style="display: none;">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-eye me-2"></i>ตัวอย่างและบันทึก
            </h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6><i class="fas fa-cog me-1"></i>ทดสอบด้วยข้อมูลตัวอย่าง</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadSampleDataForPreview()">
                        <i class="fas fa-play me-1"></i>โหลดข้อมูลตัวอย่าง
                    </button>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="refreshPreview()">
                        <i class="fas fa-sync me-1"></i>รีเฟรชตัวอย่าง
                    </button>
                </div>
            </div>
            
            <div id="final-preview">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="mt-2 text-muted">กำลังโหลดตัวอย่าง...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Buttons -->
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary" id="prevBtn" onclick="previousStep()" style="display: none;">
                <i class="fas fa-arrow-left me-2"></i>ก่อนหน้า
            </button>
            <div class="ms-auto d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()" id="draftBtn">
                    <i class="fas fa-save me-2"></i>บันทึกแบบร่าง
                </button>
                <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">
                    ถัดไป<i class="fas fa-arrow-right ms-2"></i>
                </button>
                <button type="submit" class="btn btn-success" id="saveBtn" style="display: none;">
                    <i class="fas fa-check me-2"></i>สร้างเทมเพลต
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Sidebar -->
<div class="col-lg-4">
    <!-- Progress Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="fas fa-tasks me-2"></i>ความคืบหน้าการสร้าง
            </h6>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span>ความคืบหน้า</span>
                    <span id="progress-percentage">25%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 25%"></div>
                </div>
            </div>
            
            <div class="small">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-check-circle text-success me-2" id="step1-check" style="display: none;"></i>
                    <i class="fas fa-circle text-muted me-2" id="step1-pending"></i>
                    <span>ข้อมูลพื้นฐาน</span>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-check-circle text-success me-2" id="step2-check" style="display: none;"></i>
                    <i class="fas fa-circle text-muted me-2" id="step2-pending"></i>
                    <span>เนื้อหาเทมเพลต</span>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-check-circle text-success me-2" id="step3-check" style="display: none;"></i>
                    <i class="fas fa-circle text-muted me-2" id="step3-pending"></i>
                    <span>การตั้งค่าตัวแปร</span>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle text-success me-2" id="step4-check" style="display: none;"></i>
                    <i class="fas fa-circle text-muted me-2" id="step4-pending"></i>
                    <span>ตรวจสอบและบันทึก</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Variable Helper -->
    <div class="card variable-helper">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="fas fa-code me-2"></i>ตัวแปรที่มีให้ใช้
            </h6>
        </div>
        
        <div class="card-body">
            <p class="small text-muted mb-3">คลิกเพื่อแทรกตัวแปรเข้าในเนื้อหา</p>
            
            <div class="mb-3">
                <h6 class="small fw-bold text-uppercase">ตัวแปรผู้ใช้</h6>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge bg-info text-white variable-badge" data-variable="user_name" title="ชื่อเต็มของผู้ใช้">@{{user_name}}</span>
                    <span class="badge bg-info text-white variable-badge" data-variable="user_email" title="ที่อยู่อีเมลของผู้ใช้">@{{user_email}}</span>
                    <span class="badge bg-info text-white variable-badge" data-variable="user_first_name" title="ชื่อแรกของผู้ใช้">@{{user_first_name}}</span>
                    <span class="badge bg-info text-white variable-badge" data-variable="user_last_name" title="นามสกุลของผู้ใช้">@{{user_last_name}}</span>
                    <span class="badge bg-info text-white variable-badge" data-variable="user_department" title="แผนกของผู้ใช้">@{{user_department}}</span>
                    <span class="badge bg-info text-white variable-badge" data-variable="user_title" title="ตำแหน่งงานของผู้ใช้">@{{user_title}}</span>
                </div>
                <small class="text-muted">ข้อมูลสดจาก LDAP/AD</small>
            </div>

            <div class="mb-3">
                <h6 class="small fw-bold text-uppercase">ตัวแปรระบบ</h6>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge bg-success text-white variable-badge" data-variable="current_date" title="วันที่ปัจจุบัน">@{{current_date}}</span>
                    <span class="badge bg-success text-white variable-badge" data-variable="current_time" title="เวลาปัจจุบัน">@{{current_time}}</span>
                    <span class="badge bg-success text-white variable-badge" data-variable="current_datetime" title="วันที่และเวลาปัจจุบัน">@{{current_datetime}}</span>
                    <span class="badge bg-success text-white variable-badge" data-variable="app_name" title="ชื่อแอปพลิเคชัน">@{{app_name}}</span>
                    <span class="badge bg-success text-white variable-badge" data-variable="app_url" title="URL แอปพลิเคชัน">@{{app_url}}</span>
                    <span class="badge bg-success text-white variable-badge" data-variable="system_name" title="ชื่อระบบ">@{{system_name}}</span>
                    <span class="badge bg-success text-white variable-badge" data-variable="year" title="ปีปัจจุบัน">@{{year}}</span>
                    <span class="badge bg-success text-white variable-badge" data-variable="month" title="เดือนปัจจุบัน">@{{month}}</span>
                    <span class="badge bg-success text-white variable-badge" data-variable="day" title="วันปัจจุบัน">@{{day}}</span>
                </div>
                <small class="text-muted">เติมข้อมูลอัตโนมัติ</small>
            </div>

            <div class="mb-3">
                <h6 class="small fw-bold text-uppercase">ตัวแปรการแจ้งเตือน</h6>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge bg-secondary text-white variable-badge" data-variable="notification_id" title="รหัสการแจ้งเตือน">@{{notification_id}}</span>
                    <span class="badge bg-secondary text-white variable-badge" data-variable="subject" title="หัวข้อ">@{{subject}}</span>
                    <span class="badge bg-secondary text-white variable-badge" data-variable="priority" title="ระดับความสำคัญ">@{{priority}}</span>
                </div>
                <small class="text-muted">ข้อมูลจากการแจ้งเตือน</small>
            </div>

            <div class="mb-3">
                <h6 class="small fw-bold text-uppercase">ตัวแปรกำหนดเอง</h6>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge bg-warning text-dark variable-badge" data-variable="message" title="เนื้อหาข้อความกำหนดเอง">@{{message}}</span>
                    <span class="badge bg-warning text-dark variable-badge" data-variable="url" title="URL กำหนดเอง">@{{url}}</span>
                    <span class="badge bg-warning text-dark variable-badge" data-variable="action_url" title="URL สำหรับปุ่ม">@{{action_url}}</span>
                    <span class="badge bg-warning text-dark variable-badge" data-variable="action_text" title="ข้อความปุ่ม">@{{action_text}}</span>
                    <span class="badge bg-warning text-dark variable-badge" data-variable="company" title="ชื่อบริษัท">@{{company}}</span>
                    <span class="badge bg-warning text-dark variable-badge" data-variable="status" title="สถานะ">@{{status}}</span>
                    <span class="badge bg-warning text-dark variable-badge" data-variable="deadline" title="กำหนดเวลา">@{{deadline}}</span>
                    <span class="badge bg-warning text-dark variable-badge" data-variable="amount" title="จำนวนเงิน">@{{amount}}</span>
                </div>
                <small class="text-muted">ตัวแปรที่ผู้ใช้กำหนด</small>
            </div>

            <div class="mb-3">
                <h6 class="small fw-bold text-uppercase">ตัวแปรการประชุม</h6>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge bg-primary text-white variable-badge" data-variable="meeting_title" title="หัวข้อประชุม">@{{meeting_title}}</span>
                    <span class="badge bg-primary text-white variable-badge" data-variable="meeting_date" title="วันที่ประชุม">@{{meeting_date}}</span>
                    <span class="badge bg-primary text-white variable-badge" data-variable="meeting_time" title="เวลาประชุม">@{{meeting_time}}</span>
                    <span class="badge bg-primary text-white variable-badge" data-variable="meeting_location" title="สถานที่ประชุม">@{{meeting_location}}</span>
                    <span class="badge bg-primary text-white variable-badge" data-variable="agenda" title="วาระการประชุม">@{{agenda}}</span>
                </div>
                <small class="text-muted">สำหรับเทมเพลตการประชุม</small>
            </div>

            <div class="mb-3">
                <h6 class="small fw-bold text-uppercase">ตัวแปรโครงการ</h6>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge bg-success text-white variable-badge" data-variable="project_name" title="ชื่อโครงการ">@{{project_name}}</span>
                    <span class="badge bg-success text-white variable-badge" data-variable="progress" title="ความคืบหน้า">@{{progress}}</span>
                    <span class="badge bg-success text-white variable-badge" data-variable="updated_by" title="อัปเดตโดย">@{{updated_by}}</span>
                </div>
                <small class="text-muted">สำหรับเทมเพลตอัปเดตสถานะ</small>
            </div>

            <div class="mb-3">
                <h6 class="small fw-bold text-uppercase">เพิ่มตัวแปรกำหนดเอง</h6>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="customVariable" placeholder="ชื่อตัวแปร">
                    <button class="btn btn-outline-primary" type="button" onclick="addCustomVariableFunction()">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <small class="text-muted">ระบุชื่อตัวแปรและคลิก + เพื่อเพิ่ม</small>
            </div>

            <hr>

            <div>
                <h6 class="small fw-bold text-uppercase">คู่มือการใช้งาน</h6>
                <div class="small">
                    <p class="mb-1"><code>@{{ตัวแปร}}</code> - ตัวแปรทั่วไป</p>
                    <p class="mb-1"><code>@{{#if ตัวแปร}}ข้อความ@{{/if}}</code> - เงื่อนไข</p>
                    <p class="mb-1"><code>@{{#each รายการ}}@{{this}}@{{/each}}</code> - วนซ้ำ</p>
                    <p class="mb-0"><code>@{{ตัวแปร|ค่าเริ่มต้น}}</code> - ค่าเริ่มต้น</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</form>

</div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="fas fa-eye me-2"></i>ตัวอย่างเทมเพลต
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">กำลังโหลดตัวอย่าง...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-primary" onclick="refreshPreview()">
                    <i class="fas fa-sync me-2"></i>รีเฟรชตัวอย่าง
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Variable Helper Modal -->
<div class="modal fade" id="variableHelperModal" tabindex="-1" aria-labelledby="variableHelperModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="variableHelperModalLabel">
                    <i class="fas fa-code me-2"></i>ตัวแปรที่มีให้ใช้
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>ตัวแปรระบบ</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ตัวแปร</th>
                                        <th>คำอธิบาย</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>@{{user_name}}</code></td>
                                        <td>ชื่อเต็มของผู้ใช้</td>
                                    </tr>
                                    <tr>
                                        <td><code>@{{user_email}}</code></td>
                                        <td>อีเมลของผู้ใช้</td>
                                    </tr>
                                    <tr>
                                        <td><code>@{{current_date}}</code></td>
                                        <td>วันที่ปัจจุบัน</td>
                                    </tr>
                                    <tr>
                                        <td><code>@{{current_time}}</code></td>
                                        <td>เวลาปัจจุบัน</td>
                                    </tr>
                                    <tr>
                                        <td><code>@{{app_name}}</code></td>
                                        <td>ชื่อแอปพลิเคชัน</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>ตัวแปรกำหนดเอง</h6>
                        <div id="customVariablesList">
                            <p class="text-muted">เพิ่มตัวแปรกำหนดเองในขั้นตอนที่ 3 เพื่อดูที่นี่</p>
                        </div>
                    </div>
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>

{{-- Load Thai JavaScript modules --}}
<script type="module">
    // Import the new Thai modules
    import '{{ asset('js/template-gallery.js') }}';
    import '{{ asset('js/system-variables.js') }}';
    import '{{ asset('js/template-utils.js') }}';
    import '{{ asset('js/template-creator-thai.js') }}';
</script>

{{-- Fallback for non-module browsers --}}
<script>
    // If modules are not supported, load as regular scripts
    if (!window.TemplateCreator) {
        const scripts = [
            '{{ asset('js/template-gallery.js') }}',
            '{{ asset('js/system-variables.js') }}',
            '{{ asset('js/template-utils.js') }}',
            '{{ asset('js/template-creator-thai.js') }}'
        ];
        
        let loadedScripts = 0;
        scripts.forEach((src, index) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = () => {
                loadedScripts++;
                if (loadedScripts === scripts.length) {
                    console.log('All template scripts loaded successfully');
                }
            };
            script.onerror = () => {
                console.error('Failed to load script:', src);
            };
            document.head.appendChild(script);
        });
    }
</script>

{{-- Additional helper functions --}}
<script>
// Show HTML examples
function showHtmlExamples() {
    const examples = `
รูปแบบ HTML ที่ใช้บ่อย:

1. หัวข้อ:
   <h1>หัวข้อหลัก</h1>
   <h2>หัวข้อรอง</h2>

2. ย่อหน้า:
   <p>ข้อความของคุณที่นี่</p>

3. ลิงก์:
   <a href="@{{url}}">คลิกที่นี่</a>

4. ตัวหนา/ตัวเอียง:
   <strong>ตัวหนา</strong>
   <em>ตัวเอียง</em>

5. รายการ:
   <ul>
     <li>รายการที่ 1</li>
     <li>รายการที่ 2</li>
   </ul>

6. กล่องข้อความ:
   <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
     เนื้อหาเด่น
   </div>

7. ตาราง:
   <table style="width: 100%; border-collapse: collapse;">
     <tr>
       <td style="padding: 5px; border: 1px solid #ddd;">ข้อมูล 1</td>
       <td style="padding: 5px; border: 1px solid #ddd;">ข้อมูล 2</td>
     </tr>
   </table>

8. ปุ่ม:
   <a href="@{{action_url}}" style="display: inline-block; background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">@{{action_text}}</a>
`;
    
    alert(examples);
}

// Initialize Select2 if available
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
    
    // Initialize tooltips
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// Global error handler
window.addEventListener('error', function(e) {
    console.error('Template Creator Error:', e.error);
});

// Performance monitoring
window.addEventListener('load', function() {
    console.log('Template Creator page loaded in:', performance.now().toFixed(2), 'ms');
});
</script>

@endpush



