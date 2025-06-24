@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนใหม่')

@push('styles')
<style>
    .recipient-section {
        transition: all 0.3s ease;
    }
    
    .template-preview {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .variable-input {
        margin-bottom: 0.5rem;
    }
    
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .card-header h6 {
        color: white !important;
    }
    
    .form-check-input:checked {
        background-color: #667eea;
        border-color: #667eea;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    }
    
    .alert-warning {
        background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
        border: none;
        color: #2d3436;
    }
    
    .loading-spinner {
        width: 1rem;
        height: 1rem;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-plus-circle text-primary"></i> สร้างการแจ้งเตือนใหม่
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> กลับ
        </a>
    </div>
</div>

<form method="POST" action="{{ route('admin.notifications.store') }}" id="notificationForm">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <!-- Content Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-file-text"></i> เนื้อหาการแจ้งเตือน</h6>
                </div>
                <div class="card-body">
                    <!-- Template Selection -->
                    <div class="mb-4">
                        <label for="template_id" class="form-label fw-bold">เทมเพลต (ไม่บังคับ)</label>
                        <select name="template_id" id="template_id" class="form-select @error('template_id') is-invalid @enderror">
                            <option value="">เลือกเทมเพลต หรือสร้างเองแบบกำหนดเอง</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" 
                                        data-variables="{{ json_encode($template->variables ?? []) }}"
                                        data-channels="{{ json_encode($template->supported_channels) }}"
                                        {{ old('template_id', $selectedTemplate?->id) == $template->id ? 'selected' : '' }}>
                                    {{ $template->name }} ({{ implode(', ', $template->supported_channels) }})
                                </option>
                            @endforeach
                        </select>
                        @error('template_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Template Loading Indicator -->
                    <div id="templateLoading" style="display: none;" class="text-center mb-3">
                        <div class="loading-spinner d-inline-block me-2"></div>
                        <span class="text-muted">กำลังโหลดเทมเพลต...</span>
                    </div>

                    <!-- Custom Content -->
                    <div id="customContent">
                        <div class="mb-3">
                            <label for="subject" class="form-label fw-bold">หัวข้อ *</label>
                            <input type="text" name="subject" id="subject" 
                                   class="form-control @error('subject') is-invalid @enderror" 
                                   value="{{ old('subject', $selectedTemplate?->subject_template) }}" 
                                   maxlength="255" placeholder="กรอกหัวข้อการแจ้งเตือน">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="body_html" class="form-label fw-bold">
                                        เนื้อหา HTML 
                                        <small class="text-muted">(สำหรับอีเมล)</small>
                                    </label>
                                    <textarea name="body_html" id="body_html" 
                                              class="form-control @error('body_html') is-invalid @enderror" 
                                              rows="8" placeholder="เนื้อหาแบบ HTML สำหรับอีเมล">{{ old('body_html', $selectedTemplate?->body_html_template) }}</textarea>
                                    @error('body_html')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="body_text" class="form-label fw-bold">
                                        เนื้อหา Text 
                                        <small class="text-muted">(สำหรับ Teams)</small>
                                    </label>
                                    <textarea name="body_text" id="body_text" 
                                              class="form-control @error('body_text') is-invalid @enderror" 
                                              rows="8" placeholder="เนื้อหาแบบข้อความธรรมดา สำหรับ Teams และ Fallback">{{ old('body_text', $selectedTemplate?->body_text_template) }}</textarea>
                                    @error('body_text')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Template Variables -->
                    <div id="templateVariables" style="display: none;">
                        <div class="alert alert-info border-0">
                            <h6><i class="bi bi-info-circle"></i> ตัวแปรของเทมเพลต</h6>
                            <p class="mb-2">กรอกข้อมูลตัวแปรที่จะแทนที่ในเทมเพลต:</p>
                            <div id="variableInputs"></div>
                        </div>
                    </div>

                    <!-- Template Preview -->
                    <div id="templatePreview" style="display: none;">
                        <h6><i class="bi bi-eye"></i> ตัวอย่างเทมเพลต</h6>
                        <div class="template-preview p-3">
                            <div id="previewContent"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="updatePreview()">
                            <i class="bi bi-arrow-clockwise"></i> อัปเดตตัวอย่าง
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recipients Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-people"></i> ผู้รับการแจ้งเตือน</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">ประเภทผู้รับ *</label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check h-100">
                                    <input class="form-check-input" type="radio" name="recipient_type" 
                                           id="manual" value="manual" 
                                           {{ old('recipient_type', 'manual') == 'manual' ? 'checked' : '' }}>
                                    <label class="form-check-label d-flex align-items-center" for="manual">
                                        <i class="bi bi-person-plus me-2 text-primary"></i>
                                        <div>
                                            <strong>เลือกเอง</strong>
                                            <small class="d-block text-muted">กรอกอีเมลผู้รับเอง</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check h-100">
                                    <input class="form-check-input" type="radio" name="recipient_type" 
                                           id="groups" value="groups" 
                                           {{ old('recipient_type') == 'groups' ? 'checked' : '' }}>
                                    <label class="form-check-label d-flex align-items-center" for="groups">
                                        <i class="bi bi-people me-2 text-success"></i>
                                        <div>
                                            <strong>ตามกลุ่ม</strong>
                                            <small class="d-block text-muted">เลือกจากกลุ่มที่มีอยู่</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check h-100">
                                    <input class="form-check-input" type="radio" name="recipient_type" 
                                           id="all_users" value="all_users" 
                                           {{ old('recipient_type') == 'all_users' ? 'checked' : '' }}>
                                    <label class="form-check-label d-flex align-items-center" for="all_users">
                                        <i class="bi bi-globe me-2 text-warning"></i>
                                        <div>
                                            <strong>ผู้ใช้ทั้งหมด</strong>
                                            <small class="d-block text-muted">ส่งให้ทุกคนในระบบ</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Manual Recipients -->
                    <div id="manualRecipients" class="recipient-section">
                        <label for="recipients" class="form-label fw-bold">อีเมลผู้รับ *</label>
                        <textarea name="recipients[]" id="recipients" 
                                  class="form-control @error('recipients') is-invalid @enderror" 
                                  rows="5" placeholder="กรอกอีเมล แยกด้วย Enter หรือ comma&#10;example1@company.com&#10;example2@company.com">{{ old('recipients') ? implode("\n", old('recipients')) : '' }}</textarea>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> 
                            กรอกอีเมลผู้รับ แยกด้วย Enter หรือ comma
                        </div>
                        @error('recipients')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Group Recipients -->
                    <div id="groupRecipients" class="recipient-section" style="display: none;">
                        <label class="form-label fw-bold">เลือกกลุ่ม *</label>
                        <div class="row g-2">
                            @forelse($groups->chunk(3) as $groupChunk)
                                <div class="col-md-4">
                                    @foreach($groupChunk as $group)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="recipient_groups[]" id="group_{{ $group->id }}" 
                                                   value="{{ $group->id }}"
                                                   {{ in_array($group->id, old('recipient_groups', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="group_{{ $group->id }}">
                                                <strong>{{ $group->name }}</strong>
                                                <small class="d-block text-muted">
                                                    {{ $group->member_count ?? $group->users_count ?? 0 }} คน
                                                </small>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        ยังไม่มีกลุ่มในระบบ กรุณาสร้างกลุ่มก่อน
                                    </div>
                                </div>
                            @endforelse
                        </div>
                        @error('recipient_groups')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- All Users -->
                    <div id="allUsersRecipients" class="recipient-section" style="display: none;">
                        <div class="alert alert-warning border-0">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle fs-4 me-3"></i>
                                <div>
                                    <strong>ส่งให้ผู้ใช้ทั้งหมด ({{ $users->count() }} คน)</strong>
                                    <br>การแจ้งเตือนจะถูกส่งไปยังผู้ใช้ทุกคนในระบบ กรุณาตรวจสอบเนื้อหาให้ดี
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Settings Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-gear"></i> การตั้งค่า</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">ช่องทางการส่ง *</label>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" 
                                       value="email" id="channel_email" 
                                       {{ in_array('email', old('channels', ['email'])) ? 'checked' : '' }}>
                                <label class="form-check-label d-flex align-items-center" for="channel_email">
                                    <i class="bi bi-envelope me-2 text-primary"></i>
                                    <strong>อีเมล</strong>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" 
                                       value="teams" id="channel_teams"
                                       {{ in_array('teams', old('channels', [])) ? 'checked' : '' }}>
                                <label class="form-check-label d-flex align-items-center" for="channel_teams">
                                    <i class="bi bi-microsoft-teams me-2 text-info"></i>
                                    <strong>Microsoft Teams</strong>
                                </label>
                            </div>
                        </div>
                        @error('channels')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="priority" class="form-label fw-bold">ระดับความสำคัญ *</label>
                        <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror">
                            <option value="low" {{ old('priority', 'normal') == 'low' ? 'selected' : '' }}>
                                🟢 ต่ำ
                            </option>
                            <option value="normal" {{ old('priority', 'normal') == 'normal' ? 'selected' : '' }}>
                                🔵 ปกติ
                            </option>
                            <option value="high" {{ old('priority', 'normal') == 'high' ? 'selected' : '' }}>
                                🟡 สูง
                            </option>
                            <option value="urgent" {{ old('priority', 'normal') == 'urgent' ? 'selected' : '' }}>
                                🔴 เร่งด่วน
                            </option>
                        </select>
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">กำหนดการส่ง *</label>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="schedule_type" 
                                       id="immediate" value="immediate" 
                                       {{ old('schedule_type', 'immediate') == 'immediate' ? 'checked' : '' }}>
                                <label class="form-check-label d-flex align-items-center" for="immediate">
                                    <i class="bi bi-send me-2 text-success"></i>
                                    <strong>ส่งทันที</strong>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="schedule_type" 
                                       id="scheduled" value="scheduled"
                                       {{ old('schedule_type') == 'scheduled' ? 'checked' : '' }}>
                                <label class="form-check-label d-flex align-items-center" for="scheduled">
                                    <i class="bi bi-calendar me-2 text-warning"></i>
                                    <strong>กำหนดเวลา</strong>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="scheduledDateTime" style="display: none;">
                        <div class="mb-3">
                            <label for="scheduled_at" class="form-label fw-bold">วันเวลาที่กำหนด</label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" 
                                   class="form-control @error('scheduled_at') is-invalid @enderror" 
                                   value="{{ old('scheduled_at') }}" 
                                   min="{{ now()->format('Y-m-d\TH:i') }}">
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Notification Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-bug"></i> ทดสอบการแจ้งเตือน</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">ทดสอบการส่งการแจ้งเตือนก่อนส่งจริง</p>
                    <div class="mb-2">
                        <input type="email" id="test_email" class="form-control form-control-sm" 
                               placeholder="อีเมลสำหรับทดสอบ" value="{{ auth()->user()->email }}">
                    </div>
                    <button type="button" class="btn btn-outline-info btn-sm w-100" onclick="sendTestNotification()">
                        <i class="bi bi-send"></i> ส่งทดสอบ
                    </button>
                    <div id="testResult" class="mt-2"></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle"></i> สร้างการแจ้งเตือน
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
                            <i class="bi bi-save"></i> บันทึกร่าง
                        </button>
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-danger">
                            <i class="bi bi-x-circle"></i> ยกเลิก
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeRecipientTabs();
    initializeScheduleToggle();
    initializeTemplateHandling();
    
    // Initialize current state
    updateRecipientDisplay();
    updateScheduleDisplay();
});

function initializeRecipientTabs() {
    const recipientTypes = document.querySelectorAll('input[name="recipient_type"]');
    
    recipientTypes.forEach(radio => {
        radio.addEventListener('change', updateRecipientDisplay);
    });
}

function updateRecipientDisplay() {
    const checkedType = document.querySelector('input[name="recipient_type"]:checked');
    const allSections = document.querySelectorAll('.recipient-section');
    
    // Hide all sections
    allSections.forEach(section => {
        section.style.display = 'none';
    });
    
    if (checkedType) {
        const targetSection = document.getElementById(checkedType.value + 'Recipients');
        if (targetSection) {
            targetSection.style.display = 'block';
        }
    }
}

function initializeScheduleToggle() {
    const scheduleTypes = document.querySelectorAll('input[name="schedule_type"]');
    
    scheduleTypes.forEach(radio => {
        radio.addEventListener('change', updateScheduleDisplay);
    });
}

function updateScheduleDisplay() {
    const checkedType = document.querySelector('input[name="schedule_type"]:checked');
    const scheduledDateTime = document.getElementById('scheduledDateTime');
    
    if (checkedType && checkedType.value === 'scheduled') {
        scheduledDateTime.style.display = 'block';
    } else {
        scheduledDateTime.style.display = 'none';
    }
}

function initializeTemplateHandling() {
    const templateSelect = document.getElementById('template_id');
    
    templateSelect.addEventListener('change', function() {
        if (this.value) {
            loadTemplate(this.value);
        } else {
            showCustomContent();
        }
    });
    
    // Load template if pre-selected
    if (templateSelect.value) {
        loadTemplate(templateSelect.value);
    }
}

function showCustomContent() {
    document.getElementById('customContent').style.display = 'block';
    document.getElementById('templateVariables').style.display = 'none';
    document.getElementById('templatePreview').style.display = 'none';
}

function loadTemplate(templateId) {
    const loadingEl = document.getElementById('templateLoading');
    const customContent = document.getElementById('customContent');
    const templateVariables = document.getElementById('templateVariables');
    const templatePreview = document.getElementById('templatePreview');
    
    // Show loading
    loadingEl.style.display = 'block';
    customContent.style.display = 'none';
    templateVariables.style.display = 'none';
    templatePreview.style.display = 'none';
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch('/admin/notifications/template-preview', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            template_id: templateId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        loadingEl.style.display = 'none';
        
        if (data.success) {
            const template = data.template;
            const preview = data.preview;
            
            // Hide custom content
            customContent.style.display = 'none';
            
            // Show template variables if any
            if (template.variables && template.variables.length > 0) {
                showTemplateVariables(template.variables);
                templateVariables.style.display = 'block';
            } else {
                templateVariables.style.display = 'none';
            }
            
            // Show preview
            showTemplatePreview(preview);
            templatePreview.style.display = 'block';
            
            // Update supported channels
            updateSupportedChannels(template.supported_channels);
            
        } else {
            throw new Error(data.message || 'Failed to load template');
        }
    })
    .catch(error => {
        console.error('Error loading template:', error);
        loadingEl.style.display = 'none';
        showCustomContent();
        
        // Show error message
        showAlert('เกิดข้อผิดพลาดในการโหลดเทมเพลต: ' + error.message, 'danger');
    });
}

function showTemplateVariables(variables) {
    const variableInputs = document.getElementById('variableInputs');
    variableInputs.innerHTML = '';
    
    variables.forEach(variable => {
        const inputGroup = document.createElement('div');
        inputGroup.className = 'variable-input';
        inputGroup.innerHTML = `
            <label class="form-label small fw-bold">${variable}</label>
            <input type="text" 
                   class="form-control form-control-sm" 
                   name="variables[${variable}]" 
                   placeholder="กรอกค่าสำหรับ ${variable}"
                   onchange="updatePreview()">
        `;
        variableInputs.appendChild(inputGroup);
    });
}

function showTemplatePreview(preview) {
    const previewContent = document.getElementById('previewContent');
    previewContent.innerHTML = `
        <div class="mb-3">
            <strong class="text-primary">หัวข้อ:</strong>
            <div class="border rounded p-2 mt-1 bg-white">${preview.subject || 'ไม่มีหัวข้อ'}</div>
        </div>
        ${preview.body_html ? `
        <div class="mb-3">
            <strong class="text-primary">เนื้อหา HTML:</strong>
            <div class="border rounded p-2 mt-1 bg-white" style="max-height: 200px; overflow-y: auto;">
                ${preview.body_html}
            </div>
        </div>
        ` : ''}
        ${preview.body_text ? `
        <div class="mb-3">
            <strong class="text-primary">เนื้อหา Text:</strong>
            <pre class="border rounded p-2 mt-1 bg-white" style="max-height: 150px; overflow-y: auto; white-space: pre-wrap;">${preview.body_text}</pre>
        </div>
        ` : ''}
    `;
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

function updatePreview() {
    const templateId = document.getElementById('template_id').value;
    if (!templateId) return;
    
    // Collect variable values
    const variables = {};
    document.querySelectorAll('input[name^="variables["]').forEach(input => {
        const varName = input.name.match(/variables\[([^\]]+)\]/)[1];
        variables[varName] = input.value;
    });
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch('/admin/notifications/template-preview', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            template_id: templateId,
            variables: variables
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showTemplatePreview(data.preview);
        }
    })
    .catch(error => {
        console.error('Error updating preview:', error);
    });
}

function sendTestNotification() {
    const testEmail = document.getElementById('test_email').value;
    const channels = Array.from(document.querySelectorAll('input[name="channels[]"]:checked')).map(cb => cb.value);
    const priority = document.getElementById('priority').value;
    
    let subject, message;
    
    // Get content based on whether template is selected
    const templateId = document.getElementById('template_id').value;
    if (templateId) {
        // For template: get from preview
        const previewContent = document.getElementById('previewContent');
        if (previewContent) {
            const subjectDiv = previewContent.querySelector('.border');
            const bodyDiv = previewContent.querySelectorAll('.border')[1];
            subject = subjectDiv ? subjectDiv.textContent : 'Template Test';
            message = bodyDiv ? bodyDiv.innerHTML || bodyDiv.textContent : 'Template test message';
        } else {
            subject = 'Template Test';
            message = 'Template test message';
        }
    } else {
        // For custom content
        subject = document.getElementById('subject').value;
        message = document.getElementById('body_text').value || document.getElementById('body_html').value;
    }

    if (!testEmail || !subject || !message || channels.length === 0) {
        showAlert('กรุณากรอกข้อมูลให้ครบถ้วนก่อนทดสอบ', 'warning');
        return;
    }

    const testResult = document.getElementById('testResult');
    testResult.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>กำลังส่ง...';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch('/admin/notifications/test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            test_email: testEmail,
            channels: channels,
            subject: subject,
            message: message,
            priority: priority
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            testResult.innerHTML = `<div class="alert alert-success alert-sm mb-0 mt-2">${data.message}</div>`;
        } else {
            testResult.innerHTML = `<div class="alert alert-danger alert-sm mb-0 mt-2">${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Error sending test:', error);
        testResult.innerHTML = '<div class="alert alert-danger alert-sm mb-0 mt-2">เกิดข้อผิดพลาดในการส่งทดสอบ</div>';
    });
}

function saveDraft() {
    // Add draft input
    const form = document.getElementById('notificationForm');
    const draftInput = document.createElement('input');
    draftInput.type = 'hidden';
    draftInput.name = 'save_as_draft';
    draftInput.value = '1';
    form.appendChild(draftInput);
    
    // Submit form
    form.submit();
}

function showAlert(message, type = 'info') {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to body
    document.body.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Form validation before submit
document.getElementById('notificationForm').addEventListener('submit', function(e) {
    const recipientType = document.querySelector('input[name="recipient_type"]:checked').value;
    const channels = document.querySelectorAll('input[name="channels[]"]:checked');
    const templateId = document.getElementById('template_id').value;
    
    let isValid = true;
    let errorMessage = '';
    
    // Check channels
    if (channels.length === 0) {
        isValid = false;
        errorMessage = 'กรุณาเลือกช่องทางการส่งอย่างน้อย 1 ช่องทาง';
    }
    
    // Check content
    if (!templateId) {
        const subject = document.getElementById('subject').value.trim();
        const bodyHtml = document.getElementById('body_html').value.trim();
        const bodyText = document.getElementById('body_text').value.trim();
        
        if (!subject) {
            isValid = false;
            errorMessage = 'กรุณากรอกหัวข้อการแจ้งเตือน';
        } else if (!bodyHtml && !bodyText) {
            isValid = false;
            errorMessage = 'กรุณากรอกเนื้อหาการแจ้งเตือน (HTML หรือ Text)';
        }
    }
    
    // Check recipients
    if (recipientType === 'manual') {
        const recipients = document.getElementById('recipients').value.trim();
        if (!recipients) {
            isValid = false;
            errorMessage = 'กรุณากรอกอีเมลผู้รับ';
        }
    } else if (recipientType === 'groups') {
        const groups = document.querySelectorAll('input[name="recipient_groups[]"]:checked');
        if (groups.length === 0) {
            isValid = false;
            errorMessage = 'กรุณาเลือกกลุ่มผู้รับอย่างน้อย 1 กลุ่ม';
        }
    }
    
    // Check scheduled time
    const scheduleType = document.querySelector('input[name="schedule_type"]:checked').value;
    if (scheduleType === 'scheduled') {
        const scheduledAt = document.getElementById('scheduled_at').value;
        if (!scheduledAt) {
            isValid = false;
            errorMessage = 'กรุณากำหนดวันเวลาที่ต้องการส่ง';
        } else {
            const scheduledDate = new Date(scheduledAt);
            const now = new Date();
            if (scheduledDate <= now) {
                isValid = false;
                errorMessage = 'วันเวลาที่กำหนดต้องเป็นอนาคต';
            }
        }
    }
    
    if (!isValid) {
        e.preventDefault();
        showAlert(errorMessage, 'danger');
        return false;
    }
});

// Parse recipients textarea for better UX
document.getElementById('recipients').addEventListener('input', function() {
    let emails = this.value.split(/[\n,]+/).map(email => email.trim()).filter(email => email);
    
    // Show recipient count
    const recipientSection = document.getElementById('manualRecipients');
    let countDisplay = recipientSection.querySelector('.recipient-count');
    
    if (!countDisplay) {
        countDisplay = document.createElement('small');
        countDisplay.className = 'recipient-count text-muted';
        recipientSection.appendChild(countDisplay);
    }
    
    countDisplay.textContent = emails.length > 0 ? `จำนวนผู้รับ: ${emails.length} คน` : '';
});
</script>
@endpush