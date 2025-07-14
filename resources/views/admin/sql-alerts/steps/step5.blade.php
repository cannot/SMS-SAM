@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนแบบ SQL - กำหนดตัวแปรใน Scripts')

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

.variables-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.variables-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.variables-title {
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.variable-item {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.variable-item:hover {
    border-color: #4f46e5;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.variable-row {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr auto;
    gap: 15px;
    align-items: end;
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

.btn {
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
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

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.75rem;
}

.predefined-variables {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.predefined-header {
    font-weight: 600;
    margin-bottom: 15px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.variable-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.variable-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.variable-card:hover {
    border-color: #4f46e5;
    background: #f0f9ff;
    transform: translateY(-1px);
}

.variable-name {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #7c3aed;
    margin-bottom: 5px;
}

.variable-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 8px;
}

.variable-example {
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
    color: #059669;
    background: rgba(16, 185, 129, 0.1);
    padding: 4px 6px;
    border-radius: 4px;
}

.sql-preview {
    background: #1f2937;
    color: #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    line-height: 1.5;
}

.sql-preview-header {
    color: #fbbf24;
    font-weight: 600;
    margin-bottom: 15px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.highlight-variable {
    background: rgba(251, 191, 36, 0.3);
    color: #fbbf24;
    padding: 2px 4px;
    border-radius: 3px;
}

.variable-types {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.type-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.type-badge.system {
    background: #dbeafe;
    color: #1d4ed8;
}

.type-badge.date {
    background: #dcfce7;
    color: #166534;
}

.type-badge.custom {
    background: #fef3c7;
    color: #92400e;
}

.type-badge.selected {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.validation-note {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 12px 16px;
    border-radius: 0 6px 6px 0;
    margin-top: 15px;
}

.validation-note h6 {
    color: #92400e;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.875rem;
}

.validation-note ul {
    margin-bottom: 0;
    color: #92400e;
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
    
    .variable-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .variable-grid {
        grid-template-columns: 1fr;
    }
    
    .variable-types {
        flex-direction: column;
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
            <div class="wizard-title">🔧 กำหนดตัวแปรใน Scripts</div>
            <div class="wizard-subtitle">สร้างตัวแปรที่จะใช้ในการแจ้งเตือนและ Email Template</div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
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
                <div class="step"></div>
                <div class="step"></div>
            </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
            <!-- Step 5: SQL Variables -->
            <div class="section-title">
                <div class="section-icon">5</div>
                กำหนดตัวแปรใน Scripts
            </div>

            <!-- Variable Types Filter -->
            <div class="variable-types">
                <div class="type-badge system selected" data-type="system" onclick="filterVariables('system')">
                    <i class="fas fa-cog me-1"></i>
                    ตัวแปรระบบ
                </div>
                <div class="type-badge date" data-type="date" onclick="filterVariables('date')">
                    <i class="fas fa-calendar me-1"></i>
                    วันที่และเวลา
                </div>
                <div class="type-badge custom" data-type="custom" onclick="filterVariables('custom')">
                    <i class="fas fa-edit me-1"></i>
                    กำหนดเอง
                </div>
            </div>

            <!-- Predefined Variables -->
            <div class="predefined-variables">
                <div class="predefined-header">
                    <i class="fas fa-list"></i>
                    ตัวแปรที่พร้อมใช้งาน
                </div>
                
                <div class="variable-grid" id="predefinedGrid">
                    <!-- System Variables -->
                    <div class="variable-card system-var" onclick="addPredefinedVariable('{{record_count}}', 'จำนวนแถวข้อมูล', 'COUNT(*)')">
                        <div class="variable-name">{{record_count}}</div>
                        <div class="variable-description">จำนวนแถวข้อมูลที่ได้จาก Query</div>
                        <div class="variable-example">ตัวอย่าง: 25</div>
                    </div>

                    <div class="variable-card system-var" onclick="addPredefinedVariable('{{query_execution_time}}', 'เวลาในการรัน Query', 'EXECUTION_TIME')">
                        <div class="variable-name">{{query_execution_time}}</div>
                        <div class="variable-description">เวลาที่ใช้ในการรัน SQL Query</div>
                        <div class="variable-example">ตัวอย่าง: 0.25s</div>
                    </div>

                    <div class="variable-card system-var" onclick="addPredefinedVariable('{{database_name}}', 'ชื่อฐานข้อมูล', 'DATABASE_NAME')">
                        <div class="variable-name">{{database_name}}</div>
                        <div class="variable-description">ชื่อฐานข้อมูลที่เชื่อมต่อ</div>
                        <div class="variable-example">ตัวอย่าง: company_db</div>
                    </div>

                    <!-- Date Variables -->
                    <div class="variable-card date-var" onclick="addPredefinedVariable('{{current_date}}', 'วันที่ปัจจุบัน', 'CURDATE()')">
                        <div class="variable-name">{{current_date}}</div>
                        <div class="variable-description">วันที่ปัจจุบัน</div>
                        <div class="variable-example">ตัวอย่าง: 2025-07-11</div>
                    </div>

                    <div class="variable-card date-var" onclick="addPredefinedVariable('{{current_datetime}}', 'วันที่และเวลาปัจจุบัน', 'NOW()')">
                        <div class="variable-name">{{current_datetime}}</div>
                        <div class="variable-description">วันที่และเวลาปัจจุบัน</div>
                        <div class="variable-example">ตัวอย่าง: 2025-07-11 14:30:00</div>
                    </div>

                    <div class="variable-card date-var" onclick="addPredefinedVariable('{{current_time}}', 'เวลาปัจจุบัน', 'CURTIME()')">
                        <div class="variable-name">{{current_time}}</div>
                        <div class="variable-description">เวลาปัจจุบัน</div>
                        <div class="variable-example">ตัวอย่าง: 14:30:00</div>
                    </div>

                    <div class="variable-card date-var" onclick="addPredefinedVariable('{{yesterday}}', 'เมื่อวานนี้', 'DATE_SUB(CURDATE(), INTERVAL 1 DAY)')">
                        <div class="variable-name">{{yesterday}}</div>
                        <div class="variable-description">วันที่เมื่อวานนี้</div>
                        <div class="variable-example">ตัวอย่าง: 2025-07-10</div>
                    </div>

                    <div class="variable-card date-var" onclick="addPredefinedVariable('{{last_week}}', 'สัปดาห์ที่แล้ว', 'DATE_SUB(CURDATE(), INTERVAL 1 WEEK)')">
                        <div class="variable-name">{{last_week}}</div>
                        <div class="variable-description">วันที่เมื่อสัปดาห์ที่แล้ว</div>
                        <div class="variable-example">ตัวอย่าง: 2025-07-04</div>
                    </div>
                </div>
            </div>

            <!-- Custom Variables Section -->
            <div class="variables-section">
                <div class="variables-header">
                    <div class="variables-title">
                        <i class="fas fa-plus-circle"></i>
                        ตัวแปรที่กำหนดเอง
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="addVariable()">
                        <i class="fas fa-plus"></i>
                        เพิ่มตัวแปร
                    </button>
                </div>

                <div id="variablesContainer">
                    <!-- Default variable -->
                    <div class="variable-item" id="variable-0">
                        <div class="variable-row">
                            <div class="form-group">
                                <label class="form-label">ชื่อตัวแปร</label>
                                <input type="text" 
                                       class="form-control" 
                                       name="variables[0][name]" 
                                       placeholder="เช่น: alert_count"
                                       value=""
                                       onchange="updatePreview()">
                            </div>
                            <div class="form-group">
                                <label class="form-label">คำอธิบาย</label>
                                <input type="text" 
                                       class="form-control" 
                                       name="variables[0][description]" 
                                       placeholder="เช่น: จำนวนการแจ้งเตือน"
                                       onchange="updatePreview()">
                            </div>
                            <div class="form-group">
                                <label class="form-label">ประเภท</label>
                                <select class="form-control form-select" 
                                        name="variables[0][type]"
                                        onchange="updatePreview()">
                                    <option value="system">ระบบ</option>
                                    <option value="query">จาก Query</option>
                                    <option value="date">วันที่</option>
                                    <option value="custom">กำหนดเอง</option>
                                </select>
                            </div>
                            <div>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeVariable(0)" title="ลบตัวแปร">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="validation-note">
                    <h6>
                        <i class="fas fa-info-circle me-1"></i>
                        หมายเหตุการใช้งาน
                    </h6>
                    <ul>
                        <li><strong>ชื่อตัวแปร:</strong> ใช้ในรูปแบบ {{variable_name}} ใน Email Template</li>
                        <li><strong>ประเภทระบบ:</strong> ตัวแปรที่ระบบสร้างอัตโนมัติ เช่น จำนวนข้อมูล, วันที่</li>
                        <li><strong>ประเภท Query:</strong> ค่าที่ได้จากผลลัพธ์ SQL Query</li>
                        <li><strong>ประเภทวันที่:</strong> ตัวแปรที่เกี่ยวข้องกับวันที่และเวลา</li>
                    </ul>
                </div>
            </div>

            <!-- SQL Preview with Variables -->
            <div class="sql-preview">
                <div class="sql-preview-header">
                    <i class="fas fa-eye me-2"></i>
                    ตัวอย่าง SQL Query พร้อมตัวแปร
                </div>
                <div id="sqlPreviewContent">
                    -- SQL Query ของคุณ
                    SELECT employee_id, employee_name, department 
                    FROM system_alerts 
                    WHERE created_at >= <span class="highlight-variable">{{current_date}}</span>
                    
                    -- ตัวแปรที่จะใช้ใน Email:
                    -- จำนวนข้อมูล: <span class="highlight-variable">{{record_count}}</span>
                    -- วันที่รัน: <span class="highlight-variable">{{current_datetime}}</span>
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
                    ขั้นตอนที่ 5 จาก 14
                </div>
                
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    ถัดไป (ดูตัวอย่างข้อมูล)
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@verbatim
<script>
let variableCount = 1;
let currentFilter = 'system';

document.addEventListener('DOMContentLoaded', function() {
    loadSavedVariables();
    updatePreview();
});

function filterVariables(type) {
    currentFilter = type;
    
    // Update active badge
    document.querySelectorAll('.type-badge').forEach(badge => {
        badge.classList.remove('selected');
    });
    document.querySelector('[data-type="' + type + '"]').classList.add('selected');
    
    // Show/hide variables
    const systemVars = document.querySelectorAll('.system-var');
    const dateVars = document.querySelectorAll('.date-var');
    
    systemVars.forEach(el => el.style.display = (type === 'system' || type === 'all') ? 'block' : 'none');
    dateVars.forEach(el => el.style.display = (type === 'date' || type === 'all') ? 'block' : 'none');
}

function addPredefinedVariable(name, description, value) {
    const container = document.getElementById('variablesContainer');
    const newId = variableCount++;
    
    // ป้องกัน null/undefined และ clean up curly braces
    const safeName = name ? name.replace(/\{\{|\}\}/g, '') : '';
    const safeDescription = description || '';
    
    const variableHtml = 
        '<div class="variable-item" id="variable-' + newId + '">' +
            '<div class="variable-row">' +
                '<div class="form-group">' +
                    '<label class="form-label">ชื่อตัวแปร</label>' +
                    '<input type="text" class="form-control" ' +
                           'name="variables[' + newId + '][name]" ' +
                           'value="' + safeName + '" ' +
                           'onchange="updatePreview()">' +
                '</div>' +
                '<div class="form-group">' +
                    '<label class="form-label">คำอธิบาย</label>' +
                    '<input type="text" class="form-control" ' +
                           'name="variables[' + newId + '][description]" ' +
                           'value="' + safeDescription + '" ' +
                           'onchange="updatePreview()">' +
                '</div>' +
                '<div class="form-group">' +
                    '<label class="form-label">ประเภท</label>' +
                    '<select class="form-control form-select" ' +
                            'name="variables[' + newId + '][type]" ' +
                            'onchange="updatePreview()">' +
                        '<option value="system"' + (currentFilter === 'system' ? ' selected' : '') + '>ระบบ</option>' +
                        '<option value="query">จาก Query</option>' +
                        '<option value="date"' + (currentFilter === 'date' ? ' selected' : '') + '>วันที่</option>' +
                        '<option value="custom">กำหนดเอง</option>' +
                    '</select>' +
                '</div>' +
                '<div>' +
                    '<button type="button" class="btn btn-danger btn-sm" onclick="removeVariable(' + newId + ')" title="ลบตัวแปร">' +
                        '<i class="fas fa-trash"></i>' +
                    '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    
    container.insertAdjacentHTML('beforeend', variableHtml);
    updatePreview();
    saveVariables();
}

function addVariable() {
    const container = document.getElementById('variablesContainer');
    const newId = variableCount++;
    
    const variableHtml = 
        '<div class="variable-item" id="variable-' + newId + '">' +
            '<div class="variable-row">' +
                '<div class="form-group">' +
                    '<label class="form-label">ชื่อตัวแปร</label>' +
                    '<input type="text" class="form-control" ' +
                           'name="variables[' + newId + '][name]" ' +
                           'placeholder="เช่น: alert_count" ' +
                           'onchange="updatePreview()">' +
                '</div>' +
                '<div class="form-group">' +
                    '<label class="form-label">คำอธิบาย</label>' +
                    '<input type="text" class="form-control" ' +
                           'name="variables[' + newId + '][description]" ' +
                           'placeholder="เช่น: จำนวนการแจ้งเตือน" ' +
                           'onchange="updatePreview()">' +
                '</div>' +
                '<div class="form-group">' +
                    '<label class="form-label">ประเภท</label>' +
                    '<select class="form-control form-select" ' +
                            'name="variables[' + newId + '][type]" ' +
                            'onchange="updatePreview()">' +
                        '<option value="system">ระบบ</option>' +
                        '<option value="query">จาก Query</option>' +
                        '<option value="date">วันที่</option>' +
                        '<option value="custom">กำหนดเอง</option>' +
                    '</select>' +
                '</div>' +
                '<div>' +
                    '<button type="button" class="btn btn-danger btn-sm" onclick="removeVariable(' + newId + ')" title="ลบตัวแปร">' +
                        '<i class="fas fa-trash"></i>' +
                    '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    
    container.insertAdjacentHTML('beforeend', variableHtml);
    updatePreview();
}

function removeVariable(id) {
    const element = document.getElementById('variable-' + id);
    if (element) {
        element.remove();
        updatePreview();
        saveVariables();
    }
}

function updatePreview() {
    const sqlQuery = sessionStorage.getItem('sql_alert_query') || 'SELECT * FROM your_table';
    const variables = getCurrentVariables();
    
    let preview = '-- SQL Query ของคุณ\n' + sqlQuery + '\n\n-- ตัวแปรที่จะใช้ใน Email:';
    
    variables.forEach(variable => {
        if (variable.name && variable.description) {
            preview += '\n-- ' + variable.description + ': <span class="highlight-variable">{{' + variable.name + '}}</span>';
        }
    });
    
    document.getElementById('sqlPreviewContent').innerHTML = preview;
}

function getCurrentVariables() {
    const variables = [];
    const container = document.getElementById('variablesContainer');
    const variableItems = container.querySelectorAll('.variable-item');
    
    variableItems.forEach(item => {
        const nameInput = item.querySelector('input[name*="[name]"]');
        const descInput = item.querySelector('input[name*="[description]"]');
        const typeSelect = item.querySelector('select[name*="[type]"]');
        
        if (nameInput && descInput && typeSelect) {
            const name = nameInput.value.trim();
            const description = descInput.value.trim();
            const type = typeSelect.value;
            
            if (name && description) {
                variables.push({
                    name: name,
                    description: description,
                    type: type
                });
            }
        }
    });
    
    return variables;
}

function saveVariables() {
    const variables = getCurrentVariables();
    sessionStorage.setItem('sql_alert_variables', JSON.stringify(variables));
}

function loadSavedVariables() {
    const saved = sessionStorage.getItem('sql_alert_variables');
    if (saved) {
        try {
            const variables = JSON.parse(saved);
            const container = document.getElementById('variablesContainer');
            
            // Clear existing except first one
            const existingItems = container.querySelectorAll('.variable-item');
            for (let i = 1; i < existingItems.length; i++) {
                existingItems[i].remove();
            }
            
            // Load saved variables
            variables.forEach((variable, index) => {
                if (index === 0) {
                    // Update first item
                    const firstItem = container.querySelector('.variable-item');
                    if (firstItem) {
                        firstItem.querySelector('input[name*="[name]"]').value = variable.name;
                        firstItem.querySelector('input[name*="[description]"]').value = variable.description;
                        firstItem.querySelector('select[name*="[type]"]').value = variable.type;
                    }
                } else {
                    // Add new items
                    const newId = variableCount++;
                    const variableHtml = 
                        '<div class="variable-item" id="variable-' + newId + '">' +
                            '<div class="variable-row">' +
                                '<div class="form-group">' +
                                    '<label class="form-label">ชื่อตัวแปร</label>' +
                                    '<input type="text" class="form-control" ' +
                                           'name="variables[' + newId + '][name]" ' +
                                           'value="' + variable.name + '" ' +
                                           'onchange="updatePreview()">' +
                                '</div>' +
                                '<div class="form-group">' +
                                    '<label class="form-label">คำอธิบาย</label>' +
                                    '<input type="text" class="form-control" ' +
                                           'name="variables[' + newId + '][description]" ' +
                                           'value="' + variable.description + '" ' +
                                           'onchange="updatePreview()">' +
                                '</div>' +
                                '<div class="form-group">' +
                                    '<label class="form-label">ประเภท</label>' +
                                    '<select class="form-control form-select" ' +
                                            'name="variables[' + newId + '][type]" ' +
                                            'onchange="updatePreview()">' +
                                        '<option value="system"' + (variable.type === 'system' ? ' selected' : '') + '>ระบบ</option>' +
                                        '<option value="query"' + (variable.type === 'query' ? ' selected' : '') + '>จาก Query</option>' +
                                        '<option value="date"' + (variable.type === 'date' ? ' selected' : '') + '>วันที่</option>' +
                                        '<option value="custom"' + (variable.type === 'custom' ? ' selected' : '') + '>กำหนดเอง</option>' +
                                    '</select>' +
                                '</div>' +
                                '<div>' +
                                    '<button type="button" class="btn btn-danger btn-sm" onclick="removeVariable(' + newId + ')" title="ลบตัวแปร">' +
                                        '<i class="fas fa-trash"></i>' +
                                    '</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                    container.insertAdjacentHTML('beforeend', variableHtml);
                }
            });
            
        } catch (e) {
            console.error('Error loading saved variables:', e);
        }
    }
}

function validateVariables() {
    const variables = getCurrentVariables();
    const errors = [];
    const names = [];
    
    variables.forEach((variable, index) => {
        // Check for empty names
        if (!variable.name) {
            errors.push('ตัวแปรที่ ' + (index + 1) + ': ไม่ได้ระบุชื่อ');
        }
        
        // Check for duplicate names
        if (names.includes(variable.name)) {
            errors.push('ตัวแปร "' + variable.name + '": ชื่อซ้ำกัน');
        } else {
            names.push(variable.name);
        }
        
        // Check variable name format
        if (variable.name && !/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(variable.name)) {
            errors.push('ตัวแปร "' + variable.name + '": ชื่อไม่ถูกต้อง (ใช้ได้เฉพาะ a-z, A-Z, 0-9, _)');
        }
    });
    
    return {
        isValid: errors.length === 0,
        errors: errors,
        variables: variables
    };
}

function previousStep() {
    saveVariables();
    window.location.href = '{{ route("sql-alerts.create") }}?step=4';
}

function nextStep() {
    const validation = validateVariables();
    
    if (!validation.isValid) {
        alert('พบข้อผิดพลาด:\n' + validation.errors.join('\n'));
        return;
    }
    
    if (validation.variables.length === 0) {
        if (!confirm('คุณยังไม่ได้กำหนดตัวแปร ต้องการดำเนินการต่อไหม?')) {
            return;
        }
    }
    
    saveVariables();
    sessionStorage.setItem('sql_alert_step', '6');
    window.location.href = '{{ route("sql-alerts.create") }}?step=6';
}

// Auto-save on input change
document.addEventListener('input', function(e) {
    if (e.target.matches('input[name*="variables"], select[name*="variables"]')) {
        updatePreview();
        saveVariables();
    }
});

// Initialize default system variables
document.addEventListener('DOMContentLoaded', function() {
    const saved = sessionStorage.getItem('sql_alert_variables');
    if (!saved) {
        // Add some default system variables
        setTimeout(() => {
            addPredefinedVariable('{{record_count}}', 'จำนวนแถวข้อมูล', 'COUNT(*)');
            addPredefinedVariable('{{current_date}}', 'วันที่ปัจจุบัน', 'CURDATE()');
        }, 500);
    }
});
</script>
@endverbatim
@endpush
@endsection