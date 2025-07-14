<style>
    .wizard-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
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
        background: rgba(255, 255, 255, 0.3);
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

    .sql-editor-container {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
    }

    .sql-editor {
        position: relative;
    }

    .sql-editor-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 15px;
    }

    .editor-label {
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .editor-info {
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 15px;
    }

    .sql-textarea {
        width: 100%;
        min-height: 300px;
        padding: 20px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-family: 'Courier New', 'Monaco', monospace;
        font-size: 14px;
        line-height: 1.5;
        background: #1f2937;
        color: #e5e7eb;
        resize: vertical;
        transition: all 0.3s ease;
    }

    .sql-textarea:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .sql-toolbar {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        flex-wrap: wrap;
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
        font-size: 0.9rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
    }

    .btn-primary:hover:not(:disabled) {
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

    .btn-warning {
        background: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background: #d97706;
    }

    .btn-info {
        background: #06b6d4;
        color: white;
    }

    .btn-info:hover {
        background: #0891b2;
    }

    .btn-sm {
        padding: 8px 16px;
        font-size: 0.875rem;
    }

    .templates-section {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .templates-header {
        font-weight: 600;
        margin-bottom: 15px;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .template-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 15px;
    }

    .template-card {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .template-card:hover {
        border-color: #4f46e5;
        background: #f0f9ff;
        transform: translateY(-2px);
    }

    .template-title {
        font-weight: 600;
        margin-bottom: 5px;
        color: #374151;
    }

    .template-description {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 10px;
    }

    .template-code {
        background: #1f2937;
        color: #e5e7eb;
        padding: 10px;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        white-space: pre-wrap;
        overflow-x: auto;
    }

    .sql-validation {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
        display: none;
    }

    .sql-validation.show {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    .validation-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .validation-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: white;
    }

    .validation-success {
        border-color: #10b981;
        background: #f0fdf4;
    }

    .validation-success .validation-icon {
        background: #10b981;
    }

    .validation-error {
        border-color: #ef4444;
        background: #fef2f2;
    }

    .validation-error .validation-icon {
        background: #ef4444;
    }

    .validation-warning {
        border-color: #f59e0b;
        background: #fefbf2;
    }

    .validation-warning .validation-icon {
        background: #f59e0b;
    }

    .validation-details {
        margin-top: 10px;
    }

    .validation-list {
        list-style: none;
        padding-left: 0;
    }

    .validation-list li {
        padding: 5px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .validation-list li:before {
        content: "•";
        color: #6b7280;
        font-weight: bold;
    }

    .sql-tips {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 15px;
        border-radius: 0 8px 8px 0;
        margin-top: 20px;
    }

    .sql-tips h6 {
        color: #92400e;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .sql-tips ul {
        margin-bottom: 0;
        color: #92400e;
    }

    .sql-tips ul li {
        margin-bottom: 5px;
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

    @media (max-width: 768px) {
        .wizard-content {
            padding: 25px;
        }

        .template-grid {
            grid-template-columns: 1fr;
        }

        .sql-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .sql-toolbar .btn {
            justify-content: center;
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
        <div class="wizard-title">📝 วาง SQL Scripts</div>
        <div class="wizard-subtitle">สร้าง SQL Query สำหรับดึงข้อมูลที่ต้องการแจ้งเตือน</div>

        <!-- Step Indicator -->
        <div class="step-indicator">
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
            <div class="step"></div>
        </div>
    </div>

    <!-- Wizard Content -->
    <div class="wizard-content">
        <!-- Step 4: SQL Scripts -->
        <div class="section-title">
            <div class="section-icon">4</div>
            วาง SQL Scripts
        </div>

        <!-- SQL Templates Section -->
        <div class="templates-section">
            <div class="templates-header">
                <i class="fas fa-file-code"></i>
                เทมเพลต SQL ตัวอย่าง
            </div>

            <div class="template-grid">
                <div class="template-card" onclick="loadTemplate('system_alerts')">
                    <div class="template-title">🚨 การแจ้งเตือนระบบ</div>
                    <div class="template-description">ตรวจหาการแจ้งเตือนที่ยังไม่ได้จัดการ</div>
                    <div class="template-code">SELECT alert_id, alert_type, message, created_at
                        FROM system_alerts
                        WHERE status = 'pending'
                        AND priority = 'high'</div>
                </div>

                <div class="template-card" onclick="loadTemplate('user_activity')">
                    <div class="template-title">👥 กิจกรรมผู้ใช้</div>
                    <div class="template-description">ติดตามกิจกรรมผู้ใช้ที่ผิดปกติ</div>
                    <div class="template-code">SELECT user_id, username, action, ip_address
                        FROM user_logs
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                        AND action IN ('failed_login', 'suspicious')</div>
                </div>

                <div class="template-card" onclick="loadTemplate('performance')">
                    <div class="template-title">⚡ ประสิทธิภาพระบบ</div>
                    <div class="template-description">ตรวจสอบประสิทธิภาพและทรัพยากร</div>
                    <div class="template-code">SELECT server_name, cpu_usage, memory_usage
                        FROM server_stats
                        WHERE (cpu_usage > 90 OR memory_usage > 85)
                        AND measured_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)</div>
                </div>

                <div class="template-card" onclick="loadTemplate('custom')">
                    <div class="template-title">✏️ กำหนดเอง</div>
                    <div class="template-description">เขียน SQL Query เอง</div>
                    <div class="template-code">-- เขียน SQL Query ของคุณเอง
                        SELECT * FROM your_table
                        WHERE your_condition = 'your_value'</div>
                </div>
            </div>
        </div>

        <!-- SQL Editor -->
        <div class="sql-editor-container">
            <div class="sql-editor-header">
                <div class="editor-label">
                    <i class="fas fa-code"></i>
                    SQL Query Editor
                </div>
            </div>

            <div class="editor-info">
                วาง SQL Query ที่ต้องการใช้สำหรับการแจ้งเตือน (รองรับเฉพาะ SELECT statement เท่านั้น)
            </div>

            <div class="sql-editor">
                <textarea class="sql-textarea" id="sqlQuery" name="sql_query"
                    placeholder="-- วาง SQL Query ของคุณที่นี่
SELECT 
    column1,
    column2,
    column3
FROM your_table 
WHERE your_condition = 'value'
ORDER BY column1 DESC;"
                    required>-- ตัวอย่าง SQL สำหรับการแจ้งเตือน
SELECT 
    employee_id,
    employee_name,
    department,
    alert_type,
    message,
    created_at
FROM system_alerts 
WHERE 
    status = 'pending' 
    AND priority = 'high'
    AND created_at >= CURDATE()
ORDER BY created_at DESC;</textarea>

                <div class="sql-toolbar">
                    <button type="button" class="btn btn-info btn-sm" onclick="formatSQL()">
                        <i class="fas fa-magic"></i>
                        Format SQL
                    </button>

                    <button type="button" class="btn btn-warning btn-sm" onclick="validateSQL()">
                        <i class="fas fa-check-circle"></i>
                        Validate SQL
                    </button>

                    <button type="button" class="btn btn-success btn-sm" onclick="testSQL()">
                        <i class="fas fa-play"></i>
                        Test Query
                    </button>

                    <button type="button" class="btn btn-secondary btn-sm" onclick="clearSQL()">
                        <i class="fas fa-trash"></i>
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- SQL Validation Results -->
        <div class="sql-validation" id="sqlValidation">
            <div class="validation-header">
                <div class="validation-icon">
                    <i class="fas fa-check" id="validationIcon"></i>
                </div>
                <div>
                    <strong id="validationTitle">SQL Validation Results</strong>
                    <div id="validationSummary" style="font-size: 0.875rem; color: #6b7280;"></div>
                </div>
            </div>

            <div class="validation-details" id="validationDetails">
                <ul class="validation-list" id="validationList">
                </ul>
            </div>
        </div>

        <!-- SQL Tips -->
        <div class="sql-tips">
            <h6>
                <i class="fas fa-lightbulb me-1"></i>
                เคล็ดลับการเขียน SQL สำหรับการแจ้งเตือน
            </h6>
            <ul>
                <li><strong>ใช้ WHERE clause:</strong> กรองข้อมูลที่ต้องการแจ้งเตือนเท่านั้น</li>
                <li><strong>ระบุ Date Range:</strong> ใช้ CURDATE(), NOW() หรือ DATE_SUB() เพื่อกรองตามวันที่</li>
                <li><strong>จำกัดจำนวนผลลัพธ์:</strong> ใช้ LIMIT เพื่อป้องกันข้อมูลมากเกินไป</li>
                <li><strong>ใช้ Column Alias:</strong> ตั้งชื่อคอลัมน์ให้เข้าใจง่าย AS alias_name</li>
                <li><strong>ทดสอบก่อนบันทึก:</strong> คลิก Test Query เพื่อดูผลลัพธ์</li>
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
                ขั้นตอนที่ 4 จาก 14
            </div>

            <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">
                ถัดไป (กำหนดตัวแปร)
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>
</div>
