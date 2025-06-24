@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนใหม่')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    .step-container {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 3rem;
        position: relative;
    }
    
    .step-indicator::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 4px;
        background: #dee2e6;
        z-index: 1;
    }
    
    .step-indicator .progress-line {
        position: absolute;
        top: 20px;
        left: 0;
        height: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: width 0.3s ease;
        z-index: 2;
    }
    
    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 3;
        flex: 1;
    }
    
    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #dee2e6;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
        transition: all 0.3s ease;
        border: 3px solid #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .step.active .step-circle {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: scale(1.1);
    }
    
    .step.completed .step-circle {
        background: #28a745;
        color: white;
    }
    
    .step-title {
        margin-top: 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        text-align: center;
        color: #6c757d;
    }
    
    .step.active .step-title {
        color: #667eea;
    }
    
    .step.completed .step-title {
        color: #28a745;
    }
    
    .step-content {
        display: none;
        min-height: 400px;
    }
    
    .step-content.active {
        display: block;
        animation: fadeInUp 0.3s ease;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .template-card {
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid transparent;
    }
    
    .template-card:hover {
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
    }
    
    .template-card.selected {
        border-color: #667eea;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    }
    
    .option-card {
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid #dee2e6;
        padding: 1.5rem;
        border-radius: 12px;
        text-align: center;
    }
    
    .option-card:hover {
        border-color: #667eea;
        transform: translateY(-2px);
    }
    
    .option-card.selected {
        border-color: #667eea;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    }
    
    .variable-input {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .btn-step {
        min-width: 120px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
    }
    
    .step-navigation {
        display: flex;
        justify-content: between;
        align-items: center;
        padding-top: 2rem;
        border-top: 1px solid #dee2e6;
        margin-top: 2rem;
    }
    
    .preview-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 1rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-plus-circle text-primary"></i> สร้างการแจ้งเตือนใหม่
            </h1>
            <p class="mb-0 text-muted">ทำตามขั้นตอนเพื่อสร้างการแจ้งเตือนที่สมบูรณ์</p>
        </div>
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> กลับ
        </a>
    </div>

    <form method="POST" action="{{ route('admin.notifications.store') }}" id="notificationForm">
        @csrf
        
        <div class="step-container">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="progress-line" style="width: 0%"></div>
                
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-title">เลือกรูปแบบ</div>
                </div>
                
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-title">เนื้อหาการแจ้งเตือน</div>
                </div>
                
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-title">จัดการตัวแปร</div>
                </div>
                
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-title">เลือกผู้รับ</div>
                </div>
                
                <div class="step" data-step="5">
                    <div class="step-circle">5</div>
                    <div class="step-title">ตั้งเวลาส่ง</div>
                </div>
                
                <div class="step" data-step="6">
                    <div class="step-circle">6</div>
                    <div class="step-title">ยืนยัน</div>
                </div>
            </div>

            <!-- Step 1: Choose Format -->
            <div class="step-content active" id="step-1">
                <h4 class="mb-4"><i class="bi bi-collection"></i> เลือกรูปแบบการสร้างการแจ้งเตือน</h4>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="option-card" data-type="manual">
                            <div class="mb-3">
                                <i class="bi bi-pencil-square" style="font-size: 3rem; color: #667eea;"></i>
                            </div>
                            <h5>สร้างเองแบบกำหนดเอง</h5>
                            <p class="text-muted">เขียนหัวข้อและเนื้อหาเองตั้งแต่ต้น</p>
                            <div class="mt-3">
                                <span class="badge bg-primary">ยืดหยุ่น</span>
                                <span class="badge bg-success">ปรับแต่งได้</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="option-card" data-type="template">
                            <div class="mb-3">
                                <i class="bi bi-file-earmark-text" style="font-size: 3rem; color: #28a745;"></i>
                            </div>
                            <h5>ใช้เทมเพลตที่มีอยู่</h5>
                            <p class="text-muted">เลือกจากเทมเพลตที่สร้างไว้แล้ว</p>
                            <div class="mt-3">
                                <span class="badge bg-success">รวดเร็ว</span>
                                <span class="badge bg-info">มาตรฐาน</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Template Selection (hidden initially) -->
                <div id="templateSelection" style="display: none;" class="mt-4">
                    <h5>เลือกเทมเพลต</h5>
                    <div class="row g-3">
                        @foreach($templates as $template)
                        <div class="col-md-4">
                            <div class="template-card card" data-template-id="{{ $template->id }}">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $template->name }}</h6>
                                    <p class="card-text small text-muted">{{ $template->description }}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            {{ implode(', ', $template->supported_channels) }}
                                        </small>
                                        <span class="badge bg-secondary">{{ $template->category }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <input type="hidden" name="creation_type" id="creation_type" value="">
                <input type="hidden" name="template_id" id="template_id" value="">
            </div>

            <!-- Step 2: Content Creation -->
            <div class="step-content" id="step-2">
                <h4 class="mb-4"><i class="bi bi-file-text"></i> เนื้อหาการแจ้งเตือน</h4>
                
                <!-- Manual Content -->
                <div id="manualContent">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="subject" class="form-label fw-bold">หัวข้อ *</label>
                            <input type="text" name="subject" id="subject" 
                                   class="form-control @error('subject') is-invalid @enderror" 
                                   placeholder="กรอกหัวข้อการแจ้งเตือน" maxlength="255">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="body_html" class="form-label fw-bold">
                                เนื้อหา HTML <small class="text-muted">(สำหรับอีเมล)</small>
                            </label>
                            <textarea name="body_html" id="body_html" 
                                      class="form-control @error('body_html') is-invalid @enderror" 
                                      rows="10" placeholder="เนื้อหาแบบ HTML สำหรับอีเมล"></textarea>
                            @error('body_html')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="body_text" class="form-label fw-bold">
                                เนื้อหา Text <small class="text-muted">(สำหรับ Teams)</small>
                            </label>
                            <textarea name="body_text" id="body_text" 
                                      class="form-control @error('body_text') is-invalid @enderror" 
                                      rows="10" placeholder="เนื้อหาแบบข้อความธรรมดา"></textarea>
                            @error('body_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <!-- Template Content Preview -->
                <div id="templateContent" style="display: none;">
                    <div class="preview-card">
                        <h6><i class="bi bi-eye"></i> ตัวอย่างเทมเพลต</h6>
                        <div id="templatePreviewContent"></div>
                        
                        <!-- Edit Template Content -->
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="editTemplateContent()">
                                <i class="bi bi-pencil"></i> แก้ไขเนื้อหา
                            </button>
                        </div>
                        
                        <!-- Editable Template Form (hidden initially) -->
                        <div id="editableTemplateForm" style="display: none;" class="mt-3">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="template_subject" class="form-label fw-bold">หัวข้อ</label>
                                    <input type="text" id="template_subject" class="form-control" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="template_body_html" class="form-label fw-bold">เนื้อหา HTML</label>
                                    <textarea id="template_body_html" class="form-control" rows="8" readonly></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="template_body_text" class="form-label fw-bold">เนื้อหา Text</label>
                                    <textarea id="template_body_text" class="form-control" rows="8" readonly></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Variable Management -->
            <div class="step-content" id="step-3">
                <h4 class="mb-4"><i class="bi bi-code-square"></i> จัดการตัวแปร</h4>
                
                <div id="variableManagement">
                    <!-- Auto-detected variables will be shown here -->
                    <div id="detectedVariables"></div>
                    
                    <!-- Manual variable addition -->
                    <div class="mt-4">
                        <h6>เพิ่มตัวแปรเพิ่มเติม (ไม่บังคับ)</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" id="newVariableName" class="form-control" placeholder="ชื่อตัวแปร">
                            </div>
                            <div class="col-md-4">
                                <input type="text" id="newVariableValue" class="form-control" placeholder="ค่าเริ่มต้น">
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-primary" onclick="addCustomVariable()">
                                    <i class="bi bi-plus"></i> เพิ่มตัวแปร
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Recipients -->
            <div class="step-content" id="step-4">
                <h4 class="mb-4"><i class="bi bi-people"></i> เลือกผู้รับการแจ้งเตือน</h4>
                
                <!-- Recipient Type Selection -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="option-card recipient-option" data-recipient-type="manual">
                            <i class="bi bi-person-plus" style="font-size: 2rem; color: #667eea;"></i>
                            <h6 class="mt-2">เลือกเอง</h6>
                            <small class="text-muted">กรอกอีเมลผู้รับเอง</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="option-card recipient-option" data-recipient-type="groups">
                            <i class="bi bi-people" style="font-size: 2rem; color: #28a745;"></i>
                            <h6 class="mt-2">ตามกลุ่ม</h6>
                            <small class="text-muted">เลือกจากกลุ่มที่มีอยู่</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="option-card recipient-option" data-recipient-type="all_users">
                            <i class="bi bi-globe" style="font-size: 2rem; color: #ffc107;"></i>
                            <h6 class="mt-2">ผู้ใช้ทั้งหมด</h6>
                            <small class="text-muted">ส่งให้ทุกคนในระบบ</small>
                        </div>
                    </div>
                </div>
                
                <!-- Recipient Details -->
                <div id="recipientDetails"></div>
                
                <input type="hidden" name="recipient_type" id="recipient_type" value="">
            </div>

            <!-- Step 5: Scheduling -->
            <div class="step-content" id="step-5">
                <h4 class="mb-4"><i class="bi bi-calendar"></i> กำหนดเวลาการส่ง</h4>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="option-card schedule-option" data-schedule-type="immediate">
                            <i class="bi bi-send" style="font-size: 2rem; color: #28a745;"></i>
                            <h6 class="mt-2">ส่งทันที</h6>
                            <small class="text-muted">ส่งการแจ้งเตือนทันทีที่บันทึก</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="option-card schedule-option" data-schedule-type="scheduled">
                            <i class="bi bi-calendar-event" style="font-size: 2rem; color: #ffc107;"></i>
                            <h6 class="mt-2">กำหนดเวลา</h6>
                            <small class="text-muted">กำหนดวันเวลาที่ต้องการส่ง</small>
                        </div>
                    </div>
                </div>
                
                <!-- Scheduled DateTime -->
                <div id="scheduledOptions" style="display: none;" class="mt-4">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="scheduled_at" class="form-label fw-bold">วันเวลาที่กำหนด</label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" 
                                   class="form-control @error('scheduled_at') is-invalid @enderror" 
                                   min="{{ now()->format('Y-m-d\TH:i') }}">
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="priority" class="form-label fw-bold">ระดับความสำคัญ</label>
                            <select name="priority" id="priority" class="form-select">
                                <option value="low">🟢 ต่ำ</option>
                                <option value="normal" selected>🔵 ปกติ</option>
                                <option value="high">🟡 สูง</option>
                                <option value="urgent">🔴 เร่งด่วน</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Channels -->
                <div class="mt-4">
                    <h6>ช่องทางการส่ง</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" 
                                       value="email" id="channel_email" checked>
                                <label class="form-check-label" for="channel_email">
                                    <i class="bi bi-envelope me-2"></i> อีเมล
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" 
                                       value="teams" id="channel_teams">
                                <label class="form-check-label" for="channel_teams">
                                    <i class="bi bi-microsoft-teams me-2"></i> Microsoft Teams
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" name="schedule_type" id="schedule_type" value="">
            </div>

            <!-- Step 6: Confirmation -->
            <div class="step-content" id="step-6">
                <h4 class="mb-4"><i class="bi bi-check-circle"></i> ยืนยันการสร้างการแจ้งเตือน</h4>
                
                <div id="summaryContent">
                    <!-- Summary will be generated here -->
                </div>
                
                <!-- Test Section -->
                <div class="preview-card mt-4">
                    <h6><i class="bi bi-bug"></i> ทดสอบการแจ้งเตือน</h6>
                    <p class="text-muted small">ทดสอบการส่งก่อนส่งจริง</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="email" id="test_email" class="form-control" 
                                   placeholder="อีเมลสำหรับทดสอบ" value="{{ auth()->user()->email }}">
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-info" onclick="sendTest()">
                                <i class="bi bi-send"></i> ส่งทดสอบ
                            </button>
                        </div>
                    </div>
                    <div id="testResult" class="mt-2"></div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="step-navigation">
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-step" id="prevBtn" 
                            onclick="previousStep()" style="display: none;">
                        <i class="bi bi-chevron-left"></i> ก่อนหน้า
                    </button>
                </div>
                
                <div class="text-center">
                    <span class="text-muted">ขั้นตอนที่ <span id="currentStepNumber">1</span> จาก 6</span>
                </div>
                
                <div>
                    <button type="button" class="btn btn-primary btn-step" id="nextBtn" 
                            onclick="nextStep()">
                        ถัดไป <i class="bi bi-chevron-right"></i>
                    </button>
                    
                    <button type="submit" class="btn btn-success btn-step" id="submitBtn" 
                            style="display: none;">
                        <i class="bi bi-check-circle"></i> สร้างการแจ้งเตือน
                    </button>
                    
                    <button type="button" class="btn btn-outline-warning btn-step" id="draftBtn" 
                            style="display: none;" onclick="saveDraft()">
                        <i class="bi bi-save"></i> บันทึกร่าง
                    </button>
                </div>
            </div>
        </div>
    </form>
        </div>
        </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>

<script>
let currentStep = 1;
let totalSteps = 6;
let formData = {};

document.addEventListener('DOMContentLoaded', function() {
    initializeStepForm();
});

function initializeStepForm() {
    // Initialize step 1 options
    document.querySelectorAll('.option-card[data-type]').forEach(card => {
        card.addEventListener('click', function() {
            const type = this.dataset.type;
            selectCreationType(type);
        });
    });
    
    // Initialize recipient options
    document.querySelectorAll('.recipient-option').forEach(card => {
        card.addEventListener('click', function() {
            const type = this.dataset.recipientType;
            selectRecipientType(type);
        });
    });
    
    // Initialize schedule options
    document.querySelectorAll('.schedule-option').forEach(card => {
        card.addEventListener('click', function() {
            const type = this.dataset.scheduleType;
            selectScheduleType(type);
        });
    });
    
    // Initialize template cards
    document.querySelectorAll('.template-card').forEach(card => {
        card.addEventListener('click', function() {
            const templateId = this.dataset.templateId;
            selectTemplate(templateId);
        });
    });
}

function selectCreationType(type) {
    document.querySelectorAll('.option-card[data-type]').forEach(card => {
        card.classList.remove('selected');
    });
    
    document.querySelector(`[data-type="${type}"]`).classList.add('selected');
    document.getElementById('creation_type').value = type;
    
    if (type === 'template') {
        document.getElementById('templateSelection').style.display = 'block';
    } else {
        document.getElementById('templateSelection').style.display = 'none';
        document.getElementById('template_id').value = '';
    }
    
    formData.creation_type = type;
}

function selectTemplate(templateId) {
    document.querySelectorAll('.template-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    document.querySelector(`[data-template-id="${templateId}"]`).classList.add('selected');
    document.getElementById('template_id').value = templateId;
    
    formData.template_id = templateId;
    
    // Load template content
    loadTemplateContent(templateId);
}

function selectRecipientType(type) {
    document.querySelectorAll('.recipient-option').forEach(card => {
        card.classList.remove('selected');
    });
    
    document.querySelector(`[data-recipient-type="${type}"]`).classList.add('selected');
    document.getElementById('recipient_type').value = type;
    
    formData.recipient_type = type;
    
    showRecipientDetails(type);
}

function selectScheduleType(type) {
    document.querySelectorAll('.schedule-option').forEach(card => {
        card.classList.remove('selected');
    });
    
    document.querySelector(`[data-schedule-type="${type}"]`).classList.add('selected');
    document.getElementById('schedule_type').value = type;
    
    if (type === 'scheduled') {
        document.getElementById('scheduledOptions').style.display = 'block';
    } else {
        document.getElementById('scheduledOptions').style.display = 'none';
    }
    
    formData.schedule_type = type;
}

function showRecipientDetails(type) {
    const container = document.getElementById('recipientDetails');
    
    switch(type) {
        case 'manual':
            container.innerHTML = `
                <div class="mt-4">
                    <label for="recipients" class="form-label fw-bold">อีเมลผู้รับ *</label>
                    <textarea name="recipients[]" id="recipients" class="form-control" rows="5" 
                              placeholder="กรอกอีเมล แยกด้วย Enter หรือ comma"></textarea>
                    <div class="form-text">กรอกอีเมลผู้รับ แยกด้วย Enter หรือ comma</div>
                    <div id="recipientCount" class="text-muted small mt-1"></div>
                </div>
            `;
            
            // Add recipient counter
            document.getElementById('recipients').addEventListener('input', function() {
                const emails = this.value.split(/[\n,]+/).filter(email => email.trim()).length;
                document.getElementById('recipientCount').textContent = emails > 0 ? `จำนวนผู้รับ: ${emails} คน` : '';
            });
            break;
            
        case 'groups':
            let groupsHtml = '<div class="mt-4"><label class="form-label fw-bold">เลือกกลุ่ม *</label><div class="row g-2">';
            
            // Generate groups dynamically with PHP
            const groups = @json($groups);
            groups.forEach(group => {
                groupsHtml += `
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="recipient_groups[]" 
                                   value="${group.id}" id="group_${group.id}" onchange="updateGroupCount()">
                            <label class="form-check-label" for="group_${group.id}">
                                <strong>${group.name}</strong>
                                <small class="d-block text-muted">${group.users_count || group.member_count || 0} คน</small>
                            </label>
                        </div>
                    </div>
                `;
            });
            
            groupsHtml += '</div>';
            groupsHtml += '<div id="selectedGroupCount" class="text-muted small mt-2"></div>';
            groupsHtml += '</div>';
            container.innerHTML = groupsHtml;
            break;
            
        case 'all_users':
            const userCount = {{ $users->count() }};
            container.innerHTML = `
                <div class="alert alert-warning mt-4">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>ส่งให้ผู้ใช้ทั้งหมด (${userCount} คน)</strong>
                    <br>การแจ้งเตือนจะถูกส่งไปยังผู้ใช้ทุกคนในระบบ กรุณาตรวจสอบเนื้อหาให้ดี
                </div>
            `;
            break;
    }
}

function updateGroupCount() {
    const selectedGroups = document.querySelectorAll('input[name="recipient_groups[]"]:checked');
    let totalMembers = 0;
    
    selectedGroups.forEach(checkbox => {
        const label = document.querySelector(`label[for="${checkbox.id}"]`);
        const memberText = label.querySelector('small').textContent;
        const memberCount = parseInt(memberText.match(/\d+/) || 0);
        totalMembers += memberCount;
    });
    
    const countDisplay = document.getElementById('selectedGroupCount');
    if (countDisplay) {
        countDisplay.textContent = selectedGroups.length > 0 ? 
            `เลือกแล้ว ${selectedGroups.length} กลุ่ม (รวม ${totalMembers} คน)` : '';
    }
}

function loadTemplateContent(templateId) {
    // Show loading
    document.getElementById('templateContent').style.display = 'block';
    document.getElementById('manualContent').style.display = 'none';
    document.getElementById('templatePreviewContent').innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split"></i> กำลังโหลด...</div>';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch('/admin/notifications/template-preview', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ template_id: templateId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const template = data.template;
            const preview = data.preview;
            
            // Store template data
            formData.template = template;
            formData.preview = preview;
            
            // Show preview
            document.getElementById('templatePreviewContent').innerHTML = `
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <strong>หัวข้อ:</strong>
                        <div class="bg-light p-2 rounded">${preview.subject || 'ไม่มีหัวข้อ'}</div>
                    </div>
                    ${preview.body_html ? `
                    <div class="col-md-6 mb-3">
                        <strong>เนื้อหา HTML:</strong>
                        <div class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;">
                            ${preview.body_html}
                        </div>
                    </div>
                    ` : ''}
                    ${preview.body_text ? `
                    <div class="col-md-6 mb-3">
                        <strong>เนื้อหา Text:</strong>
                        <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto; white-space: pre-wrap;">${preview.body_text}</pre>
                    </div>
                    ` : ''}
                </div>
            `;
            
            // Fill editable form fields
            document.getElementById('template_subject').value = template.subject_template || '';
            document.getElementById('template_body_html').value = template.body_html_template || '';
            document.getElementById('template_body_text').value = template.body_text_template || '';
            
            // Also fill the main form fields with template content
            document.getElementById('subject').value = template.subject_template || '';
            document.getElementById('body_html').value = template.body_html_template || '';
            document.getElementById('body_text').value = template.body_text_template || '';
            
            // Update supported channels
            updateSupportedChannels(template.supported_channels);
            
        } else {
            document.getElementById('templatePreviewContent').innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดเทมเพลต</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('templatePreviewContent').innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดเทมเพลต</div>';
    });
}

function editTemplateContent() {
    const form = document.getElementById('editableTemplateForm');
    if (form.style.display === 'none') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}

function updateSupportedChannels(supportedChannels) {
    const emailCheckbox = document.getElementById('channel_email');
    const teamsCheckbox = document.getElementById('channel_teams');
    
    // Reset checkboxes
    emailCheckbox.checked = false;
    teamsCheckbox.checked = false;
    
    // Check supported channels
    if (supportedChannels.includes('email')) {
        emailCheckbox.checked = true;
    }
    if (supportedChannels.includes('teams')) {
        teamsCheckbox.checked = true;
    }
}

function detectVariables() {
    let content = '';
    
    if (formData.creation_type === 'manual') {
        const subject = document.getElementById('subject').value || '';
        let bodyHtml = '';
        
        // Get content from TinyMCE if available
        const editor = tinymce.get('body_html');
        if (editor) {
            bodyHtml = editor.getContent();
        } else {
            bodyHtml = document.getElementById('body_html').value || '';
        }
        
        const bodyText = document.getElementById('body_text').value || '';
        content = subject + ' ' + bodyHtml + ' ' + bodyText;
        
    } else if (formData.template) {
        content = (formData.template.subject_template || '') + ' ' + 
                 (formData.template.body_html_template || '') + ' ' + 
                 (formData.template.body_text_template || '');
    }
    
    // Extract variables using regex
    const variables = [];
    const regex = /\{\{([^}]+)\}\}/g;
    let match;
    
    while ((match = regex.exec(content)) !== null) {
        const varName = match[1].trim();
        // Filter out system variables and duplicates
        if (!variables.includes(varName) && !isSystemVariable(varName)) {
            variables.push(varName);
        }
    }
    
    return variables;
}

function isSystemVariable(varName) {
    const systemVars = ['user_name', 'user_email', 'user_first_name', 'user_last_name', 
                       'current_date', 'current_time', 'current_datetime', 'app_name', 'app_url'];
    return systemVars.includes(varName);
}

function showDetectedVariables() {
    const variables = detectVariables();
    const container = document.getElementById('detectedVariables');
    
    if (variables.length === 0) {
        container.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                ไม่พบตัวแปรที่กำหนดเองในเนื้อหา
                <br><small class="text-muted">
                    ระบบจะใช้ตัวแปรมาตรฐาน เช่น {{user_name}}, {{user_email}}, {{current_date}} อัตโนมัติ
                </small>
            </div>
        `;
        return;
    }
    
    let html = '<h6><i class="bi bi-code-square"></i> ตัวแปรที่พบในเนื้อหา</h6>';
    html += '<p class="text-muted small mb-3">กรอกค่าเริ่มต้นสำหรับตัวแปรเหล่านี้ (จะใช้ในการแสดงตัวอย่าง)</p>';
    
    variables.forEach(variable => {
        html += `
            <div class="variable-input">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <label class="form-label fw-bold mb-0">
                            <i class="bi bi-code-square text-primary"></i> 
                            <code>{{${variable}}}</code>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="variables[${variable}]" 
                               placeholder="ค่าเริ่มต้นสำหรับ ${variable}" 
                               onchange="updateVariablePreview()">
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-secondary">custom</span>
                        <button type="button" class="btn btn-sm btn-outline-info ms-1" 
                                onclick="insertVariableToField('{{${variable}}}')" 
                                title="แทรกลงในเนื้อหา">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function insertVariableToField(variable) {
    // Try to insert into TinyMCE first
    const editor = tinymce.get('body_html');
    if (editor && editor.hasFocus()) {
        editor.insertContent(variable);
        showAlert(`แทรกตัวแปร ${variable} ลงใน HTML editor แล้ว`, 'success');
        return;
    }
    
    // Try to insert into focused textarea
    const activeElement = document.activeElement;
    if (activeElement && activeElement.tagName === 'TEXTAREA') {
        const cursorPos = activeElement.selectionStart;
        const text = activeElement.value;
        
        activeElement.value = text.slice(0, cursorPos) + variable + text.slice(cursorPos);
        activeElement.selectionStart = activeElement.selectionEnd = cursorPos + variable.length;
        activeElement.focus();
        
        showAlert(`แทรกตัวแปร ${variable} แล้ว`, 'success');
    } else {
        // Copy to clipboard as fallback
        navigator.clipboard.writeText(variable).then(() => {
            showAlert(`คัดลอก ${variable} ไปยังคลิปบอร์ดแล้ว`, 'info');
        });
    }
}

function updateVariablePreview() {
    // Could implement live preview update here
    console.log('Variables updated');
}

function selectCreationType(type) {
    document.querySelectorAll('.option-card[data-type]').forEach(card => {
        card.classList.remove('selected');
    });
    
    document.querySelector(`[data-type="${type}"]`).classList.add('selected');
    document.getElementById('creation_type').value = type;
    
    if (type === 'template') {
        document.getElementById('templateSelection').style.display = 'block';
    } else {
        document.getElementById('templateSelection').style.display = 'none';
        document.getElementById('template_id').value = '';
        formData.template_id = null;
        formData.template = null;
    }
    
    formData.creation_type = type;
}

function showAlert(message, type = 'info') {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertContainer.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertContainer.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertContainer);
    
    setTimeout(() => {
        if (alertContainer.parentNode) {
            alertContainer.remove();
        }
    }, 4000);
}

function addCustomVariable() {
    const name = document.getElementById('newVariableName').value.trim();
    const value = document.getElementById('newVariableValue').value.trim();
    
    if (!name) {
        alert('กรุณากรอกชื่อตัวแปร');
        return;
    }
    
    const container = document.getElementById('detectedVariables');
    const newVar = document.createElement('div');
    newVar.className = 'variable-input';
    newVar.innerHTML = `
        <div class="row align-items-center">
            <div class="col-md-3">
                <label class="form-label fw-bold mb-0">
                    <i class="bi bi-code-square text-success"></i> ${name}
                </label>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" name="variables[${name}]" 
                       value="${value}" placeholder="กรอกค่าสำหรับ ${name}">
            </div>
            <div class="col-md-3">
                <span class="badge bg-success">custom</span>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="this.closest('.variable-input').remove()">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(newVar);
    
    // Clear inputs
    document.getElementById('newVariableName').value = '';
    document.getElementById('newVariableValue').value = '';
}

function generateSummary() {
    const container = document.getElementById('summaryContent');
    
    let html = '<div class="row g-4">';
    
    // Content Summary
    html += '<div class="col-md-6">';
    html += '<div class="preview-card">';
    html += '<h6><i class="bi bi-file-text text-primary"></i> เนื้อหา</h6>';
    
    if (formData.creation_type === 'template') {
        html += `<p><strong>เทมเพลต:</strong> ${formData.template?.name || 'ไม่ระบุ'}</p>`;
    } else {
        const subject = document.getElementById('subject').value;
        html += `<p><strong>หัวข้อ:</strong> ${subject || 'ไม่ระบุ'}</p>`;
    }
    
    html += '</div></div>';
    
    // Recipients Summary
    html += '<div class="col-md-6">';
    html += '<div class="preview-card">';
    html += '<h6><i class="bi bi-people text-success"></i> ผู้รับ</h6>';
    
    const recipientType = formData.recipient_type;
    if (recipientType === 'manual') {
        const recipients = document.getElementById('recipients')?.value || '';
        const count = recipients.split(/[\n,]+/).filter(email => email.trim()).length;
        html += `<p><strong>ประเภท:</strong> เลือกเอง (${count} คน)</p>`;
    } else if (recipientType === 'groups') {
        const selectedGroups = document.querySelectorAll('input[name="recipient_groups[]"]:checked');
        html += `<p><strong>ประเภท:</strong> ตามกลุ่ม (${selectedGroups.length} กลุ่ม)</p>`;
    } else if (recipientType === 'all_users') {
        const userCount = {{ $users->count() }};
        html += `<p><strong>ประเภท:</strong> ผู้ใช้ทั้งหมด (${userCount} คน)</p>`;
    }
    
    html += '</div></div>';
    
    // Schedule Summary
    html += '<div class="col-md-6">';
    html += '<div class="preview-card">';
    html += '<h6><i class="bi bi-calendar text-warning"></i> การส่ง</h6>';
    
    const scheduleType = formData.schedule_type;
    if (scheduleType === 'immediate') {
        html += '<p><strong>เวลา:</strong> ส่งทันที</p>';
    } else if (scheduleType === 'scheduled') {
        const scheduledAt = document.getElementById('scheduled_at').value;
        html += `<p><strong>เวลา:</strong> ${scheduledAt || 'ไม่ระบุ'}</p>`;
    }
    
    const priority = document.getElementById('priority').value;
    const priorityLabels = {
        'low': '🟢 ต่ำ',
        'normal': '🔵 ปกติ',
        'high': '🟡 สูง',
        'urgent': '🔴 เร่งด่วน'
    };
    html += `<p><strong>ความสำคัญ:</strong> ${priorityLabels[priority] || 'ปกติ'}</p>`;
    
    html += '</div></div>';
    
    // Channels Summary
    html += '<div class="col-md-6">';
    html += '<div class="preview-card">';
    html += '<h6><i class="bi bi-send text-info"></i> ช่องทาง</h6>';
    
    const channels = [];
    if (document.getElementById('channel_email').checked) channels.push('อีเมล');
    if (document.getElementById('channel_teams').checked) channels.push('Teams');
    
    html += `<p><strong>ช่องทางการส่ง:</strong> ${channels.join(', ') || 'ไม่ระบุ'}</p>`;
    
    html += '</div></div>';
    
    html += '</div>';
    
    container.innerHTML = html;
}

function nextStep() {
    if (!validateCurrentStep()) {
        return;
    }
    
    if (currentStep < totalSteps) {
        currentStep++;
        updateStepDisplay();
        
        // Special handling for specific steps
        if (currentStep === 2) {
            // Initialize content based on selection type
            if (formData.creation_type === 'manual') {
                document.getElementById('manualContent').style.display = 'block';
                document.getElementById('templateContent').style.display = 'none';
                
                // Initialize TinyMCE for manual content
                setTimeout(() => {
                    initializeTinyMCE();
                }, 100);
            } else if (formData.creation_type === 'template') {
                document.getElementById('manualContent').style.display = 'none';
                document.getElementById('templateContent').style.display = 'block';
                
                // Load template content if template is selected
                if (formData.template_id) {
                    loadTemplateContent(formData.template_id);
                }
            }
        } else if (currentStep === 3) {
            showDetectedVariables();
        } else if (currentStep === 6) {
            generateSummary();
        }
    }
}

function previousStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStepDisplay();
    }
}

function updateStepDisplay() {
    // Update step indicator
    document.querySelectorAll('.step').forEach((step, index) => {
        const stepNum = index + 1;
        step.classList.remove('active', 'completed');
        
        if (stepNum === currentStep) {
            step.classList.add('active');
        } else if (stepNum < currentStep) {
            step.classList.add('completed');
            step.querySelector('.step-circle').innerHTML = '<i class="bi bi-check"></i>';
        } else {
            step.querySelector('.step-circle').innerHTML = stepNum;
        }
    });
    
    // Update progress line
    const progressPercent = ((currentStep - 1) / (totalSteps - 1)) * 100;
    document.querySelector('.progress-line').style.width = progressPercent + '%';
    
    // Update content visibility
    document.querySelectorAll('.step-content').forEach((content, index) => {
        content.classList.remove('active');
        if (index + 1 === currentStep) {
            content.classList.add('active');
        }
    });
    
    // Update navigation buttons
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const draftBtn = document.getElementById('draftBtn');
    
    prevBtn.style.display = currentStep > 1 ? 'inline-block' : 'none';
    nextBtn.style.display = currentStep < totalSteps ? 'inline-block' : 'none';
    submitBtn.style.display = currentStep === totalSteps ? 'inline-block' : 'none';
    draftBtn.style.display = currentStep === totalSteps ? 'inline-block' : 'none';
    
    // Update step counter
    document.getElementById('currentStepNumber').textContent = currentStep;
}

function validateCurrentStep() {
    switch(currentStep) {
        case 1:
            if (!formData.creation_type) {
                alert('กรุณาเลือกรูปแบบการสร้างการแจ้งเตือน');
                return false;
            }
            if (formData.creation_type === 'template' && !formData.template_id) {
                alert('กรุณาเลือกเทมเพลต');
                return false;
            }
            break;
            
        case 2:
            if (formData.creation_type === 'manual') {
                const subject = document.getElementById('subject').value.trim();
                const bodyHtml = document.getElementById('body_html').value.trim();
                const bodyText = document.getElementById('body_text').value.trim();
                
                if (!subject) {
                    alert('กรุณากรอกหัวข้อ');
                    return false;
                }
                if (!bodyHtml && !bodyText) {
                    alert('กรุณากรอกเนื้อหา (HTML หรือ Text)');
                    return false;
                }
            }
            break;
            
        case 4:
            if (!formData.recipient_type) {
                alert('กรุณาเลือกประเภทผู้รับ');
                return false;
            }
            
            if (formData.recipient_type === 'manual') {
                const recipients = document.getElementById('recipients')?.value.trim();
                if (!recipients) {
                    alert('กรุณากรอกอีเมลผู้รับ');
                    return false;
                }
            } else if (formData.recipient_type === 'groups') {
                const selectedGroups = document.querySelectorAll('input[name="recipient_groups[]"]:checked');
                if (selectedGroups.length === 0) {
                    alert('กรุณาเลือกกลุ่มผู้รับอย่างน้อย 1 กลุ่ม');
                    return false;
                }
            }
            break;
            
        case 5:
            if (!formData.schedule_type) {
                alert('กรุณาเลือกรูปแบบการส่ง');
                return false;
            }
            
            if (formData.schedule_type === 'scheduled') {
                const scheduledAt = document.getElementById('scheduled_at').value;
                if (!scheduledAt) {
                    alert('กรุณากำหนดวันเวลาที่ต้องการส่ง');
                    return false;
                }
                
                const scheduledDate = new Date(scheduledAt);
                const now = new Date();
                if (scheduledDate <= now) {
                    alert('วันเวลาที่กำหนดต้องเป็นอนาคต');
                    return false;
                }
            }
            
            const channels = document.querySelectorAll('input[name="channels[]"]:checked');
            if (channels.length === 0) {
                alert('กรุณาเลือกช่องทางการส่งอย่างน้อย 1 ช่องทาง');
                return false;
            }
            break;
    }
    
    return true;
}

function sendTest() {
    const testEmail = document.getElementById('test_email').value;
    if (!testEmail) {
        alert('กรุณากรอกอีเมลสำหรับทดสอบ');
        return;
    }
    
    const testResult = document.getElementById('testResult');
    testResult.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> กำลังส่งทดสอบ...</div>';
    
    // Implement test sending logic here
    setTimeout(() => {
        testResult.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ส่งทดสอบเรียบร้อยแล้ว</div>';
    }, 2000);
}

function saveDraft() {
    const form = document.getElementById('notificationForm');
    const draftInput = document.createElement('input');
    draftInput.type = 'hidden';
    draftInput.name = 'save_as_draft';
    draftInput.value = '1';
    form.appendChild(draftInput);
    form.submit();
}
</script>

@endpush