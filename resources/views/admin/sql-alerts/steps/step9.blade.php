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

.template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.template-card {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    user-select: none;
}

.template-card:hover {
    border-color: #4f46e5;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.template-card.active {
    border-color: #4f46e5;
    background: #f8fafc;
    box-shadow: 0 4px 20px rgba(79, 70, 229, 0.2);
}

.template-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.template-icon {
    font-size: 1.5rem;
}

.template-title {
    font-size: 1.1rem;
    font-weight: 600;
}

.template-body {
    padding: 20px;
}

.template-description {
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 15px;
}

.template-features {
    list-style: none;
    padding: 0;
    margin: 0;
}

.template-features li {
    padding: 5px 0;
    color: #059669;
    display: flex;
    align-items: center;
    gap: 8px;
}

.template-features li::before {
    content: "✓";
    color: #059669;
    font-weight: bold;
}

.template-preview {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
    margin-top: 15px;
    font-size: 0.9rem;
}

.template-preview-header {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.email-field {
    display: flex;
    margin-bottom: 10px;
}

.email-field-label {
    font-weight: 600;
    color: #374151;
    min-width: 100px;
}

.email-field-value {
    color: #6b7280;
    flex: 1;
}

.variable-highlight {
    background: #fef3c7;
    color: #92400e;
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: 600;
}

.custom-template-editor {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 25px;
    margin-top: 20px;
    display: none;
}

.editor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.editor-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
}

.editor-help {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.editor-group {
    margin-bottom: 20px;
}

.editor-label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.editor-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
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

.variable-toolbar {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.variable-btn {
    background: #e0e7ff;
    color: #3730a3;
    border: 1px solid #c7d2fe;
    padding: 5px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.variable-btn:hover {
    background: #c7d2fe;
    color: #312e81;
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
    padding: 15px;
    background: #fafafa;
}

.wizard-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 30px 40px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
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

.wizard-progress {
    text-align: center;
    color: #6b7280;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .wizard-content {
        padding: 20px;
    }
    
    .template-grid {
        grid-template-columns: 1fr;
    }
    
    .wizard-navigation {
        padding: 20px;
        flex-direction: column;
        gap: 15px;
    }
}
</style>

    <div class="wizard-container">
        <div class="wizard-header">
        <div class="wizard-title">สร้างการแจ้งเตือนแบบ SQL</div>
        <div class="wizard-subtitle">เลือก Template สำหรับ Email</div>
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

        <div class="wizard-content">
            <div class="section-title">
            <div class="section-icon">📧</div>
            เลือก Template สำหรับ Email
                </div>

                <div class="template-grid">
            <!-- Template 1: Alert -->
            <div class="template-card" data-template="alert" onclick="selectTemplate('alert')">
                        <div class="template-header">
                    <div class="template-icon">🚨</div>
                    <div class="template-title">แจ้งเตือนระบบ</div>
                            </div>
                <div class="template-body">
                    <div class="template-description">
                        รูปแบบการแจ้งเตือนที่เน้นความเร่งด่วน เหมาะสำหรับการแจ้งเตือนปัญหาหรือเหตุการณ์สำคัญ
                            </div>
                    <ul class="template-features">
                        <li>หัวข้อเตือนภัยที่ชัดเจน</li>
                        <li>สีแดงเพื่อสื่อความเร่งด่วน</li>
                        <li>แสดงจำนวนและเวลาที่เกิดขึ้น</li>
                        <li>ข้อมูลสถิติโดยย่อ</li>
                    </ul>
                    <div class="template-preview">
                        <div class="template-preview-header">🔍 ตัวอย่าง</div>
                        <div class="email-field">
                            <div class="email-field-label">หัวข้อ:</div>
                            <div class="email-field-value">🚨 แจ้งเตือนระบบ - &#123;&#123;query_date&#125;&#125;</div>
                        </div>
                        <div class="email-field">
                            <div class="email-field-label">เนื้อหา:</div>
                            <div class="email-field-value">พบการแจ้งเตือน &#123;&#123;record_count&#125;&#125; รายการ...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template 2: Report -->
            <div class="template-card" data-template="report" onclick="selectTemplate('report')">
                <div class="template-header">
                    <div class="template-icon">📊</div>
                    <div class="template-title">รายงานข้อมูล</div>
                </div>
                <div class="template-body">
                        <div class="template-description">
                        รูปแบบรายงานที่เป็นมืออาชีพ เหมาะสำหรับการแสดงผลข้อมูลสถิติและการวิเคราะห์
                        </div>
                        <ul class="template-features">
                        <li>การแสดงผลแบบกราฟิก</li>
                        <li>ข้อมูลสถิติที่ครบถ้วน</li>
                        <li>การจัดรูปแบบที่เป็นมืออาชีพ</li>
                        <li>สรุปผลการวิเคราะห์</li>
                        </ul>
                    <div class="template-preview">
                        <div class="template-preview-header">🔍 ตัวอย่าง</div>
                        <div class="email-field">
                            <div class="email-field-label">หัวข้อ:</div>
                            <div class="email-field-value">📊 รายงานข้อมูล - &#123;&#123;query_date&#125;&#125;</div>
                    </div>
                        <div class="email-field">
                            <div class="email-field-label">เนื้อหา:</div>
                            <div class="email-field-value">รายงานประจำวันที่ &#123;&#123;query_date&#125;&#125; พบข้อมูล &#123;&#123;record_count&#125;&#125; รายการ...</div>
                        </div>
                            </div>
                            </div>
                        </div>

            <!-- Template 3: Summary -->
            <div class="template-card" data-template="summary" onclick="selectTemplate('summary')">
                <div class="template-header">
                    <div class="template-icon">📋</div>
                    <div class="template-title">สรุปรายวัน</div>
                </div>
                <div class="template-body">
                        <div class="template-description">
                        รูปแบบสรุปที่เรียบง่าย เหมาะสำหรับการรายงานประจำวันหรือข้อมูลที่ต้องการความกระชับ
                        </div>
                        <ul class="template-features">
                        <li>การออกแบบที่เรียบง่าย</li>
                        <li>ข้อมูลสรุปที่กระชับ</li>
                        <li>ตารางข้อมูลที่เป็นระเบียบ</li>
                        <li>เหมาะสำหรับการใช้งานประจำ</li>
                        </ul>
                    <div class="template-preview">
                        <div class="template-preview-header">🔍 ตัวอย่าง</div>
                        <div class="email-field">
                            <div class="email-field-label">หัวข้อ:</div>
                            <div class="email-field-value">📋 สรุปรายวัน - &#123;&#123;query_date&#125;&#125;</div>
                    </div>
                        <div class="email-field">
                            <div class="email-field-label">เนื้อหา:</div>
                            <div class="email-field-value">สรุปประจำวันที่ &#123;&#123;query_date&#125;&#125; พบข้อมูล &#123;&#123;record_count&#125;&#125; รายการ...</div>
                        </div>
                            </div>
                            </div>
                        </div>

            <!-- Template 4: Simple -->
            <div class="template-card" data-template="simple" onclick="selectTemplate('simple')">
                <div class="template-header">
                    <div class="template-icon">✉️</div>
                    <div class="template-title">แบบเรียบง่าย</div>
                </div>
                <div class="template-body">
                        <div class="template-description">
                        รูปแบบพื้นฐานที่เรียบง่าย เหมาะสำหรับการแจ้งเตือนทั่วไปหรือข้อมูลที่ไม่ซับซ้อน
                        </div>
                        <ul class="template-features">
                        <li>การออกแบบที่เรียบง่าย</li>
                        <li>ข้อความที่ชัดเจน</li>
                        <li>ไม่มีการตกแต่งมาก</li>
                        <li>เหมาะสำหรับการใช้งานทั่วไป</li>
                        </ul>
                    <div class="template-preview">
                        <div class="template-preview-header">🔍 ตัวอย่าง</div>
                        <div class="email-field">
                            <div class="email-field-label">หัวข้อ:</div>
                            <div class="email-field-value">การแจ้งเตือน - &#123;&#123;query_date&#125;&#125;</div>
                    </div>
                        <div class="email-field">
                            <div class="email-field-label">เนื้อหา:</div>
                            <div class="email-field-value">การแจ้งเตือน ข้อมูลจำนวน &#123;&#123;record_count&#125;&#125; รายการ...</div>
                        </div>
                            </div>
                            </div>
                        </div>

            <!-- Template 5: Custom -->
            <div class="template-card" data-template="custom" onclick="selectTemplate('custom')">
                <div class="template-header">
                    <div class="template-icon">🎨</div>
                    <div class="template-title">กำหนดเอง</div>
                </div>
                <div class="template-body">
                        <div class="template-description">
                        สร้าง template ใหม่ตามความต้องการ สามารถปรับแต่งทุกส่วนได้ตามต้องการ
                        </div>
                        <ul class="template-features">
                            <li>ปรับแต่งได้ทุกส่วน</li>
                            <li>ใช้ตัวแปรได้อย่างอิสระ</li>
                        <li>ควบคุมการแสดงผลได้เต็มที่</li>
                            <li>เหมาะสำหรับความต้องการพิเศษ</li>
                        </ul>
                    <div class="template-preview">
                        <div class="template-preview-header">🔍 ตัวอย่าง</div>
                        <div class="email-field">
                            <div class="email-field-label">หัวข้อ:</div>
                            <div class="email-field-value">กำหนดเอง...</div>
                    </div>
                        <div class="email-field">
                            <div class="email-field-label">เนื้อหา:</div>
                            <div class="email-field-value">สร้างเนื้อหาใหม่ตามต้องการ...</div>
                </div>
            </div>
                </div>
            </div>
                    </div>

        <!-- Custom Template Editor -->
        <div class="custom-template-editor" id="customTemplateEditor">
            <div class="editor-header">
                <div class="editor-title">🎨 สร้าง Template แบบกำหนดเอง</div>
                    </div>
            <div class="editor-help">
                ใช้ตัวแปรในรูปแบบ &#123;&#123;variable_name&#125;&#125; เพื่อแสดงข้อมูลจาก SQL Query
                </div>

            <div class="variable-toolbar">
                <div class="variable-btn" onclick="insertVariable('record_count')">&#123;&#123;record_count&#125;&#125;</div>
                <div class="variable-btn" onclick="insertVariable('query_date')">&#123;&#123;query_date&#125;&#125;</div>
                <div class="variable-btn" onclick="insertVariable('query_time')">&#123;&#123;query_time&#125;&#125;</div>
                <div class="variable-btn" onclick="insertVariable('execution_time')">&#123;&#123;execution_time&#125;&#125;</div>
                <div class="variable-btn" onclick="insertVariable('data_size')">&#123;&#123;data_size&#125;&#125;</div>
                <div class="variable-btn" onclick="insertVariable('export_filename')">&#123;&#123;export_filename&#125;&#125;</div>
                <div class="variable-btn" onclick="insertVariable('export_size')">&#123;&#123;export_size&#125;&#125;</div>
                    </div>

            <div class="editor-group">
                <label class="editor-label">หัวข้อ Email (Subject)</label>
                <input type="text" class="editor-input" id="customSubject" placeholder="เช่น: การแจ้งเตือน - &#123;&#123;query_date&#125;&#125;">
            </div>

            <div class="editor-group">
                <label class="editor-label">เนื้อหา Email (HTML)</label>
                <textarea class="editor-input editor-textarea" id="customHtmlBody" placeholder="เนื้อหา HTML ของอีเมล..."></textarea>
                </div>

            <div class="editor-group">
                <label class="editor-label">เนื้อหา Email (ข้อความธรรมดา)</label>
                <textarea class="editor-input editor-textarea" id="customTextBody" placeholder="เนื้อหาข้อความธรรมดาของอีเมล..."></textarea>
                    </div>

            <div class="preview-container">
                <div class="preview-header">👀 ตัวอย่าง</div>
                <div class="preview-content" id="templatePreview">
                                <div class="email-field">
                        <div class="email-field-label">หัวข้อ:</div>
                        <div class="email-field-value" id="previewSubject">-</div>
                                </div>
                                <div class="email-field">
                        <div class="email-field-label">เนื้อหา:</div>
                        <div class="email-field-value" id="previewBody">-</div>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>

            <div class="wizard-navigation">
                <button type="button" class="btn btn-secondary" onclick="previousStep()">
            ← ย้อนกลับ
                </button>
        <div class="wizard-progress">
                    ขั้นตอนที่ 9 จาก 14
                </div>
                <button type="button" class="btn btn-primary" onclick="nextStep()">
            ถัดไป →
                </button>
    </div>
</div>

<script>
let selectedTemplate = null;
let customTemplateData = null;

// Templates data
const templates = {
    alert: {
        name: 'แจ้งเตือนระบบ',
        subject: '🚨 แจ้งเตือนระบบ - &#123;&#123;query_date&#125;&#125;',
        htmlBody: 'ระบบพบการแจ้งเตือนจำนวน &#123;&#123;record_count&#125;&#125; รายการ ณ วันที่ &#123;&#123;query_date&#125;&#125; เวลา &#123;&#123;query_time&#125;&#125;',
        textBody: 'แจ้งเตือนระบบ - &#123;&#123;query_date&#125;&#125; ระบบพบการแจ้งเตือนจำนวน &#123;&#123;record_count&#125;&#125; รายการ'
    },
    report: {
        name: 'รายงานข้อมูล',
        subject: '📊 รายงานข้อมูล - &#123;&#123;query_date&#125;&#125;',
        htmlBody: 'รายงานประจำวันที่ &#123;&#123;query_date&#125;&#125; พบข้อมูล &#123;&#123;record_count&#125;&#125; รายการ',
        textBody: 'รายงานข้อมูล - &#123;&#123;query_date&#125;&#125; สรุปข้อมูล: รายการทั้งหมด &#123;&#123;record_count&#125;&#125;'
    },
    summary: {
        name: 'สรุปรายวัน',
        subject: '📋 สรุปรายวัน - &#123;&#123;query_date&#125;&#125;',
        htmlBody: 'สรุปประจำวันที่ &#123;&#123;query_date&#125;&#125; พบข้อมูล &#123;&#123;record_count&#125;&#125; รายการใหม่',
        textBody: 'สรุปประจำวัน - &#123;&#123;query_date&#125;&#125; พบข้อมูล &#123;&#123;record_count&#125;&#125; รายการใหม่'
    },
    simple: {
        name: 'แบบเรียบง่าย',
        subject: 'การแจ้งเตือน - &#123;&#123;query_date&#125;&#125;',
        htmlBody: 'ข้อมูลจำนวน &#123;&#123;record_count&#125;&#125; รายการ',
        textBody: 'การแจ้งเตือน - &#123;&#123;query_date&#125;&#125; ข้อมูลจำนวน &#123;&#123;record_count&#125;&#125; รายการ'
    }
};

// DOM ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Step 9 DOM loaded');
    
    // Add click events to template cards
    const templateCards = document.querySelectorAll('.template-card');
    console.log('Found template cards:', templateCards.length);
    
    templateCards.forEach(card => {
        card.addEventListener('click', function() {
            const templateId = this.getAttribute('data-template');
            console.log('Template clicked:', templateId);
            selectTemplate(templateId);
        });
    });
    
    // Add input events to custom template inputs
    const customSubject = document.getElementById('customSubject');
    const customHtmlBody = document.getElementById('customHtmlBody');
    const customTextBody = document.getElementById('customTextBody');
    
    if (customSubject) {
        customSubject.addEventListener('input', updateCustomPreview);
        console.log('Custom subject input listener added');
    }
    if (customHtmlBody) {
        customHtmlBody.addEventListener('input', updateCustomPreview);
        console.log('Custom HTML body input listener added');
    }
    if (customTextBody) {
        customTextBody.addEventListener('input', updateCustomPreview);
        console.log('Custom text body input listener added');
    }
    
    // Load saved data
    loadSavedData();
    
    console.log('Step 9 initialized');
});

function selectTemplate(templateId) {
    console.log('selectTemplate called with:', templateId);
    
    // Remove active class from all cards
    const templateCards = document.querySelectorAll('.template-card');
    console.log('Removing active class from', templateCards.length, 'cards');
    
    templateCards.forEach(card => {
        card.classList.remove('active');
    });
    
    // Add active class to selected card
    const selectedCard = document.querySelector('[data-template="' + templateId + '"]');
    console.log('Selected card:', selectedCard);
    
    if (selectedCard) {
        selectedCard.classList.add('active');
        console.log('Added active class to card:', templateId);
    } else {
        console.error('Card not found for template:', templateId);
    }
    
    selectedTemplate = templateId;
    
    // Show/hide custom editor
    const customEditor = document.getElementById('customTemplateEditor');
    if (templateId === 'custom') {
        customEditor.style.display = 'block';
        console.log('Showing custom editor');
        updateCustomPreview();
    } else {
        customEditor.style.display = 'none';
        console.log('Hiding custom editor');
        updatePreview(templateId);
    }
    
    // Save selection
    saveData();
    
    console.log('Template selected successfully:', templateId);
}

function updatePreview(templateId) {
    if (!templates[templateId]) return;
    
    const template = templates[templateId];
    const previewSubject = document.getElementById('previewSubject');
    const previewBody = document.getElementById('previewBody');
    
    if (previewSubject) {
        previewSubject.textContent = template.subject;
    }
    if (previewBody) {
        previewBody.innerHTML = template.htmlBody.substring(0, 200) + '...';
    }
}

function updateCustomPreview() {
    const subject = document.getElementById('customSubject').value;
    const htmlBody = document.getElementById('customHtmlBody').value;
    const textBody = document.getElementById('customTextBody').value;
    
    customTemplateData = {
        subject: subject,
        htmlBody: htmlBody,
        textBody: textBody
    };
    
    const previewSubject = document.getElementById('previewSubject');
    const previewBody = document.getElementById('previewBody');
    
    if (previewSubject) {
        previewSubject.textContent = subject || 'กรุณาใส่หัวข้อ...';
    }
    if (previewBody) {
        previewBody.innerHTML = htmlBody ? htmlBody.substring(0, 200) + '...' : 'กรุณาใส่เนื้อหา...';
    }
    
    // Save data
    saveData();
}

function insertVariable(variableName) {
    const activeElement = document.activeElement;
    
    if (activeElement && (activeElement.id === 'customSubject' || activeElement.id === 'customHtmlBody' || activeElement.id === 'customTextBody')) {
        const start = activeElement.selectionStart;
        const end = activeElement.selectionEnd;
        const value = activeElement.value;
        
        const newValue = value.substring(0, start) + '{{' + variableName + '}}' + value.substring(end);
        activeElement.value = newValue;
        
        // Update cursor position
        const newCursorPosition = start + ('{{' + variableName + '}}').length;
        activeElement.setSelectionRange(newCursorPosition, newCursorPosition);
        
        // Update preview
        updateCustomPreview();
    } else {
        alert('ตัวแปร {{' + variableName + '}} จะถูกใส่ในตำแหน่งเคอร์เซอร์ในขั้นตอนถัดไป');
    }
}

function saveData() {
    const data = {
        selectedTemplate: selectedTemplate,
        customTemplateData: customTemplateData
    };
    
    sessionStorage.setItem('sql_alert_email_template', JSON.stringify(data));
    console.log('Email template data saved:', data);
}

function loadSavedData() {
    const savedData = sessionStorage.getItem('sql_alert_email_template');
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            
            if (data.selectedTemplate) {
                selectTemplate(data.selectedTemplate);
            }
            
            if (data.customTemplateData) {
                customTemplateData = data.customTemplateData;
                const customSubject = document.getElementById('customSubject');
                const customHtmlBody = document.getElementById('customHtmlBody');
                const customTextBody = document.getElementById('customTextBody');
                
                if (customSubject) customSubject.value = customTemplateData.subject || '';
                if (customHtmlBody) customHtmlBody.value = customTemplateData.htmlBody || '';
                if (customTextBody) customTextBody.value = customTemplateData.textBody || '';
                
                updateCustomPreview();
            }
        } catch (error) {
            console.error('Error loading saved data:', error);
        }
    }
}

function previousStep() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('step', '8');
    window.location.href = currentUrl.toString();
}

function nextStep() {
    if (!selectedTemplate) {
        alert('กรุณาเลือก Template สำหรับ Email');
        return;
    }
    
    if (selectedTemplate === 'custom') {
        if (!customTemplateData || !customTemplateData.subject || !customTemplateData.htmlBody) {
            alert('กรุณากรอกข้อมูล Template แบบกำหนดเอง');
        return;
    }
    }
    
    saveData();
    
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('step', '10');
    window.location.href = currentUrl.toString();
}

// Export functions to window
window.selectTemplate = selectTemplate;
window.insertVariable = insertVariable;
window.previousStep = previousStep;
window.nextStep = nextStep;
window.initializeCurrentStep = function() {
    console.log('Step 9 initialized');
};

console.log('Step 9 script loaded');
</script>