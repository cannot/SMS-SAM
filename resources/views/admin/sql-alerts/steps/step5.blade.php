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

.parameters-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    border-left: 4px solid #3b82f6;
}

.parameters-header {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1e40af;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.parameter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.parameter-item {
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    cursor: pointer;
    transition: all 0.3s ease;
}

.parameter-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.parameter-name {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #1e40af;
    margin-bottom: 5px;
}

.parameter-description {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 8px;
}

.parameter-example {
    background: #f1f5f9;
    padding: 8px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    color: #475569;
}

.sql-editor {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    border: 1px solid #e5e7eb;
}

.sql-editor-header {
    font-size: 1.2rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sql-textarea {
    width: 100%;
    min-height: 200px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    line-height: 1.5;
    padding: 15px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    resize: vertical;
    background: #f9fafb;
}

.sql-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.sql-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.btn {
    padding: 10px 20px;
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
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
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

.results-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    border: 1px solid #e5e7eb;
    display: none;
}

.results-section.show {
    display: block;
}

.results-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e40af;
    margin-bottom: 5px;
}

.stat-label {
    color: #6b7280;
    font-size: 0.9rem;
}

.sample-data {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

.sample-data table {
    width: 100%;
    border-collapse: collapse;
}

.sample-data th,
.sample-data td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.sample-data th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
}

.variables-generated {
    background: #ecfdf5;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
    border-left: 4px solid #10b981;
}

.variables-header {
    font-size: 1.1rem;
    font-weight: 600;
    color: #065f46;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.variable-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
}

.variable-item {
    background: white;
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #d1fae5;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.variable-name {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #065f46;
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
    background: #f3f4f6;
    padding: 2px 6px;
    border-radius: 4px;
}

.empty-value {
    color: #d1d5db;
    font-style: italic;
    font-size: 0.9em;
    background: #f9fafb;
    padding: 2px 6px;
    border-radius: 4px;
}

.no-data {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
    font-size: 1.1rem;
}

.no-data i {
    display: block;
    font-size: 2rem;
    margin-bottom: 15px;
    color: #d1d5db;
}
</style>

    <!-- Wizard Container -->
    <div class="wizard-container">
        <!-- Wizard Header -->
        <div class="wizard-header">
        <div class="wizard-title">🔧 ปรับปรุง SQL Query</div>
        <div class="wizard-subtitle">เพิ่ม System Parameters และปรับปรุง Query สำหรับการแจ้งเตือน</div>
            
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
        <!-- Step 5: SQL Enhancement -->
            <div class="section-title">
                <div class="section-icon">5</div>
            ปรับปรุง SQL Query พร้อม System Parameters
            </div>

        <!-- System Parameters Section -->
        <div class="parameters-section">
            <div class="parameters-header">
                <i class="fas fa-cogs"></i>
                System Parameters ที่ใช้ได้
            </div>

            <div class="parameter-grid">
                <div class="parameter-item" onclick="insertParameter('CURDATE()')">
                    <div class="parameter-name">&#123;&#123;current_date&#125;&#125;</div>
                    <div class="parameter-description">วันที่ปัจจุบัน</div>
                    <div class="parameter-example">CURDATE()</div>
                </div>
                
                <div class="parameter-item" onclick="insertParameter('NOW()')">
                    <div class="parameter-name">&#123;&#123;current_datetime&#125;&#125;</div>
                    <div class="parameter-description">วันที่และเวลาปัจจุบัน</div>
                    <div class="parameter-example">NOW()</div>
                    </div>

                <div class="parameter-item" onclick="insertParameter('DATE_SUB(CURDATE(), INTERVAL 1 DAY)')">
                    <div class="parameter-name">&#123;&#123;yesterday&#125;&#125;</div>
                    <div class="parameter-description">เมื่อวาน</div>
                    <div class="parameter-example">DATE_SUB(CURDATE(), INTERVAL 1 DAY)</div>
                    </div>

                <div class="parameter-item" onclick="insertParameter('DATE_ADD(CURDATE(), INTERVAL 1 DAY)')">
                    <div class="parameter-name">&#123;&#123;tomorrow&#125;&#125;</div>
                    <div class="parameter-description">พรุ่งนี้</div>
                    <div class="parameter-example">DATE_ADD(CURDATE(), INTERVAL 1 DAY)</div>
                    </div>

                <div class="parameter-item" onclick="insertParameter('DATE_SUB(NOW(), INTERVAL 1 HOUR)')">
                    <div class="parameter-name">&#123;&#123;last_hour&#125;&#125;</div>
                    <div class="parameter-description">1 ชั่วโมงที่แล้ว</div>
                    <div class="parameter-example">DATE_SUB(NOW(), INTERVAL 1 HOUR)</div>
                    </div>

                <div class="parameter-item" onclick="insertParameter('DATE_SUB(NOW(), INTERVAL 7 DAY)')">
                    <div class="parameter-name">&#123;&#123;last_week&#125;&#125;</div>
                    <div class="parameter-description">สัปดาห์ที่แล้ว</div>
                    <div class="parameter-example">DATE_SUB(NOW(), INTERVAL 7 DAY)</div>
                    </div>
            </div>
                    </div>

        <!-- SQL Editor Section -->
        <div class="sql-editor">
            <div class="sql-editor-header">
                <i class="fas fa-code"></i>
                แก้ไข SQL Query
                    </div>

            <textarea class="sql-textarea" id="enhancedSqlQuery" placeholder="แก้ไข SQL Query ของคุณที่นี่..."></textarea>
            
            <div class="sql-actions">
                <button type="button" class="btn btn-primary" onclick="formatSQL()">
                    <i class="fas fa-magic"></i>
                    Format SQL
                </button>
                <button type="button" class="btn btn-secondary" onclick="validateSQL()">
                    <i class="fas fa-check"></i>
                    Validate
                </button>
                <button type="button" class="btn btn-success" onclick="executeEnhancedQuery()">
                    <i class="fas fa-play"></i>
                    Execute Query
                </button>
                </div>
            </div>

        <!-- Results Section -->
        <div class="results-section" id="resultsSection">
            <div class="results-header">
                <h4>
                    <i class="fas fa-chart-line"></i>
                    ผลลัพธ์การรัน Query
                </h4>
                </div>

            <!-- Stats -->
            <div class="results-stats">
                <div class="stat-item">
                    <div class="stat-value" id="recordCount">-</div>
                    <div class="stat-label">จำนวนแถว</div>
                            </div>
                <div class="stat-item">
                    <div class="stat-value" id="executionTime">-</div>
                    <div class="stat-label">เวลารัน (วินาที)</div>
                            </div>
                <div class="stat-item">
                    <div class="stat-value" id="columnCount">-</div>
                    <div class="stat-label">จำนวนคอลัมน์</div>
                            </div>
                <div class="stat-item">
                    <div class="stat-value" id="dataSize">-</div>
                    <div class="stat-label">ขนาดข้อมูล</div>
                    </div>
                </div>

            <!-- Sample Data -->
            <div class="sample-data" id="sampleDataTable">
                <!-- Table will be populated here -->
            </div>

            <!-- Generated Variables -->
            <div class="variables-generated">
                <div class="variables-header">
                    <i class="fas fa-tags"></i>
                    ตัวแปรที่สร้างขึ้นสำหรับ Email Template
                </div>
                <div class="variable-list" id="generatedVariables">
                    <!-- Variables will be populated here -->
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
                
            <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()" disabled>
                ถัดไป (ดูภาพรวม)
                    <i class="fas fa-arrow-right"></i>
                </button>
        </div>
    </div>
</div>

<script>
let queryExecuted = false;
let queryResults = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Step 5 DOM loaded');
    initializeStep5();
});

function initializeStep5() {
    console.log('Initializing Step 5...');
    
    // Load SQL from step 4
    const savedSQL = sessionStorage.getItem('sql_alert_query');
    if (savedSQL) {
        document.getElementById('enhancedSqlQuery').value = savedSQL;
    }
}

function insertParameter(parameterSQL) {
    const textarea = document.getElementById('enhancedSqlQuery');
    const cursorPos = textarea.selectionStart;
    const textBefore = textarea.value.substring(0, cursorPos);
    const textAfter = textarea.value.substring(cursorPos);
    
    textarea.value = textBefore + parameterSQL + textAfter;
    textarea.focus();
    textarea.setSelectionRange(cursorPos + parameterSQL.length, cursorPos + parameterSQL.length);
    
    // Auto-save
    sessionStorage.setItem('sql_alert_enhanced_query', textarea.value);
}

function formatSQL() {
    const textarea = document.getElementById('enhancedSqlQuery');
    let sql = textarea.value;
    
    // Basic SQL formatting
    sql = sql.replace(/\s+/g, ' ').trim();
    sql = sql.replace(/\b(SELECT|FROM|WHERE|JOIN|LEFT JOIN|RIGHT JOIN|INNER JOIN|ORDER BY|GROUP BY|HAVING|LIMIT)\b/gi, '\n$1');
    sql = sql.replace(/,/g, ',\n    ');
    sql = sql.replace(/\n\s*\n/g, '\n');
    
    textarea.value = sql;
    sessionStorage.setItem('sql_alert_enhanced_query', sql);
}

function validateSQL() {
    const sql = document.getElementById('enhancedSqlQuery').value.trim();
    
    if (!sql) {
        alert('กรุณาใส่ SQL Query');
        return;
    }
    
    // Basic validation
    if (!sql.toLowerCase().includes('select')) {
        alert('Query ต้องเป็น SELECT statement');
        return;
    }
    
    const dangerousKeywords = ['drop', 'delete', 'update', 'insert', 'create', 'alter', 'truncate'];
    const sqlLower = sql.toLowerCase();
    
    for (let keyword of dangerousKeywords) {
        if (sqlLower.includes(keyword)) {
            alert(`ไม่อนุญาตให้ใช้คำสั่ง ${keyword.toUpperCase()}`);
            return;
        }
    }
    
    alert('SQL Query ถูกต้อง!');
}

function executeEnhancedQuery() {
    const sql = document.getElementById('enhancedSqlQuery').value.trim();
    
    if (!sql) {
        alert('กรุณาใส่ SQL Query');
        return;
    }
    
    // Get connection data from previous steps
    const connectionData = JSON.parse(sessionStorage.getItem('sql_alert_connection') || '{}');
    
    if (!connectionData.host) {
        alert('ไม่พบข้อมูลการเชื่อมต่อ กรุณากลับไปขั้นตอนที่ 2-3');
        return;
    }
    
    // Show loading
    const executeBtn = document.querySelector('button[onclick="executeEnhancedQuery()"]');
    executeBtn.disabled = true;
    executeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังรัน...';
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Execute real query
    fetch('/admin/sql-alerts/execute-query', {
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
        console.log('Query execution result:', data);
        
        if (data.success) {
            const results = {
                records_count: data.data.records_count || 0,
                execution_time: data.data.execution_time || 0,
                columns: data.data.columns || [],
                sample_data: data.data.sample_data || []
            };
            
            showResults(results);
            queryExecuted = true;
            queryResults = results;
            
            // Enable next button
            document.getElementById('nextBtn').disabled = false;
            
            // Save enhanced query and results
            sessionStorage.setItem('sql_alert_enhanced_query', sql);
            sessionStorage.setItem('sql_alert_final_results', JSON.stringify(results));
            
            // Also save for other steps
            sessionStorage.setItem('sql_alert_query_result', JSON.stringify(data));
            sessionStorage.setItem('sql_alert_query_results', JSON.stringify({
                columns: results.columns,
                rows: results.sample_data,
                totalRows: results.records_count,
                executionTime: results.execution_time,
                querySize: sql.length
            }));
            
        } else {
            throw new Error(data.message || 'Query execution failed');
        }
    })
    .catch(error => {
        console.error('Query execution error:', error);
        alert('เกิดข้อผิดพลาด: ' + (error.message || 'ไม่สามารถรัน Query ได้'));
        
        // Hide results section
        document.getElementById('resultsSection').classList.remove('show');
    })
    .finally(() => {
        // Reset button
        executeBtn.disabled = false;
        executeBtn.innerHTML = '<i class="fas fa-play"></i> Execute Query';
    });
}

function showResults(results) {
    const resultsSection = document.getElementById('resultsSection');
    resultsSection.classList.add('show');
    
    // Update stats
    document.getElementById('recordCount').textContent = results.records_count.toLocaleString();
    document.getElementById('executionTime').textContent = results.execution_time + 's';
    document.getElementById('columnCount').textContent = results.columns.length;
    document.getElementById('dataSize').textContent = Math.round(results.records_count * 0.5) + ' KB';
    
    // Show sample data
    showSampleData(results);
    
    // Generate variables
    generateVariables(results);
}

function showSampleData(results) {
    const tableContainer = document.getElementById('sampleDataTable');
    
    if (!results.sample_data || results.sample_data.length === 0) {
        tableContainer.innerHTML = `
            <div class="no-data">
                <i class="fas fa-info-circle"></i>
                ไม่มีข้อมูลตัวอย่าง
                        </div>
                    `;
        return;
    }
    
    let html = '<table><thead><tr>';
    results.columns.forEach(col => {
        html += `<th>${col}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    results.sample_data.forEach(row => {
        html += '<tr>';
        results.columns.forEach(col => {
            const value = row[col];
            let displayValue;
            
            if (value === null || value === undefined) {
                displayValue = '<span class="null-value">NULL</span>';
            } else if (typeof value === 'string' && value.trim() === '') {
                displayValue = '<span class="empty-value">Empty</span>';
            } else if (typeof value === 'object') {
                displayValue = JSON.stringify(value);
            } else {
                displayValue = String(value);
            }
            
            html += `<td>${displayValue}</td>`;
        });
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    tableContainer.innerHTML = html;
}

function generateVariables(results) {
    const variablesContainer = document.getElementById('generatedVariables');
    
    // Create system variables from real data (ไม่รวมตัวแปร column)
    const variables = [
        { name: 'record_count', value: results.records_count },
        { name: 'query_execution_time', value: results.execution_time + 's' },
        { name: 'current_date', value: new Date().toISOString().split('T')[0] },
        { name: 'current_datetime', value: new Date().toISOString().replace('T', ' ').slice(0, 19) },
        { name: 'database_name', value: sessionStorage.getItem('sql_alert_db_name') || 'database' },
        { name: 'database_type', value: sessionStorage.getItem('sql_alert_db_type') || 'mysql' },
        { name: 'total_columns', value: results.columns.length },
        { name: 'sample_rows', value: results.sample_data.length },
        { name: 'query_date', value: new Date().toISOString().split('T')[0] },
        { name: 'query_time', value: new Date().toLocaleTimeString('th-TH') }
    ];
    
    // ไม่แสดงตัวแปร column เพื่อไม่ให้ template ยุ่งเหยิง
    // (ตัวแปร column จะถูกสร้างในขั้นตอนการส่ง email จริง)
    
    let html = '';
    variables.forEach(variable => {
        html += `
            <div class="variable-item">
                <div class="variable-name">&#123;&#123;${variable.name}&#125;&#125;</div>
                <div class="variable-value">${variable.value}</div>
            </div>
        `;
    });
    
    variablesContainer.innerHTML = html;
    
    // Save variables for next steps
    sessionStorage.setItem('sql_alert_system_variables', JSON.stringify(variables));
}

function previousStep() {
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.previousStep();
    } else {
        window.location.href = '/admin/sql-alerts/create?step=4';
    }
}

function nextStep() {
    if (!queryExecuted) {
        alert('กรุณารัน Query ก่อนดำเนินการต่อ');
        return;
    }
    
    // Save current progress
    const sql = document.getElementById('enhancedSqlQuery').value;
    sessionStorage.setItem('sql_alert_enhanced_query', sql);
    sessionStorage.setItem('sql_alert_step', '6');
    
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.nextStep();
    } else {
        window.location.href = '/admin/sql-alerts/create?step=6';
    }
}

// Auto-save on input
document.getElementById('enhancedSqlQuery').addEventListener('input', function() {
    sessionStorage.setItem('sql_alert_enhanced_query', this.value);
});

// Export functions
window.initializeStep5 = initializeStep5;
window.initializeCurrentStep = initializeStep5;
window.insertParameter = insertParameter;
window.formatSQL = formatSQL;
window.validateSQL = validateSQL;
window.executeEnhancedQuery = executeEnhancedQuery;
window.previousStep = previousStep;
window.nextStep = nextStep;

console.log('Step 5 script loaded');
</script>