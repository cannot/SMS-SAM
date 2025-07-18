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

.variable-panel {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.variable-panel-header {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 20px;
}

.variables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
}

.variable-tag {
    background: #e0e7ff;
    color: #3730a3;
    border: 1px solid #c7d2fe;
    padding: 10px 15px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.variable-tag:hover {
    background: #c7d2fe;
    color: #312e81;
}

.variable-name {
    font-weight: 600;
}

.variable-description {
    font-size: 0.85rem;
    opacity: 0.8;
}

.content-editor {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
}

.tabs {
    display: flex;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 20px;
}

.tab {
    padding: 12px 24px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;
    font-weight: 500;
}

.tab.active {
    color: #4f46e5;
    border-bottom-color: #4f46e5;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.editor-group {
    margin-bottom: 25px;
}

.editor-label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.editor-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
}

.editor-input:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.editor-textarea {
    min-height: 120px;
    resize: vertical;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
}

.help-text {
    font-size: 0.85rem;
    color: #6b7280;
    margin-top: 5px;
}

.preview-container {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.preview-header {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.preview-content {
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 20px;
    background: #fafafa;
    min-height: 150px;
}

.variable-highlight-preview {
    background: #fef3c7;
    color: #92400e;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 600;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid;
}

.alert-info {
    background: #eff6ff;
    color: #1e40af;
    border-color: #bfdbfe;
}

.wizard-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 40px;
    padding-top: 25px;
    border-top: 1px solid #e5e7eb;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-secondary {
    background: #e5e7eb;
    color: #374151;
}

.btn-secondary:hover {
    background: #d1d5db;
}

.btn-primary {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
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
        padding: 20px;
    }
    
    .variables-grid {
        grid-template-columns: 1fr;
    }
    
    .wizard-navigation {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

    <!-- Wizard Container -->
    <div class="wizard-container">
        <!-- Wizard Header -->
        <div class="wizard-header">
        <div class="wizard-title">📧 กำหนดเนื้อหา Email</div>
        <div class="wizard-subtitle">ปรับแต่งหัวข้อและเนื้อหา email สำหรับการแจ้งเตือน</div>
            
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
            กำหนดเนื้อหา Email
            </div>

        <div class="alert alert-info">
            <strong>💡 คำแนะนำ:</strong> คลิกที่ตัวแปรด้านล่างเพื่อเพิ่มลงในเนื้อหา หรือพิมพ์ตัวแปรในรูปแบบ <code>&#123;&#123;variable_name&#125;&#125;</code>
                    </div>

        <!-- Variable Panel -->
        <div class="variable-panel">
            <div class="variable-panel-header">
                🏷️ ตัวแปรที่สามารถใช้ได้
                    </div>
            <div class="variables-grid" id="availableVariables">
                <!-- Variables will be populated by JavaScript -->
                        </div>
                    </div>

        <!-- Content Editor -->
        <div class="content-editor">
            <div class="tabs">
                <div class="tab active" data-tab="subject">📧 หัวข้อ Email</div>
                <div class="tab" data-tab="html">🌐 เนื้อหา HTML</div>
                <div class="tab" data-tab="text">📝 เนื้อหาข้อความ</div>
                        </div>

            <div class="tab-content active" id="subject-content">
                <div class="editor-group">
                    <label class="editor-label">หัวข้อ Email (Subject)</label>
                    <input type="text" class="editor-input" id="emailSubject" placeholder="กรอกหัวข้อ email...">
                    <div class="help-text">หัวข้อที่จะแสดงในอีเมล สามารถใช้ตัวแปรได้</div>
                    </div>
                </div>

            <div class="tab-content" id="html-content">
                <div class="editor-group">
                    <label class="editor-label">เนื้อหา HTML</label>
                    <textarea class="editor-input editor-textarea" id="emailHtmlBody" placeholder="กรอกเนื้อหา HTML..."></textarea>
                    <div class="help-text">เนื้อหาในรูปแบบ HTML สำหรับการแสดงผลที่สวยงาม</div>
                        </div>
                    </div>

            <div class="tab-content" id="text-content">
                <div class="editor-group">
                    <label class="editor-label">เนื้อหาข้อความธรรมดา</label>
                    <textarea class="editor-input editor-textarea" id="emailTextBody" placeholder="กรอกเนื้อหาข้อความธรรมดา..."></textarea>
                    <div class="help-text">เนื้อหาสำรองในรูปแบบข้อความธรรมดา</div>
                                </div>
                </div>
            </div>

        <!-- Preview -->
        <div class="preview-container">
            <div class="preview-header">
                👀 ตัวอย่างการแสดงผล
                    </div>
            <div class="preview-content" id="emailPreview">
                <div style="margin-bottom: 10px;">
                    <strong>หัวข้อ:</strong> <span id="previewSubject">-</span>
                </div>
                <div>
                    <strong>เนื้อหา:</strong>
                    <div id="previewBody" style="margin-top: 10px; padding: 10px; border: 1px solid #e5e7eb; border-radius: 4px; background: white;">-</div>
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
                    ขั้นตอนที่ 10 จาก 14
                </div>
                
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                ถัดไป (ตั้งค่าผู้รับ)
                    <i class="fas fa-arrow-right"></i>
                </button>
        </div>
    </div>
</div>

<script>
let currentTab = 'subject';
let availableVariables = [];

document.addEventListener('DOMContentLoaded', function() {
    console.log('Step 10 DOM loaded');
    initializeStep10();
});

function initializeStep10() {
    console.log('Initializing Step 10...');
    
    // Load available variables
    loadAvailableVariables();
    
    // Initialize tabs
    initializeTabs();
    
    // Load template data from step 9
    loadTemplateData();
    
    // Load saved data
    loadSavedData();
    
    // Initialize preview
    updatePreview();
    
    // Add input event listeners
    document.getElementById('emailSubject').addEventListener('input', updatePreview);
    document.getElementById('emailHtmlBody').addEventListener('input', updatePreview);
    document.getElementById('emailTextBody').addEventListener('input', updatePreview);
    
    console.log('Step 10 initialized');
}

function loadAvailableVariables() {
    // Load from previous steps
    const systemVariables = JSON.parse(sessionStorage.getItem('sql_alert_system_variables') || '[]');
    const statsVariables = JSON.parse(sessionStorage.getItem('sql_alert_statistics_variables') || '[]');
    
    availableVariables = [
        // System variables
        { name: 'record_count', description: 'จำนวนแถวข้อมูล' },
        { name: 'query_date', description: 'วันที่รัน query' },
        { name: 'query_time', description: 'เวลาที่รัน query' },
        { name: 'query_datetime', description: 'วันที่และเวลาที่รัน query' },
        { name: 'execution_time', description: 'เวลาการประมวลผล' },
        { name: 'data_size', description: 'ขนาดข้อมูล' },
        { name: 'total_columns', description: 'จำนวนคอลัมน์' },
        { name: 'database_name', description: 'ชื่อฐานข้อมูล' },
        { name: 'database_type', description: 'ประเภทฐานข้อมูล' },
        { name: 'current_date', description: 'วันที่ปัจจุบัน' },
        { name: 'current_datetime', description: 'วันที่และเวลาปัจจุบัน' }
    ];
    
    // Add statistics variables if available
    if (statsVariables.length > 0) {
        statsVariables.forEach(variable => {
            availableVariables.push({
                name: variable.name,
                description: variable.description || 'ตัวแปรจากสถิติ'
            });
        });
    }
    
    // Add export variables
    const exportSettings = JSON.parse(sessionStorage.getItem('sql_alert_export_settings') || '{}');
    if (exportSettings.excel && exportSettings.excel.enabled) {
        availableVariables.push(
            { name: 'export_filename', description: 'ชื่อไฟล์ส่งออก' },
            { name: 'export_size', description: 'ขนาดไฟล์ส่งออก' }
        );
    }
    
    renderVariables();
}

function initializeTabs() {
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            switchTab(tabName);
        });
    });
}

function switchTab(tabName) {
    // Update active tab
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    // Update active content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`${tabName}-content`).classList.add('active');
    
    currentTab = tabName;
}

function renderVariables() {
    const container = document.getElementById('availableVariables');
    container.innerHTML = '';
    
    availableVariables.forEach(variable => {
        const variableElement = document.createElement('div');
        variableElement.className = 'variable-tag';
        variableElement.onclick = () => insertVariable(variable.name);
        
        variableElement.innerHTML = `
            <div class="variable-name">&#123;&#123;${variable.name}&#125;&#125;</div>
            <div class="variable-description">${variable.description || 'ตัวแปรจากข้อมูล'}</div>
        `;
        
        container.appendChild(variableElement);
    });
}

function insertVariable(variableName) {
    let activeInput;
    
    if (currentTab === 'subject') {
        activeInput = document.getElementById('emailSubject');
    } else if (currentTab === 'html') {
        activeInput = document.getElementById('emailHtmlBody');
    } else if (currentTab === 'text') {
        activeInput = document.getElementById('emailTextBody');
    }
    
    if (activeInput) {
        const start = activeInput.selectionStart || 0;
        const end = activeInput.selectionEnd || 0;
        const value = activeInput.value;
        
        const variableText = `&#123;&#123;${variableName}&#125;&#125;`;
        const newValue = value.substring(0, start) + variableText + value.substring(end);
        
        activeInput.value = newValue;
        
        // Update cursor position
        const newCursorPosition = start + variableText.length;
        activeInput.setSelectionRange(newCursorPosition, newCursorPosition);
        
        // Update preview
        updatePreview();
        
        // Focus back to input
        activeInput.focus();
    }
}

function updatePreview() {
    const subject = document.getElementById('emailSubject').value;
    const htmlBody = document.getElementById('emailHtmlBody').value;
    const textBody = document.getElementById('emailTextBody').value;
    
    // Mock data for preview
    const mockData = {
        'record_count': '25',
        'query_date': '2024-01-15',
        'query_time': '14:30:00',
        'query_datetime': '2024-01-15 14:30:00',
        'execution_time': '0.25s',
        'data_size': '1.2MB',
        'total_columns': '8',
        'database_name': 'example_db',
        'database_type': 'MySQL',
        'current_date': new Date().toISOString().split('T')[0],
        'current_datetime': new Date().toISOString().replace('T', ' ').slice(0, 19),
        'export_filename': 'report_2024-01-15.xlsx',
        'export_size': '45KB'
    };
    
    let previewSubject = subject;
    let previewBody = htmlBody || textBody;
    
    // Replace variables with mock data
    Object.keys(mockData).forEach(key => {
        const regex = new RegExp(`&#123;&#123;${key}&#125;&#125;`, 'g');
        previewSubject = previewSubject.replace(regex, mockData[key]);
        previewBody = previewBody.replace(regex, mockData[key]);
    });
    
    // Highlight remaining variables
    previewBody = previewBody.replace(/&#123;&#123;([^}]+)&#125;&#125;/g, '<span class="variable-highlight-preview">$1</span>');
    
    // Update preview
    document.getElementById('previewSubject').textContent = previewSubject || 'ไม่ได้กำหนดหัวข้อ';
    document.getElementById('previewBody').innerHTML = previewBody || 'ไม่ได้กำหนดเนื้อหา';
    
    // Save data
    saveData();
}

function loadTemplateData() {
    const templateData = sessionStorage.getItem('sql_alert_email_template');
    if (templateData) {
        try {
            const data = JSON.parse(templateData);
            
            if (data.selectedTemplate && data.selectedTemplate !== 'custom') {
                // Load predefined template
                loadPredefinedTemplate(data.selectedTemplate);
            } else if (data.customTemplateData) {
                // Load custom template
                document.getElementById('emailSubject').value = data.customTemplateData.subject || '';
                document.getElementById('emailHtmlBody').value = data.customTemplateData.htmlBody || '';
                document.getElementById('emailTextBody').value = data.customTemplateData.textBody || '';
            }
        } catch (error) {
            console.error('Error loading template data:', error);
        }
    }
}

function loadPredefinedTemplate(templateType) {
    const templates = {
        alert: {
            subject: '🚨 แจ้งเตือนระบบ - &#123;&#123;query_date&#125;&#125;',
            htmlBody: '<h2>🚨 แจ้งเตือนระบบ</h2><p>ระบบพบการแจ้งเตือนจำนวน <strong>&#123;&#123;record_count&#125;&#125;</strong> รายการ</p><p>วันที่: &#123;&#123;query_date&#125;&#125;</p><p>เวลา: &#123;&#123;query_time&#125;&#125;</p>',
            textBody: 'แจ้งเตือนระบบ\\n\\nระบบพบการแจ้งเตือนจำนวน &#123;&#123;record_count&#125;&#125; รายการ\\nวันที่: &#123;&#123;query_date&#125;&#125;\\nเวลา: &#123;&#123;query_time&#125;&#125;'
        },
        report: {
            subject: '📊 รายงานข้อมูล - &#123;&#123;query_date&#125;&#125;',
            htmlBody: '<h2>📊 รายงานข้อมูล</h2><p>รายงานประจำวันที่ <strong>&#123;&#123;query_date&#125;&#125;</strong></p><p>พบข้อมูลทั้งหมด: &#123;&#123;record_count&#125;&#125; รายการ</p><p>เวลาการประมวลผล: &#123;&#123;execution_time&#125;&#125;</p>',
            textBody: 'รายงานข้อมูล\\n\\nรายงานประจำวันที่ &#123;&#123;query_date&#125;&#125;\\nพบข้อมูลทั้งหมด: &#123;&#123;record_count&#125;&#125; รายการ\\nเวลาการประมวลผล: &#123;&#123;execution_time&#125;&#125;'
        },
        summary: {
            subject: '📋 สรุปรายวัน - &#123;&#123;query_date&#125;&#125;',
            htmlBody: '<h2>📋 สรุปรายวัน</h2><p>สรุปประจำวันที่ &#123;&#123;query_date&#125;&#125;</p><p>ข้อมูลใหม่: &#123;&#123;record_count&#125;&#125; รายการ</p>',
            textBody: 'สรุปรายวัน\\n\\nสรุปประจำวันที่ &#123;&#123;query_date&#125;&#125;\\nข้อมูลใหม่: &#123;&#123;record_count&#125;&#125; รายการ'
        },
        simple: {
            subject: 'การแจ้งเตือน - &#123;&#123;query_date&#125;&#125;',
            htmlBody: '<p>การแจ้งเตือน</p><p>ข้อมูลจำนวน: &#123;&#123;record_count&#125;&#125; รายการ</p><p>วันที่: &#123;&#123;query_date&#125;&#125;</p>',
            textBody: 'การแจ้งเตือน\\n\\nข้อมูลจำนวน: &#123;&#123;record_count&#125;&#125; รายการ\\nวันที่: &#123;&#123;query_date&#125;&#125;'
        }
    };
    
    if (templates[templateType]) {
        const template = templates[templateType];
        document.getElementById('emailSubject').value = template.subject;
        document.getElementById('emailHtmlBody').value = template.htmlBody;
        document.getElementById('emailTextBody').value = template.textBody;
    }
}

function saveData() {
    const data = {
        subject: document.getElementById('emailSubject').value,
        htmlBody: document.getElementById('emailHtmlBody').value,
        textBody: document.getElementById('emailTextBody').value,
        lastUpdated: new Date().toISOString()
    };
    
    sessionStorage.setItem('sql_alert_email_content', JSON.stringify(data));
    console.log('Email content saved:', data);
}

function loadSavedData() {
    const savedData = sessionStorage.getItem('sql_alert_email_content');
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            document.getElementById('emailSubject').value = data.subject || '';
            document.getElementById('emailHtmlBody').value = data.htmlBody || '';
            document.getElementById('emailTextBody').value = data.textBody || '';
        } catch (error) {
            console.error('Error loading saved data:', error);
        }
    }
}

function previousStep() {
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.previousStep();
    } else {
        window.location.href = '/admin/sql-alerts/create?step=9';
    }
}

function nextStep() {
    const subject = document.getElementById('emailSubject').value;
    const htmlBody = document.getElementById('emailHtmlBody').value;
    const textBody = document.getElementById('emailTextBody').value;
    
    if (!subject.trim()) {
        alert('กรุณากรอกหัวข้อ email');
        return;
    }
    
    if (!htmlBody.trim() && !textBody.trim()) {
        alert('กรุณากรอกเนื้อหา email อย่างน้อย 1 แบบ');
        return;
    }
    
    saveData();
    sessionStorage.setItem('sql_alert_step', '11');
    
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.nextStep();
    } else {
        window.location.href = '/admin/sql-alerts/create?step=11';
    }
}

// Export functions
window.initializeStep10 = initializeStep10;
window.initializeCurrentStep = initializeStep10;
window.insertVariable = insertVariable;
window.switchTab = switchTab;
window.previousStep = previousStep;
window.nextStep = nextStep;

console.log('Step 10 script loaded');
</script>