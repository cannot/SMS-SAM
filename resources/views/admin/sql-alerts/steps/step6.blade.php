@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนแบบ SQL - ดูตัวอย่างข้อมูล')

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
    justify-content: between;
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
                
                <div class="summary-grid" id="summaryStats">
                    <div class="summary-item">
                        <div class="summary-value" id="rowCount">0</div>
                        <div class="summary-label">แถวข้อมูล</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="columnCount">0</div>
                        <div class="summary-label">คอลัมน์</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="executionTime">0.00s</div>
                        <div class="summary-label">เวลารัน</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="dataSize">0 KB</div>
                        <div class="summary-label">ขนาดข้อมูล</div>
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
</div>

@push('scripts')
<script>
let queryExecuted = false;
let queryResults = null;

document.addEventListener('DOMContentLoaded', function() {
    loadQueryInfo();
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
    
    // Auto-execute if returning from next step
    const executed = sessionStorage.getItem('sql_alert_query_executed');
    if (executed === '1') {
        setTimeout(() => {
            executeQuery();
        }, 1000);
    }
});

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
    const now = new Date();
    const timeString = now.toLocaleString('th-TH', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    document.getElementById('currentTime').value = timeString;
}

async function executeQuery() {
    if (queryExecuted && !confirm('Query ได้รันไปแล้ว ต้องการรันใหม่อีกครั้งหรือไม่?')) {
        return;
    }
    
    const sqlQuery = sessionStorage.getItem('sql_alert_query');
    if (!sqlQuery) {
        alert('ไม่พบ SQL Query กรุณากลับไปที่ขั้นตอนที่ 4');
        return;
    }
    
    // Show progress
    showExecutionProgress();
    
    // Disable execute button
    document.getElementById('executeBtn').disabled = true;
    
    try {
        // Simulate query execution with progress messages
        await simulateQueryExecution();
        
        // Generate mock results
        const results = generateMockResults();
        queryResults = results;
        
        // Show results
        showQueryResults(results);
        
        // Enable next button
        document.getElementById('nextBtn').disabled = false;
        
        queryExecuted = true;
        sessionStorage.setItem('sql_alert_query_executed', '1');
        sessionStorage.setItem('sql_alert_query_results', JSON.stringify(results));
        
    } catch (error) {
        showQueryError(error);
    } finally {
        hideExecutionProgress();
        document.getElementById('executeBtn').disabled = false;
    }
}

async function simulateQueryExecution() {
    const progressMessages = [
        'กำลังเชื่อมต่อฐานข้อมูล...',
        'กำลังตรวจสอบ SQL Syntax...',
        'กำลังวิเคราะห์ Query Plan...',
        'กำลังดึงข้อมูล...',
        'กำลังประมวลผลผลลัพธ์...',
        'เสร็จสิ้น!'
    ];
    
    for (let i = 0; i < progressMessages.length; i++) {
        document.getElementById('progressMessage').textContent = progressMessages[i];
        await delay(800 + Math.random() * 400); // Random delay between 800-1200ms
    }
}

function generateMockResults() {
    const limitRows = parseInt(document.getElementById('limitRows').value) || 25;
    const actualRowCount = Math.floor(Math.random() * 100) + 10; // 10-110 rows
    const displayRowCount = limitRows === 0 ? actualRowCount : Math.min(limitRows, actualRowCount);
    
    // Sample column names based on typical alert data
    const columns = [
        { name: 'alert_id', type: 'number' },
        { name: 'employee_name', type: 'text' },
        { name: 'department', type: 'text' },
        { name: 'alert_type', type: 'text' },
        { name: 'severity', type: 'text' },
        { name: 'message', type: 'text' },
        { name: 'created_at', type: 'datetime' }
    ];
    
    // Generate mock data
    const rows = [];
    for (let i = 0; i < displayRowCount; i++) {
        const row = {
            alert_id: 1000 + i,
            employee_name: generateRandomName(),
            department: generateRandomDepartment(),
            alert_type: generateRandomAlertType(),
            severity: generateRandomSeverity(),
            message: generateRandomMessage(),
            created_at: generateRandomDateTime()
        };
        rows.push(row);
    }
    
    return {
        columns: columns,
        rows: rows,
        totalRows: actualRowCount,
        displayRows: displayRowCount,
        executionTime: (Math.random() * 2 + 0.1).toFixed(3),
        dataSize: (rows.length * columns.length * 15 / 1024).toFixed(2) // Rough estimate
    };
}

function generateRandomName() {
    const firstNames = ['สมชาย', 'สมหญิง', 'วิชัย', 'สุดา', 'ประเสริฐ', 'กัญญา', 'อนันต์', 'พิมพ์', 'นิติ', 'ดารา'];
    const lastNames = ['ใจดี', 'รักงาน', 'เก่งมาก', 'สวยงาม', 'มั่นคง', 'เปี่ยม', 'สุขใส', 'ยิ้มแย้ม', 'มีสุข', 'ร่วมใจ'];
    return firstNames[Math.floor(Math.random() * firstNames.length)] + ' ' + 
           lastNames[Math.floor(Math.random() * lastNames.length)];
}

function generateRandomDepartment() {
    const departments = ['IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Sales', 'Support'];
    return departments[Math.floor(Math.random() * departments.length)];
}

function generateRandomAlertType() {
    const types = ['System Alert', 'Security Alert', 'Performance Alert', 'User Alert', 'Database Alert'];
    return types[Math.floor(Math.random() * types.length)];
}

function generateRandomSeverity() {
    const severities = ['Critical', 'High', 'Medium', 'Low'];
    const weights = [0.1, 0.3, 0.4, 0.2]; // Probability weights
    const random = Math.random();
    let cumulative = 0;
    
    for (let i = 0; i < severities.length; i++) {
        cumulative += weights[i];
        if (random <= cumulative) {
            return severities[i];
        }
    }
    return 'Medium';
}

function generateRandomMessage() {
    const messages = [
        'CPU usage exceeds 90% threshold',
        'Failed login attempts detected',
        'Database connection timeout',
        'Disk space running low',
        'Memory usage critical',
        'Network latency high',
        'Service response time slow',
        'Security policy violation',
        'Backup process failed',
        'User session expired'
    ];
    return messages[Math.floor(Math.random() * messages.length)];
}

function generateRandomDateTime() {
    const now = new Date();
    const randomHours = Math.random() * 24 * 7; // Last 7 days
    const randomDate = new Date(now.getTime() - randomHours * 60 * 60 * 1000);
    return randomDate.toISOString().slice(0, 19).replace('T', ' ');
}

function showExecutionProgress() {
    document.getElementById('executionProgress').classList.add('show');
    document.getElementById('resultSummary').classList.remove('show');
    document.getElementById('dataPreview').classList.remove('show');
}

function hideExecutionProgress() {
    document.getElementById('executionProgress').classList.remove('show');
}

function showQueryResults(results) {
    // Update last executed time
    document.getElementById('lastExecuted').textContent = 'รันเมื่อ ' + new Date().toLocaleTimeString('th-TH');
    
    // Show result summary
    const summaryDiv = document.getElementById('resultSummary');
    summaryDiv.className = 'result-summary show success';
    
    document.getElementById('summaryTitle').textContent = 'Query รันสำเร็จ!';
    document.getElementById('summaryMessage').textContent = `พบข้อมูล ${results.totalRows} แถว แสดง ${results.displayRows} แถว`;
    
    // Update stats
    document.getElementById('rowCount').textContent = results.totalRows.toLocaleString();
    document.getElementById('columnCount').textContent = results.columns.length;
    document.getElementById('executionTime').textContent = results.executionTime + 's';
    document.getElementById('dataSize').textContent = results.dataSize + ' KB';
    
    // Show variables preview
    showVariablesPreview(results);
    
    // Show data table
    showDataTable(results);
}

function showVariablesPreview(results) {
    const variables = JSON.parse(sessionStorage.getItem('sql_alert_variables') || '[]');
    const variablesList = document.getElementById('variablesList');
    
    // Add system variables
    const systemVariables = [
        { name: 'record_count', value: results.totalRows },
        { name: 'query_date', value: new Date().toISOString().split('T')[0] },
        { name: 'query_time', value: new Date().toLocaleTimeString('th-TH') },
        { name: 'execution_time', value: results.executionTime + 's' },
        { name: 'database_name', value: sessionStorage.getItem('sql_alert_db_name') || 'database' }
    ];
    
    variablesList.innerHTML = '';
    
    // Show system variables
    systemVariables.forEach(variable => {
        const div = document.createElement('div');
        div.className = 'variable-item';
        div.innerHTML = `
            <span class="variable-name">{{${variable.name}}}</span>
            <span class="variable-value">${variable.value}</span>
        `;
        variablesList.appendChild(div);
    });
    
    // Show custom variables
    variables.forEach(variable => {
        const div = document.createElement('div');
        div.className = 'variable-item';
        div.innerHTML = `
            <span class="variable-name">{{${variable.name}}}</span>
            <span class="variable-value">[จากข้อมูล]</span>
        `;
        variablesList.appendChild(div);
    });
    
    // Save variables for next steps
    sessionStorage.setItem('sql_alert_computed_variables', JSON.stringify(systemVariables));
}

function showDataTable(results) {
    const dataPreview = document.getElementById('dataPreview');
    const tableHeader = document.getElementById('tableHeader');
    const tableBody = document.getElementById('tableBody');
    
    // Update preview stats
    document.getElementById('previewRowCount').textContent = `แสดง ${results.displayRows} จาก ${results.totalRows} แถว`;
    document.getElementById('previewColumnCount').textContent = `${results.columns.length} คอลัมน์`;
    
    // Create table headers
    tableHeader.innerHTML = '';
    const headerRow = document.createElement('tr');
    results.columns.forEach(column => {
        const th = document.createElement('th');
        th.textContent = column.name;
        headerRow.appendChild(th);
    });
    tableHeader.appendChild(headerRow);
    
    // Create table body
    tableBody.innerHTML = '';
    results.rows.forEach(row => {
        const tr = document.createElement('tr');
        results.columns.forEach(column => {
            const td = document.createElement('td');
            const value = row[column.name];
            
            if (value === null || value === undefined) {
                td.textContent = 'NULL';
                td.className = 'null';
            } else if (column.type === 'number') {
                td.textContent = value.toLocaleString();
                td.className = 'number';
            } else if (column.type === 'datetime') {
                td.textContent = value;
                td.className = 'date';
            } else {
                td.textContent = value;
            }
            
            tr.appendChild(td);
        });
        tableBody.appendChild(tr);
    });
    
    // Show data preview
    dataPreview.classList.add('show');
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

function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function previousStep() {
    window.location.href = '{{ route("sql-alerts.create") }}?step=5';
}

function nextStep() {
    if (!queryExecuted) {
        alert('กรุณารัน SQL Query ให้สำเร็จก่อนดำเนินการต่อ');
        return;
    }
    
    sessionStorage.setItem('sql_alert_step', '7');
    window.location.href = '{{ route("sql-alerts.create") }}?step=7';
}

// Save results when query is executed
function saveQueryResults(results) {
    sessionStorage.setItem('sql_alert_preview_data', JSON.stringify({
        totalRows: results.totalRows,
        displayRows: results.displayRows,
        columns: results.columns.length,
        executionTime: results.executionTime,
        dataSize: results.dataSize,
        sampleData: results.rows.slice(0, 5) // Save first 5 rows as sample
    }));
}
</script>
@endpush
@endsection