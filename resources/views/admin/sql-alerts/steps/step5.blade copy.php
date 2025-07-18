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

<div class="container">

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
                    <div class="variable-card system-var" onclick="addPredefinedVariable('record_count', 'จำนวนแถวข้อมูล', 'COUNT(*)')">
                        <div class="variable-name">&#123;&#123;record_count&#125;&#125;</div>
                        <div class="variable-description">จำนวนแถวข้อมูลที่ได้จาก Query</div>
                        <div class="variable-example">ตัวอย่าง: 25</div>
                    </div>

                    <div class="variable-card system-var" onclick="addPredefinedVariable('query_execution_time', 'เวลาในการรัน Query', 'EXECUTION_TIME')">
                        <div class="variable-name">&#123;&#123;query_execution_time&#125;&#125;</div>
                        <div class="variable-description">เวลาที่ใช้ในการรัน SQL Query</div>
                        <div class="variable-example">ตัวอย่าง: 0.25s</div>
                    </div>

                    <div class="variable-card system-var" onclick="addPredefinedVariable('current_date', 'วันที่ปัจจุบัน', 'DATE')">
                        <div class="variable-name">&#123;&#123;current_date&#125;&#125;</div>
                        <div class="variable-description">วันที่ปัจจุบัน</div>
                        <div class="variable-example">ตัวอย่าง: 2025-07-17</div>
                    </div>

                    <div class="variable-card system-var" onclick="addPredefinedVariable('current_datetime', 'วันที่และเวลาปัจจุบัน', 'DATETIME')">
                        <div class="variable-name">&#123;&#123;current_datetime&#125;&#125;</div>
                        <div class="variable-description">วันที่และเวลาปัจจุบัน</div>
                        <div class="variable-example">ตัวอย่าง: 2025-07-17 14:30:00</div>
                    </div>

                    <div class="variable-card system-var" onclick="addPredefinedVariable('database_name', 'ชื่อฐานข้อมูล', 'DATABASE')">
                        <div class="variable-name">&#123;&#123;database_name&#125;&#125;</div>
                        <div class="variable-description">ชื่อฐานข้อมูลที่ใช้</div>
                        <div class="variable-example">ตัวอย่าง: company_db</div>
                    </div>

                    <div class="variable-card system-var" onclick="addPredefinedVariable('alert_name', 'ชื่อการแจ้งเตือน', 'ALERT_NAME')">
                        <div class="variable-name">&#123;&#123;alert_name&#125;&#125;</div>
                        <div class="variable-description">ชื่อการแจ้งเตือนที่ตั้งไว้</div>
                        <div class="variable-example">ตัวอย่าง: User Activity Alert</div>
                    </div>

                    <!-- Date Variables -->
                    <div class="variable-card date-var" onclick="addPredefinedVariable('yesterday', 'วันที่เมื่อวาน', 'DATE_SUB(CURDATE(), INTERVAL 1 DAY)')">
                        <div class="variable-name">&#123;&#123;yesterday&#125;&#125;</div>
                        <div class="variable-description">วันที่เมื่อวาน</div>
                        <div class="variable-example">ตัวอย่าง: 2025-07-16</div>
                    </div>

                    <div class="variable-card date-var" onclick="addPredefinedVariable('last_week', 'สัปดาห์ที่แล้ว', 'DATE_SUB(CURDATE(), INTERVAL 1 WEEK)')">
                        <div class="variable-name">&#123;&#123;last_week&#125;&#125;</div>
                        <div class="variable-description">วันที่สัปดาห์ที่แล้ว</div>
                        <div class="variable-example">ตัวอย่าง: 2025-07-10</div>
                    </div>

                    <div class="variable-card date-var" onclick="addPredefinedVariable('last_month', 'เดือนที่แล้ว', 'DATE_SUB(CURDATE(), INTERVAL 1 MONTH)')">
                        <div class="variable-name">&#123;&#123;last_month&#125;&#125;</div>
                        <div class="variable-description">วันที่เดือนที่แล้ว</div>
                        <div class="variable-example">ตัวอย่าง: 2025-06-17</div>
                    </div>

                    <!-- Custom Variables -->
                    <div class="variable-card custom-var" onclick="addPredefinedVariable('threshold_value', 'ค่าเกณฑ์', '100')">
                        <div class="variable-name">&#123;&#123;threshold_value&#125;&#125;</div>
                        <div class="variable-description">ค่าเกณฑ์สำหรับการแจ้งเตือน</div>
                        <div class="variable-example">ตัวอย่าง: 100</div>
                    </div>

                    <!-- Query Result Variables -->
                    <div class="variable-card custom-var" onclick="addPredefinedVariable('first_row_data', 'ข้อมูลแถวแรก', 'FIRST_ROW')">
                        <div class="variable-name">&#123;&#123;first_row_data&#125;&#125;</div>
                        <div class="variable-description">ข้อมูลจากแถวแรกของ Query</div>
                        <div class="variable-example">ตัวอย่าง: JSON Object</div>
                    </div>

                    <div class="variable-card custom-var" onclick="addPredefinedVariable('max_value', 'ค่าสูงสุด', 'MAX_VALUE')">
                        <div class="variable-name">&#123;&#123;max_value&#125;&#125;</div>
                        <div class="variable-description">ค่าสูงสุดจากผลลัพธ์</div>
                        <div class="variable-example">ตัวอย่าง: 1000</div>
                    </div>

                    <div class="variable-card custom-var" onclick="addPredefinedVariable('min_value', 'ค่าต่ำสุด', 'MIN_VALUE')">
                        <div class="variable-name">&#123;&#123;min_value&#125;&#125;</div>
                        <div class="variable-description">ค่าต่ำสุดจากผลลัพธ์</div>
                        <div class="variable-example">ตัวอย่าง: 10</div>
                    </div>

                    <div class="variable-card custom-var" onclick="addPredefinedVariable('avg_value', 'ค่าเฉลี่ย', 'AVG_VALUE')">
                        <div class="variable-name">&#123;&#123;avg_value&#125;&#125;</div>
                        <div class="variable-description">ค่าเฉลี่ยจากผลลัพธ์</div>
                        <div class="variable-example">ตัวอย่าง: 250.5</div>
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
                        <li><strong>ชื่อตัวแปร:</strong> ใช้ในรูปแบบ <code>&#123;&#123;variable_name&#125;&#125;</code> ใน Email Template</li>
                        <li><strong>ประเภทระบบ:</strong> ตัวแปรที่ระบบสร้างอัตโนมัติ เช่น จำนวนข้อมูล, วันที่</li>
                        <li><strong>ประเภท Query:</strong> ค่าที่ได้จากผลลัพธ์ SQL Query หรือข้อมูลจากแถวแรก</li>
                        <li><strong>ประเภทวันที่:</strong> ตัวแปรที่เกี่ยวข้องกับวันที่และเวลา</li>
                        <li><strong>การใช้งาน:</strong> ตัวแปรจะถูกแทนที่ด้วยค่าจริงเมื่อส่ง Email</li>
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
                    WHERE created_at >= <span class="highlight-variable">&#123;&#123;current_date&#125;&#125;</span>
                    
                    -- ตัวแปรที่จะใช้ใน Email:
                    -- จำนวนข้อมูล: <span class="highlight-variable">&#123;&#123;record_count&#125;&#125;</span>
                    -- วันที่รัน: <span class="highlight-variable">&#123;&#123;current_datetime&#125;&#125;</span>
                </div>
            </div>

            <!-- Email Template Example -->
            <div class="predefined-variables">
                <div class="predefined-header">
                    <i class="fas fa-envelope"></i>
                    ตัวอย่างการใช้ตัวแปรใน Email Template
                </div>
                
                <div class="sql-preview" style="background: #f8f9fa; color: #374151; border: 1px solid #e5e7eb;">
                    <div class="sql-preview-header" style="color: #4f46e5;">
                        📧 Email Template ตัวอย่าง
                    </div>
                    <div style="line-height: 1.6;">
                        <strong>หัวข้อ:</strong> แจ้งเตือน - <span class="highlight-variable">&#123;&#123;alert_name&#125;&#125;</span><br><br>
                        
                        <strong>เนื้อหา:</strong><br>
                        เรียน ทีมงาน,<br><br>
                        
                        ระบบได้ตรวจพบข้อมูลใหม่จาก Query ของคุณ<br>
                        • จำนวนข้อมูลทั้งหมด: <span class="highlight-variable">&#123;&#123;record_count&#125;&#125;</span> แถว<br>
                        • วันที่รัน Query: <span class="highlight-variable">&#123;&#123;current_datetime&#125;&#125;</span><br>
                        • ฐานข้อมูล: <span class="highlight-variable">&#123;&#123;database_name&#125;&#125;</span><br><br>
                        
                        <strong>ข้อมูลจากคอลัมน์:</strong><br>
                        • Employee ID: <span class="highlight-variable">&#123;&#123;employee_id&#125;&#125;</span><br>
                        • Employee Name: <span class="highlight-variable">&#123;&#123;employee_name&#125;&#125;</span><br>
                        • Department: <span class="highlight-variable">&#123;&#123;department&#125;&#125;</span><br><br>
                        
                        โปรดตรวจสอบข้อมูลเพิ่มเติม<br><br>
                        
                        ขอบคุณ,<br>
                        ระบบแจ้งเตือน
                    </div>
                </div>
                
                <div class="validation-note" style="margin-top: 15px;">
                    <h6>
                        <i class="fas fa-lightbulb me-1"></i>
                        การใช้งานตัวแปรใน Email Template
                    </h6>
                    <ul>
                        <li><strong>ตัวแปรระบบ:</strong> เช่น <code>&#123;&#123;record_count&#125;&#125;</code>, <code>&#123;&#123;current_date&#125;&#125;</code> - ใช้งานได้ทันที</li>
                        <li><strong>ตัวแปรจากคอลัมน์:</strong> เช่น <code>&#123;&#123;employee_id&#125;&#125;</code> - ใช้ข้อมูลจากแถวแรก</li>
                        <li><strong>ตัวแปรกำหนดเอง:</strong> เช่น <code>&#123;&#123;threshold_value&#125;&#125;</code> - กำหนดค่าได้</li>
                        <li><strong>ข้อควรทราบ:</strong> หากไม่มีข้อมูลในคอลัมน์ จะแสดง "-" แทน</li>
                    </ul>
                </div>
            </div>

            <!-- Sample Data Preview Table -->
            <div class="predefined-variables">
                <div class="predefined-header">
                    <i class="fas fa-table"></i>
                    ตัวอย่างข้อมูลจาก SQL Query (แสดงสูงสุด 10 คอลัมน์ × 10 แถว)
                    <button type="button" class="btn btn-sm btn-success" onclick="refreshQueryPreview()" style="margin-left: auto;">
                        <i class="fas fa-sync"></i>
                        รีเฟรชข้อมูล
                    </button>
                </div>
                
                <div id="queryDataPreview">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        กำลังโหลดข้อมูลจาก Query...
                    </div>
                </div>

                <!-- How to use variables -->
                <div class="validation-note" style="margin-top: 20px;">
                    <h6>
                        <i class="fas fa-question-circle me-1"></i>
                        วิธีใช้ตัวแปรจากข้อมูลด้านบน
                    </h6>
                    <ul>
                        <li><strong>ชื่อคอลัมน์:</strong> ใช้ชื่อคอลัมน์จากตารางเป็นชื่อตัวแปร เช่น <code>&#123;&#123;employee_id&#125;&#125;</code></li>
                        <li><strong>ข้อมูลแถวแรก:</strong> ระบบจะใช้ค่าจากแถวแรกเป็นค่าตัวแปร</li>
                        <li><strong>ตัวอย่าง:</strong> หากมีคอลัมน์ "employee_name" ใช้ <code>&#123;&#123;employee_name&#125;&#125;</code> ใน Email Template</li>
                        <li><strong>การสร้างตัวแปร:</strong> คลิกที่ชื่อคอลัมน์ด้านล่างเพื่อเพิ่มเป็นตัวแปร</li>
                    </ul>
                    
                    <div style="margin-top: 15px;">
                        <button type="button" class="btn btn-primary btn-sm" onclick="addAllColumnsAsVariables()">
                            <i class="fas fa-plus"></i>
                            เพิ่มตัวแปรทั้งหมดจากคอลัมน์
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearAllVariables()">
                            <i class="fas fa-trash"></i>
                            ล้างตัวแปรทั้งหมด
                        </button>
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

<script>
let variableCount = 1;
let currentFilter = 'system';

document.addEventListener('DOMContentLoaded', function() {
    console.log('Step 5 DOM loaded');
    
    // Add delay to ensure DOM is fully ready
    setTimeout(() => {
        initializeStep5();
    }, 100);
});

function initializeStep5() {
    console.log('Initializing Step 5...');
    
    // Check if required elements exist
    const container = document.getElementById('variablesContainer');
    const previewContainer = document.getElementById('queryDataPreview');
    
    if (!container) {
        console.error('variablesContainer not found');
        return;
    }
    
    if (!previewContainer) {
        console.error('queryDataPreview not found');
        return;
    }
    
    try {
        loadSavedVariables();
        updatePreview();
        loadQueryDataPreview();
    } catch (error) {
        console.error('Error initializing Step 5:', error);
    }
}

function filterVariables(type) {
    currentFilter = type;
    
    // Update active badge
    const badges = document.querySelectorAll('.type-badge');
    badges.forEach(badge => {
        badge.classList.remove('selected');
    });
    
    const targetBadge = document.querySelector('[data-type="' + type + '"]');
    if (targetBadge) {
        targetBadge.classList.add('selected');
    }
    
    // Show/hide variables
    const systemVars = document.querySelectorAll('.system-var');
    const dateVars = document.querySelectorAll('.date-var');
    
    systemVars.forEach(el => el.style.display = (type === 'system' || type === 'all') ? 'block' : 'none');
    dateVars.forEach(el => el.style.display = (type === 'date' || type === 'all') ? 'block' : 'none');
}

function addPredefinedVariable(name, description, value) {
    const container = document.getElementById('variablesContainer');
    if (!container) {
        console.error('variablesContainer not found');
        return;
    }
    
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
    if (!container) {
        console.error('variablesContainer not found');
        return;
    }
    
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
    const previewElement = document.getElementById('sqlPreviewContent');
    if (!previewElement) {
        console.error('sqlPreviewContent not found');
        return;
    }
    
    const sqlQuery = sessionStorage.getItem('sql_alert_query') || 'SELECT * FROM your_table';
    const variables = getCurrentVariables();
    
    let preview = '-- SQL Query ของคุณ\n' + sqlQuery + '\n\n-- ตัวแปรที่จะใช้ใน Email:';
    
    // เพิ่มตัวแปรระบบ
    preview += '\n-- จำนวนข้อมูล: <span class="highlight-variable">&#123;&#123;record_count&#125;&#125;</span>';
    preview += '\n-- วันที่รัน: <span class="highlight-variable">&#123;&#123;current_datetime&#125;&#125;</span>';
    preview += '\n-- ฐานข้อมูล: <span class="highlight-variable">&#123;&#123;database_name&#125;&#125;</span>';
    
    // เพิ่มตัวแปรที่ผู้ใช้กำหนด
    variables.forEach(variable => {
        if (variable.name && variable.description) {
            preview += '\n-- ' + variable.description + ': <span class="highlight-variable">&#123;&#123;' + variable.name + '&#125;&#125;</span>';
        }
    });
    
    // เพิ่มตัวแปรจากคอลัมน์ (ถ้ามี)
    const queryResult = sessionStorage.getItem('sql_alert_query_result');
    if (queryResult) {
        try {
            const result = JSON.parse(queryResult);
            if (result.success && result.data && result.data.columns) {
                preview += '\n\n-- ตัวแปรจากคอลัมน์ที่มี:';
                result.data.columns.slice(0, 5).forEach(column => {
                    const cleanName = column.replace(/[^a-zA-Z0-9_]/g, '_').toLowerCase();
                    preview += '\n-- ' + column + ': <span class="highlight-variable">&#123;&#123;' + cleanName + '&#125;&#125;</span>';
                });
                
                if (result.data.columns.length > 5) {
                    preview += '\n-- และอีก ' + (result.data.columns.length - 5) + ' คอลัมน์... (คลิกที่หัวตารางเพื่อเพิ่ม)';
                }
            }
        } catch (e) {
            console.error('Error parsing query result for preview:', e);
        }
    }
    
    previewElement.innerHTML = preview;
}

function getCurrentVariables() {
    const variables = [];
    const container = document.getElementById('variablesContainer');
    
    if (!container) {
        console.error('variablesContainer not found');
        return variables;
    }
    
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
    const container = document.getElementById('variablesContainer');
    if (!container) {
        console.error('variablesContainer not found');
        return;
    }
    
    const saved = sessionStorage.getItem('sql_alert_variables');
    if (saved) {
        try {
            const variables = JSON.parse(saved);
            
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
                        const nameInput = firstItem.querySelector('input[name*="[name]"]');
                        const descInput = firstItem.querySelector('input[name*="[description]"]');
                        const typeSelect = firstItem.querySelector('select[name*="[type]"]');
                        
                        if (nameInput) nameInput.value = variable.name;
                        if (descInput) descInput.value = variable.description;
                        if (typeSelect) typeSelect.value = variable.type;
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
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.previousStep();
    } else {
        window.location.href = '{{ route("admin.sql-alerts.create") }}?step=4';
    }
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
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.nextStep();
    } else {
        window.location.href = '{{ route("admin.sql-alerts.create") }}?step=6';
    }
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
            addPredefinedVariable('record_count', 'จำนวนแถวข้อมูล', 'COUNT(*)');
            addPredefinedVariable('current_date', 'วันที่ปัจจุบัน', 'CURDATE()');
        }, 500);
    }
});

// เพิ่มฟังก์ชันโหลดข้อมูลตัวอย่าง
function loadQueryDataPreview() {
    const previewContainer = document.getElementById('queryDataPreview');
    if (!previewContainer) {
        console.error('queryDataPreview not found');
        return;
    }
    
    // ลองดึงข้อมูลจาก sessionStorage
    const savedQuery = sessionStorage.getItem('sql_alert_query');
    const savedConnection = sessionStorage.getItem('sql_alert_connection');
    const savedQueryResult = sessionStorage.getItem('sql_alert_query_result');
    
    if (!savedQuery || !savedConnection) {
        previewContainer.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ไม่พบข้อมูล Query หรือการเชื่อมต่อฐานข้อมูล กรุณาไปที่ขั้นตอนที่ 4 เพื่อทดสอบ Query ก่อน
                <br><br>
                <button class="btn btn-primary btn-sm" onclick="goToStep4()">
                    <i class="fas fa-arrow-left"></i>
                    ไปที่ขั้นตอนที่ 4
                </button>
            </div>
        `;
        return;
    }
    
    // ใช้ข้อมูลจาก sessionStorage ถ้ามี
    if (savedQueryResult) {
        try {
            const queryResult = JSON.parse(savedQueryResult);
            if (queryResult.success && queryResult.data) {
                displayQueryData({
                    totalRows: queryResult.data.records_count || 0,
                    sampleData: queryResult.data.sample_data || [],
                    columns: queryResult.data.columns || []
                });
                return;
            }
        } catch (e) {
            console.error('Error parsing saved query result:', e);
        }
    }
    
    // ถ้าไม่มีข้อมูลใน sessionStorage ให้รัน query ใหม่
    runQueryForPreview();
}

function runQueryForPreview() {
    const previewContainer = document.getElementById('queryDataPreview');
    if (!previewContainer) {
        console.error('queryDataPreview not found');
        return;
    }
    
    previewContainer.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-spinner fa-spin me-2"></i>
            กำลังโหลดข้อมูลจาก Query...
        </div>
    `;
    
    const savedQuery = sessionStorage.getItem('sql_alert_query');
    const savedConnection = sessionStorage.getItem('sql_alert_connection');
    
    if (!savedQuery || !savedConnection) {
        previewContainer.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ไม่พบข้อมูล Query หรือการเชื่อมต่อฐานข้อมูล
            </div>
        `;
        return;
    }
    
    let connectionData;
    try {
        connectionData = JSON.parse(savedConnection);
    } catch (e) {
        console.error('Error parsing connection data:', e);
        previewContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-times me-2"></i>
                ข้อผิดพลาดในการอ่านข้อมูลการเชื่อมต่อ
            </div>
        `;
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch('{{ route("admin.sql-alerts.execute-query") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            sql_query: savedQuery,
            database_config: connectionData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const queryData = {
                totalRows: data.data.records_count || 0,
                sampleData: data.data.sample_data || [],
                columns: data.data.columns || []
            };
            
            // บันทึกผลลัพธ์ลง sessionStorage
            sessionStorage.setItem('sql_alert_query_result', JSON.stringify(data));
            
            displayQueryData(queryData);
        } else {
            previewContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times me-2"></i>
                    ข้อผิดพลาดในการรัน Query: ${data.message}
                    <br><br>
                    <button class="btn btn-primary btn-sm" onclick="goToStep4()">
                        <i class="fas fa-arrow-left"></i>
                        กลับไปแก้ไข Query
                    </button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error executing query:', error);
        previewContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-times me-2"></i>
                ข้อผิดพลาดในการเชื่อมต่อ: ${error.message}
                <br><br>
                <button class="btn btn-primary btn-sm" onclick="goToStep4()">
                    <i class="fas fa-arrow-left"></i>
                    กลับไปแก้ไข Query
                </button>
            </div>
        `;
    });
}

function displayQueryData(queryData) {
    const previewContainer = document.getElementById('queryDataPreview');
    if (!previewContainer) {
        console.error('queryDataPreview not found');
        return;
    }
    
    if (!queryData.sampleData || queryData.sampleData.length === 0) {
        previewContainer.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                ไม่มีข้อมูลจาก Query นี้
            </div>
        `;
        return;
    }
    
    const columns = queryData.columns || Object.keys(queryData.sampleData[0]);
    const displayColumns = columns.slice(0, 10); // แสดงเฉพาะ 10 คอลัมน์แรก
    const displayRows = queryData.sampleData.slice(0, 10); // แสดงเฉพาะ 10 แถวแรก
    
    let tableHtml = `
        <div class="table-responsive" style="margin-top: 20px;">
            <table class="table table-bordered table-striped" style="font-size: 0.85rem;">
                <thead style="background-color: #4f46e5; color: white;">
                    <tr>
    `;
    
    // สร้าง header - เพิ่มฟังก์ชันคลิกเพื่อเพิ่มเป็นตัวแปร
    displayColumns.forEach(column => {
        tableHtml += `
            <th style="padding: 8px; max-width: 150px; word-wrap: break-word; cursor: pointer;" 
                onclick="addColumnAsVariable('${column}')" 
                title="คลิกเพื่อเพิ่มเป็นตัวแปร">
                ${column}
                <i class="fas fa-plus-circle" style="margin-left: 5px; opacity: 0.7;"></i>
            </th>
        `;
    });
    
    tableHtml += `
                    </tr>
                </thead>
                <tbody>
    `;
    
    // สร้าง rows
    displayRows.forEach(row => {
        tableHtml += '<tr>';
        displayColumns.forEach(column => {
            let value = row[column] || '';
            // ตัดข้อความที่ยาวเกินไป
            if (typeof value === 'string' && value.length > 50) {
                value = value.substring(0, 50) + '...';
            }
            tableHtml += `<td style="padding: 8px; max-width: 150px; word-wrap: break-word;">${value}</td>`;
        });
        tableHtml += '</tr>';
    });
    
    tableHtml += `
                </tbody>
            </table>
        </div>
    `;
    
    // เพิ่มสถิติข้อมูล
    tableHtml += `
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>จำนวนแถวทั้งหมด:</strong> ${queryData.totalRows} แถว
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-success">
                    <i class="fas fa-columns me-2"></i>
                    <strong>จำนวนคอลัมน์:</strong> ${displayColumns.length} คอลัมน์${columns.length > 10 ? ` (จากทั้งหมด ${columns.length} คอลัมน์)` : ''}
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-warning">
                    <i class="fas fa-database me-2"></i>
                    <strong>ข้อมูลแสดง:</strong> ${displayRows.length} แถว${queryData.totalRows > 10 ? ` (จากทั้งหมด ${queryData.totalRows} แถว)` : ''}
                </div>
            </div>
        </div>
    `;
    
    previewContainer.innerHTML = tableHtml;
}

// ฟังก์ชันเพิ่มคอลัมน์เป็นตัวแปร
function addColumnAsVariable(columnName) {
    const cleanColumnName = columnName.replace(/[^a-zA-Z0-9_]/g, '_').toLowerCase();
    const description = `ข้อมูลจากคอลัมน์ ${columnName}`;
    
    addPredefinedVariable(cleanColumnName, description, 'COLUMN_VALUE');
    
    // แสดงข้อความยืนยัน
    const toast = document.createElement('div');
    toast.className = 'alert alert-success';
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; width: 300px;';
    toast.innerHTML = `
        <i class="fas fa-check me-2"></i>
        เพิ่มตัวแปร <strong>&#123;&#123;${cleanColumnName}&#125;&#125;</strong> เรียบร้อย
    `;
    
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// ฟังก์ชันกลับไป step 4
function goToStep4() {
    sessionStorage.setItem('sql_alert_step', '4');
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.goToStep(4);
    } else {
        window.location.href = '{{ route("admin.sql-alerts.create") }}?step=4';
    }
}

// ฟังก์ชันรีเฟรชข้อมูลตัวอย่าง
function refreshQueryPreview() {
    // ลบข้อมูลเก่าออก
    sessionStorage.removeItem('sql_alert_query_result');
    // โหลดข้อมูลใหม่
    loadQueryDataPreview();
}

// ฟังก์ชันเพิ่มตัวแปรทั้งหมดจากคอลัมน์
function addAllColumnsAsVariables() {
    const queryResult = sessionStorage.getItem('sql_alert_query_result');
    if (!queryResult) {
        alert('ไม่พบข้อมูล Query กรุณาไปที่ขั้นตอนที่ 4 เพื่อทดสอบ Query ก่อน');
        return;
    }
    
    try {
        const result = JSON.parse(queryResult);
        if (result.success && result.data && result.data.columns) {
            let addedCount = 0;
            
            result.data.columns.forEach(column => {
                const cleanColumnName = column.replace(/[^a-zA-Z0-9_]/g, '_').toLowerCase();
                const description = `ข้อมูลจากคอลัมน์ ${column}`;
                
                // ตรวจสอบว่าตัวแปรนี้มีอยู่แล้วหรือไม่
                const existingVariables = getCurrentVariables();
                const exists = existingVariables.some(v => v.name === cleanColumnName);
                
                if (!exists) {
                    addPredefinedVariable(cleanColumnName, description, 'COLUMN_VALUE');
                    addedCount++;
                }
            });
            
            // แสดงข้อความยืนยัน
            const toast = document.createElement('div');
            toast.className = 'alert alert-success';
            toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; width: 350px;';
            toast.innerHTML = `
                <i class="fas fa-check me-2"></i>
                เพิ่มตัวแปรจากคอลัมน์ <strong>${addedCount}</strong> ตัวแปร<br>
                <small>ตัวแปรที่มีอยู่แล้วจะถูกข้าม</small>
            `;
            
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.remove();
            }, 4000);
            
        } else {
            alert('ไม่สามารถดึงข้อมูลคอลัมน์ได้');
        }
    } catch (e) {
        console.error('Error parsing query result:', e);
        alert('เกิดข้อผิดพลาดในการดึงข้อมูลคอลัมน์');
    }
}

// ฟังก์ชันล้างตัวแปรทั้งหมด
function clearAllVariables() {
    const container = document.getElementById('variablesContainer');
    if (!container) {
        console.error('variablesContainer not found');
        return;
    }
    
    if (!confirm('คุณต้องการล้างตัวแปรทั้งหมดหรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        return;
    }
    
    const variableItems = container.querySelectorAll('.variable-item');
    
    // ล้างข้อมูลใน item แรก
    if (variableItems.length > 0) {
        const firstItem = variableItems[0];
        const nameInput = firstItem.querySelector('input[name*="[name]"]');
        const descInput = firstItem.querySelector('input[name*="[description]"]');
        const typeSelect = firstItem.querySelector('select[name*="[type]"]');
        
        if (nameInput) nameInput.value = '';
        if (descInput) descInput.value = '';
        if (typeSelect) typeSelect.value = 'system';
    }
    
    // ลบ items อื่น ๆ
    for (let i = 1; i < variableItems.length; i++) {
        variableItems[i].remove();
    }
    
    // อัปเดต preview และบันทึก
    updatePreview();
    saveVariables();
    
    // แสดงข้อความยืนยัน
    const toast = document.createElement('div');
    toast.className = 'alert alert-info';
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; width: 300px;';
    toast.innerHTML = `
        <i class="fas fa-info-circle me-2"></i>
        ล้างตัวแปรทั้งหมดเรียบร้อย
    `;
    
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Export functions to global scope
window.addPredefinedVariable = addPredefinedVariable;
window.addVariable = addVariable;
window.removeVariable = removeVariable;
window.filterVariables = filterVariables;
window.previousStep = previousStep;
window.nextStep = nextStep;
window.refreshQueryPreview = refreshQueryPreview;
window.addColumnAsVariable = addColumnAsVariable;
window.addAllColumnsAsVariables = addAllColumnsAsVariables;
window.clearAllVariables = clearAllVariables;
window.goToStep4 = goToStep4;
window.initializeCurrentStep = initializeStep5;

console.log('Step 5 script loaded');
</script>