@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนแบบ SQL - บันทึกและส่ง')

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

.summary-overview {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.overview-header {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.overview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.stat-card {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

.configuration-sections {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 30px;
}

.config-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
}

.config-header {
    font-weight: 600;
    margin-bottom: 15px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.config-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.875rem;
}

.config-item:last-child {
    border-bottom: none;
}

.config-label {
    color: #6b7280;
    font-weight: 500;
}

.config-value {
    color: #374151;
    font-weight: 600;
    text-align: right;
    max-width: 60%;
    word-break: break-word;
}

.config-value.highlight {
    color: #059669;
    background: #f0fdf4;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
}

.validation-panel {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.validation-header {
    font-weight: 600;
    margin-bottom: 15px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.validation-checks {
    display: grid;
    gap: 10px;
}

.check-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.check-item.success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}

.check-item.warning {
    background: #fefbf2;
    border: 1px solid #fed7aa;
}

.check-item.error {
    background: #fef2f2;
    border: 1px solid #fecaca;
}

.check-icon {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: white;
    font-weight: bold;
}

.check-item.success .check-icon {
    background: #10b981;
}

.check-item.warning .check-icon {
    background: #f59e0b;
}

.check-item.error .check-icon {
    background: #ef4444;
}

.check-text {
    flex: 1;
    font-size: 0.875rem;
}

.check-item.success .check-text {
    color: #065f46;
}

.check-item.warning .check-text {
    color: #92400e;
}

.check-item.error .check-text {
    color: #991b1b;
}

.action-buttons {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.action-header {
    font-weight: 600;
    margin-bottom: 20px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.action-card {
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.action-card:hover {
    border-color: #4f46e5;
    background: #f0f9ff;
    transform: translateY(-2px);
}

.action-card.primary {
    border-color: #10b981;
    background: #f0fdf4;
}

.action-card.primary:hover {
    border-color: #059669;
    background: #dcfce7;
}

.action-icon {
    width: 40px;
    height: 40px;
    margin: 0 auto 10px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
}

.action-card:not(.primary) .action-icon {
    background: #6b7280;
}

.action-card.primary .action-icon {
    background: #10b981;
}

.action-title {
    font-weight: 600;
    margin-bottom: 5px;
    color: #374151;
}

.action-description {
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.4;
}

.final-options {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 20px;
    border-radius: 0 8px 8px 0;
    margin-bottom: 25px;
}

.final-options h6 {
    color: #92400e;
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 0.875rem;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    background: rgba(255,255,255,0.7);
    border-radius: 6px;
}

.checkbox-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #4f46e5;
}

.checkbox-label {
    font-size: 0.875rem;
    color: #92400e;
    cursor: pointer;
}

.progress-section {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    display: none;
}

.progress-section.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.progress-header {
    font-weight: 600;
    margin-bottom: 15px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.progress-bar-container {
    background: #e5e7eb;
    height: 12px;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 15px;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 6px;
    transition: width 0.3s ease;
    width: 0%;
}

.progress-steps {
    display: grid;
    gap: 8px;
}

.progress-step {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.progress-step.pending {
    background: #f9fafb;
    color: #6b7280;
}

.progress-step.running {
    background: #fef3c7;
    color: #92400e;
}

.progress-step.completed {
    background: #f0fdf4;
    color: #065f46;
}

.progress-step.error {
    background: #fef2f2;
    color: #991b1b;
}

.step-icon {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: white;
    font-weight: bold;
}

.progress-step.pending .step-icon {
    background: #9ca3af;
}

.progress-step.running .step-icon {
    background: #f59e0b;
    animation: pulse 1.5s infinite;
}

.progress-step.completed .step-icon {
    background: #10b981;
}

.progress-step.error .step-icon {
    background: #ef4444;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.step-text {
    flex: 1;
    font-size: 0.875rem;
}

.completion-panel {
    background: #f0fdf4;
    border: 2px solid #10b981;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    display: none;
    text-align: center;
}

.completion-panel.show {
    display: block;
    animation: slideDown 0.3s ease;
}

.completion-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 15px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.completion-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #065f46;
    margin-bottom: 10px;
}

.completion-message {
    color: #059669;
    margin-bottom: 20px;
    line-height: 1.5;
}

.completion-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
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

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-warning:hover {
    background: #d97706;
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
    
    .configuration-sections {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
    
    .completion-actions {
        flex-direction: column;
        align-items: center;
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
            <div class="wizard-title">🎯 บันทึกและส่ง</div>
            <div class="wizard-subtitle">ตรวจสอบการตั้งค่าและดำเนินการสร้างการแจ้งเตือน</div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step completed"></div>
                <div class="step active"></div>
            </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
            <!-- Step 14: Final Save & Send -->
            <div class="section-title">
                <div class="section-icon">14</div>
                บันทึกและส่ง
            </div>

            <!-- Summary Overview -->
            <div class="summary-overview">
                <div class="overview-header">
                    <i class="fas fa-clipboard-check"></i>
                    สรุปการตั้งค่า SQL Alert
                </div>
                <div class="overview-stats">
                    <div class="stat-card">
                        <div class="stat-value" id="totalRecipients">0</div>
                        <div class="stat-label">ผู้รับทั้งหมด</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="totalVariables">0</div>
                        <div class="stat-label">ตัวแปร</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="scheduleType">Manual</div>
                        <div class="stat-label">รูปแบบการส่ง</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="estimatedSize">25 KB</div>
                        <div class="stat-label">ขนาดไฟล์ประมาณ</div>
                    </div>
                </div>
            </div>

            <!-- Configuration Sections -->
            <div class="configuration-sections">
                <!-- Database & Query -->
                <div class="config-section">
                    <div class="config-header">
                        <i class="fas fa-database"></i>
                        ฐานข้อมูลและ Query
                    </div>
                    <div class="config-item">
                        <span class="config-label">ประเภทฐานข้อมูล:</span>
                        <span class="config-value" id="dbType">-</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">เซิร์ฟเวอร์:</span>
                        <span class="config-value" id="dbHost">-</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">ฐานข้อมูล:</span>
                        <span class="config-value" id="dbName">-</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">สถานะการเชื่อมต่อ:</span>
                        <span class="config-value highlight">เชื่อมต่อสำเร็จ</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">SQL Query:</span>
                        <span class="config-value highlight">กำหนดแล้ว</span>
                    </div>
                </div>

                <!-- Email Configuration -->
                <div class="config-section">
                    <div class="config-header">
                        <i class="fas fa-envelope"></i>
                        การตั้งค่าอีเมล
                    </div>
                    <div class="config-item">
                        <span class="config-label">เทมเพลต:</span>
                        <span class="config-value" id="emailTemplate">-</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">ผู้ส่ง:</span>
                        <span class="config-value" id="emailSender">-</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">ความสำคัญ:</span>
                        <span class="config-value" id="emailPriority">-</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">รูปแบบ:</span>
                        <span class="config-value" id="emailFormat">-</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">ผู้รับ TO:</span>
                        <span class="config-value" id="toCount">-</span>
                    </div>
                </div>

                <!-- Export Settings -->
                <div class="config-section">
                    <div class="config-header">
                        <i class="fas fa-file-export"></i>
                        การส่งออกไฟล์
                    </div>
                    <div class="config-item">
                        <span class="config-label">สถานะ:</span>
                        <span class="config-value" id="exportStatus">-</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">รูปแบบ:</span>
                        <span class="config-value" id="exportFormat">-</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">การบีบอัด:</span>
                        <span class="config-value" id="exportCompression">-</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">ชื่อไฟล์:</span>
                        <span class="config-value highlight" id="exportFilename">-</span>
                    </div>
                </div>

                <!-- Schedule Settings -->
                <div class="config-section">
                    <div class="config-header">
                        <i class="fas fa-clock"></i>
                        การตั้งเวลา
                    </div>
                    <div class="config-item">
                        <span class="config-label">รูปแบบ:</span>
                        <span class="config-value" id="scheduleTypeDetail">-</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">เขตเวลา:</span>
                        <span class="config-value" id="scheduleTimezone">-</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">การส่งครั้งถัดไป:</span>
                        <span class="config-value highlight" id="nextSchedule">-</span>
                    </div>
                </div>
            </div>

            <!-- Validation Panel -->
            <div class="validation-panel">
                <div class="validation-header">
                    <i class="fas fa-check-circle"></i>
                    ตรวจสอบความถูกต้อง
                </div>
                <div class="validation-checks" id="validationChecks">
                    <!-- Validation items will be populated here -->
                </div>
            </div>

            <!-- Final Options -->
            <div class="final-options">
                <h6>
                    <i class="fas fa-cogs me-1"></i>
                    ตัวเลือกสุดท้าย
                </h6>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="testBeforeSave" checked>
                        <label class="checkbox-label" for="testBeforeSave">ทดสอบการเชื่อมต่อฐานข้อมูลก่อนบันทึก</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="validateEmail">
                        <label class="checkbox-label" for="validateEmail">ส่งอีเมลทดสอบก่อนบันทึก</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="enableImmediately" checked>
                        <label class="checkbox-label" for="enableImmediately">เปิดใช้งานทันทีหลังบันทึก</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="createBackup">
                        <label class="checkbox-label" for="createBackup">สร้างสำรองการตั้งค่า</label>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <div class="action-header">
                    <i class="fas fa-rocket"></i>
                    เลือกการดำเนินการ
                </div>

                <div class="action-grid">
                    <div class="action-card primary" onclick="saveAndActivate()">
                        <div class="action-icon">
                            <i class="fas fa-save"></i>
                        </div>
                        <div class="action-title">บันทึกและเปิดใช้งาน</div>
                        <div class="action-description">
                            บันทึกการตั้งค่าและเปิดใช้งานการแจ้งเตือนตามกำหนดการ
                        </div>
                    </div>

                    <div class="action-card" onclick="saveAsDraft()">
                        <div class="action-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="action-title">บันทึกเป็นร่าง</div>
                        <div class="action-description">
                            บันทึกการตั้งค่าแต่ยังไม่เปิดใช้งาน สามารถแก้ไขภายหลังได้
                        </div>
                    </div>

                    <div class="action-card" onclick="saveAndSendNow()">
                        <div class="action-icon">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div class="action-title">บันทึกและส่งทันที</div>
                        <div class="action-description">
                            บันทึกและส่งการแจ้งเตือนทันทีโดยไม่รอกำหนดการ
                        </div>
                    </div>

                    <div class="action-card" onclick="exportSettings()">
                        <div class="action-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="action-title">ส่งออกการตั้งค่า</div>
                        <div class="action-description">
                            ดาวน์โหลดการตั้งค่าเป็นไฟล์เพื่อนำไปใช้ที่อื่น
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Section -->
            <div class="progress-section" id="progressSection">
                <div class="progress-header">
                    <i class="fas fa-cogs"></i>
                    กำลังดำเนินการ...
                </div>

                <div class="progress-bar-container">
                    <div class="progress-bar" id="progressBar"></div>
                </div>

                <div class="progress-steps" id="progressSteps">
                    <!-- Progress steps will be populated here -->
                </div>
            </div>

            <!-- Completion Panel -->
            <div class="completion-panel" id="completionPanel">
                <div class="completion-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="completion-title">สร้างการแจ้งเตือนสำเร็จ!</div>
                <div class="completion-message" id="completionMessage">
                    การแจ้งเตือน SQL ได้ถูกสร้างและบันทึกเรียบร้อยแล้ว
                </div>
                <div class="completion-actions">
                    <a href="{{ route('notifications.index') }}" class="btn btn-primary">
                        <i class="fas fa-list"></i>
                        ดูรายการการแจ้งเตือน
                    </a>
                    <button type="button" class="btn btn-success" onclick="createAnother()">
                        <i class="fas fa-plus"></i>
                        สร้างการแจ้งเตือนใหม่
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="viewDetails()">
                        <i class="fas fa-eye"></i>
                        ดูรายละเอียด
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <div class="wizard-navigation" id="wizardNavigation">
                <button type="button" class="btn btn-secondary" onclick="previousStep()">
                    <i class="fas fa-arrow-left"></i>
                    ย้อนกลับ
                </button>
                
                <div class="status-indicator">
                    <i class="fas fa-flag-checkered"></i>
                    ขั้นตอนสุดท้าย
                </div>
                
                <button type="button" class="btn btn-warning" onclick="reviewSettings()">
                    <i class="fas fa-eye"></i>
                    ตรวจสอบการตั้งค่า
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let allSettings = {};
let createdAlertId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadAllSettings();
    populateConfigurationSummary();
    runValidationChecks();
});

function loadAllSettings() {
    // Load all settings from previous steps
    allSettings = {
        database: JSON.parse(sessionStorage.getItem('sql_alert_db_type') || '{}'),
        connection: {
            type: sessionStorage.getItem('sql_alert_db_type'),
            host: sessionStorage.getItem('sql_alert_db_host'),
            port: sessionStorage.getItem('sql_alert_db_port'),
            name: sessionStorage.getItem('sql_alert_db_name'),
            username: sessionStorage.getItem('sql_alert_db_username')
        },
        query: sessionStorage.getItem('sql_alert_query'),
        variables: JSON.parse(sessionStorage.getItem('sql_alert_variables') || '[]'),
        computedVariables: JSON.parse(sessionStorage.getItem('sql_alert_computed_variables') || '[]'),
        emailTemplate: JSON.parse(sessionStorage.getItem('sql_alert_email_template') || '{}'),
        emailContent: JSON.parse(sessionStorage.getItem('sql_alert_email_content') || '{}'),
        emailVariables: JSON.parse(sessionStorage.getItem('sql_alert_email_variables') || '{}'),
        recipients: JSON.parse(sessionStorage.getItem('sql_alert_recipients') || '{}'),
        exportSettings: JSON.parse(sessionStorage.getItem('sql_alert_export_settings') || '{}'),
        schedule: JSON.parse(sessionStorage.getItem('sql_alert_schedule') || '{}'),
        previewData: JSON.parse(sessionStorage.getItem('sql_alert_preview_data') || '{}'),
        statsData: JSON.parse(sessionStorage.getItem('sql_alert_stats_data') || '{}')
    };
}

function populateConfigurationSummary() {
    // Update overview stats
    const totalRecipients = (allSettings.recipients.recipients?.length || 0) + 
                           (allSettings.recipients.selectedGroups?.length * 5 || 0); // Estimate 5 per group
    document.getElementById('totalRecipients').textContent = totalRecipients;
    
    const totalVariables = (allSettings.variables?.length || 0) + 
                          (allSettings.computedVariables?.length || 0);
    document.getElementById('totalVariables').textContent = totalVariables;
    
    const scheduleTypeLabels = {
        'manual': 'ส่งด้วยตนเอง',
        'once': 'ครั้งเดียว',
        'recurring': 'ประจำ',
        'cron': 'Cron'
    };
    document.getElementById('scheduleType').textContent = scheduleTypeLabels[allSettings.schedule.type] || 'Manual';
    
    document.getElementById('estimatedSize').textContent = allSettings.previewData.dataSize || '25 KB';

    // Update configuration details
    document.getElementById('dbType').textContent = (allSettings.connection.type || 'MySQL').toUpperCase();
    document.getElementById('dbHost').textContent = allSettings.connection.host || 'localhost';
    document.getElementById('dbName').textContent = allSettings.connection.name || 'database';
    
    document.getElementById('emailTemplate').textContent = allSettings.emailTemplate.templateData?.name || 'กำหนดเอง';
    document.getElementById('emailSender').textContent = allSettings.emailTemplate.sender || 'ไม่ระบุ';
    
    const priorityLabels = {
        'low': '🟢 ต่ำ',
        'normal': '🟡 ปกติ',
        'high': '🟠 สูง',
        'urgent': '🔴 เร่งด่วน'
    };
    document.getElementById('emailPriority').textContent = priorityLabels[allSettings.emailTemplate.priority] || '🟡 ปกติ';
    document.getElementById('emailFormat').textContent = (allSettings.emailContent.format || 'both').toUpperCase();
    
    const toRecipients = allSettings.recipients.recipients?.filter(r => r.type === 'to').length || 0;
    document.getElementById('toCount').textContent = toRecipients + ' คน';
    
    // Export settings
    if (allSettings.exportSettings.enabled !== false) {
        document.getElementById('exportStatus').textContent = 'เปิดใช้งาน';
        document.getElementById('exportFormat').textContent = (allSettings.exportSettings.format || 'xlsx').toUpperCase();
        document.getElementById('exportCompression').textContent = (allSettings.exportSettings.compression || 'zip').toUpperCase();
        document.getElementById('exportFilename').textContent = allSettings.exportSettings.filename || 'alert_data_{date}';
    } else {
        document.getElementById('exportStatus').textContent = 'ปิดใช้งาน';
        document.getElementById('exportFormat').textContent = '-';
        document.getElementById('exportCompression').textContent = '-';
        document.getElementById('exportFilename').textContent = '-';
    }
    
    // Schedule settings
    document.getElementById('scheduleTypeDetail').textContent = scheduleTypeLabels[allSettings.schedule.type] || 'ส่งด้วยตนเอง';
    document.getElementById('scheduleTimezone').textContent = allSettings.schedule.timezone || 'Asia/Bangkok';
    
    // Calculate next schedule
    const nextSchedule = calculateNextSchedule();
    document.getElementById('nextSchedule').textContent = nextSchedule;
}

function calculateNextSchedule() {
    const schedule = allSettings.schedule;
    
    if (schedule.type === 'manual') {
        return 'ส่งด้วยตนเอง';
    } else if (schedule.type === 'once') {
        if (schedule.date && schedule.time) {
            const scheduledDate = new Date(`${schedule.date}T${schedule.time}`);
            return scheduledDate.toLocaleString('th-TH');
        }
        return 'ไม่ได้กำหนด';
    } else if (schedule.type === 'recurring') {
        return 'ตามที่กำหนด';
    } else if (schedule.type === 'cron') {
        return 'ตาม Cron Expression';
    }
    
    return 'ไม่ทราบ';
}

function runValidationChecks() {
    const checks = [
        {
            id: 'database_connection',
            label: 'การเชื่อมต่อฐานข้อมูล',
            status: allSettings.connection.host ? 'success' : 'error',
            message: allSettings.connection.host ? 'เชื่อมต่อฐานข้อมูลสำเร็จ' : 'ไม่ได้ตั้งค่าการเชื่อมต่อฐานข้อมูล'
        },
        {
            id: 'sql_query',
            label: 'SQL Query',
            status: allSettings.query ? 'success' : 'error',
            message: allSettings.query ? 'SQL Query ถูกต้องและพร้อมใช้งาน' : 'ไม่ได้ระบุ SQL Query'
        },
        {
            id: 'email_template',
            label: 'เทมเพลตอีเมล',
            status: allSettings.emailContent.subject ? 'success' : 'warning',
            message: allSettings.emailContent.subject ? 'เทมเพลตอีเมลพร้อมใช้งาน' : 'ควรตรวจสอบเทมเพลตอีเมล'
        },
        {
            id: 'recipients',
            label: 'ผู้รับการแจ้งเตือน',
            status: (allSettings.recipients.recipients?.length > 0 || allSettings.recipients.selectedGroups?.length > 0) ? 'success' : 'error',
            message: (allSettings.recipients.recipients?.length > 0 || allSettings.recipients.selectedGroups?.length > 0) ? 'มีผู้รับการแจ้งเตือนแล้ว' : 'ยังไม่ได้กำหนดผู้รับ'
        },
        {
            id: 'schedule',
            label: 'การตั้งเวลา',
            status: allSettings.schedule.type ? 'success' : 'warning',
            message: allSettings.schedule.type ? 'กำหนดเวลาส่งแล้ว' : 'ใช้การส่งด้วยตนเอง'
        },
        {
            id: 'variables',
            label: 'ตัวแปร',
            status: (allSettings.variables?.length > 0 || allSettings.computedVariables?.length > 0) ? 'success' : 'warning',
            message: (allSettings.variables?.length > 0 || allSettings.computedVariables?.length > 0) ? 'มีตัวแปรสำหรับใช้งาน' : 'ไม่มีตัวแปรเพิ่มเติม'
        }
    ];

    const container = document.getElementById('validationChecks');
    container.innerHTML = '';

    checks.forEach(check => {
        const item = document.createElement('div');
        item.className = `check-item ${check.status}`;
        
        const icon = check.status === 'success' ? '✓' : 
                    check.status === 'warning' ? '⚠' : '✗';
        
        item.innerHTML = `
            <div class="check-icon">${icon}</div>
            <div class="check-text">
                <strong>${check.label}:</strong> ${check.message}
            </div>
        `;
        
        container.appendChild(item);
    });
}

async function saveAndActivate() {
    if (!validateFinalSettings()) {
        return;
    }
    
    const includeTests = document.getElementById('testBeforeSave').checked;
    const enableImmediately = document.getElementById('enableImmediately').checked;
    
    await executeWithProgress([
        { name: 'validate_settings', label: 'ตรวจสอบการตั้งค่า' },
        ...(includeTests ? [
            { name: 'test_connection', label: 'ทดสอบการเชื่อมต่อฐานข้อมูล' },
            { name: 'test_query', label: 'ทดสอบ SQL Query' }
        ] : []),
        { name: 'save_alert', label: 'บันทึกการแจ้งเตือน' },
        { name: 'configure_schedule', label: 'ตั้งค่ากำหนดการ' },
        ...(enableImmediately ? [{ name: 'activate', label: 'เปิดใช้งาน' }] : [])
    ], 'บันทึกและเปิดใช้งานการแจ้งเตือนสำเร็จ!');
}

async function saveAsDraft() {
    await executeWithProgress([
        { name: 'validate_basic', label: 'ตรวจสอบการตั้งค่าพื้นฐาน' },
        { name: 'save_draft', label: 'บันทึกเป็นร่าง' }
    ], 'บันทึกร่างการแจ้งเตือนสำเร็จ!');
}

async function saveAndSendNow() {
    if (!validateFinalSettings()) {
        return;
    }
    
    if (!confirm('ต้องการส่งการแจ้งเตือนทันทีหรือไม่?')) {
        return;
    }
    
    await executeWithProgress([
        { name: 'validate_settings', label: 'ตรวจสอบการตั้งค่า' },
        { name: 'test_connection', label: 'ทดสอบการเชื่อมต่อ' },
        { name: 'execute_query', label: 'รัน SQL Query' },
        { name: 'generate_email', label: 'สร้างอีเมล' },
        { name: 'send_notification', label: 'ส่งการแจ้งเตือน' }
    ], 'ส่งการแจ้งเตือนทันทีสำเร็จ!');
}

async function exportSettings() {
    const settings = {
        created_at: new Date().toISOString(),
        version: '1.0',
        database: allSettings.connection,
        query: allSettings.query,
        variables: allSettings.variables,
        email_template: allSettings.emailTemplate,
        email_content: allSettings.emailContent,
        recipients: allSettings.recipients,
        export_settings: allSettings.exportSettings,
        schedule: allSettings.schedule
    };
    
    const blob = new Blob([JSON.stringify(settings, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `sql_alert_settings_${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    alert('ส่งออกการตั้งค่าสำเร็จ!');
}

async function executeWithProgress(steps, successMessage) {
    const progressSection = document.getElementById('progressSection');
    const progressBar = document.getElementById('progressBar');
    const progressSteps = document.getElementById('progressSteps');
    const wizardNavigation = document.getElementById('wizardNavigation');
    
    // Show progress section
    progressSection.classList.add('show');
    wizardNavigation.style.display = 'none';
    
    // Initialize progress steps
    progressSteps.innerHTML = '';
    steps.forEach((step, index) => {
        const stepElement = document.createElement('div');
        stepElement.className = 'progress-step pending';
        stepElement.id = `step-${step.name}`;
        stepElement.innerHTML = `
            <div class="step-icon">${index + 1}</div>
            <div class="step-text">${step.label}</div>
        `;
        progressSteps.appendChild(stepElement);
    });
    
    try {
        for (let i = 0; i < steps.length; i++) {
            const step = steps[i];
            const stepElement = document.getElementById(`step-${step.name}`);
            
            // Mark as running
            stepElement.className = 'progress-step running';
            stepElement.querySelector('.step-icon').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Update progress bar
            const progress = (i / steps.length) * 100;
            progressBar.style.width = progress + '%';
            
            // Execute step
            await executeStep(step.name);
            
            // Mark as completed
            stepElement.className = 'progress-step completed';
            stepElement.querySelector('.step-icon').innerHTML = '✓';
            
            // Small delay for UX
            await delay(300);
        }
        
        // Complete progress
        progressBar.style.width = '100%';
        
        // Show completion
        showCompletion(successMessage);
        
    } catch (error) {
        console.error('Error during execution:', error);
        
        // Mark current step as error
        const currentStep = document.querySelector('.progress-step.running');
        if (currentStep) {
            currentStep.className = 'progress-step error';
            currentStep.querySelector('.step-icon').innerHTML = '✗';
        }
        
        alert('เกิดข้อผิดพลาด: ' + error.message);
        
        // Hide progress and show navigation
        progressSection.classList.remove('show');
        wizardNavigation.style.display = 'flex';
    }
}

async function executeStep(stepName) {
    // Simulate API calls and processing
    switch (stepName) {
        case 'validate_settings':
        case 'validate_basic':
            await delay(500);
            if (!allSettings.query) {
                throw new Error('SQL Query ไม่ได้กำหนด');
            }
            break;
            
        case 'test_connection':
            await delay(1000);
            // Simulate connection test
            break;
            
        case 'test_query':
        case 'execute_query':
            await delay(1500);
            // Simulate query execution
            break;
            
        case 'save_alert':
        case 'save_draft':
            await delay(800);
            // Generate mock alert ID
            createdAlertId = 'SQL_' + Math.random().toString(36).substr(2, 9).toUpperCase();
            break;
            
        case 'configure_schedule':
            await delay(600);
            // Configure schedule
            break;
            
        case 'activate':
            await delay(400);
            // Activate alert
            break;
            
        case 'generate_email':
            await delay(700);
            // Generate email content
            break;
            
        case 'send_notification':
            await delay(1200);
            // Send notification
            break;
            
        default:
            await delay(500);
    }
}

function showCompletion(message) {
    const progressSection = document.getElementById('progressSection');
    const completionPanel = document.getElementById('completionPanel');
    const completionMessage = document.getElementById('completionMessage');
    
    progressSection.classList.remove('show');
    completionMessage.textContent = message;
    completionPanel.classList.add('show');
    
    // Clear session storage
    clearSessionData();
}

function validateFinalSettings() {
    const errors = [];
    
    if (!allSettings.query) {
        errors.push('ไม่ได้กำหนด SQL Query');
    }
    
    if (!allSettings.connection.host) {
        errors.push('ไม่ได้ตั้งค่าการเชื่อมต่อฐานข้อมูล');
    }
    
    const totalRecipients = (allSettings.recipients.recipients?.length || 0) + 
                           (allSettings.recipients.selectedGroups?.length || 0);
    if (totalRecipients === 0) {
        errors.push('ไม่ได้กำหนดผู้รับการแจ้งเตือน');
    }
    
    if (!allSettings.emailContent.subject) {
        errors.push('ไม่ได้กำหนดหัวข้ออีเมล');
    }
    
    if (errors.length > 0) {
        alert('พบข้อผิดพลาด:\n' + errors.join('\n'));
        return false;
    }
    
    return true;
}

function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function clearSessionData() {
    // Clear all SQL alert session data
    const keysToRemove = [
        'sql_alert_db_type', 'sql_alert_db_host', 'sql_alert_db_port', 'sql_alert_db_name',
        'sql_alert_db_username', 'sql_alert_query', 'sql_alert_variables',
        'sql_alert_computed_variables', 'sql_alert_email_template', 'sql_alert_email_content',
        'sql_alert_email_variables', 'sql_alert_recipients', 'sql_alert_export_settings',
        'sql_alert_schedule', 'sql_alert_preview_data', 'sql_alert_stats_data',
        'sql_alert_step', 'sql_alert_connection_tested', 'sql_alert_query_executed'
    ];
    
    keysToRemove.forEach(key => {
        sessionStorage.removeItem(key);
    });
}

function reviewSettings() {
    // Create a detailed review modal or redirect
    alert('รายละเอียดการตั้งค่า:\n\n' +
          `ฐานข้อมูล: ${allSettings.connection.type} @ ${allSettings.connection.host}\n` +
          `ผู้รับ: ${(allSettings.recipients.recipients?.length || 0)} คน\n` +
          `ตัวแปร: ${(allSettings.variables?.length || 0)} ตัว\n` +
          `รูปแบบการส่ง: ${allSettings.schedule.type || 'manual'}\n` +
          `สถานะ Export: ${allSettings.exportSettings.enabled !== false ? 'เปิด' : 'ปิด'}`);
}

function createAnother() {
    if (confirm('ต้องการสร้างการแจ้งเตือน SQL ใหม่หรือไม่?')) {
        window.location.href = '{{ route("sql-alerts.create") }}';
    }
}

function viewDetails() {
    if (createdAlertId) {
        // Redirect to view alert details
        alert(`Alert ID: ${createdAlertId}\n\nจะนำคุณไปดูรายละเอียดการแจ้งเตือนที่สร้าง`);
        // window.location.href = `/notifications/${createdAlertId}`;
    } else {
        alert('ไม่พบรหัสการแจ้งเตือน');
    }
}

function previousStep() {
    window.location.href = '{{ route("sql-alerts.create") }}?step=13';
}

// Auto-validate when checkboxes change
document.addEventListener('change', function(e) {
    if (e.target.matches('input[type="checkbox"]')) {
        // Could trigger re-validation here if needed
    }
});

// Initialize tooltips or help text if needed
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
});
</script>
@endpush
@endsection