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
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.export-toggle.active {
    border-color: #4f46e5;
    background: #f0f9ff;
}

.toggle-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.toggle-icon {
    width: 40px;
    height: 40px;
    background: #4f46e5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.toggle-info {
    flex: 1;
}

.toggle-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 5px;
}

.toggle-description {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.4;
}

.toggle-switch {
    width: 60px;
    height: 30px;
    background: #e5e7eb;
    border-radius: 15px;
    position: relative;
    transition: all 0.3s ease;
}

.toggle-switch.active {
    background: #4f46e5;
}

.toggle-switch::before {
    content: '';
    position: absolute;
    width: 26px;
    height: 26px;
    background: white;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: all 0.3s ease;
}

.toggle-switch.active::before {
    transform: translateX(30px);
}

.export-settings {
    display: none;
    background: #f9fafb;
    border-radius: 8px;
    padding: 20px;
    margin-top: 15px;
}

.export-settings.show {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
    display: block;
    font-size: 0.9rem;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-text {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 5px;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 10px;
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
    font-size: 0.9rem;
    color: #374151;
    cursor: pointer;
}

.preview-section {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.preview-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    font-weight: 600;
    color: #374151;
}

.preview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #4f46e5;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.8rem;
    color: #6b7280;
}

.preview-sample {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    overflow-x: auto;
}

.btn {
    padding: 12px 20px;
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
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
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

.btn-lg {
    padding: 16px 24px;
    font-size: 1.1rem;
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

    <!-- Wizard Container -->
    <div class="wizard-container">
        <!-- Wizard Header -->
        <div class="wizard-header">
        <div class="wizard-title">📊 ตัวเลือกการส่งออกข้อมูล</div>
        <div class="wizard-subtitle">กำหนดรูปแบบการส่งออกข้อมูลและไฟล์แนบ</div>
            
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

        <!-- Export Options -->
            <div class="export-options">
                <div class="export-header">
                <i class="fas fa-download"></i>
                <div class="export-title">ตัวเลือกการส่งออกข้อมูล</div>
                </div>

            <!-- Excel Export -->
            <div class="export-toggle" id="excelToggle" onclick="toggleExport('excel')">
                <div class="toggle-header">
                        <div class="toggle-icon">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <div class="toggle-info">
                        <div class="toggle-title">Excel (XLSX)</div>
                        <div class="toggle-description">ส่งออกข้อมูลเป็นไฟล์ Excel พร้อมการจัดรูปแบบ</div>
                        </div>
                    <div class="toggle-switch" id="excelSwitch"></div>
                </div>

                <div class="export-settings" id="excelSettings">
                    <div class="form-group">
                        <label class="form-label">ชื่อไฟล์</label>
                        <input type="text" class="form-control" id="excelFilename" value="sql_alert_data" placeholder="ชื่อไฟล์">
                            </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">ชื่อ Worksheet</label>
                            <input type="text" class="form-control" id="excelWorksheet" value="Data" placeholder="ชื่อ Worksheet">
                                </div>
                        <div class="form-group">
                            <label class="form-label">จำกัดแถว</label>
                            <select class="form-control form-select" id="excelLimit">
                                <option value="0">ไม่จำกัด</option>
                                <option value="100">100 แถว</option>
                                <option value="500">500 แถว</option>
                                <option value="1000" selected>1,000 แถว</option>
                                <option value="5000">5,000 แถว</option>
                            </select>
                        </div>
                            </div>

                    <div class="form-group">
                        <label class="form-label">ตัวเลือกเพิ่มเติม</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="excelHeaders" checked>
                                <label class="checkbox-label" for="excelHeaders">รวม Header</label>
                                </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="excelAutofit" checked>
                                <label class="checkbox-label" for="excelAutofit">Auto-fit คอลัมน์</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="excelFilter">
                                <label class="checkbox-label" for="excelFilter">เปิด Auto Filter</label>
                                </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="excelFormat" checked>
                                <label class="checkbox-label" for="excelFormat">จัดรูปแบบข้อมูล</label>
                            </div>
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
                                <div class="form-text">&#123;&#123;attachment_filename&#125;&#125; - ชื่อไฟล์ที่แนบ</div>
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control" id="sizeVar" value="attachment_size" placeholder="ชื่อตัวแปรขนาด">
                                <div class="form-text">&#123;&#123;attachment_size&#125;&#125; - ขนาดไฟล์</div>
                            </div>
                        </div>
                    </div>
                            </div>
                        </div>

            <!-- CSV Export -->
            <div class="export-toggle" id="csvToggle" onclick="toggleExport('csv')">
                <div class="toggle-header">
                    <div class="toggle-icon">
                        <i class="fas fa-file-csv"></i>
                        </div>
                    <div class="toggle-info">
                        <div class="toggle-title">CSV</div>
                        <div class="toggle-description">ส่งออกข้อมูลเป็นไฟล์ CSV สำหรับการประมวลผลข้อมูล</div>
                    </div>
                    <div class="toggle-switch" id="csvSwitch"></div>
                </div>
                
                <div class="export-settings" id="csvSettings">
                    <div class="form-group">
                        <label class="form-label">ชื่อไฟล์</label>
                        <input type="text" class="form-control" id="csvFilename" value="sql_alert_data" placeholder="ชื่อไฟล์">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">ตัวคั่น</label>
                            <select class="form-control form-select" id="csvSeparator">
                                <option value=",">Comma (,)</option>
                                <option value=";">Semicolon (;)</option>
                                <option value="|">Pipe (|)</option>
                                <option value="tab">Tab</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">การเข้ารหัส</label>
                            <select class="form-control form-select" id="csvEncoding">
                                <option value="utf8">UTF-8</option>
                                <option value="utf8bom">UTF-8 with BOM</option>
                                <option value="windows1252">Windows-1252</option>
                                <option value="tis620">TIS-620</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ตัวเลือกเพิ่มเติม</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="csvHeaders" checked>
                                <label class="checkbox-label" for="csvHeaders">รวม Header</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="csvQuotes" checked>
                                <label class="checkbox-label" for="csvQuotes">ใส่ Quote รอบข้อความ</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="csvEscape">
                                <label class="checkbox-label" for="csvEscape">Escape อักขระพิเศษ</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="csvDateFormat" checked>
                                <label class="checkbox-label" for="csvDateFormat">จัดรูปแบบวันที่</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- JSON Export -->
            <div class="export-toggle" id="jsonToggle" onclick="toggleExport('json')">
                <div class="toggle-header">
                    <div class="toggle-icon">
                        <i class="fas fa-file-code"></i>
                    </div>
                    <div class="toggle-info">
                        <div class="toggle-title">JSON</div>
                        <div class="toggle-description">ส่งออกข้อมูลเป็นไฟล์ JSON สำหรับการใช้งาน API</div>
                    </div>
                    <div class="toggle-switch" id="jsonSwitch"></div>
                </div>

                <div class="export-settings" id="jsonSettings">
                    <div class="form-group">
                        <label class="form-label">ชื่อไฟล์</label>
                        <input type="text" class="form-control" id="jsonFilename" value="sql_alert_data" placeholder="ชื่อไฟล์">
                        </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">รูปแบบ JSON</label>
                            <select class="form-control form-select" id="jsonFormat">
                                <option value="array">Array of Objects</option>
                                <option value="object">Single Object</option>
                                <option value="nested">Nested Structure</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">การเข้ารหัส</label>
                            <select class="form-control form-select" id="jsonEncoding">
                                <option value="utf8">UTF-8</option>
                                <option value="unicode">Unicode Escape</option>
                            </select>
                        </div>
                        </div>

                    <div class="form-group">
                        <label class="form-label">ตัวเลือกเพิ่มเติม</label>
                        <div class="checkbox-group">
                        <div class="checkbox-item">
                                <input type="checkbox" id="jsonPretty" checked>
                                <label class="checkbox-label" for="jsonPretty">Pretty Print</label>
                        </div>
                        <div class="checkbox-item">
                                <input type="checkbox" id="jsonMetadata">
                                <label class="checkbox-label" for="jsonMetadata">รวม Metadata</label>
                        </div>
                        <div class="checkbox-item">
                                <input type="checkbox" id="jsonCompress">
                                <label class="checkbox-label" for="jsonCompress">บีบอัดไฟล์</label>
                        </div>
                        <div class="checkbox-item">
                                <input type="checkbox" id="jsonDateISO" checked>
                                <label class="checkbox-label" for="jsonDateISO">วันที่ในรูปแบบ ISO</label>
                            </div>
                        </div>
                    </div>
                        </div>
                    </div>

            <!-- PDF Export -->
            <div class="export-toggle" id="pdfToggle" onclick="toggleExport('pdf')">
                <div class="toggle-header">
                    <div class="toggle-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="toggle-info">
                        <div class="toggle-title">PDF</div>
                        <div class="toggle-description">ส่งออกข้อมูลเป็นไฟล์ PDF สำหรับการพิมพ์และการนำเสนอ</div>
                    </div>
                    <div class="toggle-switch" id="pdfSwitch"></div>
                </div>
                
                <div class="export-settings" id="pdfSettings">
                    <div class="form-group">
                        <label class="form-label">ชื่อไฟล์</label>
                        <input type="text" class="form-control" id="pdfFilename" value="sql_alert_report" placeholder="ชื่อไฟล์">
                        </div>
                    
                        <div class="form-row">
                            <div class="form-group">
                            <label class="form-label">ขนาดกระดาษ</label>
                            <select class="form-control form-select" id="pdfPageSize">
                                <option value="A4">A4</option>
                                <option value="A3">A3</option>
                                <option value="letter">Letter</option>
                                <option value="legal">Legal</option>
                            </select>
                            </div>
                            <div class="form-group">
                            <label class="form-label">ทิศทางกระดาษ</label>
                            <select class="form-control form-select" id="pdfOrientation">
                                <option value="portrait">แนวตั้ง</option>
                                <option value="landscape">แนวนอน</option>
                            </select>
                            </div>
                        </div>

                    <div class="form-group">
                        <label class="form-label">ตัวเลือกเพิ่มเติม</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="pdfHeaders" checked>
                                <label class="checkbox-label" for="pdfHeaders">รวม Header</label>
                    </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="pdfFooter" checked>
                                <label class="checkbox-label" for="pdfFooter">รวม Footer</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="pdfPageNumbers" checked>
                                <label class="checkbox-label" for="pdfPageNumbers">เลขหน้า</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="pdfGrid" checked>
                                <label class="checkbox-label" for="pdfGrid">แสดงเส้นตาราง</label>
                            </div>
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

            <div class="preview-sample" id="previewSample">
                <div style="color: #6b7280; text-align: center; padding: 20px;">
                    เลือกรูปแบบการส่งออกเพื่อดูตัวอย่าง
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
                    ขั้นตอนที่ 7 จาก 14
                </div>
                
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                ถัดไป (เทมเพลต Email)
                    <i class="fas fa-arrow-right"></i>
                </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Step 7 DOM loaded');
    
    // Add delay to ensure DOM is fully ready
    setTimeout(() => {
        initializeStep7();
    }, 100);
});

function initializeStep7() {
    console.log('Initializing Step 7...');
    
    try {
        loadSavedExportSettings();
        updatePreview();
        
        // Auto-enable Excel export by default
        if (!hasAnyExportEnabled()) {
            toggleExport('excel');
        }
    } catch (error) {
        console.error('Error initializing Step 7:', error);
    }
}

function toggleExport(type) {
    const toggle = document.getElementById(type + 'Toggle');
    const switchElement = document.getElementById(type + 'Switch');
    const settings = document.getElementById(type + 'Settings');
    
    if (!toggle || !switchElement || !settings) {
        console.error('Export elements not found for type:', type);
        return;
    }
    
    const isActive = toggle.classList.contains('active');
    
    if (isActive) {
        // Deactivate
        toggle.classList.remove('active');
        switchElement.classList.remove('active');
        settings.classList.remove('show');
    } else {
        // Activate
        toggle.classList.add('active');
        switchElement.classList.add('active');
        settings.classList.add('show');
    }
    
    updatePreview();
    saveExportSettings();
}

function hasAnyExportEnabled() {
    const types = ['excel', 'csv', 'json', 'pdf'];
    return types.some(type => {
        const toggle = document.getElementById(type + 'Toggle');
        return toggle && toggle.classList.contains('active');
    });
}

function updatePreview() {
    const activeExports = getActiveExports();
    let queryResults = JSON.parse(sessionStorage.getItem('sql_alert_query_results') || '{}');
    
    // Debug
    console.log('Step 7 - queryResults from sessionStorage:', queryResults);
    
    // Fallback data if no results found
    if (!queryResults.columns || !queryResults.rows) {
        const finalResults = JSON.parse(sessionStorage.getItem('sql_alert_final_results') || '{}');
        console.log('Step 7 - trying final_results:', finalResults);
        
        if (finalResults.columns && finalResults.sample_data) {
            queryResults = {
                columns: finalResults.columns,
                rows: finalResults.sample_data,
                totalRows: finalResults.records_count || 0,
                executionTime: finalResults.execution_time || 0
            };
        } else {
            // Ultimate fallback
            queryResults = {
                columns: ['id', 'name', 'created_at'],
                rows: [
                    { id: 1, name: 'Sample Data 1', created_at: '2024-01-15' },
                    { id: 2, name: 'Sample Data 2', created_at: '2024-01-16' },
                    { id: 3, name: 'Sample Data 3', created_at: '2024-01-17' }
                ],
                totalRows: 342,
                executionTime: 0.25
            };
        }
    }
    
    if (activeExports.length === 0) {
        document.getElementById('previewSample').innerHTML = 
            '<div style="color: #6b7280; text-align: center; padding: 20px;">เลือกรูปแบบการส่งออกเพื่อดูตัวอย่าง</div>';
        return;
    }
    
    // Update stats
    if (queryResults.totalRows) {
        document.getElementById('previewRows').textContent = queryResults.totalRows.toLocaleString();
        document.getElementById('previewColumns').textContent = queryResults.columns ? queryResults.columns.length : 0;
        document.getElementById('previewSize').textContent = estimateFileSize(queryResults, activeExports[0]);
        document.getElementById('previewFormat').textContent = activeExports[0].toUpperCase();
    }
    
    // Update sample preview
    updateSamplePreview(activeExports[0], queryResults);
}

function getActiveExports() {
    const types = ['excel', 'csv', 'json', 'pdf'];
    return types.filter(type => {
        const toggle = document.getElementById(type + 'Toggle');
        return toggle && toggle.classList.contains('active');
    });
}

function estimateFileSize(queryResults, type) {
    if (!queryResults.totalRows) return '0 KB';
    
    const rows = queryResults.totalRows;
    const columns = queryResults.columns ? queryResults.columns.length : 0;
    
    let sizePerRow = 0;
    switch (type) {
        case 'excel':
            sizePerRow = columns * 15; // Average 15 bytes per cell
            break;
        case 'csv':
            sizePerRow = columns * 12; // Average 12 bytes per cell
            break;
        case 'json':
            sizePerRow = columns * 25; // Average 25 bytes per field
            break;
        case 'pdf':
            sizePerRow = columns * 20; // Average 20 bytes per cell
            break;
    }
    
    const totalSize = rows * sizePerRow;
    if (totalSize < 1024) return totalSize + ' B';
    if (totalSize < 1024 * 1024) return (totalSize / 1024).toFixed(1) + ' KB';
    return (totalSize / 1024 / 1024).toFixed(1) + ' MB';
}

function updateSamplePreview(type, queryResults) {
    const previewElement = document.getElementById('previewSample');
    
    if (!queryResults.columns || !queryResults.rows) {
        previewElement.innerHTML = '<div style="color: #6b7280; text-align: center; padding: 20px;">ไม่มีข้อมูลตัวอย่าง</div>';
        return;
    }
    
    // Handle both string arrays and object arrays for columns
    const columns = Array.isArray(queryResults.columns) ? queryResults.columns.slice(0, 5) : []; // First 5 columns
    const rows = Array.isArray(queryResults.rows) ? queryResults.rows.slice(0, 3) : []; // First 3 rows
    
    // Additional safety check
    if (columns.length === 0 || rows.length === 0) {
        previewElement.innerHTML = '<div style="color: #6b7280; text-align: center; padding: 20px;">ไม่มีข้อมูลตัวอย่าง</div>';
        return;
    }
    
    let sample = '';
    
    switch (type) {
        case 'excel':
            sample = generateExcelSample(columns, rows);
            break;
        case 'csv':
            sample = generateCsvSample(columns, rows);
            break;
        case 'json':
            sample = generateJsonSample(columns, rows);
            break;
        case 'pdf':
            sample = generatePdfSample(columns, rows);
            break;
    }
    
    previewElement.innerHTML = sample;
}

function generateExcelSample(columns, rows) {
    let sample = '<div style="color: #10b981; font-weight: 600; margin-bottom: 10px;">📊 Excel (XLSX) Sample:</div>';
    sample += '<table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">';
    
    // Header
    sample += '<tr>';
    columns.forEach(col => {
        const colName = typeof col === 'string' ? col : col.name;
        sample += `<th style="border: 1px solid #d1d5db; padding: 5px; background: #f3f4f6; font-weight: 600;">${colName}</th>`;
    });
    sample += '</tr>';
    
    // Data rows
    rows.forEach(row => {
        sample += '<tr>';
        columns.forEach(col => {
            const colName = typeof col === 'string' ? col : col.name;
            const value = row[colName] || '';
            sample += `<td style="border: 1px solid #d1d5db; padding: 5px;">${value}</td>`;
        });
        sample += '</tr>';
    });
    
    sample += '</table>';
    sample += '<div style="margin-top: 10px; font-size: 0.7rem; color: #6b7280;">✓ รองรับการจัดรูปแบบและสูตร Excel</div>';
    
    return sample;
}

function generateCsvSample(columns, rows) {
    const separator = document.getElementById('csvSeparator')?.value || ',';
    const actualSep = separator === 'tab' ? '\t' : separator;
    
    let sample = '<div style="color: #10b981; font-weight: 600; margin-bottom: 10px;">📄 CSV Sample:</div>';
    sample += '<div style="font-size: 0.8rem; line-height: 1.6;">';
    
    // Header
    sample += columns.map(col => typeof col === 'string' ? col : col.name).join(actualSep) + '<br>';
    
    // Data rows
    rows.forEach(row => {
        const values = columns.map(col => {
            const colName = typeof col === 'string' ? col : col.name;
            const value = row[colName] || '';
            return typeof value === 'string' && value.includes(actualSep) ? `"${value}"` : value;
        });
        sample += values.join(actualSep) + '<br>';
    });
    
    sample += '</div>';
    sample += '<div style="margin-top: 10px; font-size: 0.7rem; color: #6b7280;">✓ รองรับการเข้ารหัส UTF-8</div>';
    
    return sample;
}

function generateJsonSample(columns, rows) {
    let sample = '<div style="color: #10b981; font-weight: 600; margin-bottom: 10px;">🔧 JSON Sample:</div>';
    sample += '<div style="font-size: 0.8rem; line-height: 1.6;">';
    
    const jsonData = rows.map(row => {
        const obj = {};
        columns.forEach(col => {
            const colName = typeof col === 'string' ? col : col.name;
            obj[colName] = row[colName] || null;
        });
        return obj;
    });
    
    sample += '<pre style="margin: 0; white-space: pre-wrap;">' + JSON.stringify(jsonData, null, 2) + '</pre>';
    sample += '</div>';
    sample += '<div style="margin-top: 10px; font-size: 0.7rem; color: #6b7280;">✓ รองรับ Pretty Print และ Metadata</div>';
    
    return sample;
}

function generatePdfSample(columns, rows) {
    let sample = '<div style="color: #10b981; font-weight: 600; margin-bottom: 10px;">📋 PDF Sample:</div>';
    sample += '<div style="border: 1px solid #d1d5db; padding: 15px; background: white; font-size: 0.8rem;">';
    
    // Header
    sample += '<div style="text-align: center; font-weight: 600; margin-bottom: 15px; border-bottom: 2px solid #374151; padding-bottom: 10px;">SQL Alert Report</div>';
    
    // Table
    sample += '<table style="width: 100%; border-collapse: collapse; font-size: 0.7rem;">';
    
    // Header
    sample += '<tr>';
    columns.forEach(col => {
        const colName = typeof col === 'string' ? col : col.name;
        sample += `<th style="border: 1px solid #374151; padding: 5px; background: #f3f4f6; font-weight: 600;">${colName}</th>`;
    });
    sample += '</tr>';
    
    // Data rows
    rows.forEach(row => {
        sample += '<tr>';
        columns.forEach(col => {
            const colName = typeof col === 'string' ? col : col.name;
            const value = row[colName] || '';
            sample += `<td style="border: 1px solid #374151; padding: 5px;">${value}</td>`;
        });
        sample += '</tr>';
    });
    
    sample += '</table>';
    
    // Footer
    sample += '<div style="text-align: center; margin-top: 15px; font-size: 0.6rem; color: #6b7280; border-top: 1px solid #d1d5db; padding-top: 10px;">Generated on ' + new Date().toLocaleString() + ' | Page 1</div>';
    
    sample += '</div>';
    sample += '<div style="margin-top: 10px; font-size: 0.7rem; color: #6b7280;">✓ รองรับการจัดรูปแบบและเลขหน้า</div>';
    
    return sample;
}

function saveExportSettings() {
    const settings = {
        excel: {
            enabled: document.getElementById('excelToggle')?.classList.contains('active') || false,
            filename: document.getElementById('excelFilename')?.value || 'sql_alert_data',
            worksheet: document.getElementById('excelWorksheet')?.value || 'Data',
            limit: document.getElementById('excelLimit')?.value || '1000',
            headers: document.getElementById('excelHeaders')?.checked || false,
            autofit: document.getElementById('excelAutofit')?.checked || false,
            filter: document.getElementById('excelFilter')?.checked || false,
            format: document.getElementById('excelFormat')?.checked || false
        },
        csv: {
            enabled: document.getElementById('csvToggle')?.classList.contains('active') || false,
            filename: document.getElementById('csvFilename')?.value || 'sql_alert_data',
            separator: document.getElementById('csvSeparator')?.value || ',',
            encoding: document.getElementById('csvEncoding')?.value || 'utf8',
            headers: document.getElementById('csvHeaders')?.checked || false,
            quotes: document.getElementById('csvQuotes')?.checked || false,
            escape: document.getElementById('csvEscape')?.checked || false,
            dateFormat: document.getElementById('csvDateFormat')?.checked || false
        },
        json: {
            enabled: document.getElementById('jsonToggle')?.classList.contains('active') || false,
            filename: document.getElementById('jsonFilename')?.value || 'sql_alert_data',
            format: document.getElementById('jsonFormat')?.value || 'array',
            encoding: document.getElementById('jsonEncoding')?.value || 'utf8',
            pretty: document.getElementById('jsonPretty')?.checked || false,
            metadata: document.getElementById('jsonMetadata')?.checked || false,
            compress: document.getElementById('jsonCompress')?.checked || false,
            dateISO: document.getElementById('jsonDateISO')?.checked || false
        },
        pdf: {
            enabled: document.getElementById('pdfToggle')?.classList.contains('active') || false,
            filename: document.getElementById('pdfFilename')?.value || 'sql_alert_report',
            pageSize: document.getElementById('pdfPageSize')?.value || 'A4',
            orientation: document.getElementById('pdfOrientation')?.value || 'portrait',
            headers: document.getElementById('pdfHeaders')?.checked || false,
            footer: document.getElementById('pdfFooter')?.checked || false,
            pageNumbers: document.getElementById('pdfPageNumbers')?.checked || false,
            grid: document.getElementById('pdfGrid')?.checked || false
        }
    };
    
    sessionStorage.setItem('sql_alert_export_settings', JSON.stringify(settings));
}

function loadSavedExportSettings() {
    const saved = sessionStorage.getItem('sql_alert_export_settings');
    if (!saved) return;
    
    try {
        const settings = JSON.parse(saved);
        
        // Load Excel settings
        if (settings.excel) {
            if (settings.excel.enabled) toggleExport('excel');
            if (document.getElementById('excelFilename')) document.getElementById('excelFilename').value = settings.excel.filename || 'sql_alert_data';
            if (document.getElementById('excelWorksheet')) document.getElementById('excelWorksheet').value = settings.excel.worksheet || 'Data';
            if (document.getElementById('excelLimit')) document.getElementById('excelLimit').value = settings.excel.limit || '1000';
            if (document.getElementById('excelHeaders')) document.getElementById('excelHeaders').checked = settings.excel.headers;
            if (document.getElementById('excelAutofit')) document.getElementById('excelAutofit').checked = settings.excel.autofit;
            if (document.getElementById('excelFilter')) document.getElementById('excelFilter').checked = settings.excel.filter;
            if (document.getElementById('excelFormat')) document.getElementById('excelFormat').checked = settings.excel.format;
        }
        
        // Load CSV settings
        if (settings.csv) {
            if (settings.csv.enabled) toggleExport('csv');
            if (document.getElementById('csvFilename')) document.getElementById('csvFilename').value = settings.csv.filename || 'sql_alert_data';
            if (document.getElementById('csvSeparator')) document.getElementById('csvSeparator').value = settings.csv.separator || ',';
            if (document.getElementById('csvEncoding')) document.getElementById('csvEncoding').value = settings.csv.encoding || 'utf8';
            if (document.getElementById('csvHeaders')) document.getElementById('csvHeaders').checked = settings.csv.headers;
            if (document.getElementById('csvQuotes')) document.getElementById('csvQuotes').checked = settings.csv.quotes;
            if (document.getElementById('csvEscape')) document.getElementById('csvEscape').checked = settings.csv.escape;
            if (document.getElementById('csvDateFormat')) document.getElementById('csvDateFormat').checked = settings.csv.dateFormat;
        }
        
        // Load JSON settings
        if (settings.json) {
            if (settings.json.enabled) toggleExport('json');
            if (document.getElementById('jsonFilename')) document.getElementById('jsonFilename').value = settings.json.filename || 'sql_alert_data';
            if (document.getElementById('jsonFormat')) document.getElementById('jsonFormat').value = settings.json.format || 'array';
            if (document.getElementById('jsonEncoding')) document.getElementById('jsonEncoding').value = settings.json.encoding || 'utf8';
            if (document.getElementById('jsonPretty')) document.getElementById('jsonPretty').checked = settings.json.pretty;
            if (document.getElementById('jsonMetadata')) document.getElementById('jsonMetadata').checked = settings.json.metadata;
            if (document.getElementById('jsonCompress')) document.getElementById('jsonCompress').checked = settings.json.compress;
            if (document.getElementById('jsonDateISO')) document.getElementById('jsonDateISO').checked = settings.json.dateISO;
        }
        
        // Load PDF settings
        if (settings.pdf) {
            if (settings.pdf.enabled) toggleExport('pdf');
            if (document.getElementById('pdfFilename')) document.getElementById('pdfFilename').value = settings.pdf.filename || 'sql_alert_report';
            if (document.getElementById('pdfPageSize')) document.getElementById('pdfPageSize').value = settings.pdf.pageSize || 'A4';
            if (document.getElementById('pdfOrientation')) document.getElementById('pdfOrientation').value = settings.pdf.orientation || 'portrait';
            if (document.getElementById('pdfHeaders')) document.getElementById('pdfHeaders').checked = settings.pdf.headers;
            if (document.getElementById('pdfFooter')) document.getElementById('pdfFooter').checked = settings.pdf.footer;
            if (document.getElementById('pdfPageNumbers')) document.getElementById('pdfPageNumbers').checked = settings.pdf.pageNumbers;
            if (document.getElementById('pdfGrid')) document.getElementById('pdfGrid').checked = settings.pdf.grid;
        }
    } catch (error) {
        console.error('Error loading saved export settings:', error);
    }
}

function previousStep() {
    saveExportSettings();
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.previousStep();
    } else {
        window.location.href = '/admin/sql-alerts/create?step=6';
    }
}

function nextStep() {
    const activeExports = getActiveExports();
    
    if (activeExports.length === 0) {
        alert('กรุณาเลือกอย่างน้อยหนึ่งรูปแบบการส่งออก');
        return;
    }
    
    saveExportSettings();
    
    // **เพิ่มการสร้างข้อมูลจำลองสำหรับ step 8 ถ้าไม่มี**
    const existingQueryResults = sessionStorage.getItem('sql_alert_query_results');
    if (!existingQueryResults) {
        const mockQueryResults = {
            columns: ['id', 'alert_type', 'message', 'created_at', 'status'],
            rows: [
                { id: 1, alert_type: 'error', message: 'Database connection failed', created_at: '2024-01-15', status: 'pending' },
                { id: 2, alert_type: 'warning', message: 'High CPU usage detected', created_at: '2024-01-16', status: 'resolved' },
                { id: 3, alert_type: 'info', message: 'System update completed', created_at: '2024-01-17', status: 'acknowledged' }
            ],
            totalRows: 75,
            executionTime: 0.35,
            querySize: 250
        };
        sessionStorage.setItem('sql_alert_query_results', JSON.stringify(mockQueryResults));
    }
    
    sessionStorage.setItem('sql_alert_step', '8');
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.nextStep();
    } else {
        window.location.href = '/admin/sql-alerts/create?step=8';
    }
}

// Auto-save on change
document.addEventListener('change', function(e) {
    if (e.target.matches('#excelFilename, #csvFilename, #jsonFilename, #pdfFilename') ||
        e.target.matches('input[type="checkbox"]') ||
        e.target.matches('select')) {
        saveExportSettings();
        updatePreview();
    }
});

// Export functions to global scope
window.toggleExport = toggleExport;
window.previousStep = previousStep;
window.nextStep = nextStep;
window.initializeCurrentStep = initializeStep7;

console.log('Step 7 script loaded');
</script>