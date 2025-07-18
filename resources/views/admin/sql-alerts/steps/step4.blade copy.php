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
        justify-content: space-between;
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

    .validation-message.success {
        border-color: #10b981;
        background: #f0fdf4;
    }

    .validation-message.success .validation-icon {
        background: #10b981;
    }

    .validation-message.error {
        border-color: #ef4444;
        background: #fef2f2;
    }

    .validation-message.error .validation-icon {
        background: #ef4444;
    }

    .validation-message.warning {
        border-color: #f59e0b;
        background: #fefbf2;
    }

    .validation-message.warning .validation-icon {
        background: #f59e0b;
    }

    .validation-message.info {
        border-color: #06b6d4;
        background: #f0f9ff;
    }

    .validation-message.info .validation-icon {
        background: #06b6d4;
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

    .query-results-table {
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

    .validation-note {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 15px;
        border-radius: 0 8px 8px 0;
        margin-top: 20px;
    }

    .validation-note h6 {
        color: #92400e;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .validation-note ul {
        margin-bottom: 0;
        color: #92400e;
    }

    .validation-note ul li {
        margin-bottom: 5px;
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
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
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
                <textarea class="sql-textarea" 
                          id="sqlQuery" 
                          name="sql_query" 
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

        <!-- Query Results Table -->
        <div class="query-results-table" id="queryResultsTable" style="display: none;">
            <div class="predefined-header">
                <i class="fas fa-table"></i>
                ตัวอย่างข้อมูลจาก SQL Query (แสดงสูงสุด 10 คอลัมน์ × 10 แถว)
            </div>
            
            <div id="queryDataContainer">
                <!-- ตารางจะถูกสร้างที่นี่ -->
            </div>
            
            <div class="row mt-3" id="queryStats">
                <!-- สถิติข้อมูลจะถูกสร้างที่นี่ -->
            </div>
            
            <div class="validation-note">
                <h6>
                    <i class="fas fa-table me-1"></i>
                    หมายเหตุเกี่ยวกับข้อมูลตัวอย่าง
                </h6>
                <ul>
                    <li><strong>การแสดงผล:</strong> แสดงเฉพาะ 10 คอลัมน์แรก และ 10 แถวแรก เพื่อความสะดวกในการดู</li>
                    <li><strong>ข้อมูลจริง:</strong> Query ของคุณจะมีข้อมูลครบถ้วนตามที่ระบุ</li>
                    <li><strong>ตัวแปร:</strong> สามารถใช้ <code>{{ '{' }}{{ '{' }}record_count{{ '}' }}{{ '}' }}</code> เพื่อแสดงจำนวนแถวทั้งหมด</li>
                    <li><strong>ภาษาไทย:</strong> ข้อมูลจะแสดงภาษาไทยได้ถูกต้อง</li>
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

<script>
// SQL Templates
const sqlTemplates = {
    'system_alerts': `-- System Alerts Template
SELECT 
    id,
    alert_type,
    title,
    message,
    severity,
    created_at,
    status
FROM system_alerts 
WHERE 
    status IN ('pending', 'new')
    AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY severity DESC, created_at DESC
LIMIT 100;`,

    'user_activity': `-- User Activity Template
SELECT 
    u.id,
    u.name,
    u.email,
    COUNT(al.id) as login_count,
    MAX(al.created_at) as last_login,
    SUM(CASE WHEN al.action = 'failed_login' THEN 1 ELSE 0 END) as failed_attempts
FROM users u
LEFT JOIN activity_logs al ON u.id = al.user_id
WHERE 
    al.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    AND al.action IN ('login', 'failed_login')
GROUP BY u.id, u.name, u.email
HAVING failed_attempts > 3
ORDER BY failed_attempts DESC;`,

    'performance': `-- Performance Monitoring Template
SELECT 
    DATE(created_at) as date,
    AVG(response_time) as avg_response_time,
    MAX(response_time) as max_response_time,
    COUNT(*) as total_requests,
    SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as server_errors,
    SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) as client_errors
FROM request_logs
WHERE 
    created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
HAVING 
    avg_response_time > 1000 
    OR server_errors > 10
ORDER BY date DESC;`,

    'custom': `-- Custom Query Template
SELECT 
    id,
    name,
    status,
    created_at
FROM orders 
WHERE 
    order_status = 'pending'
    AND created_at <= DATE_SUB(NOW(), INTERVAL 2 DAY)
ORDER BY created_at ASC;`
};

// Initialize step 4
function initStep4() {
    console.log('Initializing Step 4...');
    loadSavedSQL();
    setupAutoSave();
}

function loadSavedSQL() {
    const savedSQL = sessionStorage.getItem('sql_alert_query');
    const sqlTextarea = document.getElementById('sqlQuery');
    
    if (savedSQL && sqlTextarea) {
        sqlTextarea.value = savedSQL;
        console.log('Loaded saved SQL query');
    }
}

function setupAutoSave() {
    const sqlTextarea = document.getElementById('sqlQuery');
    
    if (sqlTextarea) {
        // Auto-save SQL on change
        sqlTextarea.addEventListener('input', function() {
            sessionStorage.setItem('sql_alert_query', this.value);
        });
        
        console.log('Auto-save setup complete');
    }
}

// Global functions
function loadTemplate(templateName) {
    const template = sqlTemplates[templateName];
    const sqlTextarea = document.getElementById('sqlQuery');
    
    if (template && sqlTextarea) {
        sqlTextarea.value = template;
        sessionStorage.setItem('sql_alert_query', template);
        
        // Clear any existing validation
        hideValidation();
        
        console.log(`Loaded template: ${templateName}`);
    }
}

function formatSQL() {
    const sqlTextarea = document.getElementById('sqlQuery');
    
    if (!sqlTextarea) {
        console.error('SQL textarea not found');
        return;
    }
    
    let sql = sqlTextarea.value;
    
    if (!sql.trim()) {
        showValidation('warning', 'No SQL to format', 'Please enter SQL query first.');
        return;
    }
    
    // Basic SQL formatting
    sql = sql.replace(/\s+/g, ' '); // Remove extra spaces
    sql = sql.replace(/\bSELECT\b/gi, '\nSELECT');
    sql = sql.replace(/\bFROM\b/gi, '\nFROM');
    sql = sql.replace(/\bWHERE\b/gi, '\nWHERE');
    sql = sql.replace(/\bAND\b/gi, '\n    AND');
    sql = sql.replace(/\bOR\b/gi, '\n    OR');
    sql = sql.replace(/\bORDER BY\b/gi, '\nORDER BY');
    sql = sql.replace(/\bGROUP BY\b/gi, '\nGROUP BY');
    sql = sql.replace(/\bHAVING\b/gi, '\nHAVING');
    sql = sql.replace(/\bLIMIT\b/gi, '\nLIMIT');
    sql = sql.replace(/\bUNION\b/gi, '\nUNION');
    sql = sql.replace(/\bJOIN\b/gi, '\nJOIN');
    sql = sql.replace(/\bINNER JOIN\b/gi, '\nINNER JOIN');
    sql = sql.replace(/\bLEFT JOIN\b/gi, '\nLEFT JOIN');
    sql = sql.replace(/\bRIGHT JOIN\b/gi, '\nRIGHT JOIN');
    sql = sql.replace(/\bFULL JOIN\b/gi, '\nFULL JOIN');
    
    // Clean up extra newlines
    sql = sql.replace(/\n\s*\n/g, '\n');
    sql = sql.trim();
    
    sqlTextarea.value = sql;
    
    // Save formatted SQL
    sessionStorage.setItem('sql_alert_query', sql);
    
    showValidation('success', 'SQL Formatted', 'Your SQL query has been formatted successfully.');
}

function validateSQL() {
    const sqlTextarea = document.getElementById('sqlQuery');
    
    if (!sqlTextarea) {
        console.error('SQL textarea not found');
        return;
    }
    
    const sql = sqlTextarea.value.trim();
    
    if (!sql) {
        showValidation('error', 'No SQL Query', 'Please enter SQL query to validate.');
        return;
    }
    
    // Basic SQL validation
    const errors = [];
    const warnings = [];
    
    // Check for required keywords
    if (!sql.toLowerCase().includes('select')) {
        errors.push('Query must contain SELECT statement');
    }
    
    // Check for dangerous keywords
    const dangerousKeywords = ['drop', 'delete', 'update', 'insert', 'create', 'alter', 'truncate'];
    const sqlLower = sql.toLowerCase();
    
    dangerousKeywords.forEach(keyword => {
        if (sqlLower.includes(keyword)) {
            errors.push(`Dangerous keyword detected: ${keyword.toUpperCase()}`);
        }
    });
    
    // Check for semicolon
    if (sql.trim().endsWith(';')) {
        warnings.push('Semicolon (;) will be removed during execution for database compatibility');
    }
    
    // Check for basic syntax
    const openParens = (sql.match(/\(/g) || []).length;
    const closeParens = (sql.match(/\)/g) || []).length;
    
    if (openParens !== closeParens) {
        errors.push('Mismatched parentheses');
    }
    
    // Check database-specific syntax
    const dbType = sessionStorage.getItem('sql_alert_db_type') || 'mysql';
    
    if (dbType === 'oracle') {
        if (sql.toLowerCase().includes('limit')) {
            warnings.push('Oracle uses ROWNUM instead of LIMIT - this will be converted automatically');
        }
    }
    
    // Show results
    if (errors.length > 0) {
        showValidation('error', 'Validation Failed', 'Found issues in your SQL query:', errors.concat(warnings));
    } else if (warnings.length > 0) {
        showValidation('warning', 'Validation Passed with Warnings', 'Your SQL query is valid but has warnings:', warnings);
    } else {
        showValidation('success', 'Validation Passed', 'Your SQL query looks good!');
    }
}

function testSQL() {
    const sqlTextarea = document.getElementById('sqlQuery');
    
    if (!sqlTextarea) {
        console.error('SQL textarea not found');
        return;
    }
    
    const sql = sqlTextarea.value.trim();
    
    if (!sql) {
        showValidation('error', 'No SQL Query', 'Please enter SQL query to test.');
        return;
    }
    
    // Show loading state
    showValidation('info', 'Testing SQL Query', 'Please wait while we test your query...');
    
    const dbType = sessionStorage.getItem('sql_alert_db_type') || 'mysql';
    
    // Database info mapping
    const dbDriverMap = {
        mysql: 'mysql',
        mariadb: 'mysql',
        postgresql: 'pgsql',
        sqlserver: 'sqlsrv',
        oracle: 'oracle',
        sqlite: 'sqlite'
    };
    
    // Get connection data
    const connectionData = {
        driver: dbDriverMap[dbType] || 'mysql',
        host: sessionStorage.getItem('sql_alert_db_host') || 'localhost',
        port: parseInt(sessionStorage.getItem('sql_alert_db_port') || '3306'),
        database: sessionStorage.getItem('sql_alert_db_name') || '',
        username: sessionStorage.getItem('sql_alert_db_username') || '',
        password: sessionStorage.getItem('sql_alert_db_password') || ''
    };
    
    console.log('Testing SQL with connection data:', connectionData);
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
        showValidation('error', 'CSRF Token Missing', 'Security token not found. Please refresh the page.');
        return;
    }
    
    // Test the query
    fetch('{{ route("admin.sql-alerts.execute-query") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            sql_query: sql,
            database_config: connectionData
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
        }
        return response.json();
    })
    .then(data => {
        console.log('Query test result:', data);
        if (data.success) {
            const recordsCount = data.data?.records_count || 0;
            const columns = data.data?.columns || [];
            const sampleData = data.data?.sample_data || [];
            
            console.log('Records count:', recordsCount);
            console.log('Columns:', columns);
            console.log('Sample data:', sampleData);
            
            // **บันทึกข้อมูลลง sessionStorage สำหรับ step 5**
            sessionStorage.setItem('sql_alert_connection', JSON.stringify(connectionData));
            sessionStorage.setItem('sql_alert_query_result', JSON.stringify(data));
            sessionStorage.setItem('sql_alert_query_tested', '1');
            
            // **เพิ่มการบันทึกข้อมูลสำหรับ step 8**
            const queryResultsForStep8 = {
                columns: columns,
                rows: sampleData,
                totalRows: recordsCount,
                executionTime: data.data?.execution_time || 0,
                querySize: sql.length
            };
            sessionStorage.setItem('sql_alert_query_results', JSON.stringify(queryResultsForStep8));
            
            let resultMessage = `Query executed successfully! Found ${recordsCount} records.`;
            
            showValidation('success', 'Query Success', resultMessage, [
                `Rows returned: ${recordsCount}`,
                `Columns: ${columns.length}`,
                `Sample data available: ${sampleData.length} rows`
            ]);

            // Display query results if available
            if (sampleData.length > 0) {
                displayQueryResults(recordsCount, columns, sampleData);
            }
        } else {
            showValidation('error', 'Query Failed', data.message || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('SQL test error:', error);
        let errorMessage = 'Query test failed';
        
        if (error.message) {
            errorMessage = error.message;
        } else if (error.errors) {
            const firstError = Object.values(error.errors)[0];
            errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
        }
        
        showValidation('error', 'Query Test Failed', errorMessage);
    });
}

function displayQueryResults(totalRows, columns, sampleData) {
    const resultsContainer = document.getElementById('queryResultsTable');
    const dataContainer = document.getElementById('queryDataContainer');
    const statsContainer = document.getElementById('queryStats');
    
    if (!sampleData || sampleData.length === 0) {
        dataContainer.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Query สำเร็จแต่ไม่มีข้อมูลในผลลัพธ์
            </div>
        `;
        resultsContainer.style.display = 'block';
        return;
    }
    
    const displayColumns = columns.slice(0, 10);
    const displayRows = sampleData.slice(0, 10);
    
    let tableHtml = `
        <div class="table-responsive" style="margin-top: 20px;">
            <table class="table table-bordered table-striped" style="font-size: 0.85rem;">
                <thead style="background-color: #4f46e5; color: white;">
                    <tr>
    `;
    
    // สร้าง header
    displayColumns.forEach(column => {
        tableHtml += `<th style="padding: 8px; max-width: 150px; word-wrap: break-word;">${column}</th>`;
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
    
    dataContainer.innerHTML = tableHtml;
    
    // สร้างสถิติข้อมูล
    statsContainer.innerHTML = `
        <div class="col-md-4">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>จำนวนแถวทั้งหมด:</strong> ${totalRows} แถว
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
                <strong>ข้อมูลแสดง:</strong> ${displayRows.length} แถว${totalRows > 10 ? ` (จากทั้งหมด ${totalRows} แถว)` : ''}
            </div>
        </div>
    `;
    
    resultsContainer.style.display = 'block';
}

function clearSQL() {
    const sqlTextarea = document.getElementById('sqlQuery');
    
    if (sqlTextarea) {
        if (confirm('Are you sure you want to clear the SQL query?')) {
            sqlTextarea.value = '';
            sessionStorage.removeItem('sql_alert_query');
            hideValidation();
            document.getElementById('queryResultsTable').style.display = 'none';
        }
    }
}

function showValidation(type, title, summary, items = []) {
    const validationDiv = document.getElementById('sqlValidation');
    
    if (!validationDiv) {
        console.error('Validation div not found');
        return;
    }
    
    // Set class based on type
    validationDiv.className = `sql-validation validation-message ${type} show`;
    
    // Update content
    const titleElement = document.getElementById('validationTitle');
    const summaryElement = document.getElementById('validationSummary');
    const listElement = document.getElementById('validationList');
    
    if (titleElement) titleElement.textContent = title;
    if (summaryElement) summaryElement.textContent = summary;
    
    // Update list
    if (listElement) {
        listElement.innerHTML = '';
        
        if (Array.isArray(items)) {
            items.forEach(item => {
                const li = document.createElement('li');
                li.textContent = item;
                listElement.appendChild(li);
            });
        }
    }
    
    // Auto-hide สำหรับ info messages
    if (type === 'info') {
        setTimeout(() => {
            hideValidation();
        }, 3000);
    }
}

function hideValidation() {
    const validationDiv = document.getElementById('sqlValidation');
    
    if (validationDiv) {
        validationDiv.classList.remove('show');
    }
}

// Navigation functions
function previousStep() {
    // Use the global wizard navigation
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.previousStep();
    } else {
        // Fallback
        window.location.href = '{{ route("admin.sql-alerts.create") }}?step=3';
    }
}

function nextStep() {
    const sqlQuery = document.getElementById('sqlQuery').value.trim();
    
    if (!sqlQuery) {
        showValidation('error', 'SQL Query Required', 'Please enter your SQL query before proceeding.');
        return;
    }
    
    // Save to session
    sessionStorage.setItem('sql_alert_query', sqlQuery);
    
    // Use the global wizard navigation
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.nextStep();
    } else {
        // Fallback
        window.location.href = '{{ route("admin.sql-alerts.create") }}?step=5';
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    initStep4();
});

// For global access
window.loadTemplate = loadTemplate;
window.formatSQL = formatSQL;
window.validateSQL = validateSQL;
window.testSQL = testSQL;
window.clearSQL = clearSQL;
window.initStep4 = initStep4;
window.initializeCurrentStep = initStep4;
window.previousStep = previousStep;
window.nextStep = nextStep;

console.log('Step 4 script loaded');
</script>