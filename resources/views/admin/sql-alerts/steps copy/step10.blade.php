@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนแบบ SQL - กำหนดเนื้อหา')

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

.editor-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.editor-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
}

.editor-header {
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

.form-textarea {
    min-height: 200px;
    font-family: Arial, sans-serif;
    line-height: 1.5;
    resize: vertical;
}

.html-editor {
    min-height: 300px;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    line-height: 1.4;
    background: #1f2937;
    color: #e5e7eb;
    border: 2px solid #374151;
}

.editor-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
    padding: 10px;
    background: #f3f4f6;
    border-radius: 6px;
}

.toolbar-btn {
    padding: 6px 12px;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.toolbar-btn:hover {
    background: #f9fafb;
    border-color: #4f46e5;
}

.toolbar-btn.format {
    font-weight: bold;
}

.toolbar-btn.variable {
    background: #fef3c7;
    border-color: #f59e0b;
    color: #92400e;
}

.toolbar-btn.variable:hover {
    background: #fed7aa;
}

.variables-panel {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    max-height: 200px;
    overflow-y: auto;
}

.variables-header {
    font-weight: 600;
    margin-bottom: 10px;
    color: #374151;
    font-size: 0.875rem;
}

.variables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 8px;
}

.variable-item {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    padding: 8px 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.75rem;
}

.variable-item:hover {
    background: #f0f9ff;
    border-color: #4f46e5;
    transform: translateY(-1px);
}

.variable-name {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #7c3aed;
    margin-bottom: 2px;
}

.variable-desc {
    color: #6b7280;
    font-size: 0.65rem;
}

.preview-section {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
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

.preview-controls {
    display: flex;
    gap: 8px;
}

.preview-content {
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.email-preview {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    background: white;
}

.email-header-preview {
    background: #f9fafb;
    padding: 12px 15px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.875rem;
}

.email-field-preview {
    display: flex;
    margin-bottom: 6px;
}

.email-field-preview:last-child {
    margin-bottom: 0;
}

.email-field-label-preview {
    min-width: 60px;
    font-weight: 600;
    color: #6b7280;
}

.email-field-value-preview {
    color: #374151;
}

.email-body-preview {
    padding: 15px;
    line-height: 1.6;
    color: #374151;
}

.email-body-preview h1, 
.email-body-preview h2, 
.email-body-preview h3 {
    color: #1f2937;
    margin-bottom: 10px;
}

.email-body-preview p {
    margin-bottom: 10px;
}

.email-body-preview ul {
    margin-bottom: 10px;
    padding-left: 20px;
}

.variable-highlight-preview {
    background: #fef3c7;
    color: #92400e;
    padding: 1px 3px;
    border-radius: 2px;
    font-family: 'Courier New', monospace;
    font-size: 0.875em;
}

.advanced-options {
    background: #f8f9fa;
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
}

.form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 8px center;
    background-repeat: no-repeat;
    background-size: 16px 12px;
    padding-right: 40px;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    background: white;
    border-radius: 4px;
    cursor: pointer;
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

.btn {
    padding: 8px 16px;
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

.btn-sm {
    padding: 6px 12px;
    font-size: 0.75rem;
}

.test-email-section {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 15px;
    border-radius: 0 8px 8px 0;
    margin-bottom: 20px;
}

.test-email-header {
    font-weight: 600;
    margin-bottom: 10px;
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
    
    .editor-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
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
            <div class="wizard-title">✏️ กำหนดเนื้อหา</div>
            <div class="wizard-subtitle">ปรับแต่งเนื้อหาอีเมลและใส่ตัวแปรตามต้องการ</div>
            
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
                <div class="step active"></div>
                <div class="step"></div>
                <div class="step"></div>
                <div class="step"></div>
                <div class="step"></div>
            </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
            <!-- Step 10: Email Content Customization -->
            <div class="section-title">
                <div class="section-icon">10</div>
                กำหนดเนื้อหา
            </div>

            <!-- Editor Layout -->
            <div class="editor-layout">
                <!-- Left Side - Editor -->
                <div class="editor-section">
                    <div class="editor-header">
                        <i class="fas fa-edit"></i>
                        แก้ไขเนื้อหาอีเมล
                    </div>

                    <!-- Subject Line -->
                    <div class="form-group">
                        <label class="form-label" for="emailSubject">หัวข้ออีเมล (Subject)</label>
                        <input type="text" 
                               class="form-control" 
                               id="emailSubject" 
                               placeholder="หัวข้ออีเมล"
                               onchange="updatePreview()">
                    </div>

                    <!-- Variables Panel -->
                    <div class="variables-panel">
                        <div class="variables-header">
                            <i class="fas fa-tags me-1"></i>
                            คลิกเพื่อใส่ตัวแปร
                        </div>
                        <div class="variables-grid" id="variablesGrid">
                            <!-- Variables will be populated here -->
                        </div>
                    </div>

                    <!-- HTML Editor -->
                    <div class="form-group">
                        <label class="form-label">เนื้อหาอีเมล (HTML)</label>
                        
                        <!-- Editor Toolbar -->
                        <div class="editor-toolbar">
                            <button type="button" class="toolbar-btn format" onclick="formatText('bold')" title="ตัวหนา">
                                <b>B</b>
                            </button>
                            <button type="button" class="toolbar-btn format" onclick="formatText('italic')" title="ตัวเอียง">
                                <i>I</i>
                            </button>
                            <button type="button" class="toolbar-btn format" onclick="formatText('underline')" title="ขีดเส้นใต้">
                                <u>U</u>
                            </button>
                            <button type="button" class="toolbar-btn" onclick="insertHeading()" title="หัวข้อ">
                                H3
                            </button>
                            <button type="button" class="toolbar-btn" onclick="insertList()" title="รายการ">
                                <i class="fas fa-list"></i>
                            </button>
                            <button type="button" class="toolbar-btn" onclick="insertTable()" title="ตาราง">
                                <i class="fas fa-table"></i>
                            </button>
                        </div>

                        <textarea class="form-control html-editor" 
                                  id="emailHtmlContent" 
                                  placeholder="เนื้อหาอีเมลแบบ HTML..."
                                  onchange="updatePreview()"
                                  oninput="updatePreview()"></textarea>
                    </div>

                    <!-- Plain Text Version -->
                    <div class="form-group">
                        <label class="form-label" for="emailTextContent">เนื้อหาแบบข้อความธรรมดา</label>
                        <textarea class="form-control form-textarea" 
                                  id="emailTextContent" 
                                  placeholder="เนื้อหาอีเมลแบบข้อความธรรมดา..."
                                  onchange="updatePreview()"></textarea>
                    </div>
                </div>

                <!-- Right Side - Preview -->
                <div class="preview-section">
                    <div class="preview-header">
                        <div class="preview-title">
                            <i class="fas fa-eye"></i>
                            ตัวอย่างอีเมล
                        </div>
                        <div class="preview-controls">
                            <button type="button" class="btn btn-success btn-sm" onclick="updatePreview()">
                                <i class="fas fa-sync-alt"></i>
                                รีเฟรช
                            </button>
                        </div>
                    </div>

                    <div class="preview-content">
                        <div class="email-preview">
                            <div class="email-header-preview">
                                <div class="email-field-preview">
                                    <div class="email-field-label-preview">From:</div>
                                    <div class="email-field-value-preview" id="previewFrom">-</div>
                                </div>
                                <div class="email-field-preview">
                                    <div class="email-field-label-preview">Subject:</div>
                                    <div class="email-field-value-preview" id="previewSubject">-</div>
                                </div>
                                <div class="email-field-preview">
                                    <div class="email-field-label-preview">Priority:</div>
                                    <div class="email-field-value-preview" id="previewPriority">-</div>
                                </div>
                            </div>
                            <div class="email-body-preview" id="emailBodyPreview">
                                <!-- Preview content will be populated here -->
                            </div>
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
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="replyTo">Reply-To</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="replyTo" 
                                   placeholder="noreply@company.com">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="emailFormat">รูปแบบอีเมล</label>
                            <select class="form-control form-select" id="emailFormat">
                                <option value="html">HTML เท่านั้น</option>
                                <option value="text">ข้อความธรรมดาเท่านั้น</option>
                                <option value="both" selected>ทั้ง HTML และข้อความธรรมดา</option>
                            </select>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="trackOpen" checked>
                            <label class="checkbox-label" for="trackOpen">ติดตามการเปิดอีเมล</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="trackClick">
                            <label class="checkbox-label" for="trackClick">ติดตามการคลิกลิงก์</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="autoGenerateText" checked>
                            <label class="checkbox-label" for="autoGenerateText">สร้างข้อความธรรมดาอัตโนมัติ</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="includeUnsubscribe">
                            <label class="checkbox-label" for="includeUnsubscribe">รวมลิงก์ยกเลิกการสมัคร</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Email -->
            <div class="test-email-section">
                <div class="test-email-header">
                    <i class="fas fa-paper-plane me-1"></i>
                    ทดสอบส่งอีเมล
                </div>
                <div style="display: flex; gap: 15px; align-items: end;">
                    <div style="flex: 1;">
                        <input type="email" 
                               class="form-control" 
                               id="testEmail" 
                               placeholder="your.email@example.com"
                               style="margin-bottom: 0;">
                    </div>
                    <button type="button" class="btn btn-primary" onclick="sendTestEmail()">
                        <i class="fas fa-paper-plane"></i>
                        ส่งทดสอบ
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
                    ขั้นตอนที่ 10 จาก 14
                </div>
                
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    ถัดไป (กำหนดตัวแปรใน Mail)
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let availableVariables = [];
let templateData = {};

document.addEventListener('DOMContentLoaded', function() {
    loadSavedData();
    loadVariables();
    loadTemplateData();
    updatePreview();
});

function loadSavedData() {
    // Load saved content
    const saved = sessionStorage.getItem('sql_alert_email_content');
    if (saved) {
        try {
            const data = JSON.parse(saved);
            
            document.getElementById('emailSubject').value = data.subject || '';
            document.getElementById('emailHtmlContent').value = data.htmlContent || '';
            document.getElementById('emailTextContent').value = data.textContent || '';
            document.getElementById('replyTo').value = data.replyTo || '';
            document.getElementById('emailFormat').value = data.format || 'both';
            
            // Load checkboxes
            document.getElementById('trackOpen').checked = data.trackOpen !== false;
            document.getElementById('trackClick').checked = data.trackClick || false;
            document.getElementById('autoGenerateText').checked = data.autoGenerateText !== false;
            document.getElementById('includeUnsubscribe').checked = data.includeUnsubscribe || false;
            
        } catch (e) {
            console.error('Error loading saved content:', e);
        }
    }
}

function loadVariables() {
    // Load available variables
    const computedVars = JSON.parse(sessionStorage.getItem('sql_alert_computed_variables') || '[]');
    const customVars = JSON.parse(sessionStorage.getItem('sql_alert_variables') || '[]');
    
    availableVariables = [
        ...computedVars.map(v => ({ name: v.name, description: v.description, type: 'system' })),
        ...customVars.map(v => ({ name: v.name, description: v.description, type: 'custom' }))
    ];
    
    updateVariablesGrid();
}

function loadTemplateData() {
    // Load template from previous step
    const template = JSON.parse(sessionStorage.getItem('sql_alert_email_template') || '{}');
    templateData = template;
    
    // Pre-fill if no saved content
    const savedContent = sessionStorage.getItem('sql_alert_email_content');
    if (!savedContent && template.templateData) {
        document.getElementById('emailSubject').value = template.templateData.subject || '';
        document.getElementById('emailHtmlContent').value = template.templateData.htmlBody || '';
        document.getElementById('emailTextContent').value = template.templateData.textBody || '';
    }
}

function updateVariablesGrid() {
    const grid = document.getElementById('variablesGrid');
    grid.innerHTML = '';
    
    availableVariables.forEach(variable => {
        const item = document.createElement('div');
        item.className = 'variable-item';
        item.onclick = () => insertVariable(variable.name);
        
        item.innerHTML = `
            <div class="variable-name">{{${variable.name}}}</div>
            <div class="variable-desc">${variable.description}</div>
        `;
        
        grid.appendChild(item);
    });
}

function insertVariable(variableName) {
    const activeElement = document.activeElement;
    const variableText = `{{${variableName}}}`;
    
    if (activeElement && (activeElement.id === 'emailHtmlContent' || activeElement.id === 'emailTextContent' || activeElement.id === 'emailSubject')) {
        const start = activeElement.selectionStart;
        const end = activeElement.selectionEnd;
        const text = activeElement.value;
        
        activeElement.value = text.substring(0, start) + variableText + text.substring(end);
        activeElement.selectionStart = activeElement.selectionEnd = start + variableText.length;
        activeElement.focus();
        
        updatePreview();
        saveContent();
    } else {
        // Insert at the end of HTML content if no focus
        const htmlContent = document.getElementById('emailHtmlContent');
        htmlContent.value += variableText;
        htmlContent.focus();
        updatePreview();
        saveContent();
    }
}

function formatText(command) {
    const textarea = document.getElementById('emailHtmlContent');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    
    if (!selectedText) {
        alert('กรุณาเลือกข้อความที่ต้องการจัดรูปแบบ');
        return;
    }
    
    let formattedText = '';
    switch (command) {
        case 'bold':
            formattedText = `<strong>${selectedText}</strong>`;
            break;
        case 'italic':
            formattedText = `<em>${selectedText}</em>`;
            break;
        case 'underline':
            formattedText = `<u>${selectedText}</u>`;
            break;
        default:
            formattedText = selectedText;
    }
    
    textarea.value = textarea.value.substring(0, start) + formattedText + textarea.value.substring(end);
    textarea.selectionStart = start;
    textarea.selectionEnd = start + formattedText.length;
    textarea.focus();
    
    updatePreview();
    saveContent();
}

function insertHeading() {
    const textarea = document.getElementById('emailHtmlContent');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end) || 'หัวข้อ';
    
    const headingText = `<h3 style="color: #1f2937; margin-bottom: 15px;">${selectedText}</h3>`;
    
    textarea.value = textarea.value.substring(0, start) + headingText + textarea.value.substring(end);
    textarea.selectionStart = start;
    textarea.selectionEnd = start + headingText.length;
    textarea.focus();
    
    updatePreview();
    saveContent();
}

function insertList() {
    const textarea = document.getElementById('emailHtmlContent');
    const start = textarea.selectionStart;
    
    const listText = `<ul style="margin-bottom: 15px; padding-left: 20px;">
    <li>รายการที่ 1</li>
    <li>รายการที่ 2</li>
    <li>รายการที่ 3</li>
</ul>`;
    
    textarea.value = textarea.value.substring(0, start) + listText + textarea.value.substring(start);
    textarea.selectionStart = textarea.selectionEnd = start + listText.length;
    textarea.focus();
    
    updatePreview();
    saveContent();
}

function insertTable() {
    const textarea = document.getElementById('emailHtmlContent');
    const start = textarea.selectionStart;
    
    const tableText = `<table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
    <tr style="background: #f9fafb;">
        <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: left;">หัวข้อ 1</th>
        <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: left;">หัวข้อ 2</th>
    </tr>
    <tr>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">ข้อมูล 1</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">ข้อมูล 2</td>
    </tr>
</table>`;
    
    textarea.value = textarea.value.substring(0, start) + tableText + textarea.value.substring(start);
    textarea.selectionStart = textarea.selectionEnd = start + tableText.length;
    textarea.focus();
    
    updatePreview();
    saveContent();
}

function updatePreview() {
    // Load template data
    const template = JSON.parse(sessionStorage.getItem('sql_alert_email_template') || '{}');
    
    // Update preview header
    document.getElementById('previewFrom').textContent = template.sender || 'ไม่ระบุ';
    document.getElementById('previewSubject').textContent = document.getElementById('emailSubject').value || 'ไม่มีหัวข้อ';
    
    const priorityLabels = {
        'low': '🟢 ต่ำ (Low)',
        'normal': '🟡 ปกติ (Normal)',
        'high': '🟠 สูง (High)',
        'urgent': '🔴 เร่งด่วน (Urgent)'
    };
    document.getElementById('previewPriority').textContent = priorityLabels[template.priority] || '🟡 ปกติ (Normal)';
    
    // Update preview body
    const htmlContent = document.getElementById('emailHtmlContent').value;
    const processedContent = processVariablesForPreview(htmlContent);
    document.getElementById('emailBodyPreview').innerHTML = processedContent;
    
    // Auto-generate text content if enabled
    if (document.getElementById('autoGenerateText').checked) {
        const textContent = htmlToText(htmlContent);
        document.getElementById('emailTextContent').value = textContent;
    }
}

function processVariablesForPreview(content) {
    // Replace variables with sample values for preview
    const sampleValues = {
        'record_count': '25',
        'query_date': '2025-07-11',
        'query_time': '14:30:00',
        'query_datetime': '2025-07-11 14:30:00',
        'execution_time': '0.45s',
        'data_size': '15.2 KB',
        'export_filename': 'alert_data_20250711.xlsx',
        'export_size': '15.2 KB',
        'column_count': '7',
        'database_name': 'company_db'
    };
    
    let processed = content;
    
    // Replace variables with highlighted sample values
    Object.entries(sampleValues).forEach(([key, value]) => {
        const regex = new RegExp(`{{${key}}}`, 'g');
        processed = processed.replace(regex, `<span class="variable-highlight-preview">${value}</span>`);
    });
    
    // Handle any remaining variables
    processed = processed.replace(/{{([^}]+)}}/g, '<span class="variable-highlight-preview">$1</span>');
    
    return processed;
}

function htmlToText(html) {
    // Simple HTML to text conversion
    let text = html;
    
    // Convert common HTML tags to text equivalents
    text = text.replace(/<h[1-6][^>]*>(.*?)<\/h[1-6]>/gi, '\n$1\n');
    text = text.replace(/<p[^>]*>(.*?)<\/p>/gi, '$1\n\n');
    text = text.replace(/<br\s*\/?>/gi, '\n');
    text = text.replace(/<li[^>]*>(.*?)<\/li>/gi, '- $1\n');
    text = text.replace(/<ul[^>]*>(.*?)<\/ul>/gi, '$1\n');
    text = text.replace(/<ol[^>]*>(.*?)<\/ol>/gi, '$1\n');
    text = text.replace(/<strong[^>]*>(.*?)<\/strong>/gi, '*$1*');
    text = text.replace(/<em[^>]*>(.*?)<\/em>/gi, '_$1_');
    text = text.replace(/<u[^>]*>(.*?)<\/u>/gi, '$1');
    
    // Remove remaining HTML tags
    text = text.replace(/<[^>]*>/g, '');
    
    // Clean up whitespace
    text = text.replace(/\n\s*\n\s*\n/g, '\n\n');
    text = text.trim();
    
    return text;
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

function sendTestEmail() {
    const testEmail = document.getElementById('testEmail').value.trim();
    
    if (!testEmail) {
        alert('กรุณาระบุอีเมลสำหรับทดสอบ');
        return;
    }
    
    if (!isValidEmail(testEmail)) {
        alert('รูปแบบอีเมลไม่ถูกต้อง');
        return;
    }
    
    const subject = document.getElementById('emailSubject').value;
    const htmlContent = document.getElementById('emailHtmlContent').value;
    
    if (!subject || !htmlContent) {
        alert('กรุณากรอกหัวข้อและเนื้อหาอีเมลก่อนทดสอบ');
        return;
    }
    
    // Simulate sending test email
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังส่ง...';
    btn.disabled = true;
    
    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-check"></i> ส่งแล้ว';
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 2000);
        
        alert(`ส่งอีเมลทดสอบไปยัง ${testEmail} เรียบร้อยแล้ว`);
    }, 2000);
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function saveContent() {
    const content = {
        subject: document.getElementById('emailSubject').value,
        htmlContent: document.getElementById('emailHtmlContent').value,
        textContent: document.getElementById('emailTextContent').value,
        replyTo: document.getElementById('replyTo').value,
        format: document.getElementById('emailFormat').value,
        trackOpen: document.getElementById('trackOpen').checked,
        trackClick: document.getElementById('trackClick').checked,
        autoGenerateText: document.getElementById('autoGenerateText').checked,
        includeUnsubscribe: document.getElementById('includeUnsubscribe').checked
    };
    
    sessionStorage.setItem('sql_alert_email_content', JSON.stringify(content));
}

function validateContent() {
    const subject = document.getElementById('emailSubject').value.trim();
    const htmlContent = document.getElementById('emailHtmlContent').value.trim();
    const textContent = document.getElementById('emailTextContent').value.trim();
    const format = document.getElementById('emailFormat').value;
    
    if (!subject) {
        alert('กรุณากรอกหัวข้ออีเมล');
        return false;
    }
    
    if (format === 'html' || format === 'both') {
        if (!htmlContent) {
            alert('กรุณากรอกเนื้อหาอีเมลแบบ HTML');
            return false;
        }
    }
    
    if (format === 'text' || format === 'both') {
        if (!textContent) {
            alert('กรุณากรอกเนื้อหาอีเมลแบบข้อความธรรมดา');
            return false;
        }
    }
    
    return true;
}

function previousStep() {
    saveContent();
    window.location.href = '{{ route("sql-alerts.create") }}?step=9';
}

function nextStep() {
    if (!validateContent()) {
        return;
    }
    
    saveContent();
    sessionStorage.setItem('sql_alert_step', '11');
    window.location.href = '{{ route("sql-alerts.create") }}?step=11';
}

// Auto-save on input change
document.addEventListener('input', function(e) {
    if (e.target.matches('#emailSubject, #emailHtmlContent, #emailTextContent, #replyTo')) {
        updatePreview();
        saveContent();
    }
});

document.addEventListener('change', function(e) {
    if (e.target.matches('#emailFormat, input[type="checkbox"]')) {
        updatePreview();
        saveContent();
    }
});

// Handle auto-generate text toggle
document.getElementById('autoGenerateText').addEventListener('change', function() {
    if (this.checked) {
        const htmlContent = document.getElementById('emailHtmlContent').value;
        if (htmlContent) {
            const textContent = htmlToText(htmlContent);
            document.getElementById('emailTextContent').value = textContent;
        }
    }
    saveContent();
});

// Handle textarea resize
document.getElementById('emailHtmlContent').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.max(300, this.scrollHeight) + 'px';
});
</script>
@endpush
@endsection