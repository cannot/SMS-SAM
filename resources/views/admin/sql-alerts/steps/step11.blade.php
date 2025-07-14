@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนแบบ SQL - กำหนดตัวแปรใน Mail')

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

.variables-overview {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.overview-header {
    font-weight: 600;
    margin-bottom: 20px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.overview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-box {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #4f46e5;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.used-variables {
    background: #f0fdf4;
    border-left: 4px solid #10b981;
    padding: 15px;
    border-radius: 0 8px 8px 0;
    margin-top: 15px;
}

.used-variables h6 {
    color: #065f46;
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
    background: rgba(16, 185, 129, 0.1);
    color: #065f46;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-family: 'Courier New', monospace;
}

.mapping-section {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.mapping-header {
    font-weight: 600;
    margin-bottom: 20px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.variable-mapping {
    display: grid;
    gap: 20px;
}

.mapping-item {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s ease;
}

.mapping-item:hover {
    border-color: #4f46e5;
    background: #f0f9ff;
}

.mapping-row {
    display: grid;
    grid-template-columns: 200px 1fr 150px 100px;
    gap: 15px;
    align-items: end;
}

.mapping-variable {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #7c3aed;
    margin-bottom: 5px;
}

.mapping-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 10px;
}

.form-group {
    margin-bottom: 0;
}

.form-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    display: block;
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
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
    padding-right: 30px;
}

.mapping-type {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 4px;
}

.mapping-type.system {
    background: #dbeafe;
    color: #1d4ed8;
}

.mapping-type.custom {
    background: #dcfce7;
    color: #166534;
}

.mapping-type.computed {
    background: #fef3c7;
    color: #92400e;
}

.btn {
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
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

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-warning:hover {
    background: #d97706;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.formatting-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.formatting-header {
    font-weight: 600;
    margin-bottom: 20px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.format-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.format-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
}

.format-title {
    font-weight: 600;
    margin-bottom: 10px;
    color: #374151;
    font-size: 0.875rem;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
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
    margin-bottom: 30px;
    overflow: hidden;
}

.preview-header {
    background: #f9fafb;
    padding: 15px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.preview-title {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.preview-content {
    padding: 20px;
}

.preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.preview-table th {
    background: #f9fafb;
    padding: 10px 12px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}

.preview-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f3f4f6;
    color: #6b7280;
}

.preview-table tr:hover {
    background: #f9fafb;
}

.variable-value {
    font-family: 'Courier New', monospace;
    background: #f3f4f6;
    padding: 2px 6px;
    border-radius: 3px;
    color: #059669;
}

.validation-section {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 15px;
    border-radius: 0 8px 8px 0;
    margin-bottom: 30px;
}

.validation-header {
    font-weight: 600;
    margin-bottom: 10px;
    color: #92400e;
    font-size: 0.875rem;
}

.validation-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.validation-list li {
    padding: 5px 0;
    color: #92400e;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.validation-list li:before {
    content: "⚠️";
    font-size: 0.75rem;
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
    
    .overview-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .mapping-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .format-options {
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
            <div class="wizard-title">🏷️ กำหนดตัวแปรใน Mail</div>
            <div class="wizard-subtitle">ตั้งค่าการแมปและจัดรูปแบบตัวแปรสำหรับอีเมล</div>
            
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
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step active"></div>
                <div class="step"></div>
                <div class="step"></div>
                <div class="step"></div>
            </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
            <!-- Step 11: Email Variables Configuration -->
            <div class="section-title">
                <div class="section-icon">11</div>
                กำหนดตัวแปรใน Mail
            </div>

            <!-- Variables Overview -->
            <div class="variables-overview">
                <div class="overview-header">
                    <i class="fas fa-chart-bar"></i>
                    ภาพรวมตัวแปร
                </div>

                <div class="overview-stats">
                    <div class="stat-box">
                        <div class="stat-value" id="totalVariables">0</div>
                        <div class="stat-label">ตัวแปรทั้งหมด</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value" id="usedVariables">0</div>
                        <div class="stat-label">ใช้ในอีเมล</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value" id="systemVariables">0</div>
                        <div class="stat-label">ตัวแปรระบบ</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value" id="customVariables">0</div>
                        <div class="stat-label">ตัวแปรกำหนดเอง</div>
                    </div>
                </div>

                <div class="used-variables">
                    <h6>
                        <i class="fas fa-check-circle me-1"></i>
                        ตัวแปรที่ใช้ในอีเมล
                    </h6>
                    <div class="variable-tags" id="usedVariablesList">
                        <!-- Used variables will be populated here -->
                    </div>
                </div>
            </div>

            <!-- Variable Mapping -->
            <div class="mapping-section">
                <div class="mapping-header">
                    <i class="fas fa-exchange-alt"></i>
                    การแมปตัวแปร
                </div>

                <div class="variable-mapping" id="variableMapping">
                    <!-- Variable mappings will be populated here -->
                </div>
            </div>

            <!-- Formatting Options -->
            <div class="formatting-section">
                <div class="formatting-header">
                    <i class="fas fa-paint-brush"></i>
                    ตัวเลือกการจัดรูปแบบ
                </div>

                <div class="format-options">
                    <div class="format-card">
                        <div class="format-title">📅 วันที่และเวลา</div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="formatDates" checked>
                                <label class="checkbox-label" for="formatDates">จัดรูปแบบวันที่แบบไทย</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="formatTimes" checked>
                                <label class="checkbox-label" for="formatTimes">จัดรูปแบบเวลาแบบ 24 ชั่วโมง</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="relativeDates">
                                <label class="checkbox-label" for="relativeDates">แสดงวันที่แบบสัมพันธ์ (เมื่อวาน, วันนี้)</label>
                            </div>
                        </div>
                    </div>

                    <div class="format-card">
                        <div class="format-title">🔢 ตัวเลข</div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="formatNumbers" checked>
                                <label class="checkbox-label" for="formatNumbers">ใส่เครื่องหมายคั่นหลักพัน</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="formatCurrency">
                                <label class="checkbox-label" for="formatCurrency">แสดงสกุลเงิน (บาท)</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="formatPercentage">
                                <label class="checkbox-label" for="formatPercentage">แสดงร้อยละในรูปแบบ %</label>
                            </div>
                        </div>
                    </div>

                    <div class="format-card">
                        <div class="format-title">📁 ไฟล์</div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="formatFileSize" checked>
                                <label class="checkbox-label" for="formatFileSize">แสดงขนาดไฟล์ (KB, MB)</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="showFileIcon">
                                <label class="checkbox-label" for="showFileIcon">แสดงไอคอนตามประเภทไฟล์</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="linkToFile">
                                <label class="checkbox-label" for="linkToFile">สร้างลิงก์ไปยังไฟล์</label>
                            </div>
                        </div>
                    </div>

                    <div class="format-card">
                        <div class="format-title">🎨 การแสดงผล</div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="highlightImportant" checked>
                                <label class="checkbox-label" for="highlightImportant">เน้นข้อมูลสำคัญ</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="colorCode">
                                <label class="checkbox-label" for="colorCode">ใช้สีแยกประเภทข้อมูล</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="showTooltips">
                                <label class="checkbox-label" for="showTooltips">แสดงคำอธิบายเพิ่มเติม</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Table -->
            <div class="preview-section">
                <div class="preview-header">
                    <div class="preview-title">
                        <i class="fas fa-table"></i>
                        ตัวอย่างค่าตัวแปร
                    </div>
                    <button type="button" class="btn btn-success" onclick="refreshPreview()">
                        <i class="fas fa-sync-alt"></i>
                        รีเฟรช
                    </button>
                </div>

                <div class="preview-content">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>ตัวแปร</th>
                                <th>ค่าตัวอย่าง</th>
                                <th>ค่าที่จัดรูปแบบแล้ว</th>
                                <th>ประเภท</th>
                            </tr>
                        </thead>
                        <tbody id="previewTableBody">
                            <!-- Preview data will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Validation -->
            <div class="validation-section" id="validationSection" style="display: none;">
                <div class="validation-header">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    ข้อควรระวัง
                </div>
                <ul class="validation-list" id="validationList">
                    <!-- Validation messages will be populated here -->
                </ul>
            </div>

            <!-- Navigation -->
            <div class="wizard-navigation">
                <button type="button" class="btn btn-secondary" onclick="previousStep()">
                    <i class="fas fa-arrow-left"></i>
                    ย้อนกลับ
                </button>
                
                <div class="status-indicator">
                    <i class="fas fa-info-circle"></i>
                    ขั้นตอนที่ 11 จาก 14
                </div>
                
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    ถัดไป (เลือกผู้รับ)
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let allVariables = [];
let usedVariables = [];
let emailContent = '';

document.addEventListener('DOMContentLoaded', function() {
    loadVariables();
    loadEmailContent();
    analyzeUsedVariables();
    updateOverview();
    generateVariableMapping();
    refreshPreview();
    loadSavedSettings();
});

function loadVariables() {
    // Load all available variables
    const computedVars = JSON.parse(sessionStorage.getItem('sql_alert_computed_variables') || '[]');
    const customVars = JSON.parse(sessionStorage.getItem('sql_alert_variables') || '[]');
    
    allVariables = [
        ...computedVars.map(v => ({ 
            name: v.name, 
            description: v.description, 
            type: 'system',
            value: v.value || generateSampleValue(v.name),
            format: getDefaultFormat(v.name)
        })),
        ...customVars.map(v => ({ 
            name: v.name, 
            description: v.description, 
            type: 'custom',
            value: generateSampleValue(v.name),
            format: getDefaultFormat(v.name)
        }))
    ];
}

function loadEmailContent() {
    // Load email content to analyze used variables
    const content = JSON.parse(sessionStorage.getItem('sql_alert_email_content') || '{}');
    emailContent = (content.subject || '') + ' ' + (content.htmlContent || '') + ' ' + (content.textContent || '');
}

function analyzeUsedVariables() {
    // Find variables used in email content
    const variableRegex = /\{\{([^}]+)\}\}/g;
    const matches = [...emailContent.matchAll(variableRegex)];
    
    usedVariables = [...new Set(matches.map(match => match[1].trim()))];
}

function updateOverview() {
    document.getElementById('totalVariables').textContent = allVariables.length;
    document.getElementById('usedVariables').textContent = usedVariables.length;
    document.getElementById('systemVariables').textContent = allVariables.filter(v => v.type === 'system').length;
    document.getElementById('customVariables').textContent = allVariables.filter(v => v.type === 'custom').length;
    
    // Update used variables list
    const usedList = document.getElementById('usedVariablesList');
    usedList.innerHTML = '';
    
    usedVariables.forEach(varName => {
        const tag = document.createElement('div');
        tag.className = 'variable-tag';
        tag.textContent = `{{${varName}}}`;
        usedList.appendChild(tag);
    });
    
    if (usedVariables.length === 0) {
        usedList.innerHTML = '<span style="color: #6b7280; font-style: italic;">ไม่มีตัวแปรที่ใช้ในอีเมล</span>';
    }
}

function generateVariableMapping() {
    const mappingContainer = document.getElementById('variableMapping');
    mappingContainer.innerHTML = '';
    
    // Filter to show only used variables and some important system variables
    const importantSystemVars = ['record_count', 'query_date', 'export_filename'];
    const variablesToShow = allVariables.filter(v => 
        usedVariables.includes(v.name) || 
        (v.type === 'system' && importantSystemVars.includes(v.name))
    );
    
    if (variablesToShow.length === 0) {
        mappingContainer.innerHTML = `
            <div style="text-align: center; color: #6b7280; padding: 40px;">
                <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>ไม่มีตัวแปรที่ต้องกำหนดค่า</p>
                <p style="font-size: 0.875rem;">ตัวแปรจะแสดงที่นี่เมื่อคุณใช้ในเนื้อหาอีเมล</p>
            </div>
        `;
        return;
    }
    
    variablesToShow.forEach(variable => {
        const mappingItem = createMappingItem(variable);
        mappingContainer.appendChild(mappingItem);
    });
}

function createMappingItem(variable) {
    const item = document.createElement('div');
    item.className = 'mapping-item';
    
    const typeClass = variable.type === 'system' ? 'system' : 
                     variable.type === 'custom' ? 'custom' : 'computed';
    
    item.innerHTML = `
        <div class="mapping-row">
            <div>
                <div class="mapping-variable">{{${variable.name}}}</div>
                <div class="mapping-description">${variable.description}</div>
                <div class="mapping-type ${typeClass}">
                    <i class="fas fa-${variable.type === 'system' ? 'cog' : variable.type === 'custom' ? 'user' : 'calculator'}"></i>
                    ${variable.type === 'system' ? 'ระบบ' : variable.type === 'custom' ? 'กำหนดเอง' : 'คำนวณ'}
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">ค่าตัวอย่าง</label>
                <input type="text" 
                       class="form-control" 
                       value="${variable.value}" 
                       onchange="updateVariableValue('${variable.name}', this.value)"
                       placeholder="ค่าตัวอย่าง">
            </div>
            <div class="form-group">
                <label class="form-label">รูปแบบ</label>
                <select class="form-control form-select" 
                        onchange="updateVariableFormat('${variable.name}', this.value)">
                    <option value="raw" ${variable.format === 'raw' ? 'selected' : ''}>ไม่จัดรูปแบบ</option>
                    <option value="number" ${variable.format === 'number' ? 'selected' : ''}>ตัวเลข (1,234)</option>
                    <option value="currency" ${variable.format === 'currency' ? 'selected' : ''}>เงิน (1,234 บาท)</option>
                    <option value="percentage" ${variable.format === 'percentage' ? 'selected' : ''}>ร้อยละ (12.34%)</option>
                    <option value="date" ${variable.format === 'date' ? 'selected' : ''}>วันที่ (11 ก.ค. 2568)</option>
                    <option value="datetime" ${variable.format === 'datetime' ? 'selected' : ''}>วันที่เวลา (11 ก.ค. 2568 14:30)</option>
                    <option value="filesize" ${variable.format === 'filesize' ? 'selected' : ''}>ขนาดไฟล์ (1.5 MB)</option>
                </select>
            </div>
            <div>
                <button type="button" class="btn btn-warning" onclick="testVariable('${variable.name}')" title="ทดสอบตัวแปร">
                    <i class="fas fa-play"></i>
                </button>
            </div>
        </div>
    `;
    
    return item;
}

function updateVariableValue(varName, value) {
    const variable = allVariables.find(v => v.name === varName);
    if (variable) {
        variable.value = value;
        saveSettings();
        refreshPreview();
    }
}

function updateVariableFormat(varName, format) {
    const variable = allVariables.find(v => v.name === varName);
    if (variable) {
        variable.format = format;
        saveSettings();
        refreshPreview();
    }
}

function testVariable(varName) {
    const variable = allVariables.find(v => v.name === varName);
    if (variable) {
        const formattedValue = formatVariableValue(variable.value, variable.format);
        alert(`ตัวแปร {{${varName}}}:\n\nค่าดิบ: ${variable.value}\nค่าที่จัดรูปแบบ: ${formattedValue}`);
    }
}

function generateSampleValue(varName) {
    // Generate appropriate sample values based on variable name
    const sampleValues = {
        'record_count': '25',
        'query_date': '2025-07-11',
        'query_time': '14:30:00',
        'query_datetime': '2025-07-11 14:30:00',
        'execution_time': '0.45',
        'data_size': '15234',
        'export_filename': 'alert_data_20250711.xlsx',
        'export_size': '15234',
        'column_count': '7',
        'database_name': 'company_db',
        'compression_ratio': '65'
    };
    
    if (sampleValues[varName]) {
        return sampleValues[varName];
    }
    
    // Generate based on variable name patterns
    if (varName.includes('count') || varName.includes('number')) {
        return Math.floor(Math.random() * 1000).toString();
    } else if (varName.includes('date')) {
        return '2025-07-11';
    } else if (varName.includes('time')) {
        return '14:30:00';
    } else if (varName.includes('size')) {
        return '1024';
    } else if (varName.includes('percentage') || varName.includes('ratio')) {
        return '75';
    } else {
        return 'ตัวอย่าง';
    }
}

function getDefaultFormat(varName) {
    // Determine default format based on variable name
    if (varName.includes('count') || varName.includes('number')) {
        return 'number';
    } else if (varName.includes('date') && varName.includes('time')) {
        return 'datetime';
    } else if (varName.includes('date')) {
        return 'date';
    } else if (varName.includes('time')) {
        return 'raw';
    } else if (varName.includes('size')) {
        return 'filesize';
    } else if (varName.includes('percentage') || varName.includes('ratio')) {
        return 'percentage';
    } else {
        return 'raw';
    }
}

function formatVariableValue(value, format) {
    switch (format) {
        case 'number':
            return parseInt(value).toLocaleString('th-TH');
        
        case 'currency':
            return parseInt(value).toLocaleString('th-TH') + ' บาท';
        
        case 'percentage':
            return parseFloat(value).toFixed(1) + '%';
        
        case 'date':
            const date = new Date(value);
            return date.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        
        case 'datetime':
            const datetime = new Date(value + (value.includes('T') ? '' : 'T00:00:00'));
            return datetime.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        
        case 'filesize':
            const bytes = parseInt(value);
            const sizes = ['B', 'KB', 'MB', 'GB'];
            let i = 0;
            let size = bytes;
            while (size >= 1024 && i < sizes.length - 1) {
                size /= 1024;
                i++;
            }
            return size.toFixed(1) + ' ' + sizes[i];
        
        default:
            return value;
    }
}

function refreshPreview() {
    const tableBody = document.getElementById('previewTableBody');
    tableBody.innerHTML = '';
    
    // Show all variables with their formatted values
    allVariables.forEach(variable => {
        const row = document.createElement('tr');
        const formattedValue = formatVariableValue(variable.value, variable.format);
        const typeClass = variable.type === 'system' ? 'system' : 
                         variable.type === 'custom' ? 'custom' : 'computed';
        
        row.innerHTML = `
            <td>
                <code>{{${variable.name}}}</code>
                <div style="font-size: 0.75rem; color: #6b7280; margin-top: 2px;">
                    ${variable.description}
                </div>
            </td>
            <td>
                <span class="variable-value">${variable.value}</span>
            </td>
            <td>
                <strong>${formattedValue}</strong>
            </td>
            <td>
                <span class="mapping-type ${typeClass}">
                    <i class="fas fa-${variable.type === 'system' ? 'cog' : variable.type === 'custom' ? 'user' : 'calculator'}"></i>
                    ${variable.type === 'system' ? 'ระบบ' : variable.type === 'custom' ? 'กำหนดเอง' : 'คำนวณ'}
                </span>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
    
    // Update validation
    updateValidation();
}

function updateValidation() {
    const validationSection = document.getElementById('validationSection');
    const validationList = document.getElementById('validationList');
    const warnings = [];
    
    // Check for unused variables
    const unusedImportant = allVariables.filter(v => 
        v.type === 'system' && 
        ['record_count', 'query_date'].includes(v.name) && 
        !usedVariables.includes(v.name)
    );
    
    if (unusedImportant.length > 0) {
        warnings.push('ตัวแปรสำคัญที่ไม่ได้ใช้: ' + unusedImportant.map(v => `{{${v.name}}}`).join(', '));
    }
    
    // Check for variables used but not defined
    const undefinedVars = usedVariables.filter(varName => 
        !allVariables.find(v => v.name === varName)
    );
    
    if (undefinedVars.length > 0) {
        warnings.push('ตัวแปรที่ใช้แต่ไม่ได้กำหนดค่า: ' + undefinedVars.map(v => `{{${v}}}`).join(', '));
    }
    
    // Check formatting options
    const formatSettings = getFormatSettings();
    if (formatSettings.formatDates && !allVariables.some(v => v.format === 'date' || v.format === 'datetime')) {
        warnings.push('เปิดใช้การจัดรูปแบบวันที่แต่ไม่มีตัวแปรวันที่');
    }
    
    if (warnings.length > 0) {
        validationSection.style.display = 'block';
        validationList.innerHTML = warnings.map(warning => `<li>${warning}</li>`).join('');
    } else {
        validationSection.style.display = 'none';
    }
}

function getFormatSettings() {
    return {
        formatDates: document.getElementById('formatDates').checked,
        formatTimes: document.getElementById('formatTimes').checked,
        relativeDates: document.getElementById('relativeDates').checked,
        formatNumbers: document.getElementById('formatNumbers').checked,
        formatCurrency: document.getElementById('formatCurrency').checked,
        formatPercentage: document.getElementById('formatPercentage').checked,
        formatFileSize: document.getElementById('formatFileSize').checked,
        showFileIcon: document.getElementById('showFileIcon').checked,
        linkToFile: document.getElementById('linkToFile').checked,
        highlightImportant: document.getElementById('highlightImportant').checked,
        colorCode: document.getElementById('colorCode').checked,
        showTooltips: document.getElementById('showTooltips').checked
    };
}

function saveSettings() {
    const settings = {
        variables: allVariables,
        formatSettings: getFormatSettings()
    };
    
    sessionStorage.setItem('sql_alert_email_variables', JSON.stringify(settings));
}

function loadSavedSettings() {
    const saved = sessionStorage.getItem('sql_alert_email_variables');
    if (saved) {
        try {
            const settings = JSON.parse(saved);
            
            // Update variables with saved values and formats
            if (settings.variables) {
                settings.variables.forEach(savedVar => {
                    const variable = allVariables.find(v => v.name === savedVar.name);
                    if (variable) {
                        variable.value = savedVar.value;
                        variable.format = savedVar.format;
                    }
                });
            }
            
            // Update format checkboxes
            if (settings.formatSettings) {
                Object.entries(settings.formatSettings).forEach(([key, value]) => {
                    const checkbox = document.getElementById(key);
                    if (checkbox) {
                        checkbox.checked = value;
                    }
                });
            }
            
            // Regenerate mapping with updated values
            generateVariableMapping();
            refreshPreview();
            
        } catch (e) {
            console.error('Error loading saved email variables:', e);
        }
    }
}

function previousStep() {
    saveSettings();
    window.location.href = '{{ route("sql-alerts.create") }}?step=10';
}

function nextStep() {
    // Validate that all used variables have values
    const missingValues = usedVariables.filter(varName => {
        const variable = allVariables.find(v => v.name === varName);
        return !variable || !variable.value;
    });
    
    if (missingValues.length > 0) {
        alert(`ตัวแปรต่อไปนี้ยังไม่ได้กำหนดค่า:\n${missingValues.map(v => `- {{${v}}}`).join('\n')}`);
        return;
    }
    
    saveSettings();
    sessionStorage.setItem('sql_alert_step', '12');
    window.location.href = '{{ route("sql-alerts.create") }}?step=12';
}

// Auto-save on checkbox change
document.addEventListener('change', function(e) {
    if (e.target.matches('input[type="checkbox"]')) {
        saveSettings();
        refreshPreview();
    }
});

// Real-time validation on input
document.addEventListener('input', function(e) {
    if (e.target.matches('input[type="text"], select')) {
        updateValidation();
    }
});
</script>
@endpush
@endsection