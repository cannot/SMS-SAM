@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนแบบ SQL - ตัวเลือกการส่งออกข้อมูล')

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

.export-options {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.export-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.export-title {
    font-weight: 600;
    color: #374151;
}

.export-toggle {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.export-toggle:hover {
    border-color: #4f46e5;
    background: #f0f9ff;
}

.export-toggle.active {
    border-color: #10b981;
    background: #f0fdf4;
}

.toggle-content {
    display: flex;
    align-items: center;
    gap: 15px;
}

.toggle-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: all 0.3s ease;
}

.export-toggle:not(.active) .toggle-icon {
    background: #e5e7eb;
    color: #6b7280;
}

.export-toggle.active .toggle-icon {
    background: #10b981;
    color: white;
}

.toggle-info {
    flex: 1;
}

.toggle-label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #374151;
}

.toggle-description {
    font-size: 0.875rem;
    color: #6b7280;
}

.export-toggle.active .toggle-description {
    color: #059669;
}

.export-settings {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 20px;
    margin-top: 15px;
    display: none;
    animation: slideDown 0.3s ease;
}

.export-settings.show {
    display: block;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
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

.filename-preview {
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 10px 12px;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    color: #374151;
    margin-top: 8px;
}

.format-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.format-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.format-card:hover {
    border-color: #4f46e5;
    background: #f0f9ff;
}

.format-card.selected {
    border-color: #10b981;
    background: #f0fdf4;
}

.format-icon {
    width: 40px;
    height: 40px;
    margin: 0 auto 10px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: all 0.3s ease;
}

.format-card:not(.selected) .format-icon {
    background: #e5e7eb;
    color: #6b7280;
}

.format-card.selected .format-icon {
    background: #10b981;
    color: white;
}

.format-name {
    font-weight: 600;
    margin-bottom: 5px;
    color: #374151;
}

.format-card.selected .format-name {
    color: #059669;
}

.format-description {
    font-size: 0.75rem;
    color: #6b7280;
}

.format-card.selected .format-description {
    color: #047857;
}

.advanced-options {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.advanced-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    cursor: pointer;
}

.advanced-title {
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.advanced-content {
    display: none;
    margin-top: 15px;
}

.advanced-content.show {
    display: block;
    animation: slideDown 0.3s ease;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f9fafb;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.checkbox-item:hover {
    background: #f3f4f6;
}

.checkbox-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #4f46e5;
}

.checkbox-label {
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
}

.preview-section {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.preview-header {
    font-weight: 600;
    margin-bottom: 15px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.preview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: #f9fafb;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #374151;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 4px;
}

.file-preview {
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 15px;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

.file-header {
    font-weight: 600;
    color: #059669;
    margin-bottom: 10px;
}

.file-content {
    color: #374151;
    line-height: 1.4;
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
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .format-options {
        grid-template-columns: 1fr;
    }
    
    .preview-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .checkbox-group {
        grid-template-columns: 1fr;
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
            <div class="wizard-title">📁 ตัวเลือกการส่งออกข้อมูล</div>
            <div class="wizard-subtitle">กำหนดรูปแบบไฟล์และตัวเลือกการส่งออกสำหรับแนบกับอีเมล</div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
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
                <div class="step"></div>
                <div class="step"></div>
            </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
            <!-- Step 7: Export Options -->
            <div class="section-title">
                <div class="section-icon">7</div>
                ตัวเลือกการส่งออกข้อมูล
            </div>

            <!-- Export Enable/Disable -->
            <div class="export-options">
                <div class="export-header">
                    <div class="export-title">
                        <i class="fas fa-file-export"></i>
                        เปิดใช้งานการส่งออกข้อมูล
                    </div>
                </div>

                <div class="export-toggle active" id="exportToggle" onclick="toggleExport()">
                    <div class="toggle-content">
                        <div class="toggle-icon">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <div class="toggle-info">
                            <div class="toggle-label">บันทึกข้อมูลเป็นไฟล์เพื่อแนบกับอีเมล</div>
                            <div class="toggle-description">สร้างไฟล์จากผลลัพธ์ SQL Query เพื่อส่งพร้อมกับการแจ้งเตือนทางอีเมล</div>
                        </div>
                        <div style="margin-left: auto;">
                            <input type="checkbox" id="exportEnabled" checked style="width: 20px; height: 20px;">
                        </div>
                    </div>
                </div>

                <!-- Export Settings -->
                <div class="export-settings show" id="exportSettings">
                    <!-- File Format Selection -->
                    <div class="form-group">
                        <label class="form-label">รูปแบบไฟล์</label>
                        <div class="format-options">
                            <div class="format-card selected" data-format="xlsx" onclick="selectFormat('xlsx')">
                                <div class="format-icon">
                                    <i class="fas fa-file-excel"></i>
                                </div>
                                <div class="format-name">Excel (.xlsx)</div>
                                <div class="format-description">รองรับสูตร, การจัดรูปแบบ</div>
                            </div>

                            <div class="format-card" data-format="csv" onclick="selectFormat('csv')">
                                <div class="format-icon">
                                    <i class="fas fa-file-csv"></i>
                                </div>
                                <div class="format-name">CSV (.csv)</div>
                                <div class="format-description">ไฟล์ข้อความคั่นด้วยจุลภาค</div>
                            </div>

                            <div class="format-card" data-format="pdf" onclick="selectFormat('pdf')">
                                <div class="format-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="format-name">PDF (.pdf)</div>
                                <div class="format-description">เอกสารแบบพกพา</div>
                            </div>

                            <div class="format-card" data-format="json" onclick="selectFormat('json')">
                                <div class="format-icon">
                                    <i class="fas fa-file-code"></i>
                                </div>
                                <div class="format-name">JSON (.json)</div>
                                <div class="format-description">รูปแบบข้อมูลโครงสร้าง</div>
                            </div>
                        </div>
                    </div>

                    <!-- File Settings -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="filename">ชื่อไฟล์</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="filename" 
                                   value="alert_data_{date}" 
                                   placeholder="ชื่อไฟล์"
                                   onchange="updateFilenamePreview()">
                            <div class="form-text">
                                ใช้ {date} สำหรับวันที่, {time} สำหรับเวลา, {timestamp} สำหรับ timestamp
                            </div>
                            <div class="filename-preview" id="filenamePreview">
                                alert_data_2025-07-11.xlsx
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="encoding">การเข้ารหัสอักขระ</label>
                            <select class="form-control form-select" id="encoding">
                                <option value="utf8" selected>UTF-8 (แนะนำ)</option>
                                <option value="tis620">TIS-620 (ภาษาไทย)</option>
                                <option value="windows1252">Windows-1252</option>
                                <option value="iso88591">ISO-8859-1</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="maxRows">จำกัดจำนวนแถว</label>
                            <select class="form-control form-select" id="maxRows">
                                <option value="0">ไม่จำกัด</option>
                                <option value="100">100 แถว</option>
                                <option value="500">500 แถว</option>
                                <option value="1000" selected>1,000 แถว</option>
                                <option value="5000">5,000 แถว</option>
                                <option value="10000">10,000 แถว</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="compression">การบีบอัด</label>
                            <select class="form-control form-select" id="compression">
                                <option value="none">ไม่บีบอัด</option>
                                <option value="zip" selected>ZIP (.zip)</option>
                                <option value="gzip">GZIP (.gz)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Options -->
            <div class="advanced-options">
                <div class="advanced-header" onclick="toggleAdvanced()">
                    <div class="advanced-title">
                        <i class="fas fa-cogs"></i>
                        ตัวเลือกขั้นสูง
                    </div>
                    <i class="fas fa-chevron-down" id="advancedIcon"></i>
                </div>

                <div class="advanced-content" id="advancedContent">
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="includeHeaders" checked>
                            <label class="checkbox-label" for="includeHeaders">รวม Header ของคอลัมน์</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="includeTimestamp" checked>
                            <label class="checkbox-label" for="includeTimestamp">รวมเวลาที่สร้างไฟล์</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="includeSummary">
                            <label class="checkbox-label" for="includeSummary">รวมสรุปข้อมูลในไฟล์</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="includeQuery">
                            <label class="checkbox-label" for="includeQuery">รวม SQL Query ที่ใช้</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="formatNumbers" checked>
                            <label class="checkbox-label" for="formatNumbers">จัดรูปแบบตัวเลข</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="formatDates" checked>
                            <label class="checkbox-label" for="formatDates">จัดรูปแบบวันที่</label>
                        </div>
                    </div>

                    <!-- Email Template Variables -->
                    <div class="form-group">
                        <label class="form-label">ตัวแปรสำหรับ Email Template</label>
                        <div class="form-text" style="margin-bottom: 10px;">
                            ตัวแปรเหล่านี้จะสามารถใช้ใน Email Template ได้
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <input type="text" class="form-control" id="fileVar" value="attachment_filename" placeholder="ชื่อตัวแปรไฟล์">
                                <div class="form-text">{{attachment_filename}} - ชื่อไฟล์ที่แนบ</div>
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control" id="sizeVar" value="attachment_size" placeholder="ชื่อตัวแปรขนาด">
                                <div class="form-text">{{attachment_size}} - ขนาดไฟล์</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="preview-section">
                <div class="preview-header">
                    <i class="fas fa-eye"></i>
                    ตัวอย่างไฟล์ที่จะสร้าง
                </div>

                <div class="preview-stats">
                    <div class="stat-card">
                        <div class="stat-value" id="previewRows">25</div>
                        <div class="stat-label">แถวข้อมูล</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="previewColumns">7</div>
                        <div class="stat-label">คอลัมน์</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="previewSize">15.2 KB</div>
                        <div class="stat-label">ขนาดประมาณ</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="previewFormat">XLSX</div>
                        <div class="stat-label">รูปแบบไฟล์</div>
                    </div>
                </div>

                <div class="file-preview" id="filePreview">
                    <div class="file-header">📄 alert_data_2025-07-11.xlsx</div>
                    <div class="file-content" id="previewContent">
alert_id,employee_name,department,alert_type,severity,message,created_at
1001,สมชาย ใจดี,IT,System Alert,Critical,CPU usage exceeds 90%,2025-07-11 14:30:00
1002,สมหญิง รักงาน,HR,Security Alert,High,Failed login attempts,2025-07-11 14:25:00
1003,วิชัย เก่งมาก,Finance,Performance Alert,Medium,Database slow response,2025-07-11 14:20:00
...
                    </div>
                </div>

                <div style="text-align: center; margin-top: 15px;">
                    <button type="button" class="btn btn-success btn-sm" onclick="generatePreview()">
                        <i class="fas fa-sync-alt"></i>
                        อัปเดตตัวอย่าง
                    </button>
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
                    ขั้นตอนที่ 7 จาก 14
                </div>
                
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    ถัดไป (นับจำนวนข้อมูล)
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let selectedFormat = 'xlsx';
let exportEnabled = true;

document.addEventListener('DOMContentLoaded', function() {
    loadSavedSettings();
    updateFilenamePreview();
    updatePreviewStats();
    generatePreview();
});

function loadSavedSettings() {
    // Load saved export settings
    const saved = sessionStorage.getItem('sql_alert_export_settings');
    if (saved) {
        try {
            const settings = JSON.parse(saved);
            
            document.getElementById('exportEnabled').checked = settings.enabled !== false;
            document.getElementById('filename').value = settings.filename || 'alert_data_{date}';
            document.getElementById('encoding').value = settings.encoding || 'utf8';
            document.getElementById('maxRows').value = settings.maxRows || '1000';
            document.getElementById('compression').value = settings.compression || 'zip';
            
            // Advanced options
            document.getElementById('includeHeaders').checked = settings.includeHeaders !== false;
            document.getElementById('includeTimestamp').checked = settings.includeTimestamp !== false;
            document.getElementById('includeSummary').checked = settings.includeSummary || false;
            document.getElementById('includeQuery').checked = settings.includeQuery || false;
            document.getElementById('formatNumbers').checked = settings.formatNumbers !== false;
            document.getElementById('formatDates').checked = settings.formatDates !== false;
            
            if (settings.format) {
                selectFormat(settings.format);
            }
            
            exportEnabled = settings.enabled !== false;
            updateExportToggle();
            
        } catch (e) {
            console.error('Error loading export settings:', e);
        }
    }
}

function toggleExport() {
    exportEnabled = !exportEnabled;
    document.getElementById('exportEnabled').checked = exportEnabled;
    updateExportToggle();
    saveSettings();
}

function updateExportToggle() {
    const toggle = document.getElementById('exportToggle');
    const settings = document.getElementById('exportSettings');
    
    if (exportEnabled) {
        toggle.classList.add('active');
        settings.classList.add('show');
    } else {
        toggle.classList.remove('active');
        settings.classList.remove('show');
    }
}

function selectFormat(format) {
    selectedFormat = format;
    
    // Update format cards
    document.querySelectorAll('.format-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`[data-format="${format}"]`).classList.add('selected');
    
    // Update filename extension
    updateFilenamePreview();
    updatePreviewStats();
    generatePreview();
    saveSettings();
}

function updateFilenamePreview() {
    const filename = document.getElementById('filename').value;
    const now = new Date();
    const dateStr = now.toISOString().split('T')[0]; // YYYY-MM-DD
    const timeStr = now.toTimeString().split(' ')[0].replace(/:/g, '-'); // HH-MM-SS
    const timestamp = Math.floor(now.getTime() / 1000);
    
    let processedFilename = filename
        .replace('{date}', dateStr)
        .replace('{time}', timeStr)
        .replace('{timestamp}', timestamp);
    
    // Add extension based on selected format
    const extensions = {
        'xlsx': '.xlsx',
        'csv': '.csv',
        'pdf': '.pdf',
        'json': '.json'
    };
    
    if (!processedFilename.includes('.')) {
        processedFilename += extensions[selectedFormat] || '.xlsx';
    }
    
    document.getElementById('filenamePreview').textContent = processedFilename;
}

function toggleAdvanced() {
    const content = document.getElementById('advancedContent');
    const icon = document.getElementById('advancedIcon');
    
    if (content.classList.contains('show')) {
        content.classList.remove('show');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    } else {
        content.classList.add('show');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    }
}

function updatePreviewStats() {
    // Load data from previous step
    const previewData = JSON.parse(sessionStorage.getItem('sql_alert_preview_data') || '{}');
    const maxRows = parseInt(document.getElementById('maxRows').value) || 0;
    
    const totalRows = previewData.totalRows || 25;
    const columns = previewData.columns || 7;
    const actualRows = maxRows === 0 ? totalRows : Math.min(maxRows, totalRows);
    
    // Calculate estimated file size
    let sizeMultiplier = 1;
    switch (selectedFormat) {
        case 'xlsx':
            sizeMultiplier = 1.2; // Excel files are slightly larger
            break;
        case 'csv':
            sizeMultiplier = 0.7; // CSV files are smaller
            break;
        case 'pdf':
            sizeMultiplier = 2.5; // PDF files are larger
            break;
        case 'json':
            sizeMultiplier = 1.5; // JSON files with formatting
            break;
    }
    
    const estimatedSize = (actualRows * columns * 20 * sizeMultiplier / 1024).toFixed(1); // Rough estimate
    
    document.getElementById('previewRows').textContent = actualRows.toLocaleString();
    document.getElementById('previewColumns').textContent = columns;
    document.getElementById('previewSize').textContent = estimatedSize + ' KB';
    document.getElementById('previewFormat').textContent = selectedFormat.toUpperCase();
}

function generatePreview() {
    const format = selectedFormat;
    const filename = document.getElementById('filenamePreview').textContent;
    const includeHeaders = document.getElementById('includeHeaders').checked;
    const includeSummary = document.getElementById('includeSummary').checked;
    const includeQuery = document.getElementById('includeQuery').checked;
    
    let preview = '';
    
    switch (format) {
        case 'xlsx':
        case 'csv':
            preview = generateCSVPreview(includeHeaders, includeSummary, includeQuery);
            break;
        case 'pdf':
            preview = generatePDFPreview();
            break;
        case 'json':
            preview = generateJSONPreview();
            break;
    }
    
    document.getElementById('previewContent').textContent = preview;
    
    // Update file header
    const fileHeader = document.querySelector('.file-header');
    const formatIcons = {
        'xlsx': '📊',
        'csv': '📋',
        'pdf': '📄',
        'json': '💾'
    };
    fileHeader.textContent = `${formatIcons[format]} ${filename}`;
}

function generateCSVPreview(includeHeaders, includeSummary, includeQuery) {
    let preview = '';
    
    // Add summary if enabled
    if (includeSummary) {
        preview += '# สรุปข้อมูล\n';
        preview += `# สร้างเมื่อ: ${new Date().toLocaleString('th-TH')}\n`;
        preview += `# จำนวนแถว: ${document.getElementById('previewRows').textContent}\n`;
        preview += `# จำนวนคอลัมน์: ${document.getElementById('previewColumns').textContent}\n`;
        preview += '\n';
    }
    
    // Add query if enabled
    if (includeQuery) {
        const query = sessionStorage.getItem('sql_alert_query') || '';
        preview += '# SQL Query ที่ใช้:\n';
        preview += `# ${query.replace(/\n/g, '\n# ')}\n`;
        preview += '\n';
    }
    
    // Add headers if enabled
    if (includeHeaders) {
        preview += 'alert_id,employee_name,department,alert_type,severity,message,created_at\n';
    }
    
    // Add sample data
    preview += '1001,สมชาย ใจดี,IT,System Alert,Critical,CPU usage exceeds 90%,2025-07-11 14:30:00\n';
    preview += '1002,สมหญิง รักงาน,HR,Security Alert,High,Failed login attempts,2025-07-11 14:25:00\n';
    preview += '1003,วิชัย เก่งมาก,Finance,Performance Alert,Medium,Database slow response,2025-07-11 14:20:00\n';
    preview += '1004,ประเสริฐ มั่นคง,Operations,User Alert,Low,Password expiry warning,2025-07-11 14:15:00\n';
    preview += '...\n';
    
    return preview;
}

function generatePDFPreview() {
    return `📄 PDF Report Preview

=== ระบบแจ้งเตือน ===
วันที่สร้าง: ${new Date().toLocaleDateString('th-TH')}
จำนวนข้อมูล: ${document.getElementById('previewRows').textContent} รายการ

+-------+------------------+------------+---------------+----------+
| ID    | ชื่อพนักงาน      | แผนก       | ประเภท        | ความสำคัญ |
+-------+------------------+------------+---------------+----------+
| 1001  | สมชาย ใจดี       | IT         | System Alert  | Critical |
| 1002  | สมหญิง รักงาน    | HR         | Security      | High     |
| 1003  | วิชัย เก่งมาก     | Finance    | Performance   | Medium   |
+-------+------------------+------------+---------------+----------+

... และรายการอื่นๆ อีก ${parseInt(document.getElementById('previewRows').textContent) - 3} รายการ`;
}

function generateJSONPreview() {
    return `{
  "metadata": {
    "generated_at": "${new Date().toISOString()}",
    "total_records": ${document.getElementById('previewRows').textContent},
    "format": "json",
    "query_date": "${new Date().toISOString().split('T')[0]}"
  },
  "data": [
    {
      "alert_id": 1001,
      "employee_name": "สมชาย ใจดี",
      "department": "IT",
      "alert_type": "System Alert",
      "severity": "Critical",
      "message": "CPU usage exceeds 90%",
      "created_at": "2025-07-11T14:30:00"
    },
    {
      "alert_id": 1002,
      "employee_name": "สมหญิง รักงาน",
      "department": "HR",
      "alert_type": "Security Alert",
      "severity": "High",
      "message": "Failed login attempts",
      "created_at": "2025-07-11T14:25:00"
    }
  ]
}`;
}

function saveSettings() {
    const settings = {
        enabled: exportEnabled,
        format: selectedFormat,
        filename: document.getElementById('filename').value,
        encoding: document.getElementById('encoding').value,
        maxRows: document.getElementById('maxRows').value,
        compression: document.getElementById('compression').value,
        includeHeaders: document.getElementById('includeHeaders').checked,
        includeTimestamp: document.getElementById('includeTimestamp').checked,
        includeSummary: document.getElementById('includeSummary').checked,
        includeQuery: document.getElementById('includeQuery').checked,
        formatNumbers: document.getElementById('formatNumbers').checked,
        formatDates: document.getElementById('formatDates').checked,
        fileVar: document.getElementById('fileVar').value,
        sizeVar: document.getElementById('sizeVar').value
    };
    
    sessionStorage.setItem('sql_alert_export_settings', JSON.stringify(settings));
}

function previousStep() {
    saveSettings();
    window.location.href = '{{ route("sql-alerts.create") }}?step=6';
}

function nextStep() {
    saveSettings();
    sessionStorage.setItem('sql_alert_step', '8');
    window.location.href = '{{ route("sql-alerts.create") }}?step=8';
}

// Auto-save on input change
document.addEventListener('input', function(e) {
    if (e.target.matches('#filename')) {
        updateFilenamePreview();
    }
    if (e.target.matches('#maxRows')) {
        updatePreviewStats();
    }
    if (e.target.matches('#encoding, #compression, input[type="checkbox"]')) {
        generatePreview();
    }
    saveSettings();
});

document.addEventListener('change', function(e) {
    if (e.target.matches('#maxRows, #encoding, #compression')) {
        updatePreviewStats();
        generatePreview();
    }
    saveSettings();
});

// Initialize advanced section as collapsed
document.addEventListener('DOMContentLoaded', function() {
    const advancedContent = document.getElementById('advancedContent');
    const advancedIcon = document.getElementById('advancedIcon');
    
    // Check if there are any non-default settings to show advanced by default
    const hasAdvancedSettings = 
        document.getElementById('includeSummary').checked ||
        document.getElementById('includeQuery').checked ||
        !document.getElementById('formatNumbers').checked ||
        !document.getElementById('formatDates').checked;
    
    if (hasAdvancedSettings) {
        advancedContent.classList.add('show');
        advancedIcon.classList.remove('fa-chevron-down');
        advancedIcon.classList.add('fa-chevron-up');
    }
});
</script>
@endpush
@endsection