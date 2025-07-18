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

.overview-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.overview-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.card-header {
    font-size: 1.2rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.connection-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: #6b7280;
}

.info-value {
    font-weight: 600;
    color: #374151;
}

.sql-preview {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.5;
    color: #334155;
    white-space: pre-wrap;
    max-height: 200px;
    overflow-y: auto;
}

.results-summary {
    background: #ecfdf5;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    border-left: 4px solid #10b981;
}

.summary-header {
    font-size: 1.2rem;
    font-weight: 600;
    color: #065f46;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.summary-stat {
    text-align: center;
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #d1fae5;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #059669;
    margin-bottom: 5px;
}

.stat-label {
    color: #6b7280;
    font-size: 0.9rem;
}

.variables-preview {
    background: #fffbeb;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    border-left: 4px solid #f59e0b;
}

.variables-header {
    font-size: 1.2rem;
    font-weight: 600;
    color: #92400e;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.variables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.variable-item {
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #fed7aa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.variable-name {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #92400e;
}

.variable-value {
    color: #6b7280;
    font-size: 0.9rem;
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
    border-radius: 8px;
    font-weight: 500;
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

.status-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    color: #059669;
    background: #d1fae5;
}
</style>

    <!-- Wizard Container -->
    <div class="wizard-container">
        <!-- Wizard Header -->
        <div class="wizard-header">
        <div class="wizard-title">📊 ภาพรวมและผลลัพธ์</div>
        <div class="wizard-subtitle">ตรวจสอบการตั้งค่าและผลลัพธ์ก่อนสร้างการแจ้งเตือน</div>
            
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
        <!-- Step 6: Overview -->
            <div class="section-title">
                <div class="section-icon">6</div>
            ภาพรวมการตั้งค่า
            </div>

        <!-- Overview Grid -->
        <div class="overview-grid">
            <!-- Database Connection -->
            <div class="overview-card">
                <div class="card-header">
                    <i class="fas fa-database"></i>
                    การเชื่อมต่อฐานข้อมูล
                </div>
                <div class="connection-info">
                    <div class="info-item">
                        <span class="info-label">ประเภท:</span>
                        <span class="info-value" id="dbType">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Host:</span>
                        <span class="info-value" id="dbHost">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Port:</span>
                        <span class="info-value" id="dbPort">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Database:</span>
                        <span class="info-value" id="dbName">-</span>
                    </div>
                </div>
            </div>

            <!-- SQL Query -->
            <div class="overview-card">
                <div class="card-header">
                    <i class="fas fa-code"></i>
                    SQL Query
                </div>
                <div class="sql-preview" id="sqlPreview">
                    <!-- SQL will be displayed here -->
                    </div>
                    </div>
                    </div>
                    
        <!-- Results Summary -->
        <div class="results-summary">
            <div class="summary-header">
                <i class="fas fa-chart-line"></i>
                สรุปผลลัพธ์
                    </div>
            <div class="summary-stats">
                <div class="summary-stat">
                    <div class="stat-value" id="finalRecordCount">-</div>
                    <div class="stat-label">จำนวนแถว</div>
                </div>
                <div class="summary-stat">
                    <div class="stat-value" id="finalExecutionTime">-</div>
                    <div class="stat-label">เวลารัน</div>
                </div>
                <div class="summary-stat">
                    <div class="stat-value" id="finalColumnCount">-</div>
                    <div class="stat-label">คอลัมน์</div>
                    </div>
                <div class="summary-stat">
                    <div class="stat-value" id="finalDataSize">-</div>
                    <div class="stat-label">ขนาดข้อมูล</div>
                        </div>
                    </div>
                </div>

                <!-- Variables Preview -->
        <div class="variables-preview">
            <div class="variables-header">
                <i class="fas fa-tags"></i>
                ตัวแปรที่พร้อมใช้งานใน Email Template
            </div>
            <div class="variables-grid" id="variablesGrid">
                <!-- Variables will be populated here -->
                </div>
            </div>

            <!-- Navigation -->
            <div class="wizard-navigation">
                <button type="button" class="btn btn-secondary" onclick="previousStep()">
                    <i class="fas fa-arrow-left"></i>
                    ย้อนกลับ
                </button>
                
                <div class="status-indicator">
                <i class="fas fa-check-circle"></i>
                พร้อมสร้างการแจ้งเตือน
                </div>
                
            <button type="button" class="btn btn-primary" onclick="nextStep()">
                    ถัดไป (ตัวเลือกการส่งออก)
                    <i class="fas fa-arrow-right"></i>
                </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Step 6 DOM loaded');
    initializeStep6();
});

function initializeStep6() {
    console.log('Initializing Step 6...');
    
    // Load overview data
    loadDatabaseInfo();
    loadSQLQuery();
    loadResults();
    loadVariables();
}

function loadDatabaseInfo() {
    // Load database connection info
    document.getElementById('dbType').textContent = sessionStorage.getItem('sql_alert_db_type') || '-';
    document.getElementById('dbHost').textContent = sessionStorage.getItem('sql_alert_db_host') || '-';
    document.getElementById('dbPort').textContent = sessionStorage.getItem('sql_alert_db_port') || '-';
    document.getElementById('dbName').textContent = sessionStorage.getItem('sql_alert_db_name') || '-';
}

function loadSQLQuery() {
    // Load enhanced SQL query
    const enhancedSQL = sessionStorage.getItem('sql_alert_enhanced_query') || sessionStorage.getItem('sql_alert_query') || '';
    document.getElementById('sqlPreview').textContent = enhancedSQL || 'ไม่พบ SQL Query';
}

function loadResults() {
    // Load results from step 5
    const resultsData = JSON.parse(sessionStorage.getItem('sql_alert_final_results') || '{}');
    
    if (resultsData.records_count) {
        document.getElementById('finalRecordCount').textContent = resultsData.records_count.toLocaleString();
        document.getElementById('finalExecutionTime').textContent = resultsData.execution_time + 's';
        document.getElementById('finalColumnCount').textContent = resultsData.columns ? resultsData.columns.length : '-';
        document.getElementById('finalDataSize').textContent = Math.round(resultsData.records_count * 0.5) + ' KB';
    }
}

function loadVariables() {
    // Load system variables
    const systemVariables = JSON.parse(sessionStorage.getItem('sql_alert_system_variables') || '[]');
    const variablesGrid = document.getElementById('variablesGrid');
    
    let html = '';
    systemVariables.forEach(variable => {
        html += `
            <div class="variable-item">
                <div class="variable-name">&#123;&#123;${variable.name}&#125;&#125;</div>
                <div class="variable-value">${variable.value}</div>
            </div>
        `;
    });
    
    if (html === '') {
        html = '<div class="variable-item"><div class="variable-name">ไม่มีตัวแปร</div><div class="variable-value">-</div></div>';
    }
    
    variablesGrid.innerHTML = html;
}

function previousStep() {
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.previousStep();
    } else {
        window.location.href = '{{ route("admin.sql-alerts.create") }}?step=5';
    }
}

function nextStep() {
    sessionStorage.setItem('sql_alert_step', '7');
    
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.nextStep();
    } else {
        window.location.href = '{{ route("admin.sql-alerts.create") }}?step=7';
    }
}

// Export functions
window.initializeStep6 = initializeStep6;
window.initializeCurrentStep = initializeStep6;
window.previousStep = previousStep;
window.nextStep = nextStep;

console.log('Step 6 script loaded');
</script>