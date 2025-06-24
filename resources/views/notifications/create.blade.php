@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนใหม่')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-plus-circle"></i> สร้างการแจ้งเตือนใหม่</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> กลับ
        </a>
    </div>
</div>

<form method="POST" action="{{ route('notifications.store') }}" id="notificationForm">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <!-- Content Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-file-text"></i> เนื้อหาการแจ้งเตือน</h6>
                </div>
                <div class="card-body">
                    <!-- Template Selection -->
                    <div class="mb-3">
                        <label for="template_id" class="form-label">เทมเพลต (ไม่บังคับ)</label>
                        <select name="template_id" id="template_id" class="form-select">
                            <option value="">เลือกเทมเพลต หรือสร้างเองแบบกำหนดเอง</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ old('template_id', $selectedTemplate?->id) == $template->id ? 'selected' : '' }}>
                                    {{ $template->name }} ({{ implode(', ', $template->supported_channels) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Custom Content (shown when no template or template selected) -->
                    <div id="customContent">
                        <div class="mb-3">
                            <label for="subject" class="form-label">หัวข้อ *</label>
                            <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror" 
                                value="{{ old('subject', $selectedTemplate?->subject_template) }}" maxlength="255">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="body_html" class="form-label">เนื้อหา HTML</label>
                                    <textarea name="body_html" id="body_html" class="form-control @error('body_html') is-invalid @enderror" 
                                        rows="8" placeholder="เนื้อหาแบบ HTML สำหรับอีเมล">{{ old('body_html', $selectedTemplate?->body_html_template) }}</textarea>
                                    @error('body_html')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="body_text" class="form-label">เนื้อหา Text</label>
                                    <textarea name="body_text" id="body_text" class="form-control @error('body_text') is-invalid @enderror" 
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
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> ตัวแปรของเทมเพลต</h6>
                            <p class="mb-2">กรอกข้อมูลตัวแปรที่จะแทนที่ในเทมเพลต:</p>
                            <div id="variableInputs"></div>
                        </div>
                    </div>

                    <!-- Template Preview -->
                    <div id="templatePreview" style="display: none;">
                        <h6><i class="bi bi-eye"></i> ตัวอย่างเทมเพลต</h6>
                        <div class="border rounded p-3 bg-light">
                            <div id="previewContent"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="updatePreview()">
                            <i class="bi bi-arrow-clockwise"></i> อัปเดตตัวอย่าง
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recipients Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-people"></i> ผู้รับการแจ้งเตือน</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">ประเภทผู้รับ *</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="recipient_type" id="manual" value="manual" 
                                        {{ old('recipient_type', 'manual') == 'manual' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="manual">
                                        <i class="bi bi-person-plus"></i> เลือกเอง
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="recipient_type" id="groups" value="groups" 
                                        {{ old('recipient_type') == 'groups' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="groups">
                                        <i class="bi bi-people"></i> ตามกลุ่ม
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="recipient_type" id="all_users" value="all_users" 
                                        {{ old('recipient_type') == 'all_users' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="all_users">
                                        <i class="bi bi-globe"></i> ผู้ใช้ทั้งหมด
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Manual Recipients -->
                    <div id="manualRecipients" class="recipient-section">
                        <label for="recipients" class="form-label">อีเมลผู้รับ *</label>
                        <textarea name="recipients[]" id="recipients" class="form-control @error('recipients') is-invalid @enderror" 
                            rows="4" placeholder="กรอกอีเมล แยกด้วย Enter หรือ comma&#10;example1@company.com&#10;example2@company.com">{{ old('recipients') ? implode("\n", old('recipients')) : '' }}</textarea>
                        <div class="form-text">กรอกอีเมลผู้รับ แยกด้วย Enter หรือ comma</div>
                        @error('recipients')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Group Recipients -->
                    <div id="groupRecipients" class="recipient-section" style="display: none;">
                        <label class="form-label">เลือกกลุ่ม *</label>
                        <div class="row">
                            @foreach($groups->chunk(3) as $groupChunk)
                                <div class="col-md-4">
                                    @foreach($groupChunk as $group)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="recipient_groups[]" 
                                                id="group_{{ $group->id }}" value="{{ $group->id }}"
                                                {{ in_array($group->id, old('recipient_groups', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="group_{{ $group->id }}">
                                                {{ $group->name }} ({{ $group->member_count }} คน)
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        @error('recipient_groups')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- All Users -->
                    <div id="allUsersRecipients" class="recipient-section" style="display: none;">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>ส่งให้ผู้ใช้ทั้งหมด ({{ $users->count() }} คน)</strong>
                            <br>การแจ้งเตือนจะถูกส่งไปยังผู้ใช้ทุกคนในระบบ กรุณาตรวจสอบเนื้อหาให้ดี
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Settings Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-gear"></i> การตั้งค่า</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">ช่องทางการส่ง *</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="channel_email" 
                                {{ in_array('email', old('channels', ['email'])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="channel_email">
                                <i class="bi bi-envelope"></i> อีเมล
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="teams" id="channel_teams"
                                {{ in_array('teams', old('channels', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="channel_teams">
                                <i class="bi bi-microsoft-teams"></i> Microsoft Teams
                            </label>
                        </div>
                        @error('channels')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="priority" class="form-label">ระดับความสำคัญ *</label>
                        <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror">
                            <option value="low" {{ old('priority', 'normal') == 'low' ? 'selected' : '' }}>ต่ำ</option>
                            <option value="normal" {{ old('priority', 'normal') == 'normal' ? 'selected' : '' }}>ปกติ</option>
                            <option value="high" {{ old('priority', 'normal') == 'high' ? 'selected' : '' }}>สูง</option>
                            <option value="urgent" {{ old('priority', 'normal') == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                        </select>
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">กำหนดการส่ง *</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="schedule_type" id="immediate" value="immediate" 
                                {{ old('schedule_type', 'immediate') == 'immediate' ? 'checked' : '' }}>
                            <label class="form-check-label" for="immediate">
                                <i class="bi bi-send"></i> ส่งทันที
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="schedule_type" id="scheduled" value="scheduled"
                                {{ old('schedule_type') == 'scheduled' ? 'checked' : '' }}>
                            <label class="form-check-label" for="scheduled">
                                <i class="bi bi-calendar"></i> กำหนดเวลา
                            </label>
                        </div>
                    </div>

                    <div id="scheduledDateTime" style="display: none;">
                        <div class="mb-3">
                            <label for="scheduled_at" class="form-label">วันเวลาที่กำหนด</label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" 
                                class="form-control @error('scheduled_at') is-invalid @enderror" 
                                value="{{ old('scheduled_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}">
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Notification Card -->
            <div class="card mb-4 test-notification-form">
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
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> สร้างการแจ้งเตือน
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
                            <i class="bi bi-save"></i> บันทึกร่าง
                        </button>
                        <a href="{{ route('notifications.index') }}" class="btn btn-outline-danger">
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
    // Handle recipient type changes
    const recipientTypes = document.querySelectorAll('input[name="recipient_type"]');
    const recipientSections = document.querySelectorAll('.recipient-section');
    
    recipientTypes.forEach(radio => {
        radio.addEventListener('change', function() {
            recipientSections.forEach(section => section.style.display = 'none');
            
            const targetSection = document.getElementById(this.value + 'Recipients');
            if (targetSection) {
                targetSection.style.display = 'block';
            }
        });
    });

    // Handle schedule type changes
    const scheduleTypes = document.querySelectorAll('input[name="schedule_type"]');
    const scheduledDateTime = document.getElementById('scheduledDateTime');
    
    scheduleTypes.forEach(radio => {
        radio.addEventListener('change', function() {
            scheduledDateTime.style.display = this.value === 'scheduled' ? 'block' : 'none';
        });
    });

    // Template handling
    const templateSelect = document.getElementById('template_id');
    const customContent = document.getElementById('customContent');
    const templateVariables = document.getElementById('templateVariables');
    const templatePreview = document.getElementById('templatePreview');

    templateSelect.addEventListener('change', function() {
        if (this.value) {
            loadTemplate(this.value);
        } else {
            customContent.style.display = 'block';
            templateVariables.style.display = 'none';
            templatePreview.style.display = 'none';
        }
    });

    // Initialize recipient type display
    const checkedRecipientType = document.querySelector('input[name="recipient_type"]:checked');
    if (checkedRecipientType) {
        checkedRecipientType.dispatchEvent(new Event('change'));
    }

    // Initialize schedule type display
    const checkedScheduleType = document.querySelector('input[name="schedule_type"]:checked');
    if (checkedScheduleType) {
        checkedScheduleType.dispatchEvent(new Event('change'));
    }

    // Parse recipients textarea
    document.getElementById('recipients').addEventListener('input', function() {
        let emails = this.value.split(/[\n,]+/).map(email => email.trim()).filter(email => email);
        // Update hidden input or handle as needed
    });
});

function loadTemplate(templateId) {
    fetch(`/notifications/template-preview`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            template_id: templateId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show template preview
            document.getElementById('customContent').style.display = 'none';
            document.getElementById('templateVariables').style.display = 'block';
            document.getElementById('templatePreview').style.display = 'block';
            
            // Show preview content
            document.getElementById('previewContent').innerHTML = `
                <strong>หัวข้อ:</strong> ${data.preview.subject || ''}<br>
                <strong>เนื้อหา HTML:</strong><br>
                <div class="border p-2 mt-1">${data.preview.body_html || ''}</div>
                <strong>เนื้อหา Text:</strong><br>
                <pre class="border p-2 mt-1">${data.preview.body_text || ''}</pre>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading template:', error);
        alert('เกิดข้อผิดพลาดในการโหลดเทมเพลต');
    });
}

function updatePreview() {
    const templateId = document.getElementById('template_id').value;
    if (templateId) {
        // Get variable values
        const variables = {};
        document.querySelectorAll('#variableInputs input').forEach(input => {
            variables[input.name] = input.value;
        });
        
        // Request updated preview
        loadTemplate(templateId);
    }
}

function sendTestNotification() {
    const testEmail = document.getElementById('test_email').value;
    const channels = Array.from(document.querySelectorAll('input[name="channels[]"]:checked')).map(cb => cb.value);
    const subject = document.getElementById('subject').value;
    const message = document.getElementById('body_text').value || document.getElementById('body_html').value;
    const priority = document.getElementById('priority').value;

    if (!testEmail || !subject || !message || channels.length === 0) {
        alert('กรุณากรอกข้อมูลให้ครบถ้วนก่อนทดสอบ');
        return;
    }

    const testResult = document.getElementById('testResult');
    testResult.innerHTML = '<div class="spinner-border spinner-border-sm"></div> กำลังส่ง...';

    fetch('/notifications/test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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
            testResult.innerHTML = `<div class="alert alert-success alert-sm mb-0">${data.message}</div>`;
        } else {
            testResult.innerHTML = `<div class="alert alert-danger alert-sm mb-0">${data.message}</div>`;
        }
    })
    .catch(error => {
        testResult.innerHTML = '<div class="alert alert-danger alert-sm mb-0">เกิดข้อผิดพลาดในการส่งทดสอบ</div>';
    });
}

function saveDraft() {
    // Change form action to save as draft
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