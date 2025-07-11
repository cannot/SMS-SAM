@extends('layouts.app')

@section('title', 'แก้ไขเทมเพลต - ' . $template->name)

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

.unsaved-changes-indicator {
    color: #dc3545;
    font-size: 0.875rem;
    display: none;
}

.edit-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

/* Initially show the main form for edit mode */
#mainForm {
    display: block;
}

#stepIndicator {
    display: flex;
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

{{-- Meta tags for JavaScript --}}
<meta name="template-id" content="{{ $template->id }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="update-route" content="{{ route('templates.update', $template->id) }}">

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-edit text-primary me-2"></i>
                        แก้ไขเทมเพลต
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('templates.index') }}">เทมเพลต</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('templates.show', $template->id) }}">{{ $template->name }}</a></li>
                            <li class="breadcrumb-item active">แก้ไข</li>
                        </ol>
                    </nav>
                    <div class="unsaved-changes-indicator mt-1" id="unsaved-changes-indicator">
                        <i class="fas fa-circle text-warning"></i> มีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('templates.show', $template->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>กลับสู่เทมเพลต
                    </a>
                    <button type="button" class="btn btn-outline-warning" id="resetBtn">
                        <i class="fas fa-undo me-2"></i>รีเซ็ตการเปลี่ยนแปลง
                    </button>
                </div>
            </div>

            <!-- Edit Info Banner -->
            <div class="alert alert-info d-flex align-items-center mb-4">
                <i class="fas fa-info-circle me-2"></i>
                <div>
                    <strong>กำลังแก้ไข: {{ $template->name }}</strong><br>
                    <small class="text-muted">สร้างเมื่อ: {{ $template->created_at->format('d M Y') }} | อัปเดตล่าสุด: {{ $template->updated_at->format('d M Y H:i') }}</small>
                </div>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator" id="stepIndicator">
                <div class="step completed" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label small mt-1">ข้อมูลพื้นฐาน</div>
                </div>
                <div class="step completed" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label small mt-1">เนื้อหา</div>
                </div>
                <div class="step completed" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label small mt-1">ตัวแปร</div>
                </div>
                <div class="step completed" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-label small mt-1">ตัวอย่าง</div>
                </div>
            </div>

            <!-- Main Form -->
            <div id="mainForm">
                <form action="{{ route('templates.update', $template->id) }}" method="POST" id="editTemplateForm" data-template-id="{{ $template->id }}">
                    @csrf
                    @method('PUT')
                    
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
                                                       id="name" name="name" value="{{ old('name', $template->name) }}" required
                                                       placeholder="ระบุชื่อเทมเพลต">
                                                @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">ชื่อที่จะใช้ในการอ้างอิงเทมเพลต</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="slug" class="form-label">Slug</label>
                                                <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                                       id="slug" name="slug" value="{{ old('slug', $template->slug) }}"
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
                                                    <option value="system" {{ old('category', $template->category) === 'system' ? 'selected' : '' }}>ระบบ</option>
                                                    <option value="marketing" {{ old('category', $template->category) === 'marketing' ? 'selected' : '' }}>การตลาด</option>
                                                    <option value="operational" {{ old('category', $template->category) === 'operational' ? 'selected' : '' }}>ปฏิบัติการ</option>
                                                    <option value="emergency" {{ old('category', $template->category) === 'emergency' ? 'selected' : '' }}>ฉุกเฉิน</option>
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
                                                    <option value="low" {{ old('priority', $template->priority) === 'low' ? 'selected' : '' }}>ต่ำ</option>
                                                    <option value="medium" {{ old('priority', $template->priority) === 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                                    <option value="normal" {{ old('priority', $template->priority) === 'normal' ? 'selected' : '' }}>ปกติ</option>
                                                    <option value="high" {{ old('priority', $template->priority) === 'high' ? 'selected' : '' }}>สูง</option>
                                                    <option value="urgent" {{ old('priority', $template->priority) === 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
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
                                                      placeholder="คำอธิบายสั้นๆ เกี่ยวกับการใช้งานเทมเพลตนี้">{{ old('description', $template->description) }}</textarea>
                                            @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label class="form-label">ช่องทางที่รองรับ <span class="text-danger">*</span></label>
                                                @php
                                                    $supportedChannels = old('supported_channels');
                                                    if (!$supportedChannels) {
                                                        if (is_string($template->supported_channels)) {
                                                            $supportedChannels = json_decode($template->supported_channels, true) ?? [];
                                                        } elseif (is_array($template->supported_channels)) {
                                                            $supportedChannels = $template->supported_channels;
                                                        } else {
                                                            $supportedChannels = [];
                                                        }
                                                    }
                                                @endphp
                                                <div class="mt-2">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input @error('supported_channels') is-invalid @enderror" 
                                                               type="checkbox" id="channel_email" name="supported_channels[]" value="email"
                                                               {{ in_array('email', $supportedChannels) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="channel_email">
                                                            <i class="fas fa-envelope me-1"></i>อีเมล
                                                        </label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input @error('supported_channels') is-invalid @enderror" 
                                                               type="checkbox" id="channel_teams" name="supported_channels[]" value="teams"
                                                               {{ in_array('teams', $supportedChannels) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="channel_teams">
                                                            <i class="fab fa-microsoft me-1"></i>Microsoft Teams
                                                        </label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input @error('supported_channels') is-invalid @enderror" 
                                                               type="checkbox" id="channel_sms" name="supported_channels[]" value="sms"
                                                               {{ in_array('sms', $supportedChannels) ? 'checked' : '' }}>
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
                                                           {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
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
                                                   value="{{ old('subject_template', $template->subject_template) }}" 
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
                                                              id="body_html_template" name="body_html_template" rows="15">{{ old('body_html_template', $template->body_html_template) }}</textarea>
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
                                                              placeholder="เนื้อหาข้อความธรรมดาสำหรับ SMS, Teams และการสำรอง">{{ old('body_text_template', $template->body_text_template) }}</textarea>
                                                    <div class="form-text">
                                                        สำหรับ SMS และข้อความธรรมดา ใช้ข้อความสั้นๆ สำหรับ SMS (แนะนำ 160 ตัวอักษร)
                                                        <span id="text-char-count" class="badge bg-secondary ms-2">{{ strlen($template->body_text_template ?? '') }} ตัวอักษร</span>
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
                    @php
                        // แก้ไขการดึงข้อมูลตัวแปร
                        $templateVariables = [];
                        
                        // ลองดึงจาก variables column ก่อน
                        if (isset($template->variables) && !empty($template->variables)) {
                            if (is_string($template->variables)) {
                                $decoded = json_decode($template->variables, true);
                                if (is_array($decoded)) {
                                    $templateVariables = $decoded;
                                }
                            } elseif (is_array($template->variables)) {
                                $templateVariables = $template->variables;
                            }
                        }
                        
                        // ถ้าไม่มีข้อมูลใน variables column ให้ลองดึงจาก default_variables
                        if (empty($templateVariables) && isset($template->default_variables) && !empty($template->default_variables)) {
                            $defaultVars = [];
                            if (is_string($template->default_variables)) {
                                $decoded = json_decode($template->default_variables, true);
                                if (is_array($decoded)) {
                                    $defaultVars = $decoded;
                                }
                            } elseif (is_array($template->default_variables)) {
                                $defaultVars = $template->default_variables;
                            }
                            
                            // แปลงจาก default_variables เป็น variables format
                            $index = 0;
                            foreach ($defaultVars as $varName => $varValue) {
                                $templateVariables[] = [
                                    'name' => $varName,
                                    'default' => $varValue,
                                    'type' => 'text'
                                ];
                                $index++;
                            }
                        }
                        
                        // Debug information (ลบออกเมื่อแก้ไขเสร็จ)
                        // dd([
                        //     'template_id' => $template->id,
                        //     'raw_variables' => $template->variables,
                        //     'raw_default_variables' => $template->default_variables,
                        //     'processed_variables' => $templateVariables
                        // ]);
                    @endphp
                    
                    @if(!empty($templateVariables) && is_array($templateVariables))
                        @foreach($templateVariables as $index => $variable)
                            <div class="row mb-3 variable-row">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" placeholder="ชื่อตัวแปร" 
                                           name="variables[{{ $index }}][name]" 
                                           value="{{ $variable['name'] ?? '' }}">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" placeholder="ค่าเริ่มต้น" 
                                           name="variables[{{ $index }}][default]" 
                                           value="{{ $variable['default'] ?? '' }}">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="variables[{{ $index }}][type]">
                                        <option value="text" {{ ($variable['type'] ?? 'text') === 'text' ? 'selected' : '' }}>ข้อความ</option>
                                        <option value="number" {{ ($variable['type'] ?? 'text') === 'number' ? 'selected' : '' }}>ตัวเลข</option>
                                        <option value="date" {{ ($variable['type'] ?? 'text') === 'date' ? 'selected' : '' }}>วันที่</option>
                                        <option value="url" {{ ($variable['type'] ?? 'text') === 'url' ? 'selected' : '' }}>URL</option>
                                        <option value="email" {{ ($variable['type'] ?? 'text') === 'email' ? 'selected' : '' }}>อีเมล</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVariableRow(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        {{-- แสดงข้อความเมื่อไม่มีตัวแปร --}}
                        <div class="text-muted text-center py-3" id="no-variables-message">
                            <i class="fas fa-info-circle me-2"></i>
                            ยังไม่มีตัวแปรที่จำเป็น คลิก "เพิ่มตัวแปร" เพื่อเริ่มต้น
                        </div>
                    @endif
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
                @php
                    $defaultVariablesJson = old('default_variables_json');
                    if (!$defaultVariablesJson && isset($template->default_variables)) {
                        if (is_string($template->default_variables)) {
                            // ลองแปลงจาก JSON string
                            $defaultVars = json_decode($template->default_variables, true);
                            if ($defaultVars && is_array($defaultVars)) {
                                $defaultVariablesJson = json_encode($defaultVars, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            } else {
                                $defaultVariablesJson = $template->default_variables;
                            }
                        } elseif (is_array($template->default_variables)) {
                            $defaultVariablesJson = json_encode($template->default_variables, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        } else {
                            $defaultVariablesJson = '{}';
                        }
                    }
                    
                    // ถ้ายังไม่มีให้สร้าง default object
                    if (empty($defaultVariablesJson)) {
                        $defaultVariablesJson = '{}';
                    }
                @endphp
                <textarea class="form-control @error('default_variables') is-invalid @enderror" 
                          id="default_variables_json" name="default_variables_json" rows="8" 
                          placeholder='{"ชื่อตัวแปร": "ค่าเริ่มต้น", "user_name": "ผู้ใช้ตัวอย่าง", "message": "ข้อความตัวอย่าง"}'>{{ $defaultVariablesJson }}</textarea>
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
                                            <button type="button" class="btn btn-outline-secondary" onclick="saveEditDraft()" id="draftBtn">
                                                <i class="fas fa-save me-2"></i>บันทึกแบบร่าง
                                            </button>
                                            <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">
                                                ถัดไป<i class="fas fa-arrow-right ms-2"></i>
                                            </button>
                                            <button type="submit" class="btn btn-success" id="saveBtn" style="display: none;">
                                                <i class="fas fa-check me-2"></i>อัปเดตเทมเพลต
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
                                        <i class="fas fa-tasks me-2"></i>ความคืบหน้าการแก้ไข
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span>ความคืบหน้า</span>
                                            <span id="progress-percentage">100%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" id="progress-bar" role="progressbar" style="width: 100%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="small">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2" id="step1-check"></i>
                                            <i class="fas fa-circle text-muted me-2" id="step1-pending" style="display: none;"></i>
                                            <span>ข้อมูลพื้นฐาน</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2" id="step2-check"></i>
                                            <i class="fas fa-circle text-muted me-2" id="step2-pending" style="display: none;"></i>
                                            <span>เนื้อหาเทมเพลต</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2" id="step3-check"></i>
                                            <i class="fas fa-circle text-muted me-2" id="step3-pending" style="display: none;"></i>
                                            <span>การตั้งค่าตัวแปร</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-check-circle text-success me-2" id="step4-check"></i>
                                            <i class="fas fa-circle text-muted me-2" id="step4-pending" style="display: none;"></i>
                                            <span>ตรวจสอบและบันทึก</span>
                                        </div>
                                    </div>

                                    <hr>

                                    <!-- Template Status -->
                                    <div class="mb-3">
                                        <h6 class="small fw-bold text-uppercase">สถานะเทมเพลต</h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small">สถานะ:</span>
                                            <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $template->is_active ? 'ใช้งานได้' : 'ไม่ใช้งาน' }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <span class="small">หมวดหมู่:</span>
                                            <span class="badge bg-info">{{ ucfirst($template->category) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <span class="small">ระดับความสำคัญ:</span>
                                            <span class="badge bg-warning">{{ ucfirst($template->priority) }}</span>
                                        </div>
                                    </div>

                                    <!-- Template Usage -->
                                    <div class="mb-3">
                                        <h6 class="small fw-bold text-uppercase">สถิติการใช้งาน</h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small">จำนวนครั้งที่ใช้:</span>
                                            <span class="badge bg-secondary">{{ $template->notifications->count() ?? 0 }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <span class="small">ใช้ครั้งล่าสุด:</span>
                                            @php 
                                                $lastUsed = $template->notifications()->latest()->first();
                                            @endphp
                                            <span class="small text-muted">{{ $lastUsed ? $lastUsed->created_at->format('d M Y') : 'ไม่เคยใช้' }}</span>
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

                            <!-- Quick Actions Card -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-tools me-2"></i>การดำเนินการด่วน
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="previewBtn">
                                            <i class="fas fa-eye me-2"></i>ดูตัวอย่างเทมเพลต
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="validateTemplate()">
                                            <i class="fas fa-check-circle me-2"></i>ตรวจสอบเทมเพลต
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetToOriginalData()">
                                            <i class="fas fa-undo me-2"></i>รีเซ็ตเป็นข้อมูลเดิม
                                        </button>
                                        <hr class="my-2">
                                        <a href="{{ route('templates.show', $template->id) }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-eye me-2"></i>ดูเทมเพลต
                                        </a>
                                        <a href="{{ route('templates.duplicate', $template->id) }}" class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-copy me-2"></i>ทำสำเนาเทมเพลต
                                        </a>
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
                    console.log('สคริปต์เทมเพลตโหลดเรียบร้อยแล้ว');
                }
            };
            script.onerror = () => {
                console.error('ไม่สามารถโหลดสคริปต์:', src);
            };
            document.head.appendChild(script);
        });
    }
</script>

{{-- Edit-specific functionality --}}
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

// Edit mode specific functions
let originalFormData = {};
let hasUnsavedChanges = false;

// Store original data when page loads
function storeOriginalData() {
    originalFormData = {
        name: document.getElementById('name')?.value || '',
        slug: document.getElementById('slug')?.value || '',
        category: document.getElementById('category')?.value || '',
        priority: document.getElementById('priority')?.value || '',
        description: document.getElementById('description')?.value || '',
        subject_template: document.getElementById('subject_template')?.value || '',
        body_html_template: document.getElementById('body_html_template')?.value || '',
        body_text_template: document.getElementById('body_text_template')?.value || '',
        default_variables_json: document.getElementById('default_variables_json')?.value || '',
        is_active: document.getElementById('is_active')?.checked || false
    };
    
    // Store supported channels
    originalFormData.supported_channels = [];
    document.querySelectorAll('input[name="supported_channels[]"]:checked').forEach(cb => {
        originalFormData.supported_channels.push(cb.value);
    });
}

// Reset to original data
function resetToOriginalData() {
    if (confirm('ต้องการรีเซ็ตเป็นข้อมูลเดิมหรือไม่? การเปลี่ยนแปลงทั้งหมดจะหายไป')) {
        // Reset basic fields
        Object.keys(originalFormData).forEach(key => {
            if (key === 'supported_channels') return;
            if (key === 'is_active') {
                const field = document.getElementById(key);
                if (field) field.checked = originalFormData[key];
                return;
            }
            
            const field = document.getElementById(key);
            if (field) {
                field.value = originalFormData[key];
                
                // Handle TinyMCE
                if (key === 'body_html_template') {
                    const htmlEditor = tinymce.get('body_html_template');
                    if (htmlEditor) {
                        htmlEditor.setContent(originalFormData[key]);
                    }
                }
            }
        });
        
        // Reset supported channels
        document.querySelectorAll('input[name="supported_channels[]"]').forEach(cb => {
            cb.checked = originalFormData.supported_channels.includes(cb.value);
        });
        
        hasUnsavedChanges = false;
        updateUnsavedIndicator();
        showAlert('success', 'รีเซ็ตข้อมูลเป็นข้อมูลเดิมเรียบร้อย');
    }
}

// Track changes
function trackChanges() {
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            hasUnsavedChanges = true;
            updateUnsavedIndicator();
        });
        input.addEventListener('change', () => {
            hasUnsavedChanges = true;
            updateUnsavedIndicator();
        });
    });
}

// Update unsaved changes indicator
function updateUnsavedIndicator() {
    const indicator = document.getElementById('unsaved-changes-indicator');
    if (indicator) {
        indicator.style.display = hasUnsavedChanges ? 'block' : 'none';
    }
}

// Save edit draft
function saveEditDraft() {
    if (!isLocalStorageAvailable()) {
        showAlert('warning', 'เบราว์เซอร์ไม่รองรับการบันทึกแบบร่าง');
        return;
    }
    
    const templateId = document.querySelector('meta[name="template-id"]')?.getAttribute('content');
    if (!templateId) {
        showAlert('error', 'ไม่พบรหัสเทมเพลต');
        return;
    }
    
    const htmlEditor = tinymce.get('body_html_template');
    let htmlContent = '';
    if (htmlEditor) {
        htmlContent = htmlEditor.getContent();
    } else {
        htmlContent = document.getElementById('body_html_template')?.value || '';
    }
    
    const draftData = {
        template_id: templateId,
        name: document.getElementById('name')?.value || '',
        slug: document.getElementById('slug')?.value || '',
        category: document.getElementById('category')?.value || '',
        priority: document.getElementById('priority')?.value || '',
        description: document.getElementById('description')?.value || '',
        subject_template: document.getElementById('subject_template')?.value || '',
        body_html_template: htmlContent,
        body_text_template: document.getElementById('body_text_template')?.value || '',
        default_variables_json: document.getElementById('default_variables_json')?.value || '',
        is_active: document.getElementById('is_active')?.checked || false,
        timestamp: new Date().toISOString()
    };
    
    // บันทึก supported channels
    const channels = [];
    document.querySelectorAll('input[name="supported_channels[]"]:checked').forEach(cb => {
        channels.push(cb.value);
    });
    draftData.supported_channels = channels;
    
    localStorage.setItem(`template_edit_draft_${templateId}`, JSON.stringify(draftData));
    hasUnsavedChanges = false;
    updateUnsavedIndicator();
    showAlert('success', 'บันทึกแบบร่างเรียบร้อย!', 2000);
}

// Load edit draft
function loadEditDraft() {
    if (!isLocalStorageAvailable()) return;
    
    const templateId = document.querySelector('meta[name="template-id"]')?.getAttribute('content');
    if (!templateId) return;
    
    const draft = localStorage.getItem(`template_edit_draft_${templateId}`);
    if (draft) {
        try {
            const draftData = JSON.parse(draft);
            const draftAge = Date.now() - new Date(draftData.timestamp).getTime();
            
            // โหลด draft เฉพาะถ้าอายุไม่เกิน 24 ชั่วโมง
            if (draftAge < 24 * 60 * 60 * 1000) {
                const shouldLoad = confirm(
                    `พบแบบร่างการแก้ไขที่บันทึกไว้เมื่อ ${new Date(draftData.timestamp).toLocaleString('th-TH')} ` +
                    'ต้องการโหลดข้อมูลเพื่อทำงานต่อหรือไม่?'
                );
                
                if (shouldLoad) {
                    // โหลดข้อมูลพื้นฐาน
                    Object.keys(draftData).forEach(key => {
                        if (['timestamp', 'template_id', 'supported_channels'].includes(key)) return;
                        if (key === 'is_active') {
                            const field = document.getElementById(key);
                            if (field) field.checked = draftData[key];
                            return;
                        }
                        
                        const field = document.getElementById(key);
                        if (field && draftData[key] !== undefined) {
                            field.value = draftData[key];
                        }
                    });
                    
                    // โหลด supported channels
                    if (draftData.supported_channels) {
                        document.querySelectorAll('input[name="supported_channels[]"]').forEach(cb => {
                            cb.checked = draftData.supported_channels.includes(cb.value);
                        });
                    }
                    
                    hasUnsavedChanges = true;
                    updateUnsavedIndicator();
                    showAlert('success', 'โหลดแบบร่างการแก้ไขเรียบร้อย');
                    
                    // โหลดเนื้อหาใน TinyMCE หลังจากเริ่มต้นแล้ว
                    setTimeout(() => {
                        const htmlEditor = tinymce.get('body_html_template');
                        if (htmlEditor && draftData.body_html_template) {
                            htmlEditor.setContent(draftData.body_html_template);
                        }
                    }, 2000);
                }
            }
        } catch (e) {
            console.error('ข้อผิดพลาดในการโหลดแบบร่างการแก้ไข:', e);
            localStorage.removeItem(`template_edit_draft_${templateId}`);
        }
    }
}

// Initialize edit mode when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('เริ่มต้นโหมดแก้ไขเทมเพลต...');
    
    // Store original data for reset functionality
    storeOriginalData();
    
    // Track changes for unsaved indicator
    trackChanges();
    
    // Load any existing draft
    setTimeout(loadEditDraft, 1000);
    
    // Set current step to 1 to show basic info first
    setTimeout(() => {
        if (typeof showStep === 'function') {
            showStep(1);
        }
    }, 500);
    
    // Update character count for existing content
    const textField = document.getElementById('body_text_template');
    const charCount = document.getElementById('text-char-count');
    if (textField && charCount) {
        const count = textField.value.length;
        charCount.textContent = `${count} ตัวอักษร`;
        if (count > 160) {
            charCount.className = 'badge bg-warning ms-2';
        } else {
            charCount.className = 'badge bg-secondary ms-2';
        }
    }
    
    // Initialize Select2 if available
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
    
    // Reset button functionality
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', resetToOriginalData);
    }
    
    // Warn before leaving page with unsaved changes
    window.addEventListener('beforeunload', function (e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'คุณมีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?';
            return e.returnValue;
        }
    });
});

// Override saveTemplate function for edit mode
window.saveTemplate = function() {
    const form = document.getElementById('editTemplateForm');
    if (form) {
        form.dispatchEvent(new Event('submit'));
    }
};

// Override saveDraft function for edit mode
if (typeof saveEditDraft === 'function') {
    window.saveDraft = saveEditDraft;
}

// Custom form submission for edit mode
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('editTemplateForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log('เริ่มส่งข้อมูลการแก้ไข...');
            
            // Sync TinyMCE content
            if (tinymce.get('body_html_template')) {
                tinymce.get('body_html_template').save();
            }
            
            // ตรวจสอบทุก step
            let allValid = true;
            for (let i = 1; i <= 3; i++) { // Skip preview step for edit
                const originalStep = window.currentStep || 1;
                window.currentStep = i;
                if (typeof validateCurrentStep === 'function' && !validateCurrentStep()) {
                    allValid = false;
                    if (typeof showStep === 'function') {
                        showStep(i);
                    }
                    break;
                }
                window.currentStep = originalStep;
            }
            
            if (!allValid) return;
            
            // แสดงสถานะการโหลด
            const saveBtn = document.getElementById('saveBtn');
            if (saveBtn) {
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังอัปเดต...';
                saveBtn.disabled = true;
                
                // Submit form normally
                const formData = new FormData(editForm);
                
                // Debug information
                console.log('ข้อมูลที่จะส่ง:');
                for (let [key, value] of formData.entries()) {
                    console.log(key + ':', value);
                }
                
                fetch(editForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                .then(response => {
                    console.log('สถานะการตอบกลับ:', response.status);
                    
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('ข้อผิดพลาด:', text);
                            throw new Error(`HTTP ${response.status}: ${text}`);
                        });
                    }
                    
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        return { success: true, message: 'อัปเดตเทมเพลตเรียบร้อย' };
                    }
                })
                .then(data => {
                    console.log('ตอบกลับสำเร็จ:', data);
                    
                    if (data.success !== false) {
                        showAlert('success', data.message || 'อัปเดตเทมเพลตเรียบร้อย!');
                        
                        // ลบ draft
                        const templateId = document.querySelector('meta[name="template-id"]')?.getAttribute('content');
                        if (templateId && isLocalStorageAvailable()) {
                            localStorage.removeItem(`template_edit_draft_${templateId}`);
                        }
                        
                        hasUnsavedChanges = false;
                        updateUnsavedIndicator();
                        
                        setTimeout(() => {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                const templateId = document.querySelector('meta[name="template-id"]')?.getAttribute('content');
                                if (templateId) {
                                    window.location.href = `/templates/${templateId}`;
                                } else {
                                    window.location.href = '/templates';
                                }
                            }
                        }, 1500);
                    } else {
                        throw new Error(data.message || 'ไม่สามารถอัปเดตเทมเพลตได้');
                    }
                })
                .catch(error => {
                    console.error('ข้อผิดพลาด:', error);
                    
                    let errorMessage = 'เกิดข้อผิดพลาดในการอัปเดตเทมเพลต';
                    
                    if (error.message.includes('405')) {
                        errorMessage = 'ข้อผิดพลาดการกำหนดค่า Route กรุณาตรวจสอบ Laravel routes';
                    } else if (error.message.includes('419')) {
                        errorMessage = 'CSRF token ไม่ถูกต้อง กรุณารีเฟรชหน้าและลองใหม่';
                    } else if (error.message.includes('422')) {
                        errorMessage = 'ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบข้อมูลที่กรอก';
                    } else if (error.message) {
                        errorMessage = error.message;
                    }
                    
                    showAlert('error', errorMessage);
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
            }
        });
    }
});

// Auto-initialize variables from existing data when page loads
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบว่ามีตัวแปรโหลดมาจาก PHP หรือไม่
    const container = document.getElementById('variables-container');
    const existingRows = container.querySelectorAll('.variable-row');
    
    console.log('Found existing variable rows:', existingRows.length);
    
    // ถ้าไม่มีตัวแปรที่โหลดมาจาก PHP แต่มี JSON ให้ลองโหลดจาก JSON
    if (existingRows.length === 0) {
        const defaultVarsJson = document.getElementById('default_variables_json')?.value;
        if (defaultVarsJson && defaultVarsJson.trim() !== '{}') {
            console.log('No PHP variables found, attempting to load from JSON...');
            setTimeout(() => {
                loadTemplateVariablesFromJSON();
            }, 1000);
        }
    }
    
    // ถ้ามีตัวแปรแล้วให้ซ่อนข้อความ "ยังไม่มีตัวแปร"
    if (existingRows.length > 0) {
        const noVarMessage = document.getElementById('no-variables-message');
        if (noVarMessage) {
            noVarMessage.style.display = 'none';
        }
    }
});

// เพิ่ม debug information เมื่อโหลดหน้า
console.log('Edit template variables debug loaded');

// ฟังก์ชันสำหรับซิงค์ข้อมูลจาก required variables ไปยัง default variables JSON
window.syncVariablesToJSON = function() {
    const variables = {};
    
    document.querySelectorAll('.variable-row').forEach(row => {
        const nameInput = row.querySelector('input[placeholder*="ชื่อตัวแปร"]');
        const defaultInput = row.querySelector('input[placeholder*="ค่าเริ่มต้น"]');
        
        const varName = nameInput ? nameInput.value.trim() : '';
        const varDefault = defaultInput ? defaultInput.value.trim() : '';
        
        if (varName) {
            variables[varName] = varDefault || `ตัวอย่าง ${varName}`;
        }
    });
    
    if (Object.keys(variables).length > 0) {
        const jsonTextarea = document.getElementById('default_variables_json');
        if (jsonTextarea) {
            jsonTextarea.value = JSON.stringify(variables, null, 2);
            console.log('Synced variables to JSON:', variables);
        }
    }
};

// Auto-save for edit mode
setInterval(function() {
    const activeElement = document.activeElement;
    if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
        return; // ไม่ save ถ้ากำลังพิมพ์
    }
    
    if (hasUnsavedChanges && isLocalStorageAvailable()) {
        saveEditDraft();
    }
}, 5 * 60 * 1000); // 5 นาที

// Global error handler
window.addEventListener('error', function(e) {
    console.error('Template Edit Error:', e.error);
});

// Performance monitoring
window.addEventListener('load', function() {
    console.log('หน้าแก้ไขเทมเพลตโหลดใน:', performance.now().toFixed(2), 'ms');
});

console.log('Template Edit (Thai) โหลดเรียบร้อย');
</script>

@endpush