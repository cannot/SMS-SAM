@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนแบบ SQL - เลือก Template Email')

@push('styles')
<style>
.wizard-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.wizard-header {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    padding: 30px;
}

.wizard-title {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 10px;
}

.wizard-subtitle {
    opacity: 0.9;
    font-size: 1.1rem;
}

.step-indicator {
    display: flex;
    gap: 8px;
    margin-top: 25px;
}

.step {
    flex: 1;
    height: 4px;
    background: rgba(255,255,255,0.3);
    border-radius: 2px;
    transition: background 0.3s ease;
}

.step.active {
    background: #fbbf24;
}

.step.completed {
    background: #10b981;
}

.wizard-content {
    padding: 40px;
}

.section-title {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 25px;
    color: #4f46e5;
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-icon {
    width: 32px;
    height: 32px;
    background: #4f46e5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.template-selection {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.selection-header {
    font-weight: 600;
    margin-bottom: 20px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.template-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.template-card:hover {
    border-color: #4f46e5;
    background: #f0f9ff;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.15);
}

.template-card.selected {
    border-color: #10b981;
    background: #f0fdf4;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.template-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.template-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
    transition: all 0.3s ease;
}

.template-card:not(.selected) .template-icon {
    background: #6b7280;
}

.template-card.selected .template-icon {
    background: #10b981;
}

.template-title {
    font-weight: 600;
    color: #374151;
    margin-bottom: 5px;
}

.template-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
}

.template-description {
    color: #6b7280;
    font-size: 0.875rem;
    line-height: 1.4;
    margin-bottom: 15px;
}

.template-features {
    list-style: none;
    padding: 0;
    margin: 0;
}

.template-features li {
    font-size: 0.75rem;
    color: #059669;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.template-features li:before {
    content: "✓";
    font-weight: bold;
    color: #10b981;
}

.selected-indicator {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 24px;
    height: 24px;
    background: #10b981;
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}

.template-card.selected .selected-indicator {
    display: flex;
}

.preview-section {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 30px;
    overflow: hidden;
}

.preview-header {
    background: #f9fafb;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.preview-title {
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.preview-controls {
    display: flex;
    gap: 10px;
}

.preview-content {
    padding: 0;
}

.preview-tabs {
    display: flex;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.preview-tab {
    padding: 15px 25px;
    background: none;
    border: none;
    cursor: pointer;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.3s ease;
    border-bottom: 2px solid transparent;
}

.preview-tab.active {
    color: #4f46e5;
    border-bottom-color: #4f46e5;
    background: white;
}

.preview-tab:hover:not(.active) {
    color: #374151;
    background: #f3f4f6;
}

.tab-content {
    padding: 25px;
    display: none;
}

.tab-content.active {
    display: block;
}

.email-preview {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    background: white;
}

.email-header {
    background: #f9fafb;
    padding: 15px 20px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.875rem;
}

.email-field {
    display: flex;
    margin-bottom: 8px;
}

.email-field:last-child {
    margin-bottom: 0;
}

.email-field-label {
    min-width: 80px;
    font-weight: 600;
    color: #6b7280;
}

.email-field-value {
    color: #374151;
}

.email-body {
    padding: 20px;
    line-height: 1.6;
    color: #374151;
}

.email-body h1, .email-body h2, .email-body h3 {
    color: #1f2937;
    margin-bottom: 15px;
}

.email-body h3 {
    font-size: 1.25rem;
}

.email-body p {
    margin-bottom: 15px;
}

.email-body ul {
    margin-bottom: 15px;
    padding-left: 20px;
}

.email-body li {
    margin-bottom: 5px;
}

.email-body strong {
    color: #1f2937;
}

.variable-highlight {
    background: #fef3c7;
    color: #92400e;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 0.875em;
}

.customization-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.customization-header {
    font-weight: 600;
    margin-bottom: 20px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
    display: block;
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
}

.form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 8px center;
    background-repeat: no-repeat;
    background-size: 16px 12px;
    padding-right: 40px;
}

.form-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 4px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.variables-helper {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 15px;
    border-radius: 0 8px 8px 0;
    margin-top: 15px;
}

.variables-helper h6 {
    color: #92400e;
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 0.875rem;
}

.variable-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.variable-tag {
    background: rgba(251, 191, 36, 0.2);
    color: #92400e;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-family: 'Courier New', monospace;
    cursor: pointer;
    transition: all 0.3s ease;
}

.variable-tag:hover {
    background: rgba(251, 191, 36, 0.3);
    transform: translateY(-1px);
}

.btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 0.875rem;
}

.wizard-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 40px;
    padding-top: 25px;
    border-top: 1px solid #e5e7eb;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    color: #92400e;
    background: #fef3c7;
}

@media (max-width: 768px) {
    .wizard-content {
        padding: 25px;
    }
    
    .template-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .preview-tabs {
        flex-direction: column;
    }
    
    .preview-tab {
        text-align: center;
    }
    
    .wizard-navigation {
        flex-direction: column;
        gap: 15px;
    }
}
</style>
@endpush

@section('content')
<div class="container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-database text-primary me-2"></i>
                สร้างการแจ้งเตือนแบบ SQL
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">หน้าหลัก</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('notifications.index') }}">การแจ้งเตือน</a></li>
                    <li class="breadcrumb-item active">สร้างการแจ้งเตือนแบบ SQL</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Wizard Container -->
    <div class="wizard-container">
        <!-- Wizard Header -->
        <div class="wizard-header">
            <div class="wizard-title">📧 เลือก Template Email</div>
            <div class="wizard-subtitle">เลือกเทมเพลตอีเมลและปรับแต่งเนื้อหาสำหรับการแจ้งเตือน</div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step active"></div>
                <div class="step"></div>
                <div class="step"></div>
                <div class="step"></div>
                <div class="step"></div>
                <div class="step"></div>
            </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
            <!-- Step 9: Email Template Selection -->
            <div class="section-title">
                <div class="section-icon">9</div>
                เลือก Template Email
            </div>

            <!-- Template Selection -->
            <div class="template-selection">
                <div class="selection-header">
                    <i class="fas fa-envelope"></i>
                    เลือกเทมเพลตอีเมล
                </div>

                <div class="template-grid">
                    <!-- System Alert Template -->
                    <div class="template-card selected" data-template="system-alert" onclick="selectTemplate('system-alert')">
                        <div class="selected-indicator">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="template-header">
                            <div class="template-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <div class="template-title">🚨 แจ้งเตือนระบบ</div>
                                <div class="template-subtitle">System Alert</div>
                            </div>
                        </div>
                        <div class="template-description">
                            เทมเพลตสำหรับแจ้งเตือนเหตุการณ์สำคัญของระบบ เหมาะสำหรับการแจ้งปัญหาหรือเหตุการณ์ที่ต้องดำเนินการ
                        </div>
                        <ul class="template-features">
                            <li>สีแดงเน้นความเร่งด่วน</li>
                            <li>รวมสถิติและข้อมูลสำคัญ</li>
                            <li>แนบไฟล์ข้อมูลอัตโนมัติ</li>
                            <li>รองรับตัวแปรครบถ้วน</li>
                        </ul>
                    </div>

                    <!-- Data Report Template -->
                    <div class="template-card" data-template="data-report" onclick="selectTemplate('data-report')">
                        <div class="selected-indicator">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="template-header">
                            <div class="template-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div>
                                <div class="template-title">📊 รายงานข้อมูล</div>
                                <div class="template-subtitle">Data Report</div>
                            </div>
                        </div>
                        <div class="template-description">
                            เทมเพลตสำหรับส่งรายงานข้อมูลประจำ เน้นการแสดงสถิติและสรุปผลการวิเคราะห์
                        </div>
                        <ul class="template-features">
                            <li>รูปแบบรายงานเป็นทางการ</li>
                            <li>แสดงสถิติแบบละเอียด</li>
                            <li>เหมาะสำหรับรายงานประจำ</li>
                            <li>รองรับกราฟและตาราง</li>
                        </ul>
                    </div>

                    <!-- Daily Summary Template -->
                    <div class="template-card" data-template="daily-summary" onclick="selectTemplate('daily-summary')">
                        <div class="selected-indicator">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="template-header">
                            <div class="template-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div>
                                <div class="template-title">📋 สรุปรายวัน</div>
                                <div class="template-subtitle">Daily Summary</div>
                            </div>
                        </div>
                        <div class="template-description">
                            เทมเพลตสำหรับสรุปข้อมูลประจำวัน เน้นความกระชับและอ่านง่าย เหมาะสำหรับรายงานสถานะ
                        </div>
                        <ul class="template-features">
                            <li>รูปแบบกระชับเข้าใจง่าย</li>
                            <li>เน้นข้อมูลสำคัญ</li>
                            <li>เหมาะสำหรับการส่งประจำ</li>
                            <li>รองรับการเปรียบเทียบ</li>
                        </ul>
                    </div>

                    <!-- Custom Template -->
                    <div class="template-card" data-template="custom" onclick="selectTemplate('custom')">
                        <div class="selected-indicator">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="template-header">
                            <div class="template-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div>
                                <div class="template-title">✏️ กำหนดเอง</div>
                                <div class="template-subtitle">Custom Template</div>
                            </div>
                        </div>
                        <div class="template-description">
                            เทมเพลตว่างสำหรับปรับแต่งเอง สามารถกำหนดเนื้อหาและรูปแบบได้ตามต้องการ
                        </div>
                        <ul class="template-features">
                            <li>ปรับแต่งได้ทุกส่วน</li>
                            <li>รองรับ HTML แบบเต็ม</li>
                            <li>ใช้ตัวแปรได้อย่างอิสระ</li>
                            <li>เหมาะสำหรับความต้องการพิเศษ</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Template Customization -->
            <div class="customization-section">
                <div class="customization-header">
                    <i class="fas fa-cogs"></i>
                    ปรับแต่งเทมเพลต
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="emailSender">ผู้ส่ง (From)</label>
                        <input type="text" 
                               class="form-control" 
                               id="emailSender" 
                               value="SQL Alert System <alerts@company.com>"
                               placeholder="ชื่อผู้ส่ง <email@domain.com>">
                        <div class="form-text">รูปแบบ: ชื่อผู้ส่ง &lt;email@domain.com&gt;</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="emailPriority">ระดับความสำคัญ</label>
                        <select class="form-control form-select" id="emailPriority">
                            <option value="low">🟢 ต่ำ (Low)</option>
                            <option value="normal" selected>🟡 ปกติ (Normal)</option>
                            <option value="high">🟠 สูง (High)</option>
                            <option value="urgent">🔴 เร่งด่วน (Urgent)</option>
                        </select>
                    </div>
                </div>

                <!-- Variables Helper -->
                <div class="variables-helper">
                    <h6>
                        <i class="fas fa-tags me-1"></i>
                        ตัวแปรที่สามารถใช้ได้
                    </h6>
                    <div class="variable-tags" id="availableVariables">
                        <!-- Variables will be populated here -->
                    </div>
                </div>
            </div>

            <!-- Template Preview -->
            <div class="preview-section">
                <div class="preview-header">
                    <div class="preview-title">
                        <i class="fas fa-eye"></i>
                        ตัวอย่างอีเมล
                    </div>
                    <div class="preview-controls">
                        <button type="button" class="btn btn-success btn-sm" onclick="refreshPreview()">
                            <i class="fas fa-sync-alt"></i>
                            รีเฟรช
                        </button>
                    </div>
                </div>

                <div class="preview-content">
                    <!-- Preview Tabs -->
                    <div class="preview-tabs">
                        <button class="preview-tab active" onclick="switchTab('email')" data-tab="email">
                            <i class="fas fa-envelope me-1"></i>
                            อีเมล
                        </button>
                        <button class="preview-tab" onclick="switchTab('html')" data-tab="html">
                            <i class="fas fa-code me-1"></i>
                            HTML
                        </button>
                        <button class="preview-tab" onclick="switchTab('text')" data-tab="text">
                            <i class="fas fa-file-alt me-1"></i>
                            ข้อความธรรมดา
                        </button>
                    </div>

                    <!-- Email Preview Tab -->
                    <div class="tab-content active" id="emailTab">
                        <div class="email-preview">
                            <div class="email-header">
                                <div class="email-field">
                                    <div class="email-field-label">From:</div>
                                    <div class="email-field-value" id="previewFrom">SQL Alert System &lt;alerts@company.com&gt;</div>
                                </div>
                                <div class="email-field">
                                    <div class="email-field-label">To:</div>
                                    <div class="email-field-value">ผู้รับจะกำหนดในขั้นตอนถัดไป</div>
                                </div>
                                <div class="email-field">
                                    <div class="email-field-label">Subject:</div>
                                    <div class="email-field-value" id="previewSubject">🚨 แจ้งเตือนระบบ - {{query_date}}</div>
                                </div>
                                <div class="email-field">
                                    <div class="email-field-label">Priority:</div>
                                    <div class="email-field-value" id="previewPriority">🟡 ปกติ (Normal)</div>
                                </div>
                            </div>
                            <div class="email-body" id="emailBody">
                                <!-- Email body will be populated here -->
                            </div>
                        </div>
                    </div>

                    <!-- HTML Preview Tab -->
                    <div class="tab-content" id="htmlTab">
                        <pre style="background: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 0.875rem;"><code id="htmlCode"><!-- HTML code will be shown here --></code></pre>
                    </div>

                    <!-- Text Preview Tab -->
                    <div class="tab-content" id="textTab">
                        <pre style="background: #f3f4f6; padding: 15px; border-radius: 6px; white-space: pre-wrap; font-size: 0.875rem;" id="textContent"><!-- Text content will be shown here --></pre>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="wizard-navigation">
                <button type="button" class="btn btn-secondary" onclick="previousStep()">
                    <i class="fas fa-arrow-left"></i>
                    ย้อนกลับ
                </button>
                
                <div class="status-indicator">
                    <i class="fas fa-info-circle"></i>
                    ขั้นตอนที่ 9 จาก 14
                </div>
                
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    ถัดไป (กำหนดเนื้อหา)
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let selectedTemplate = 'system-alert';
let availableVariables = [];

// Email templates
const emailTemplates = {
    'system-alert': {
        name: '🚨 แจ้งเตือนระบบ',
        subject: '🚨 แจ้งเตือนระบบ - {{query_date}}',
        htmlBody: `
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 20px; border-radius: 8px 8px 0 0;">
        <h2 style="margin: 0; font-size: 24px;">🚨 แจ้งเตือนระบบ</h2>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">ตรวจพบเหตุการณ์ที่ต้องดำเนินการ</p>
    </div>
    
    <div style="background: white; padding: 25px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <h3 style="color: #1f2937; margin-bottom: 15px;">สรุปการแจ้งเตือน</h3>
        
        <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin-bottom: 20px; border-radius: 0 6px 6px 0;">
            <p style="margin: 0; color: #991b1b;"><strong>ระบบพบการแจ้งเตือนจำนวน <span class="variable-highlight">{{record_count}}</span> รายการ</strong></p>
            <p style="margin: 5px 0 0 0; color: #991b1b;">ณ วันที่ <span class="variable-highlight">{{query_date}}</span> เวลา <span class="variable-highlight">{{query_time}}</span></p>
        </div>
        
        <h4 style="color: #374151; margin-bottom: 10px;">📊 สถิติข้อมูล</h4>
        <ul style="margin-bottom: 20px; color: #6b7280;">
            <li>จำนวนข้อมูลทั้งหมด: <strong><span class="variable-highlight">{{record_count}}</span></strong> รายการ</li>
            <li>เวลาในการประมวลผล: <strong><span class="variable-highlight">{{execution_time}}</span></strong></li>
            <li>ขนาดข้อมูล: <strong><span class="variable-highlight">{{data_size}}</span></strong></li>
        </ul>
        
        <h4 style="color: #374151; margin-bottom: 10px;">📎 ไฟล์แนบ</h4>
        <p style="margin-bottom: 20px; color: #6b7280;">
            รายละเอียดอยู่ในไฟล์แนบ: <strong><span class="variable-highlight">{{export_filename}}</span></strong><br>
            ขนาดไฟล์: <strong><span class="variable-highlight">{{export_size}}</span></strong>
        </p>
        
        <div style="background: #f9fafb; padding: 15px; border-radius: 6px; margin-top: 20px;">
            <p style="margin: 0; color: #6b7280; font-size: 14px;">
                กรุณาตรวจสอบและดำเนินการตามความเหมาะสม<br>
                <em>ระบบแจ้งเตือนอัตโนมัติ</em>
            </p>
        </div>
    </div>
</div>`,
        textBody: `🚨 แจ้งเตือนระบบ

เรียน ผู้ดูแลระบบ

ระบบพบการแจ้งเตือนจำนวน {{record_count}} รายการ
ณ วันที่ {{query_date}} เวลา {{query_time}}

สถิติข้อมูล:
- จำนวนข้อมูลทั้งหมด: {{record_count}} รายการ
- เวลาในการประมวลผล: {{execution_time}}
- ขนาดข้อมูล: {{data_size}}

ไฟล์แนบ:
- ชื่อไฟล์: {{export_filename}}
- ขนาดไฟล์: {{export_size}}

กรุณาตรวจสอบและดำเนินการตามความเหมาะสม

ขอบคุณครับ
ระบบแจ้งเตือนอัตโนมัติ`
    },
    
    'data-report': {
        name: '📊 รายงานข้อมูล',
        subject: '📊 รายงานข้อมูล - {{query_date}}',
        htmlBody: `
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; padding: 20px; border-radius: 8px 8px 0 0;">
        <h2 style="margin: 0; font-size: 24px;">📊 รายงานข้อมูล</h2>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">สรุปผลการวิเคราะห์ข้อมูล</p>
    </div>
    
    <div style="background: white; padding: 25px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <h3 style="color: #1f2937; margin-bottom: 15px;">รายงานประจำวันที่ <span class="variable-highlight">{{query_date}}</span></h3>
        
        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
            <div style="flex: 1; background: #f0f9ff; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #0284c7;"><span class="variable-highlight">{{record_count}}</span></div>
                <div style="color: #0369a1; font-size: 14px;">รายการทั้งหมด</div>
            </div>
            <div style="flex: 1; background: #f0fdf4; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #059669;"><span class="variable-highlight">{{column_count}}</span></div>
                <div style="color: #047857; font-size: 14px;">คอลัมน์ข้อมูล</div>
            </div>
        </div>
        
        <h4 style="color: #374151; margin-bottom: 10px;">📈 สรุปผลการวิเคราะห์</h4>
        <p style="margin-bottom: 15px; color: #6b7280;">
            การวิเคราะห์ข้อมูลเสร็จสิ้นในเวลา <strong><span class="variable-highlight">{{execution_time}}</span></strong> 
            พบข้อมูลขนาด <strong><span class="variable-highlight">{{data_size}}</span></strong>
        </p>
        
        <h4 style="color: #374151; margin-bottom: 10px;">📋 รายละเอียดเพิ่มเติม</h4>
        <p style="margin-bottom: 20px; color: #6b7280;">
            ไฟล์รายงานฉบับเต็ม: <strong><span class="variable-highlight">{{export_filename}}</span></strong><br>
            ข้อมูลได้รับการประมวลผลเมื่อ: <strong><span class="variable-highlight">{{query_datetime}}</span></strong>
        </p>
        
        <div style="background: #f9fafb; padding: 15px; border-radius: 6px; margin-top: 20px;">
            <p style="margin: 0; color: #6b7280; font-size: 14px;">
                ขอบคุณที่ใช้บริการระบบรายงานข้อมูล<br>
                <em>ระบบสร้างรายงานอัตโนมัติ</em>
            </p>
        </div>
    </div>
</div>`,
        textBody: `📊 รายงานข้อมูล

รายงานประจำวันที่ {{query_date}}

สรุปผลการวิเคราะห์:
- รายการทั้งหมด: {{record_count}}
- คอลัมน์ข้อมูล: {{column_count}}
- เวลาประมวลผล: {{execution_time}}
- ขนาดข้อมูล: {{data_size}}

รายละเอียดเพิ่มเติม:
- ไฟล์รายงาน: {{export_filename}}
- ประมวลผลเมื่อ: {{query_datetime}}

ขอบคุณที่ใช้บริการระบบรายงานข้อมูล
ระบบสร้างรายงานอัตโนมัติ`
    },
    
    'daily-summary': {
        name: '📋 สรุปรายวัน',
        subject: '📋 สรุปรายวัน - {{query_date}}',
        htmlBody: `
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 20px; border-radius: 8px 8px 0 0;">
        <h2 style="margin: 0; font-size: 24px;">📋 สรุปรายวัน</h2>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">{{query_date}}</p>
    </div>
    
    <div style="background: white; padding: 25px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <h3 style="color: #1f2937; margin-bottom: 15px;">สถานะประจำวัน</h3>
        
        <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 15px; margin-bottom: 20px; border-radius: 0 6px 6px 0;">
            <p style="margin: 0; color: #065f46;"><strong>✅ ระบบทำงานปกติ</strong></p>
            <p style="margin: 5px 0 0 0; color: #065f46;">พบข้อมูล <span class="variable-highlight">{{record_count}}</span> รายการใหม่</p>
        </div>
        
        <h4 style="color: #374151; margin-bottom: 10px;">📊 สรุปตัวเลข</h4>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr style="background: #f9fafb;">
                <td style="padding: 10px; border: 1px solid #e5e7eb;">จำนวนรายการ</td>
                <td style="padding: 10px; border: 1px solid #e5e7eb; text-align: right;"><strong><span class="variable-highlight">{{record_count}}</span></strong></td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #e5e7eb;">เวลาประมวลผล</td>
                <td style="padding: 10px; border: 1px solid #e5e7eb; text-align: right;"><span class="variable-highlight">{{execution_time}}</span></td>
            </tr>
            <tr style="background: #f9fafb;">
                <td style="padding: 10px; border: 1px solid #e5e7eb;">ขนาดข้อมูล</td>
                <td style="padding: 10px; border: 1px solid #e5e7eb; text-align: right;"><span class="variable-highlight">{{data_size}}</span></td>
            </tr>
        </table>
        
        <h4 style="color: #374151; margin-bottom: 10px;">📎 ไฟล์ข้อมูล</h4>
        <p style="margin-bottom: 20px; color: #6b7280;">
            <span class="variable-highlight">{{export_filename}}</span> (<span class="variable-highlight">{{export_size}}</span>)
        </p>
        
        <div style="background: #f9fafb; padding: 15px; border-radius: 6px; margin-top: 20px; text-align: center;">
            <p style="margin: 0; color: #6b7280; font-size: 14px;">
                รายงานสร้างอัตโนมัติเมื่อ <span class="variable-highlight">{{query_time}}</span>
            </p>
        </div>
    </div>
</div>`,
        textBody: `📋 สรุปรายวัน
{{query_date}}

สถานะประจำวัน:
✅ ระบบทำงานปกติ
พบข้อมูล {{record_count}} รายการใหม่

สรุปตัวเลข:
- จำนวนรายการ: {{record_count}}
- เวลาประมวลผล: {{execution_time}}
- ขนาดข้อมูล: {{data_size}}

ไฟล์ข้อมูล:
{{export_filename}} ({{export_size}})

รายงานสร้างอัตโนมัติเมื่อ {{query_time}}`
    },
    
    'custom': {
        name: '✏️ กำหนดเอง',
        subject: 'การแจ้งเตือน - {{query_date}}',
        htmlBody: `
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: #6b7280; color: white; padding: 20px; border-radius: 8px 8px 0 0;">
        <h2 style="margin: 0; font-size: 24px;">การแจ้งเตือน</h2>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">{{query_date}}</p>
    </div>
    
    <div style="background: white; padding: 25px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <h3 style="color: #1f2937; margin-bottom: 15px;">รายละเอียดการแจ้งเตือน</h3>
        
        <p style="margin-bottom: 15px; color: #6b7280;">
            ข้อมูลจำนวน <span class="variable-highlight">{{record_count}}</span> รายการ
        </p>
        
        <p style="margin-bottom: 20px; color: #6b7280;">
            รายละเอียดในไฟล์แนบ: <span class="variable-highlight">{{export_filename}}</span>
        </p>
        
        <div style="background: #f9fafb; padding: 15px; border-radius: 6px; margin-top: 20px;">
            <p style="margin: 0; color: #6b7280; font-size: 14px;">
                สร้างโดยระบบอัตโนมัติ
            </p>
        </div>
    </div>
</div>`,
        textBody: `การแจ้งเตือน - {{query_date}}

ข้อมูลจำนวน {{record_count}} รายการ

รายละเอียดในไฟล์แนบ: {{export_filename}}

สร้างโดยระบบอัตโนมัติ`
    }
};

document.addEventListener('DOMContentLoaded', function() {
    loadAvailableVariables();
    loadSavedSettings();
    updatePreview();
});

function loadAvailableVariables() {
    // Load computed variables from previous step
    const computedVars = JSON.parse(sessionStorage.getItem('sql_alert_computed_variables') || '[]');
    const customVars = JSON.parse(sessionStorage.getItem('sql_alert_variables') || '[]');
    
    availableVariables = [
        ...computedVars.map(v => ({ name: v.name, description: v.description, type: 'system' })),
        ...customVars.map(v => ({ name: v.name, description: v.description, type: 'custom' }))
    ];
    
    updateVariableTags();
}

function updateVariableTags() {
    const container = document.getElementById('availableVariables');
    container.innerHTML = '';
    
    availableVariables.forEach(variable => {
        const tag = document.createElement('div');
        tag.className = 'variable-tag';
        tag.textContent = `{{${variable.name}}}`;
        tag.title = variable.description;
        tag.onclick = () => insertVariableIntoBody(variable.name);
        container.appendChild(tag);
    });
}

function loadSavedSettings() {
    const saved = sessionStorage.getItem('sql_alert_email_template');
    if (saved) {
        try {
            const settings = JSON.parse(saved);
            
            selectedTemplate = settings.template || 'system-alert';
            document.getElementById('emailSender').value = settings.sender || 'SQL Alert System <alerts@company.com>';
            document.getElementById('emailPriority').value = settings.priority || 'normal';
            
            selectTemplate(selectedTemplate, false);
            
        } catch (e) {
            console.error('Error loading saved template settings:', e);
        }
    }
}

function selectTemplate(templateId, updatePreview = true) {
    selectedTemplate = templateId;
    
    // Update UI
    document.querySelectorAll('.template-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`[data-template="${templateId}"]`).classList.add('selected');
    
    if (updatePreview) {
        updatePreview();
    }
    
    saveSettings();
}

function updatePreview() {
    const template = emailTemplates[selectedTemplate];
    if (!template) return;
    
    const sender = document.getElementById('emailSender').value;
    const priority = document.getElementById('emailPriority').value;
    
    // Update email fields
    document.getElementById('previewFrom').textContent = sender;
    document.getElementById('previewSubject').textContent = template.subject;
    
    const priorityLabels = {
        'low': '🟢 ต่ำ (Low)',
        'normal': '🟡 ปกติ (Normal)',
        'high': '🟠 สูง (High)',
        'urgent': '🔴 เร่งด่วน (Urgent)'
    };
    document.getElementById('previewPriority').textContent = priorityLabels[priority];
    
    // Update email body
    document.getElementById('emailBody').innerHTML = template.htmlBody;
    
    // Update HTML tab
    const fullHtml = generateFullHtml(template, sender, priority);
    document.getElementById('htmlCode').textContent = fullHtml;
    
    // Update text tab
    document.getElementById('textContent').textContent = template.textBody;
}

function generateFullHtml(template, sender, priority) {
    return `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${template.subject}</title>
</head>
<body style="margin: 0; padding: 20px; background-color: #f3f4f6; font-family: Arial, sans-serif;">
    ${template.htmlBody}
</body>
</html>`;
}

function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.preview-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`${tabName}Tab`).classList.add('active');
}

function refreshPreview() {
    updatePreview();
    
    // Show brief feedback
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> รีเฟรชแล้ว';
    setTimeout(() => {
        btn.innerHTML = originalText;
    }, 1000);
}

function insertVariableIntoBody(variableName) {
    // This would insert variable into email body editor in real implementation
    alert(`ตัวแปร {{${variableName}}} จะถูกใส่ในตำแหน่งเคอร์เซอร์ในขั้นตอนถัดไป`);
}

function saveSettings() {
    const settings = {
        template: selectedTemplate,
        sender: document.getElementById('emailSender').value,
        priority: document.getElementById('emailPriority').value,
        templateData: emailTemplates[selectedTemplate]
    };
    
    sessionStorage.setItem('sql_alert_email_template', JSON.stringify(settings));
}

function previousStep() {
    saveSettings();
    window.location.href = '{{ route("sql-alerts.create") }}?step=8';
}

function nextStep() {
    if (!selectedTemplate) {
        alert('กรุณาเลือกเทมเพลตอีเมล');
        return;
    }
    
    const sender = document.getElementById('emailSender').value.trim();
    if (!sender) {
        alert('กรุณาระบุผู้ส่งอีเมล');
        return;
    }
    
    saveSettings();
    sessionStorage.setItem('sql_alert_step', '10');
    window.location.href = '{{ route("sql-alerts.create") }}?step=10';
}

// Auto-save on input change
document.addEventListener('input', function(e) {
    if (e.target.matches('#emailSender, #emailPriority')) {
        updatePreview();
        saveSettings();
    }
});

document.addEventListener('change', function(e) {
    if (e.target.matches('#emailSender, #emailPriority')) {
        updatePreview();
        saveSettings();
    }
});
</script>
@endpush
@endsection