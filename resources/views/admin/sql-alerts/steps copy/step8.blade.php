@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนแบบ SQL - นับจำนวนข้อมูล')

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

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 2px solid #bae6fd;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #0ea5e9, #06b6d4);
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(14, 165, 233, 0.15);
}

.stat-icon {
    width: 50px;
    height: 50px;
    margin: 0 auto 15px;
    background: linear-gradient(135deg, #0ea5e9, #06b6d4);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #0c4a6e;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.875rem;
    color: #0369a1;
    font-weight: 500;
}

.detailed-stats {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.detailed-header {
    font-weight: 600;
    margin-bottom: 20px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.stats-category {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 20px;
}

.category-title {
    font-weight: 600;
    margin-bottom: 15px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.category-icon {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}

.category-icon.data {
    background: #10b981;
}

.category-icon.performance {
    background: #f59e0b;
}

.category-icon.size {
    background: #8b5cf6;
}

.category-icon.time {
    background: #ef4444;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f3f4f6;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-name {
    font-size: 0.875rem;
    color: #6b7280;
}

.stat-data {
    font-weight: 600;
    color: #374151;
}

.variables-section {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.variables-header {
    font-weight: 600;
    margin-bottom: 20px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.variables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.variable-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
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

.variable-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #059669;
    margin-bottom: 5px;
}

.variable-description {
    font-size: 0.75rem;
    color: #6b7280;
}

.export-summary {
    background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
    border: 2px solid #f59e0b;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.export-summary.disabled {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.export-summary h5 {
    color: #92400e;
    font-weight: 600;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.export-summary.disabled h5 {
    color: #6b7280;
}

.export-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.export-item {
    background: rgba(255,255,255,0.7);
    padding: 10px 12px;
    border-radius: 6px;
    text-align: center;
}

.export-summary.disabled .export-item {
    background: rgba(255,255,255,0.5);
}

.export-label {
    font-size: 0.75rem;
    color: #92400e;
    margin-bottom: 4px;
}

.export-summary.disabled .export-label {
    color: #9ca3af;
}

.export-data {
    font-weight: 600;
    color: #451a03;
}

.export-summary.disabled .export-data {
    color: #6b7280;
}

.progress-section {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    text-align: center;
}

.progress-title {
    font-weight: 600;
    margin-bottom: 15px;
    color: #374151;
}

.progress-bar-container {
    background: #e5e7eb;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 15px;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 4px;
    transition: width 0.3s ease;
    width: 0%;
}

.progress-text {
    font-size: 0.875rem;
    color: #6b7280;
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

.refresh-stats {
    text-align: center;
    margin-bottom: 20px;
}

.stats-timestamp {
    background: #e0f2fe;
    border: 1px solid #bae6fd;
    border-radius: 6px;
    padding: 10px;
    text-align: center;
    font-size: 0.875rem;
    color: #0369a1;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .wizard-content {
        padding: 25px;
    }
    
    .stats-overview {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .variables-grid {
        grid-template-columns: 1fr;
    }
    
    .export-info {
        grid-template-columns: repeat(2, 1fr);
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
            <div class="wizard-title">📊 นับจำนวนข้อมูล</div>
            <div class="wizard-subtitle">วิเคราะห์และสรุปสถิติข้อมูลสำหรับการแจ้งเตือน</div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
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
                <div class="step"></div>
            </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
            <!-- Step 8: Record Count & Statistics -->
            <div class="section-title">
                <div class="section-icon">8</div>
                นับจำนวนข้อมูล
            </div>

            <!-- Stats Timestamp -->
            <div class="stats-timestamp">
                <i class="fas fa-clock me-1"></i>
                สถิติอัปเดตล่าสุด: <span id="statsTimestamp">-</span>
            </div>

            <!-- Refresh Stats -->
            <div class="refresh-stats">
                <button type="button" class="btn btn-success" onclick="refreshStats()">
                    <i class="fas fa-sync-alt"></i>
                    รีเฟรชสถิติ
                </button>
            </div>

            <!-- Progress Section -->
            <div class="progress-section" id="progressSection" style="display: none;">
                <div class="progress-title">กำลังประมวลผลข้อมูล...</div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                <div class="progress-text" id="progressText">กำลังเชื่อมต่อฐานข้อมูล...</div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-list-ol"></i>
                    </div>
                    <div class="stat-value" id="totalRecords">-</div>
                    <div class="stat-label">จำนวนแถวทั้งหมด</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-columns"></i>
                    </div>
                    <div class="stat-value" id="totalColumns">-</div>
                    <div class="stat-label">จำนวนคอลัมน์</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-stopwatch"></i>
                    </div>
                    <div class="stat-value" id="queryTime">-</div>
                    <div class="stat-label">เวลาประมวลผล</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-weight-hanging"></i>
                    </div>
                    <div class="stat-value" id="dataSize">-</div>
                    <div class="stat-label">ขนาดข้อมูล</div>
                </div>
            </div>

            <!-- Detailed Statistics -->
            <div class="detailed-stats">
                <div class="detailed-header">
                    <i class="fas fa-chart-bar"></i>
                    สถิติรายละเอียด
                </div>

                <div class="stats-grid">
                    <!-- Data Statistics -->
                    <div class="stats-category">
                        <div class="category-title">
                            <div class="category-icon data">
                                <i class="fas fa-database"></i>
                            </div>
                            ข้อมูล
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">แถวที่มีข้อมูล</span>
                            <span class="stat-data" id="nonEmptyRows">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">แถวที่ว่าง</span>
                            <span class="stat-data" id="emptyRows">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">ค่า NULL</span>
                            <span class="stat-data" id="nullValues">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">ข้อมูลเฉพาะ</span>
                            <span class="stat-data" id="uniqueValues">-</span>
                        </div>
                    </div>

                    <!-- Performance Statistics -->
                    <div class="stats-category">
                        <div class="category-title">
                            <div class="category-icon performance">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            ประสิทธิภาพ
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">เวลาเชื่อมต่อ</span>
                            <span class="stat-data" id="connectionTime">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">เวลารัน Query</span>
                            <span class="stat-data" id="executionTime">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">แถวต่อวินาที</span>
                            <span class="stat-data" id="rowsPerSecond">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">หน่วยความจำ</span>
                            <span class="stat-data" id="memoryUsage">-</span>
                        </div>
                    </div>

                    <!-- Size Statistics -->
                    <div class="stats-category">
                        <div class="category-title">
                            <div class="category-icon size">
                                <i class="fas fa-hdd"></i>
                            </div>
                            ขนาด
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">ข้อมูลดิบ</span>
                            <span class="stat-data" id="rawDataSize">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">ข้อมูลบีบอัด</span>
                            <span class="stat-data" id="compressedSize">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">อัตราการบีบอัด</span>
                            <span class="stat-data" id="compressionRatio">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">ขนาดเฉลี่ยต่อแถว</span>
                            <span class="stat-data" id="avgRowSize">-</span>
                        </div>
                    </div>

                    <!-- Time Statistics -->
                    <div class="stats-category">
                        <div class="category-title">
                            <div class="category-icon time">
                                <i class="fas fa-clock"></i>
                            </div>
                            เวลา
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">เริ่มรัน Query</span>
                            <span class="stat-data" id="startTime">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">เสร็จสิ้น</span>
                            <span class="stat-data" id="endTime">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">ข้อมูลล่าสุด</span>
                            <span class="stat-data" id="latestRecord">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">ข้อมูลเก่าสุด</span>
                            <span class="stat-data" id="oldestRecord">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Summary -->
            <div class="export-summary" id="exportSummary">
                <h5>
                    <i class="fas fa-file-export"></i>
                    สรุปการส่งออกไฟล์
                </h5>
                <div class="export-info">
                    <div class="export-item">
                        <div class="export-label">รูปแบบไฟล์</div>
                        <div class="export-data" id="exportFormat">XLSX</div>
                    </div>
                    <div class="export-item">
                        <div class="export-label">ขนาดไฟล์ประมาณ</div>
                        <div class="export-data" id="exportSize">25.3 KB</div>
                    </div>
                    <div class="export-item">
                        <div class="export-label">จำนวนแถว</div>
                        <div class="export-data" id="exportRows">1,000</div>
                    </div>
                    <div class="export-item">
                        <div class="export-label">การบีบอัด</div>
                        <div class="export-data" id="exportCompression">ZIP</div>
                    </div>
                </div>
            </div>

            <!-- Variables for Email -->
            <div class="variables-section">
                <div class="variables-header">
                    <i class="fas fa-tags"></i>
                    ตัวแปรสำหรับ Email Template
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
                    <i class="fas fa-info-circle"></i>
                    ขั้นตอนที่ 8 จาก 14
                </div>
                
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    ถัดไป (เลือก Template Email)
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let statsData = {};

document.addEventListener('DOMContentLoaded', function() {
    loadExistingStats();
    loadExportSummary();
    
    // Auto-refresh stats if not already loaded
    const lastRefresh = sessionStorage.getItem('sql_alert_stats_timestamp');
    if (!lastRefresh) {
        setTimeout(refreshStats, 1000);
    }
});

function loadExistingStats() {
    // Load saved stats if available
    const saved = sessionStorage.getItem('sql_alert_stats_data');
    if (saved) {
        try {
            statsData = JSON.parse(saved);
            updateStatsDisplay(statsData);
            updateVariables(statsData);
            
            const timestamp = sessionStorage.getItem('sql_alert_stats_timestamp');
            if (timestamp) {
                document.getElementById('statsTimestamp').textContent = new Date(timestamp).toLocaleString('th-TH');
            }
        } catch (e) {
            console.error('Error loading saved stats:', e);
        }
    }
}

function loadExportSummary() {
    const exportSettings = JSON.parse(sessionStorage.getItem('sql_alert_export_settings') || '{}');
    const exportSummary = document.getElementById('exportSummary');
    
    if (exportSettings.enabled === false) {
        exportSummary.classList.add('disabled');
        exportSummary.querySelector('h5').innerHTML = `
            <i class="fas fa-file-times"></i>
            การส่งออกไฟล์ปิดใช้งาน
        `;
        
        document.getElementById('exportFormat').textContent = 'ไม่ส่งออก';
        document.getElementById('exportSize').textContent = '-';
        document.getElementById('exportRows').textContent = '-';
        document.getElementById('exportCompression').textContent = '-';
    } else {
        document.getElementById('exportFormat').textContent = (exportSettings.format || 'xlsx').toUpperCase();
        document.getElementById('exportCompression').textContent = (exportSettings.compression || 'zip').toUpperCase();
        
        // Calculate export size and rows from preview data
        const previewData = JSON.parse(sessionStorage.getItem('sql_alert_preview_data') || '{}');
        const maxRows = parseInt(exportSettings.maxRows || '1000');
        const totalRows = previewData.totalRows || 0;
        const exportRows = maxRows === 0 ? totalRows : Math.min(maxRows, totalRows);
        
        document.getElementById('exportRows').textContent = exportRows.toLocaleString();
        
        // Estimate file size
        const sizeMultipliers = {
            'xlsx': 1.2,
            'csv': 0.7,
            'pdf': 2.5,
            'json': 1.5
        };
        const multiplier = sizeMultipliers[exportSettings.format] || 1;
        const estimatedSize = (exportRows * (previewData.columns || 7) * 20 * multiplier / 1024).toFixed(1);
        
        document.getElementById('exportSize').textContent = estimatedSize + ' KB';
    }
}

async function refreshStats() {
    showProgressSection();
    
    try {
        // Simulate data analysis with progress
        await simulateStatsGeneration();
        
        // Generate mock statistics
        const stats = generateMockStats();
        statsData = stats;
        
        // Update displays
        updateStatsDisplay(stats);
        updateVariables(stats);
        
        // Save stats
        sessionStorage.setItem('sql_alert_stats_data', JSON.stringify(stats));
        sessionStorage.setItem('sql_alert_stats_timestamp', new Date().toISOString());
        
        // Update timestamp
        document.getElementById('statsTimestamp').textContent = new Date().toLocaleString('th-TH');
        
    } catch (error) {
        console.error('Error refreshing stats:', error);
        alert('เกิดข้อผิดพลาดในการรีเฟรชสถิติ');
    } finally {
        hideProgressSection();
    }
}

function showProgressSection() {
    const progressSection = document.getElementById('progressSection');
    progressSection.style.display = 'block';
    
    // Scroll to progress section
    progressSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function hideProgressSection() {
    const progressSection = document.getElementById('progressSection');
    progressSection.style.display = 'none';
}

async function simulateStatsGeneration() {
    const progressSteps = [
        { message: 'กำลังเชื่อมต่อฐานข้อมูล...', progress: 10 },
        { message: 'กำลังนับจำนวนแถว...', progress: 25 },
        { message: 'กำลังวิเคราะห์โครงสร้างข้อมูล...', progress: 40 },
        { message: 'กำลังคำนวณขนาดข้อมูล...', progress: 55 },
        { message: 'กำลังประมวลผลสถิติ...', progress: 70 },
        { message: 'กำลังสร้างตัวแปร...', progress: 85 },
        { message: 'เสร็จสิ้น!', progress: 100 }
    ];
    
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    for (const step of progressSteps) {
        progressBar.style.width = step.progress + '%';
        progressText.textContent = step.message;
        
        await new Promise(resolve => setTimeout(resolve, 600 + Math.random() * 400));
    }
}

function generateMockStats() {
    const previewData = JSON.parse(sessionStorage.getItem('sql_alert_preview_data') || '{}');
    const totalRows = previewData.totalRows || Math.floor(Math.random() * 1000) + 50;
    const totalColumns = previewData.columns || 7;
    const executionTime = parseFloat(previewData.executionTime || (Math.random() * 2 + 0.1).toFixed(3));
    
    // Calculate derived statistics
    const nonEmptyRows = Math.floor(totalRows * (0.85 + Math.random() * 0.1)); // 85-95%
    const emptyRows = totalRows - nonEmptyRows;
    const nullValues = Math.floor(totalRows * totalColumns * (Math.random() * 0.05)); // 0-5%
    const uniqueValues = Math.floor(totalRows * (0.7 + Math.random() * 0.2)); // 70-90%
    
    const connectionTime = (Math.random() * 0.5 + 0.1).toFixed(3);
    const rowsPerSecond = Math.floor(totalRows / executionTime);
    const memoryUsage = (totalRows * totalColumns * 50 / 1024 / 1024).toFixed(1); // MB
    
    const rawDataSize = totalRows * totalColumns * 25; // bytes
    const compressionRatio = 0.3 + Math.random() * 0.3; // 30-60%
    const compressedSize = Math.floor(rawDataSize * compressionRatio);
    const avgRowSize = Math.floor(rawDataSize / totalRows);
    
    const now = new Date();
    const startTime = new Date(now.getTime() - executionTime * 1000);
    
    return {
        // Basic stats
        totalRecords: totalRows,
        totalColumns: totalColumns,
        queryTime: executionTime + 's',
        dataSize: (rawDataSize / 1024).toFixed(1) + ' KB',
        
        // Data stats
        nonEmptyRows: nonEmptyRows.toLocaleString(),
        emptyRows: emptyRows.toLocaleString(),
        nullValues: nullValues.toLocaleString(),
        uniqueValues: uniqueValues.toLocaleString(),
        
        // Performance stats
        connectionTime: connectionTime + 's',
        executionTime: executionTime + 's',
        rowsPerSecond: rowsPerSecond.toLocaleString(),
        memoryUsage: memoryUsage + ' MB',
        
        // Size stats
        rawDataSize: (rawDataSize / 1024).toFixed(1) + ' KB',
        compressedSize: (compressedSize / 1024).toFixed(1) + ' KB',
        compressionRatio: (compressionRatio * 100).toFixed(0) + '%',
        avgRowSize: avgRowSize + ' bytes',
        
        // Time stats
        startTime: startTime.toLocaleTimeString('th-TH'),
        endTime: now.toLocaleTimeString('th-TH'),
        latestRecord: '2025-07-11 14:30:00',
        oldestRecord: '2025-07-10 08:15:00',
        
        // Raw values for variables
        rawValues: {
            totalRecords: totalRows,
            totalColumns: totalColumns,
            executionTimeSeconds: executionTime,
            dataSizeKB: (rawDataSize / 1024).toFixed(1),
            compressionRatioPercent: (compressionRatio * 100).toFixed(0),
            queryDate: now.toISOString().split('T')[0],
            queryDateTime: now.toISOString(),
            queryTime: now.toTimeString().split(' ')[0]
        }
    };
}

function updateStatsDisplay(stats) {
    // Update overview cards
    document.getElementById('totalRecords').textContent = stats.totalRecords.toLocaleString();
    document.getElementById('totalColumns').textContent = stats.totalColumns;
    document.getElementById('queryTime').textContent = stats.queryTime;
    document.getElementById('dataSize').textContent = stats.dataSize;
    
    // Update detailed stats
    document.getElementById('nonEmptyRows').textContent = stats.nonEmptyRows;
    document.getElementById('emptyRows').textContent = stats.emptyRows;
    document.getElementById('nullValues').textContent = stats.nullValues;
    document.getElementById('uniqueValues').textContent = stats.uniqueValues;
    
    document.getElementById('connectionTime').textContent = stats.connectionTime;
    document.getElementById('executionTime').textContent = stats.executionTime;
    document.getElementById('rowsPerSecond').textContent = stats.rowsPerSecond;
    document.getElementById('memoryUsage').textContent = stats.memoryUsage;
    
    document.getElementById('rawDataSize').textContent = stats.rawDataSize;
    document.getElementById('compressedSize').textContent = stats.compressedSize;
    document.getElementById('compressionRatio').textContent = stats.compressionRatio;
    document.getElementById('avgRowSize').textContent = stats.avgRowSize;
    
    document.getElementById('startTime').textContent = stats.startTime;
    document.getElementById('endTime').textContent = stats.endTime;
    document.getElementById('latestRecord').textContent = stats.latestRecord;
    document.getElementById('oldestRecord').textContent = stats.oldestRecord;
}

function updateVariables(stats) {
    const variablesGrid = document.getElementById('variablesGrid');
    const exportSettings = JSON.parse(sessionStorage.getItem('sql_alert_export_settings') || '{}');
    
    // System variables
    const systemVariables = [
        {
            name: 'record_count',
            value: stats.rawValues.totalRecords,
            description: 'จำนวนแถวข้อมูลทั้งหมด'
        },
        {
            name: 'column_count',
            value: stats.rawValues.totalColumns,
            description: 'จำนวนคอลัมน์ทั้งหมด'
        },
        {
            name: 'query_date',
            value: stats.rawValues.queryDate,
            description: 'วันที่รัน Query'
        },
        {
            name: 'query_time',
            value: stats.rawValues.queryTime,
            description: 'เวลาที่รัน Query'
        },
        {
            name: 'query_datetime',
            value: stats.rawValues.queryDateTime,
            description: 'วันที่และเวลาที่รัน Query'
        },
        {
            name: 'execution_time',
            value: stats.rawValues.executionTimeSeconds + 's',
            description: 'เวลาในการประมวลผล Query'
        },
        {
            name: 'data_size',
            value: stats.rawValues.dataSizeKB + ' KB',
            description: 'ขนาดข้อมูลที่ได้'
        },
        {
            name: 'compression_ratio',
            value: stats.rawValues.compressionRatioPercent + '%',
            description: 'อัตราการบีบอัดข้อมูล'
        }
    ];
    
    // Add export-related variables if enabled
    if (exportSettings.enabled !== false) {
        systemVariables.push(
            {
                name: 'export_filename',
                value: document.getElementById('filenamePreview')?.textContent || 'alert_data.xlsx',
                description: 'ชื่อไฟล์ที่ส่งออก'
            },
            {
                name: 'export_format',
                value: (exportSettings.format || 'xlsx').toUpperCase(),
                description: 'รูปแบบไฟล์ที่ส่งออก'
            },
            {
                name: 'export_size',
                value: document.getElementById('exportSize')?.textContent || '-',
                description: 'ขนาดไฟล์ที่ส่งออก'
            }
        );
    }
    
    // Load custom variables
    const customVariables = JSON.parse(sessionStorage.getItem('sql_alert_variables') || '[]');
    
    // Clear and populate variables grid
    variablesGrid.innerHTML = '';
    
    // Add system variables
    systemVariables.forEach(variable => {
        const card = createVariableCard(variable.name, variable.value, variable.description, 'system');
        variablesGrid.appendChild(card);
    });
    
    // Add custom variables
    customVariables.forEach(variable => {
        const card = createVariableCard(variable.name, '[จากข้อมูล]', variable.description, 'custom');
        variablesGrid.appendChild(card);
    });
    
    // Save computed variables for next steps
    const computedVariables = systemVariables.map(v => ({
        name: v.name,
        value: v.value,
        description: v.description,
        type: 'system'
    }));
    
    sessionStorage.setItem('sql_alert_computed_variables', JSON.stringify(computedVariables));
}

function createVariableCard(name, value, description, type) {
    const card = document.createElement('div');
    card.className = 'variable-card';
    
    const typeColors = {
        'system': '#7c3aed',
        'custom': '#059669'
    };
    
    card.innerHTML = `
        <div class="variable-name" style="color: ${typeColors[type] || '#7c3aed'}">{{${name}}}</div>
        <div class="variable-value">${value}</div>
        <div class="variable-description">${description}</div>
    `;
    
    return card;
}

function previousStep() {
    window.location.href = '{{ route("sql-alerts.create") }}?step=7';
}

function nextStep() {
    // Ensure stats are generated
    if (!statsData || Object.keys(statsData).length === 0) {
        alert('กรุณารีเฟรชสถิติก่อนดำเนินการต่อ');
        return;
    }
    
    sessionStorage.setItem('sql_alert_step', '9');
    window.location.href = '{{ route("sql-alerts.create") }}?step=9';
}

// Auto-update export summary when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Update export summary based on current stats
    setTimeout(() => {
        if (statsData && statsData.rawValues) {
            const exportSettings = JSON.parse(sessionStorage.getItem('sql_alert_export_settings') || '{}');
            
            if (exportSettings.enabled !== false) {
                const maxRows = parseInt(exportSettings.maxRows || '1000');
                const totalRows = statsData.rawValues.totalRecords;
                const exportRows = maxRows === 0 ? totalRows : Math.min(maxRows, totalRows);
                
                document.getElementById('exportRows').textContent = exportRows.toLocaleString();
                
                // Recalculate size based on actual stats
                const sizeMultipliers = {
                    'xlsx': 1.2,
                    'csv': 0.7,
                    'pdf': 2.5,
                    'json': 1.5
                };
                const multiplier = sizeMultipliers[exportSettings.format || 'xlsx'] || 1;
                const estimatedSize = (exportRows * statsData.rawValues.totalColumns * 20 * multiplier / 1024).toFixed(1);
                
                document.getElementById('exportSize').textContent = estimatedSize + ' KB';
            }
        }
    }, 1000);
});
</script>
@endpush
@endsection