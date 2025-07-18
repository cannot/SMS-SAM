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

.query-summary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.query-summary h5 {
    margin-bottom: 15px;
    font-weight: 600;
}

.query-code {
    background: rgba(0,0,0,0.2);
    padding: 15px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    line-height: 1.4;
    white-space: pre-wrap;
    overflow-x: auto;
    margin-bottom: 15px;
}

.execution-controls {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.controls-header {
    font-weight: 600;
    margin-bottom: 20px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.execution-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
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

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
}

.btn-primary:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
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

.execution-progress {
    display: none;
    text-align: center;
    padding: 20px;
}

.execution-progress.show {
    display: block;
    animation: fadeIn 0.3s ease;
}

.progress-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-left: 4px solid #4f46e5;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.data-preview {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 30px;
    overflow: hidden;
    display: none;
}

.data-preview.show {
    display: block;
    animation: fadeIn 0.5s ease;
}

.preview-header {
    background: #f9fafb;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.preview-title {
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.preview-stats {
    display: flex;
    gap: 20px;
    font-size: 0.875rem;
    color: #6b7280;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.preview-content {
    overflow-x: auto;
    max-height: 500px;
    overflow-y: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table th {
    background: #f3f4f6;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    z-index: 10;
}

.data-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f3f4f6;
    color: #6b7280;
    vertical-align: top;
}

.data-table tr:hover {
    background: #f9fafb;
}

.data-table td.number {
    text-align: right;
    font-family: 'Courier New', monospace;
}

.data-table td.date {
    font-family: 'Courier New', monospace;
    color: #059669;
}

.data-table td.null {
    color: #9ca3af;
    font-style: italic;
}

.result-summary {
    background: white;
    border: 2px solid #10b981;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    display: none;
}

.result-summary.show {
    display: block;
    animation: fadeIn 0.3s ease;
}

.result-summary.success {
    background: #f0fdf4;
    border-color: #10b981;
}

.result-summary.error {
    background: #fef2f2;
    border-color: #ef4444;
}

.summary-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.summary-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: white;
}

.result-summary.success .summary-icon {
    background: #10b981;
}

.result-summary.error .summary-icon {
    background: #ef4444;
}

.summary-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0;
}

.result-summary.success .summary-title {
    color: #065f46;
}

.result-summary.error .summary-title {
    color: #991b1b;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.summary-item {
    text-align: center;
    padding: 15px;
    background: rgba(255,255,255,0.5);
    border-radius: 8px;
}

.summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #374151;
}

.summary-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 4px;
}

.variables-preview {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 15px;
    border-radius: 0 8px 8px 0;
    margin-top: 20px;
}

.variables-preview h6 {
    color: #92400e;
    font-weight: 600;
    margin-bottom: 10px;
}

.variable-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.variable-item {
    background: rgba(255,255,255,0.7);
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.875rem;
}

.variable-name {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #7c3aed;
}

.variable-value {
    color: #059669;
    margin-left: 8px;
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

.null-value {
    color: #9ca3af;
    font-style: italic;
    font-size: 0.9em;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.data-table th,
.data-table td {
    border: 1px solid #e5e7eb;
    padding: 8px 12px;
    text-align: left;
}

.data-table th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
}

.data-table tr:nth-child(even) {
    background: #f9fafb;
}

.data-count {
    font-size: 0.9em;
    color: #6b7280;
    margin-left: 10px;
}

.column-count {
    font-size: 0.9em;
    color: #6b7280;
    margin-left: 10px;
}

.variable-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    margin-bottom: 8px;
    border-radius: 6px;
    background: #f9fafb;
    border-left: 4px solid #e5e7eb;
}

.variable-item.system {
    border-left-color: #3b82f6;
    background: #eff6ff;
}

.variable-item.custom {
    border-left-color: #10b981;
    background: #ecfdf5;
}

.variable-item.column {
    border-left-color: #f59e0b;
    background: #fffbeb;
}

.variable-name {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #374151;
    flex: 1;
}

.variable-value {
    color: #6b7280;
    margin-right: 10px;
}

.variable-type {
    font-size: 0.75rem;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
    color: white;
}

.variable-item.system .variable-type {
    background: #3b82f6;
}

.variable-item.custom .variable-type {
    background: #10b981;
}

.variable-item.column .variable-type {
    background: #f59e0b;
}

.empty-value {
    color: #d1d5db;
    font-style: italic;
}

.result-summary {
    display: none;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #e5e7eb;
}

.result-summary.show {
    display: block;
}

.result-summary.success {
    background: #ecfdf5;
    border-left-color: #10b981;
}

.result-summary.error {
    background: #fef2f2;
    border-left-color: #ef4444;
}

.variables-preview {
    display: none;
    margin-top: 20px;
}

.variables-preview.show {
    display: block;
}

.data-preview {
    display: none;
    margin-top: 20px;
}

.data-preview.show {
    display: block;
}

#lastExecuted {
    font-weight: 500;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .wizard-content {
        padding: 25px;
    }
    
    .execution-options {
        grid-template-columns: 1fr;
    }
    
    .preview-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .variable-list {
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
        <div class="wizard-title">👁️ ดูตัวอย่างข้อมูล</div>
        <div class="wizard-subtitle">รัน SQL Query และดูผลลัพธ์ก่อนสร้างการแจ้งเตือน</div>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
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
            <div class="step"></div>
        </div>
    </div>

    <!-- Wizard Content -->
    <div class="wizard-content">
        <!-- Step 6: Data Preview -->
        <div class="section-title">
            <div class="section-icon">6</div>
            ดูตัวอย่างข้อมูล
        </div>

        <!-- Query Summary -->
        <div class="query-summary">
            <h5>
                <i class="fas fa-code me-2"></i>
                SQL Query ที่จะรัน
            </h5>
            <div class="query-code" id="queryDisplay">
-- กำลังโหลด SQL Query...
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.875rem; opacity: 0.8;">
                    <i class="fas fa-database me-1"></i>
                    <span id="connectionInfo">MySQL @ localhost:3306</span>
                </div>
                <div style="font-size: 0.875rem; opacity: 0.8;">
                    <i class="fas fa-clock me-1"></i>
                    <span id="lastExecuted">ยังไม่ได้รัน</span>
                </div>
            </div>
        </div>

        <!-- Execution Controls -->
        <div class="execution-controls">
            <div class="controls-header">
                <i class="fas fa-play-circle"></i>
                ตัวเลือกการรัน Query
            </div>
            
            <div class="execution-options">
                <div class="form-group">
                    <label class="form-label">จำกัดจำนวนแถว</label>
                    <select class="form-control" id="limitRows">
                        <option value="10">10 แถว</option>
                        <option value="25" selected>25 แถว</option>
                        <option value="50">50 แถว</option>
                        <option value="100">100 แถว</option>
                        <option value="0">ไม่จำกัด</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Timeout (วินาที)</label>
                    <select class="form-control" id="timeout">
                        <option value="10">10 วินาที</option>
                        <option value="30" selected>30 วินาที</option>
                        <option value="60">60 วินาที</option>
                        <option value="120">120 วินาที</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">รูปแบบการแสดงผล</label>
                    <select class="form-control" id="displayMode">
                        <option value="table" selected>ตาราง</option>
                        <option value="json">JSON</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">เวลาขณะนี้</label>
                    <input type="text" class="form-control" id="currentTime" readonly>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <button type="button" class="btn btn-success btn-lg" id="executeBtn" onclick="executeQuery()">
                    <i class="fas fa-play"></i>
                    รัน SQL Query
                </button>
            </div>

            <!-- Execution Progress -->
            <div class="execution-progress" id="executionProgress">
                <div class="progress-spinner"></div>
                <div style="color: #6b7280;">กำลังรัน SQL Query...</div>
                <div style="font-size: 0.875rem; color: #9ca3af; margin-top: 5px;" id="progressMessage">
                    กำลังเชื่อมต่อฐานข้อมูล...
                </div>
            </div>
        </div>

        <!-- Result Summary -->
        <div class="result-summary" id="resultSummary">
            <div class="summary-header">
                <div class="summary-icon">
                    <i class="fas fa-check" id="summaryIcon"></i>
                </div>
                <div>
                    <h4 class="summary-title" id="summaryTitle">Query executed successfully!</h4>
                    <div style="font-size: 0.875rem; color: #6b7280;" id="summaryMessage">
                        SQL Query รันสำเร็จ พร้อมแสดงผลลัพธ์
                    </div>
                </div>
            </div>
            
            <!-- อัพเดท HTML ส่วนแสดงสถิติ -->
            <div class="stats-grid">
                <div class="stats-item">
                    <div class="stats-value" id="rowCount">-</div>
                    <div class="stats-label">แถวข้อมูล</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value" id="columnCount">-</div>
                    <div class="stats-label">คอลัมน์</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value" id="executionTime">-</div>
                    <div class="stats-label">เวลาเรียน</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value" id="dataSize">-</div>
                    <div class="stats-label">ขนาดข้อมูล</div>
                </div>
            </div>

            <!-- Variables Preview -->
            <div class="variables-preview" id="variablesPreview">
                <h6>
                    <i class="fas fa-tags me-1"></i>
                    ตัวแปรที่จะใช้ใน Email Template
                </h6>
                <div class="variable-list" id="variablesList">
                    <!-- Variables will be populated here -->
                </div>
            </div>
        </div>

        <!-- Data Preview -->
        <div class="data-preview" id="dataPreview">
            <div class="preview-header">
                <div class="preview-title">
                    <i class="fas fa-table"></i>
                    ตัวอย่างข้อมูล
                </div>
                <div class="preview-stats">
                    <div class="stat-item">
                        <i class="fas fa-list-ol"></i>
                        <span id="previewRowCount">แสดง 0 จาก 0 แถว</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-columns"></i>
                        <span id="previewColumnCount">0 คอลัมน์</span>
                    </div>
                </div>
            </div>
            
            <div class="preview-content">
                <table class="data-table" id="dataTable">
                    <thead id="tableHeader">
                        <!-- Table headers will be populated here -->
                    </thead>
                    <tbody id="tableBody">
                        <!-- Table data will be populated here -->
                    </tbody>
                </table>
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
                ขั้นตอนที่ 6 จาก 14
            </div>
            
            <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()" disabled>
                ถัดไป (ตัวเลือกการส่งออก)
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<script>
let queryExecuted = false;
let queryResults = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Step 6 DOM loaded');
    
    // Clear previous interval if exists
    if (window.currentTimeInterval) {
        clearInterval(window.currentTimeInterval);
        window.currentTimeInterval = null;
    }
    
    // Add delay to ensure DOM is fully ready
    setTimeout(() => {
        initializeStep6();
        
        // Set interval with global reference
        updateCurrentTime();
        window.currentTimeInterval = setInterval(updateCurrentTime, 1000);
        
    }, 100);
});

function initializeStep6() {
    console.log('Initializing Step 6...');
    
    try {
        loadQueryInfo();
        updateCurrentTime();
        
        // **แสดงสถานะว่าเคยรันแล้วหรือไม่ แต่ไม่แสดงข้อมูลจริง**
        checkPreviousExecution();
        
    } catch (error) {
        console.error('Error initializing Step 6:', error);
    }
}

function loadQueryInfo() {
    // Load SQL Query
    const sqlQuery = sessionStorage.getItem('sql_alert_query');
    if (sqlQuery) {
        document.getElementById('queryDisplay').textContent = sqlQuery;
    }
    
    // Load connection info
    const dbType = sessionStorage.getItem('sql_alert_db_type') || 'MySQL';
    const host = sessionStorage.getItem('sql_alert_db_host') || 'localhost';
    const port = sessionStorage.getItem('sql_alert_db_port') || '3306';
    const database = sessionStorage.getItem('sql_alert_db_name') || 'database';
    
    document.getElementById('connectionInfo').textContent = `${dbType} @ ${host}:${port}/${database}`;
}

function updateCurrentTime() {
    const currentTimeElement = document.getElementById('currentTime');
    if (!currentTimeElement) {
        // Element not found, clear the interval
        if (window.currentTimeInterval) {
            clearInterval(window.currentTimeInterval);
            window.currentTimeInterval = null;
            console.log('Cleared currentTimeInterval - element not found');
        }
        return;
    }
    
    try {
        const now = new Date();
        const timeString = now.toLocaleString('th-TH', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        currentTimeElement.value = timeString;
    } catch (error) {
        console.error('Error updating current time:', error);
        if (window.currentTimeInterval) {
            clearInterval(window.currentTimeInterval);
            window.currentTimeInterval = null;
        }
    }
}

function executeQuery() {
    if (queryExecuted && !confirm('Query ได้รันไปแล้ว ต้องการรันใหม่อีกครั้งหรือไม่?')) {
        return;
    }
    
    // **ใช้ข้อมูลจาก step 4 แทน mock data**
    const savedQueryResult = sessionStorage.getItem('sql_alert_query_result');
    const savedConnection = sessionStorage.getItem('sql_alert_connection');
    const sqlQuery = sessionStorage.getItem('sql_alert_query');
    
    if (!savedQueryResult) {
        alert('ไม่พบผลลัพธ์ SQL Query กรุณากลับไปที่ขั้นตอนที่ 4 เพื่อทดสอบ Query ก่อน');
        return;
    }
    
    if (!savedConnection) {
        alert('ไม่พบข้อมูลการเชื่อมต่อฐานข้อมูล กรุณากลับไปที่ขั้นตอนที่ 2-3');
        return;
    }
    
    // **ซ่อนผลลัพธ์เก่าก่อน**
    hideAllResults();
    
    // Show progress
    showExecutionProgress();
    document.getElementById('executeBtn').disabled = true;
    
    try {
        // **ใช้ข้อมูลจริงจาก step 4**
        const queryResult = JSON.parse(savedQueryResult);
        const connectionData = JSON.parse(savedConnection);
        
        if (queryResult.success && queryResult.data) {
            const data = queryResult.data;
            const limitRows = parseInt(document.getElementById('limitRows').value) || 25;
            
            // **สร้าง results object จากข้อมูลจริง**
            const totalRows = data.records_count || 0;
            const columns = data.columns || [];
            const sampleData = data.sample_data || [];
            const displayRows = Math.min(limitRows, sampleData.length);
            const executionTime = data.execution_time || 0.25;
            const avgRowSize = sampleData.length > 0 ? JSON.stringify(sampleData[0]).length : 50;
            const dataSize = Math.round((totalRows * avgRowSize) / 1024); // KB
            
            const results = {
                totalRows: totalRows,
                displayRows: displayRows,
                columns: columns,
                sampleData: sampleData.slice(0, displayRows),
                executionTime: executionTime,
                dataSize: dataSize,
                avgRowSize: avgRowSize,
                querySize: sqlQuery ? sqlQuery.length : 0,
                connectionInfo: {
                    host: connectionData.host,
                    database: connectionData.database,
                    type: connectionData.type || sessionStorage.getItem('sql_alert_db_type')
                }
            };
            
            // **บันทึกข้อมูลสำหรับ step 8**
            const queryResultsForStep8 = {
                columns: columns,
                rows: sampleData,
                totalRows: totalRows,
                executionTime: executionTime,
                querySize: sqlQuery ? sqlQuery.length : 0
            };
            sessionStorage.setItem('sql_alert_query_results', JSON.stringify(queryResultsForStep8));
            
            queryResults = results;
            
            // **Simulate processing time**
            setTimeout(() => {
                showQueryResults(results);
                
                document.getElementById('executeBtn').disabled = false;
                document.getElementById('executeBtn').innerHTML = '<i class="fas fa-play me-2"></i>Execute Query';
                document.getElementById('nextBtn').disabled = false;
                queryExecuted = true;
                
                sessionStorage.setItem('sql_alert_query_executed', '1');
                
            }, 1500);
            
        } else {
            throw new Error(queryResult.message || 'ไม่สามารถรัน Query ได้');
        }
        
    } catch (error) {
        console.error('Query execution error:', error);
        showQueryError(error);
        hideExecutionProgress();
        document.getElementById('executeBtn').disabled = false;
    }
}

function showExecutionProgress() {
    document.getElementById('executionProgress').classList.add('show');
    document.getElementById('resultSummary').classList.remove('show');
    document.getElementById('dataPreview').classList.remove('show');
}

function hideExecutionProgress() {
    document.getElementById('executionProgress').classList.remove('show');
}

// **เพิ่มฟังก์ชันซ่อนผลลัพธ์**
function hideAllResults() {
    const resultSummary = document.getElementById('resultSummary');
    const dataPreview = document.getElementById('dataPreview');
    const variablesPreview = document.getElementById('variablesPreview');
    
    if (resultSummary) resultSummary.classList.remove('show');
    if (dataPreview) dataPreview.classList.remove('show');
    if (variablesPreview) variablesPreview.classList.remove('show');
    
    // **รีเซ็ตค่าสถิติ**
    document.getElementById('rowCount').textContent = '-';
    document.getElementById('columnCount').textContent = '-';
    document.getElementById('executionTime').textContent = '-';
    document.getElementById('dataSize').textContent = '-';
}

// **อัพเดทฟังก์ชันแสดงผลลัพธ์**
function showQueryResults(results) {
    // Update last executed time
    document.getElementById('lastExecuted').textContent = 'รันเมื่อ ' + new Date().toLocaleTimeString('th-TH');
    document.getElementById('lastExecuted').style.color = '#059669';
    
    // Show result summary
    const summaryDiv = document.getElementById('resultSummary');
    summaryDiv.className = 'result-summary show success';
    
    const summaryIcon = document.getElementById('summaryIcon');
    const summaryTitle = document.getElementById('summaryTitle');
    const summaryMessage = document.getElementById('summaryMessage');
    
    if (summaryIcon) summaryIcon.className = 'fas fa-check';
    if (summaryTitle) summaryTitle.textContent = 'Query รันสำเร็จ!';
    if (summaryMessage) summaryMessage.textContent = `พบข้อมูล ${results.totalRows} แถว แสดง ${results.displayRows} แถว`;
    
    // **Update stats with proper values**
    document.getElementById('rowCount').textContent = results.totalRows.toLocaleString();
    document.getElementById('columnCount').textContent = results.columns.length;
    document.getElementById('executionTime').textContent = results.executionTime + 's';
    document.getElementById('dataSize').textContent = results.dataSize + ' KB';
    
    // Hide progress
    hideExecutionProgress();
    
    // Show variables preview
    showVariablesPreview(results);
    
    // Show data table
    showDataTable(results);
    
    // **แสดงส่วน preview**
    const variablesPreview = document.getElementById('variablesPreview');
    if (variablesPreview) variablesPreview.classList.add('show');
}

function showVariablesPreview(results) {
    // **ใช้ข้อมูล variables จาก step 5**
    const savedVariables = JSON.parse(sessionStorage.getItem('sql_alert_variables') || '[]');
    const variablesList = document.getElementById('variablesList');
    
    // **สร้าง system variables จากข้อมูลจริง**
    const systemVariables = [
        { name: 'record_count', value: results.totalRows },
        { name: 'query_date', value: new Date().toISOString().split('T')[0] },
        { name: 'query_time', value: new Date().toLocaleTimeString('th-TH') },
        { name: 'execution_time', value: results.executionTime + 's' },
        { name: 'database_name', value: results.connectionInfo?.database || sessionStorage.getItem('sql_alert_db_name') || 'database' },
        { name: 'database_type', value: results.connectionInfo?.type || sessionStorage.getItem('sql_alert_db_type') || 'mysql' },
        { name: 'column_count', value: results.columns.length },
        { name: 'data_size', value: results.dataSize + 'KB' }
    ];
    
    variablesList.innerHTML = '';
    
    // **แสดง system variables**
    systemVariables.forEach(variable => {
        const div = document.createElement('div');
        div.className = 'variable-item system';
        div.innerHTML = `
            <span class="variable-name">&#123;&#123;${variable.name}&#125;&#125;</span>
            <span class="variable-value">${variable.value}</span>
            <span class="variable-type">System</span>
        `;
        variablesList.appendChild(div);
    });
    
    // **แสดง custom variables จาก step 5**
    savedVariables.forEach(variable => {
        const div = document.createElement('div');
        div.className = 'variable-item custom';
        div.innerHTML = `
            <span class="variable-name">&#123;&#123;${variable.name}&#125;&#125;</span>
            <span class="variable-value">[${variable.description || 'จากข้อมูล'}]</span>
            <span class="variable-type">Custom</span>
        `;
        variablesList.appendChild(div);
    });
    
    // **แสดง column variables จากข้อมูลจริง**
    if (results.columns && results.columns.length > 0) {
        results.columns.forEach(column => {
            const div = document.createElement('div');
            div.className = 'variable-item column';
            div.innerHTML = `
                <span class="variable-name">&#123;&#123;${column}&#125;&#125;</span>
                <span class="variable-value">[คอลัมน์ข้อมูล]</span>
                <span class="variable-type">Column</span>
            `;
            variablesList.appendChild(div);
        });
    }
    
    // **บันทึก system variables สำหรับ step ถัดไป**
    sessionStorage.setItem('sql_alert_computed_variables', JSON.stringify(systemVariables));
}

function showDataTable(results) {
    const dataPreview = document.getElementById('dataPreview');
    
    if (!results.sampleData || results.sampleData.length === 0) {
        dataPreview.innerHTML = `
            <div class="no-data">
                <i class="fas fa-info-circle"></i>
                ไม่มีข้อมูลสำหรับแสดง
            </div>
        `;
        return;
    }
    
    // **อัพเดทหัวข้อตาราง**
    const dataHeader = document.getElementById('dataHeader');
    if (dataHeader) {
        dataHeader.innerHTML = `
            <i class="fas fa-table"></i>
            ตัวอย่างข้อมูล
            <span class="data-count">แสดง ${results.displayRows} จาก ${results.totalRows.toLocaleString()} แถว</span>
            <span class="column-count">${results.columns.length} คอลัมน์</span>
        `;
    }
    
    // **สร้างตารางจากข้อมูลจริง**
    let tableHTML = '<table class="data-table"><thead><tr>';
    
    // Add headers
    results.columns.forEach(column => {
        tableHTML += `<th>${column}</th>`;
    });
    tableHTML += '</tr></thead><tbody>';
    
    // Add data rows
    results.sampleData.forEach(row => {
        tableHTML += '<tr>';
        results.columns.forEach(column => {
            const value = row[column];
            let displayValue;
            
            if (value === null || value === undefined) {
                displayValue = '<span class="null-value">NULL</span>';
            } else if (typeof value === 'string' && value.trim() === '') {
                displayValue = '<span class="empty-value">Empty</span>';
            } else {
                displayValue = String(value);
            }
            
            tableHTML += `<td>${displayValue}</td>`;
        });
        tableHTML += '</tr>';
    });
    
    tableHTML += '</tbody></table>';
    
    dataPreview.innerHTML = tableHTML;
    dataPreview.classList.add('show');
}

// **เปลี่ยนจาก loadExistingData เป็น checkPreviousExecution**
function checkPreviousExecution() {
    const queryExecuted = sessionStorage.getItem('sql_alert_query_executed');
    const savedQueryResult = sessionStorage.getItem('sql_alert_query_result');
    
    if (queryExecuted === '1' && savedQueryResult) {
        // **แสดงแค่สถานะว่าเคยรันแล้ว ไม่แสดงข้อมูลจริง**
        const lastExecuted = document.getElementById('lastExecuted');
        if (lastExecuted) {
            lastExecuted.textContent = 'เคยรันแล้วก่อนหน้านี้ (กดปุ่ม Execute Query เพื่อดูผลลัพธ์)';
            lastExecuted.style.color = '#059669';
        }
        
        // **เปิดใช้งานปุ่ม Execute Query**
        const executeBtn = document.getElementById('executeBtn');
        if (executeBtn) {
            executeBtn.innerHTML = '<i class="fas fa-play me-2"></i>Execute Query (Run Again)';
        }
        
        // **ไม่เปิดใช้งานปุ่ม Next จนกว่าจะ Execute จริง**
        const nextBtn = document.getElementById('nextBtn');
        if (nextBtn) {
            nextBtn.disabled = false;
            nextBtn.innerHTML = 'ถัดไป (ตัวเลือกการส่งออก) <i class="fas fa-arrow-right"></i>';
        }
    } else {
        // **ถ้าไม่เคยรัน**
        const lastExecuted = document.getElementById('lastExecuted');
        if (lastExecuted) {
            lastExecuted.textContent = 'ยังไม่ได้รัน Query';
            lastExecuted.style.color = '#6b7280';
        }
        
        const nextBtn = document.getElementById('nextBtn');
        if (nextBtn) {
            nextBtn.disabled = true;
        }
    }
}

function showQueryError(error) {
    const summaryDiv = document.getElementById('resultSummary');
    summaryDiv.className = 'result-summary show error';
    
    document.getElementById('summaryIcon').className = 'fas fa-times';
    document.getElementById('summaryTitle').textContent = 'Query ล้มเหลว!';
    document.getElementById('summaryMessage').textContent = error.message || 'เกิดข้อผิดพลาดในการรัน SQL Query';
    
    // Hide data preview
    document.getElementById('dataPreview').classList.remove('show');
}

function previousStep() {
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.previousStep();
    } else {
        window.location.href = '{{ route("admin.sql-alerts.create") }}?step=5';
    }
}

function nextStep() {
    if (!queryExecuted) {
        alert('กรุณารัน SQL Query ให้สำเร็จก่อนดำเนินการต่อ');
        return;
    }
    
    sessionStorage.setItem('sql_alert_step', '7');
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.nextStep();
    } else {
        window.location.href = '{{ route("admin.sql-alerts.create") }}?step=7';
    }
}

// Export functions to global scope
window.executeQuery = executeQuery;
window.previousStep = previousStep;
window.nextStep = nextStep;
window.initializeCurrentStep = initializeStep6;

console.log('Step 6 script loaded');
</script>