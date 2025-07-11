@extends('layouts.app')

@section('title', 'ดูตัวอย่างเทมเพลต')

@push('styles')
<style>
.preview-section {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
}

.preview-header {
    background-color: #f8f9fa;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
}

.preview-content {
    padding: 1rem;
    background-color: #fff;
}

.variable-badge {
    cursor: help;
    transition: all 0.2s;
}

.variable-badge:hover {
    transform: scale(1.05);
}

.template-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.usage-stats {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.channel-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
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

.test-data-form {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
}

.syntax-highlight {
    background-color: #f1f3f4;
    border-left: 4px solid #4285f4;
    padding: 0.75rem;
    margin: 0.5rem 0;
    border-radius: 0 0.25rem 0.25rem 0;
}

.live-preview {
    border: 2px solid #28a745;
    border-radius: 0.5rem;
    background-color: #f8fff9;
}

.preview-tabs .nav-link {
    border-bottom: 2px solid transparent;
}

.preview-tabs .nav-link.active {
    border-bottom-color: #0d6efd;
    background-color: transparent;
}

.email-preview {
    max-width: 600px;
    margin: 0 auto;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.sms-preview {
    max-width: 320px;
    margin: 0 auto;
    background-color: #f8f9fa;
    border-radius: 20px;
    padding: 15px;
    border: 1px solid #dee2e6;
}

.teams-preview {
    background-color: #464775;
    color: white;
    border-radius: 8px;
    padding: 15px;
    margin: 0 auto;
    max-width: 500px;
}

.variable-input-group {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 15px;
    margin-bottom: 20px;
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
                        <i class="fas fa-eye text-primary me-2"></i>
                        ดูตัวอย่าง: {{ $template->name }}
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('templates.index') }}">เทมเพลต</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('templates.show', $template) }}">{{ $template->name }}</a></li>
                            <li class="breadcrumb-item active">ดูตัวอย่าง</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('templates.edit', $template) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>แก้ไขเทมเพลต
                    </a>
                    <a href="{{ route('templates.show', $template) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>กลับสู่รายละเอียด
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Main Preview Area -->
                <div class="col-lg-8">
                    <!-- Template Info Cards -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card template-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">ข้อมูลเทมเพลต</h6>
                                            <div class="small opacity-75">{{ $template->category }} • {{ ucfirst($template->priority) }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="h4 mb-0">v{{ $template->version ?? '1' }}</div>
                                            <div class="small opacity-75">เวอร์ชัน</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card usage-stats text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">สถิติการใช้งาน</h6>
                                            <div class="small opacity-75">จำนวนการแจ้งเตือนที่ส่งแล้ว</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="h4 mb-0">{{ $template->notifications()->count() }}</div>
                                            <div class="small opacity-75">ครั้งที่ใช้</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Supported Channels -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-broadcast-tower me-2"></i>ช่องทางที่รองรับ
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                @if(in_array('email', $template->supported_channels ?? []))
                                <span class="channel-indicator channel-email">
                                    <i class="fas fa-envelope"></i> อีเมล
                                </span>
                                @endif
                                @if(in_array('teams', $template->supported_channels ?? []))
                                <span class="channel-indicator channel-teams">
                                    <i class="fab fa-microsoft"></i> Microsoft Teams
                                </span>
                                @endif
                                @if(in_array('sms', $template->supported_channels ?? []))
                                <span class="channel-indicator channel-sms">
                                    <i class="fas fa-sms"></i> SMS
                                </span>
                                @endif
                            </div>
                            @if($template->description)
                            <hr>
                            <p class="text-muted mb-0">{{ $template->description }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Live Preview Section -->
                    <div class="card live-preview mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-play-circle me-2"></i>ตัวอย่างแบบสด
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <!-- Preview Tabs -->
                            <ul class="nav nav-tabs preview-tabs" id="previewTabs" role="tablist">
                                @if(in_array('email', $template->supported_channels ?? []) && $template->body_html_template)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-preview" type="button" role="tab">
                                        <i class="fas fa-envelope me-1"></i>อีเมล
                                    </button>
                                </li>
                                @endif
                                
                                @if(in_array('teams', $template->supported_channels ?? []))
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ !in_array('email', $template->supported_channels ?? []) ? 'active' : '' }}" id="teams-tab" data-bs-toggle="tab" data-bs-target="#teams-preview" type="button" role="tab">
                                        <i class="fab fa-microsoft me-1"></i>Teams
                                    </button>
                                </li>
                                @endif

                                @if(in_array('sms', $template->supported_channels ?? []) && $template->body_text_template)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ !in_array('email', $template->supported_channels ?? []) && !in_array('teams', $template->supported_channels ?? []) ? 'active' : '' }}" id="sms-tab" data-bs-toggle="tab" data-bs-target="#sms-preview" type="button" role="tab">
                                        <i class="fas fa-sms me-1"></i>SMS
                                    </button>
                                </li>
                                @endif
                            </ul>
                            
                            <div class="tab-content p-4">
                                <!-- Email Preview -->
                                @if(in_array('email', $template->supported_channels ?? []) && $template->body_html_template)
                                <div class="tab-pane fade show active" id="email-preview" role="tabpanel">
                                    <div class="email-preview">
                                        <!-- Email Header -->
                                        <div class="p-3 border-bottom" style="background-color: #f8f9fa;">
                                            <div class="row small">
                                                <div class="col-2 fw-bold">จาก:</div>
                                                <div class="col-10">{{ config('app.name', 'Smart Notification') }} &lt;noreply@company.com&gt;</div>
                                            </div>
                                            <div class="row small">
                                                <div class="col-2 fw-bold">ถึง:</div>
                                                <div class="col-10" id="email-recipient">user@company.com</div>
                                            </div>
                                            <div class="row small">
                                                <div class="col-2 fw-bold">หัวข้อ:</div>
                                                <div class="col-10" id="email-subject">{{ $template->subject_template }}</div>
                                            </div>
                                        </div>
                                        
                                        <!-- Email Body -->
                                        <div class="p-3" id="email-body" style="min-height: 200px;">
                                            {!! $template->body_html_template !!}
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Teams Preview -->
                                @if(in_array('teams', $template->supported_channels ?? []))
                                <div class="tab-pane fade {{ !in_array('email', $template->supported_channels ?? []) ? 'show active' : '' }}" id="teams-preview" role="tabpanel">
                                    <div class="teams-preview">
                                        <div class="d-flex align-items-start mb-2">
                                            <div class="me-2">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                    <i class="fas fa-robot text-white small"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small">{{ config('app.name', 'Smart Notification') }}</div>
                                                <div class="text-muted small">BOT</div>
                                            </div>
                                            <div class="text-muted small" id="teams-time">เมื่อสักครู่</div>
                                        </div>
                                        <div id="teams-content" class="p-3 rounded" style="background-color: rgba(255,255,255,0.1);">
                                            @if($template->body_html_template)
                                                {!! $template->body_html_template !!}
                                            @else
                                                {!! nl2br(e($template->body_text_template)) !!}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- SMS Preview -->
                                @if(in_array('sms', $template->supported_channels ?? []) && $template->body_text_template)
                                <div class="tab-pane fade {{ !in_array('email', $template->supported_channels ?? []) && !in_array('teams', $template->supported_channels ?? []) ? 'show active' : '' }}" id="sms-preview" role="tabpanel">
                                    <div class="sms-preview">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="fw-bold small">{{ config('app.name', 'Smart Notification') }}</div>
                                            <div class="text-muted small" id="sms-time">เมื่อสักครู่</div>
                                        </div>
                                        <div id="sms-content" class="p-3 rounded" style="background-color: #007bff; color: white;">
                                            <pre class="mb-0" style="color: white; font-family: inherit;">{{ $template->body_text_template }}</pre>
                                        </div>
                                        <div class="mt-2 text-center">
                                            <small class="text-muted" id="sms-char-count">{{ strlen($template->body_text_template) }} ตัวอักษร</small>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Template Code Section -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-code me-2"></i>โค้ดเทมเพลต
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <!-- Subject Template -->
                            @if($template->subject_template)
                            <div class="preview-section">
                                <div class="preview-header">
                                    <i class="fas fa-heading me-2"></i>เทมเพลตหัวข้อ
                                </div>
                                <div class="preview-content">
                                    <div class="syntax-highlight">
                                        <pre><code>{{ $template->subject_template }}</code></pre>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- HTML Content Template -->
                            @if($template->body_html_template)
                            <div class="preview-section">
                                <div class="preview-header">
                                    <i class="fas fa-code me-2"></i>เทมเพลต HTML
                                    <span class="badge bg-primary ms-2">อีเมล & Teams</span>
                                </div>
                                <div class="preview-content">
                                    <div class="syntax-highlight" style="max-height: 400px; overflow-y: auto;">
                                        <pre><code>{{ $template->body_html_template }}</code></pre>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Text Content Template -->
                            @if($template->body_text_template)
                            <div class="preview-section">
                                <div class="preview-header">
                                    <i class="fas fa-file-text me-2"></i>เทมเพลตข้อความธรรมดา
                                    <span class="badge bg-success ms-2">SMS & สำรอง</span>
                                </div>
                                <div class="preview-content">
                                    <div class="syntax-highlight">
                                        <pre><code>{{ $template->body_text_template }}</code></pre>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Variables Information -->
                    @if(!empty($template->variables) || !empty($detectedVariables))
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tags me-2"></i>ตัวแปรเทมเพลต
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(!empty($detectedVariables))
                            <div class="mb-3">
                                <h6 class="text-primary">ตัวแปรที่ตรวจพบ:</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($detectedVariables as $variable)
                                    <span class="badge bg-primary variable-badge" title="ตัวแปร: {{ $variable }}">
                                        &#123;&#123;{{ $variable }}&#125;&#125;
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            @if(!empty($template->variables))
                            <div class="mb-3">
                                <h6 class="text-success">ตัวแปรที่จำเป็น:</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($template->variables as $variable)
                                    <span class="badge bg-success variable-badge" title="จำเป็น: {{ $variable }}">
                                        &#123;&#123;{{ $variable }}&#125;&#125;
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            @if(!empty($template->default_variables))
                            <div>
                                <h6 class="text-info">ค่าเริ่มต้น:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>ตัวแปร</th>
                                                <th>ค่าเริ่มต้น</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($template->default_variables as $key => $value)
                                            <tr>
                                                <td><code>{{ $key }}</code></td>
                                                <td>{{ $value }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Variable Input Controls -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-sliders-h me-2"></i>ปรับแต่งตัวแปร
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">ปรับเปลี่ยนค่าตัวแปรเพื่อดูผลการแสดงแบบสด</p>
                            
                            <div class="variable-input-group" id="variableInputs">
                                <!-- Variables will be populated by JavaScript -->
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary btn-sm" onclick="updateLivePreview()">
                                    <i class="fas fa-sync me-1"></i>อัปเดตตัวอย่าง
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetToDefaults()">
                                    <i class="fas fa-undo me-1"></i>รีเซ็ตเป็นค่าเริ่มต้น
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="loadSampleData()">
                                    <i class="fas fa-magic me-1"></i>โหลดข้อมูลตัวอย่าง
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced JSON Editor -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-code me-2"></i>แก้ไขแบบ JSON
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">ข้อมูลตัวแปร (JSON)</label>
                                <textarea class="form-control" id="jsonEditor" rows="8" placeholder='{"user_name": "นายสมชาย ใจดี", "message": "ข้อความทดสอบ"}'></textarea>
                                <div class="form-text">แก้ไขข้อมูลตัวแปรในรูปแบบ JSON โดยตรง</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success btn-sm" onclick="applyJsonData()">
                                    <i class="fas fa-check me-1"></i>ใช้ข้อมูล JSON
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="formatJson()">
                                    <i class="fas fa-code me-1"></i>จัดรูปแบบ JSON
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-bolt me-2"></i>การดำเนินการด่วน
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('admin.notifications.create', ['template' => $template->id]) }}" class="btn btn-success">
                                    <i class="fas fa-paper-plane me-2"></i>ส่งการแจ้งเตือน
                                </a>
                                <a href="{{ route('templates.duplicate', $template) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-copy me-2"></i>ทำสำเนาเทมเพลต
                                </a>
                                <button class="btn btn-outline-info" onclick="exportTemplate()">
                                    <i class="fas fa-download me-2"></i>ส่งออกเทมเพลต
                                </button>
                                <button class="btn btn-outline-warning" onclick="testSendNotification()">
                                    <i class="fas fa-paper-plane me-2"></i>ทดสอบส่ง
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Template Details -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>รายละเอียดเทมเพลต
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <div class="row mb-2">
                                    <div class="col-5 fw-bold">ID:</div>
                                    <div class="col-7">{{ $template->id }}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 fw-bold">Slug:</div>
                                    <div class="col-7"><code>{{ $template->slug }}</code></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 fw-bold">สถานะ:</div>
                                    <div class="col-7">
                                        @if($template->is_active)
                                        <span class="badge bg-success">ใช้งานได้</span>
                                        @else
                                        <span class="badge bg-secondary">ไม่ใช้งาน</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 fw-bold">สร้างเมื่อ:</div>
                                    <div class="col-7">{{ $template->created_at->format('M d, Y H:i') }}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 fw-bold">อัปเดตเมื่อ:</div>
                                    <div class="col-7">{{ $template->updated_at->format('M d, Y H:i') }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-5 fw-bold">ผู้สร้าง:</div>
                                    <div class="col-7">{{ optional($template->creator)->name ?? 'ไม่ทราบ' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Template data from PHP
const templateData = {
    subject: @json($template->subject_template),
    html: @json($template->body_html_template),
    text: @json($template->body_text_template),
    defaultVariables: @json($template->default_variables ?? []),
    detectedVariables: @json($detectedVariables ?? []),
    supportedChannels: @json($template->supported_channels ?? [])
};

let currentVariableData = {};

// Load default data from template with PHP values
function loadDefaultDataWithPHP() {
    const sampleData = {
        // User variables
        user_name: @json(optional(auth()->user())->name ?: "นายสมชาย ใจดี"),
        user_email: @json(optional(auth()->user())->email ?: "somchai@company.com"),
        user_first_name: @json(optional(auth()->user())->name ? explode(" ", auth()->user()->name)[0] : "สมชาย"),
        user_last_name: @json(optional(auth()->user())->name ? (explode(" ", auth()->user()->name)[1] ?? "") : "ใจดี"),
        user_department: 'เทคโนโลยีสารสนเทศ',
        user_title: 'นักพัฒนาซอฟต์แวร์',
        
        // System variables
        app_name: @json(config("app.name", "Smart Notification")),
        app_url: @json(config("app.url", "http://localhost")),
        company: @json(config("app.name", "บริษัทของคุณ")),
        
        // Time variables
        current_date: new Date().toLocaleDateString('th-TH'),
        current_time: new Date().toLocaleTimeString('th-TH', {hour: '2-digit', minute: '2-digit'}),
        current_datetime: new Date().toLocaleString('th-TH'),
        year: new Date().getFullYear().toString(),
        month: (new Date().getMonth() + 1).toString().padStart(2, '0'),
        day: new Date().getDate().toString().padStart(2, '0'),
        
        // Common variables
        message: 'นี่คือข้อความแจ้งเตือนตัวอย่างสำหรับการทดสอบระบบ',
        subject: 'การแจ้งเตือนระบบที่สำคัญ',
        url: 'https://example.com/action-required',
        priority: 'สูง',
        status: 'ใช้งาน',
        amount: '1,250.00',
        deadline: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toLocaleDateString('th-TH'),
        
        // Override with template defaults
        ...templateData.defaultVariables
    };
    
    return sampleData;
}

// Extract all variables from template content
function extractAllVariables() {
    const variables = new Set();
    const content = (templateData.subject || '') + ' ' + (templateData.html || '') + ' ' + (templateData.text || '');
    
    const matches = content.match(/\{\{([^}#\/][^}]*?)\}\}/g);
    if (matches) {
        matches.forEach(match => {
            const varName = match.replace(/[{}]/g, '').split(':')[0].split('|')[0].trim();
            if (varName) {
                variables.add(varName);
            }
        });
    }
    
    return Array.from(variables);
}

// Replace variables in template
function replaceVariables(template, data) {
    if (!template) return '';
    
    let result = template;
    
    Object.entries(data || {}).forEach(([key, value]) => {
        const regex = new RegExp(`\\{\\{\\s*${key}\\s*\\}\\}`, 'g');
        result = result.replace(regex, value || '');
    });
    
    // Replace remaining variables with placeholder
    result = result.replace(/\{\{([^}]+)\}\}/g, (match, varName) => {
        return `[${varName.trim()}]`;
    });
    
    return result;
}

// Generate variable input controls
function generateVariableInputs() {
    const container = document.getElementById('variableInputs');
    const allVariables = [...new Set([...templateData.detectedVariables, ...Object.keys(templateData.defaultVariables)])];
    
    if (allVariables.length === 0) {
        container.innerHTML = '<p class="text-muted small">ไม่พบตัวแปรในเทมเพลต</p>';
        return;
    }
    
    let html = '';
    allVariables.forEach(varName => {
        const defaultValue = currentVariableData[varName] || templateData.defaultVariables[varName] || '';
        html += `
            <div class="mb-3">
                <label class="form-label small fw-bold">{{${varName}}}</label>
                <input type="text" class="form-control form-control-sm variable-input" 
                       data-variable="${varName}" value="${defaultValue}"
                       placeholder="ค่าสำหรับ ${varName}">
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Add event listeners for real-time updates
    container.querySelectorAll('.variable-input').forEach(input => {
        input.addEventListener('input', debounce(function() {
            currentVariableData[this.dataset.variable] = this.value;
            updateLivePreview();
        }, 500));
    });
}

// Update live preview with current variable data
function updateLivePreview() {
    const data = { ...loadDefaultDataWithPHP(), ...currentVariableData };
    
    // Update subject
    const subjectEl = document.getElementById('email-subject');
    if (subjectEl && templateData.subject) {
        subjectEl.textContent = replaceVariables(templateData.subject, data);
    }
    
    // Update email recipient
    const recipientEl = document.getElementById('email-recipient');
    if (recipientEl) {
        recipientEl.textContent = data.user_email || 'user@company.com';
    }
    
    // Update email body
    const emailBodyEl = document.getElementById('email-body');
    if (emailBodyEl && templateData.html) {
        emailBodyEl.innerHTML = replaceVariables(templateData.html, data);
    }
    
    // Update Teams content
    const teamsContentEl = document.getElementById('teams-content');
    if (teamsContentEl) {
        const content = templateData.html || templateData.text || '';
        teamsContentEl.innerHTML = replaceVariables(content, data);
    }
    
    // Update SMS content
    const smsContentEl = document.getElementById('sms-content');
    if (smsContentEl && templateData.text) {
        const textContent = replaceVariables(templateData.text, data);
        smsContentEl.querySelector('pre').textContent = textContent;
        
        // Update character count
        const charCountEl = document.getElementById('sms-char-count');
        if (charCountEl) {
            const count = textContent.length;
            charCountEl.textContent = `${count} ตัวอักษร`;
            if (count > 160) {
                charCountEl.className = 'text-warning small';
                charCountEl.textContent += ` (${Math.ceil(count / 160)} ข้อความ)`;
            } else {
                charCountEl.className = 'text-muted small';
            }
        }
    }
    
    // Update time displays
    const currentTime = new Date().toLocaleTimeString('th-TH', {hour: '2-digit', minute: '2-digit'});
    document.querySelectorAll('#teams-time, #sms-time').forEach(el => {
        el.textContent = currentTime;
    });
    
    // Update JSON editor
    const jsonEditor = document.getElementById('jsonEditor');
    if (jsonEditor) {
        jsonEditor.value = JSON.stringify(data, null, 2);
    }
}

// Reset to default values
function resetToDefaults() {
    currentVariableData = { ...templateData.defaultVariables };
    generateVariableInputs();
    updateLivePreview();
}

// Load sample data
function loadSampleData() {
    currentVariableData = loadDefaultDataWithPHP();
    generateVariableInputs();
    updateLivePreview();
}

// Apply JSON data to variables
function applyJsonData() {
    const jsonEditor = document.getElementById('jsonEditor');
    try {
        const data = JSON.parse(jsonEditor.value);
        currentVariableData = { ...currentVariableData, ...data };
        generateVariableInputs();
        updateLivePreview();
        showAlert('success', 'นำข้อมูล JSON มาใช้เรียบร้อย');
    } catch (e) {
        showAlert('error', 'รูปแบบ JSON ไม่ถูกต้อง: ' + e.message);
    }
}

// Format JSON
function formatJson() {
    const jsonEditor = document.getElementById('jsonEditor');
    try {
        const data = JSON.parse(jsonEditor.value);
        jsonEditor.value = JSON.stringify(data, null, 2);
        showAlert('success', 'จัดรูปแบบ JSON เรียบร้อย');
    } catch (e) {
        showAlert('error', 'รูปแบบ JSON ไม่ถูกต้อง: ' + e.message);
    }
}

// Export template
function exportTemplate() {
    const templateExport = {
        name: @json($template->name),
        slug: @json($template->slug),
        category: @json($template->category),
        priority: @json($template->priority),
        description: @json($template->description ?? ""),
        subject_template: templateData.subject,
        body_html_template: templateData.html,
        body_text_template: templateData.text,
        supported_channels: templateData.supportedChannels,
        variables: templateData.detectedVariables,
        default_variables: templateData.defaultVariables,
        version: @json($template->version ?? "1"),
        exported_at: new Date().toISOString()
    };
    
    const dataStr = JSON.stringify(templateExport, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = `template-${@json($template->slug)}-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
}

// Test send notification
function testSendNotification() {
    if (confirm('ต้องการทดสอบส่งการแจ้งเตือนไปยังตัวคุณเองหรือไม่?')) {
        fetch('{{ route("templates.test-send", $template) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                test_data: currentVariableData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'ส่งการแจ้งเตือนทดสอบเรียบร้อย');
            } else {
                showAlert('error', 'ข้อผิดพลาด: ' + (data.message || 'ไม่สามารถส่งการแจ้งเตือนได้'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'เกิดข้อผิดพลาดในการส่งการแจ้งเตือน');
        });
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showAlert(type, message, duration = 3000) {
    // Remove existing alerts
    document.querySelectorAll('.template-alert').forEach(alert => alert.remove());
    
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    const icon = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-triangle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    }[type] || 'fas fa-info-circle';
    
    const alertElement = document.createElement('div');
    alertElement.className = `alert ${alertClass} alert-dismissible fade show position-fixed template-alert`;
    alertElement.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertElement.innerHTML = `
        <i class="${icon}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertElement);
    
    // Auto remove
    if (type === 'success' && duration > 0) {
        setTimeout(() => {
            if (alertElement.parentNode) {
                alertElement.remove();
            }
        }, duration);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('เริ่มต้นหน้าตัวอย่างเทมเพลต...');
    
    // Initialize with default data
    currentVariableData = { ...templateData.defaultVariables, ...loadDefaultDataWithPHP() };
    
    // Generate variable inputs
    generateVariableInputs();
    
    // Initial preview update
    updateLivePreview();
    
    // Auto-update JSON editor
    const jsonEditor = document.getElementById('jsonEditor');
    if (jsonEditor) {
        jsonEditor.addEventListener('input', debounce(function() {
            try {
                const data = JSON.parse(this.value);
                currentVariableData = { ...currentVariableData, ...data };
                updateLivePreview();
            } catch (e) {
                // Invalid JSON, ignore
            }
        }, 1000));
    }
    
    console.log('หน้าตัวอย่างเทมเพลตเริ่มต้นเรียบร้อย');
});
</script>
@endpush