@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนแบบ SQL - เลือกผู้รับ')

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

.recipients-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.recipients-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
}

.section-header {
    font-weight: 600;
    margin-bottom: 20px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
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

.form-textarea {
    min-height: 120px;
    font-family: 'Courier New', monospace;
    line-height: 1.4;
    resize: vertical;
}

.form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 8px center;
    background-repeat: no-repeat;
    background-size: 16px 12px;
    padding-right: 40px;
}

.form-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 4px;
}

.groups-section {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.groups-header {
    font-weight: 600;
    margin-bottom: 20px;
    color: #374151;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.groups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.group-card {
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.group-card:hover {
    border-color: #4f46e5;
    background: #f0f9ff;
    transform: translateY(-1px);
}

.group-card.selected {
    border-color: #10b981;
    background: #f0fdf4;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1);
}

.group-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.group-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: white;
}

.group-card:not(.selected) .group-icon {
    background: #6b7280;
}

.group-card.selected .group-icon {
    background: #10b981;
}

.group-title {
    font-weight: 600;
    color: #374151;
}

.group-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 10px;
}

.group-members {
    font-size: 0.75rem;
    color: #059669;
    font-weight: 500;
}

.selected-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 20px;
    height: 20px;
    background: #10b981;
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
}

.group-card.selected .selected-indicator {
    display: flex;
}

.email-builder {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.email-input-row {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.email-input {
    flex: 1;
    padding: 8px 10px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 0.875rem;
}

.email-input:focus {
    outline: none;
    border-color: #4f46e5;
}

.btn {
    padding: 8px 16px;
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

.recipients-list {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    max-height: 200px;
    overflow-y: auto;
    margin-top: 10px;
}

.recipient-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    border-bottom: 1px solid #f3f4f6;
}

.recipient-item:last-child {
    border-bottom: none;
}

.recipient-email {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    color: #374151;
}

.recipient-type {
    font-size: 0.75rem;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: 500;
}

.recipient-type.to {
    background: #dbeafe;
    color: #1d4ed8;
}

.recipient-type.cc {
    background: #fef3c7;
    color: #92400e;
}

.recipient-type.bcc {
    background: #f3e8ff;
    color: #7c3aed;
}

.summary-section {
    background: #f0f9ff;
    border: 2px solid #bae6fd;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.summary-header {
    font-weight: 600;
    margin-bottom: 15px;
    color: #0369a1;
    display: flex;
    align-items: center;
    gap: 8px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.summary-item {
    text-align: center;
    padding: 10px;
    background: rgba(255,255,255,0.7);
    border-radius: 6px;
}

.summary-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e40af;
}

.summary-label {
    font-size: 0.75rem;
    color: #0369a1;
    margin-top: 2px;
}

.priority-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.priority-header {
    font-weight: 600;
    margin-bottom: 15px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.priority-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
}

.priority-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.priority-card:hover {
    border-color: #4f46e5;
    background: #f0f9ff;
}

.priority-card.selected {
    border-color: #10b981;
    background: #f0fdf4;
}

.priority-icon {
    font-size: 1.5rem;
    margin-bottom: 8px;
}

.priority-name {
    font-weight: 600;
    margin-bottom: 4px;
    color: #374151;
}

.priority-desc {
    font-size: 0.75rem;
    color: #6b7280;
}

.validation-section {
    background: #fef2f2;
    border-left: 4px solid #ef4444;
    padding: 15px;
    border-radius: 0 8px 8px 0;
    margin-bottom: 20px;
    display: none;
}

.validation-section.show {
    display: block;
}

.validation-header {
    font-weight: 600;
    margin-bottom: 10px;
    color: #991b1b;
    font-size: 0.875rem;
}

.validation-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.validation-list li {
    padding: 3px 0;
    color: #991b1b;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.validation-list li:before {
    content: "⚠️";
    font-size: 0.75rem;
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
    
    .recipients-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .groups-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .priority-options {
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
            <div class="wizard-title">👥 เลือกผู้รับ</div>
            <div class="wizard-subtitle">กำหนดผู้รับอีเมลและกลุ่มผู้รับสำหรับการแจ้งเตือน</div>
            
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
                <div class="step active"></div>
                <div class="step"></div>
                <div class="step"></div>
            </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
            <!-- Step 12: Recipients Selection -->
            <div class="section-title">
                <div class="section-icon">12</div>
                เลือกผู้รับ
            </div>

            <!-- Recipients Layout -->
            <div class="recipients-layout">
                <!-- Manual Recipients -->
                <div class="recipients-section">
                    <div class="section-header">
                        <i class="fas fa-edit"></i>
                        ระบุผู้รับด้วยตนเอง
                    </div>

                    <!-- Email Builder -->
                    <div class="email-builder">
                        <div class="email-input-row">
                            <input type="email" 
                                   class="email-input" 
                                   id="emailInput" 
                                   placeholder="email@example.com">
                            <select class="form-select" id="emailType" style="max-width: 80px;">
                                <option value="to">TO</option>
                                <option value="cc">CC</option>
                                <option value="bcc">BCC</option>
                            </select>
                            <button type="button" class="btn btn-primary btn-sm" onclick="addEmail()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div style="font-size: 0.75rem; color: #6b7280;">
                            กด Enter หรือคลิก + เพื่อเพิ่มอีเมล
                        </div>
                    </div>

                    <!-- Bulk Email Input -->
                    <div class="form-group">
                        <label class="form-label" for="bulkEmails">หรือใส่หลายอีเมลพร้อมกัน</label>
                        <textarea class="form-control form-textarea" 
                                  id="bulkEmails" 
                                  placeholder="admin@company.com
manager@company.com
team-lead@company.com

หรือคั่นด้วย ; หรือ , ได้"></textarea>
                        <div class="form-text">
                            คั่นด้วย Enter, เครื่องหมาย ; หรือ , ได้
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="processBulkEmails()" style="margin-top: 8px;">
                            <i class="fas fa-upload"></i>
                            เพิ่มจากรายการ
                        </button>
                    </div>

                    <!-- Recipients List -->
                    <div class="recipients-list" id="recipientsList">
                        <!-- Recipients will be populated here -->
                    </div>
                </div>

                <!-- Groups Selection -->
                <div class="recipients-section">
                    <div class="section-header">
                        <i class="fas fa-users"></i>
                        เลือกจากกลุ่มผู้ใช้
                    </div>

                    <div class="groups-grid">
                        <div class="group-card" data-group="admins" onclick="toggleGroup('admins')">
                            <div class="selected-indicator">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="group-header">
                                <div class="group-icon">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <div class="group-title">ผู้ดูแลระบบ</div>
                            </div>
                            <div class="group-description">
                                กลุ่มผู้ดูแลระบบทั้งหมด รับการแจ้งเตือนสำคัญ
                            </div>
                            <div class="group-members">👨‍💼 5 คน</div>
                        </div>

                        <div class="group-card" data-group="managers" onclick="toggleGroup('managers')">
                            <div class="selected-indicator">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="group-header">
                                <div class="group-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="group-title">ผู้จัดการ</div>
                            </div>
                            <div class="group-description">
                                ผู้จัดการทุกแผนก รับรายงานและสรุปข้อมูล
                            </div>
                            <div class="group-members">👔 12 คน</div>
                        </div>

                        <div class="group-card" data-group="it-team" onclick="toggleGroup('it-team')">
                            <div class="selected-indicator">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="group-header">
                                <div class="group-icon">
                                    <i class="fas fa-laptop-code"></i>
                                </div>
                                <div class="group-title">ทีม IT</div>
                            </div>
                            <div class="group-description">
                                ทีมเทคโนโลยีสารสนเทศ รับแจ้งเตือนระบบ
                            </div>
                            <div class="group-members">💻 8 คน</div>
                        </div>

                        <div class="group-card" data-group="hr-team" onclick="toggleGroup('hr-team')">
                            <div class="selected-indicator">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="group-header">
                                <div class="group-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="group-title">ทีม HR</div>
                            </div>
                            <div class="group-description">
                                ทีมทรัพยากรบุคคล รับข้อมูลพนักงาน
                            </div>
                            <div class="group-members">👥 6 คน</div>
                        </div>

                        <div class="group-card" data-group="finance-team" onclick="toggleGroup('finance-team')">
                            <div class="selected-indicator">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="group-header">
                                <div class="group-icon">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <div class="group-title">ทีมการเงิน</div>
                            </div>
                            <div class="group-description">
                                ทีมการเงินและบัญชี รับรายงานทางการเงิน
                            </div>
                            <div class="group-members">💰 4 คน</div>
                        </div>

                        <div class="group-card" data-group="executives" onclick="toggleGroup('executives')">
                            <div class="selected-indicator">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="group-header">
                                <div class="group-icon">
                                    <i class="fas fa-chess-king"></i>
                                </div>
                                <div class="group-title">ผู้บริหาร</div>
                            </div>
                            <div class="group-description">
                                ผู้บริหารระดับสูง รับสรุปผลรายงาน
                            </div>
                            <div class="group-members">👑 3 คน</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Priority Section -->
            <div class="priority-section">
                <div class="priority-header">
                    <i class="fas fa-flag"></i>
                    ระดับความสำคัญ
                </div>

                <div class="priority-options">
                    <div class="priority-card" data-priority="low" onclick="selectPriority('low')">
                        <div class="priority-icon">🟢</div>
                        <div class="priority-name">ต่ำ</div>
                        <div class="priority-desc">ข้อมูลทั่วไป</div>
                    </div>

                    <div class="priority-card selected" data-priority="normal" onclick="selectPriority('normal')">
                        <div class="priority-icon">🟡</div>
                        <div class="priority-name">ปกติ</div>
                        <div class="priority-desc">การแจ้งเตือนมาตรฐาน</div>
                    </div>

                    <div class="priority-card" data-priority="high" onclick="selectPriority('high')">
                        <div class="priority-icon">🟠</div>
                        <div class="priority-name">สูง</div>
                        <div class="priority-desc">ต้องดำเนินการ</div>
                    </div>

                    <div class="priority-card" data-priority="urgent" onclick="selectPriority('urgent')">
                        <div class="priority-icon">🔴</div>
                        <div class="priority-name">เร่งด่วน</div>
                        <div class="priority-desc">ต้องดำเนินการทันที</div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="summary-section">
                <div class="summary-header">
                    <i class="fas fa-clipboard-list"></i>
                    สรุปผู้รับ
                </div>

                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-value" id="totalRecipients">0</div>
                        <div class="summary-label">ผู้รับทั้งหมด</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="toRecipients">0</div>
                        <div class="summary-label">TO</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="ccRecipients">0</div>
                        <div class="summary-label">CC</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="bccRecipients">0</div>
                        <div class="summary-label">BCC</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="selectedGroups">0</div>
                        <div class="summary-label">กลุ่ม</div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 15px;">
                    <button type="button" class="btn btn-success" onclick="validateRecipients()">
                        <i class="fas fa-check-circle"></i>
                        ตรวจสอบผู้รับ
                    </button>
                </div>
            </div>

            <!-- Validation -->
            <div class="validation-section" id="validationSection">
                <div class="validation-header">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    ข้อผิดพลาด
                </div>
                <ul class="validation-list" id="validationList">
                    <!-- Validation messages will be populated here -->
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
                    ขั้นตอนที่ 12 จาก 14
                </div>
                
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    ถัดไป (ตั้งเวลาส่ง)
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let recipients = [];
let selectedGroups = [];
let selectedPriority = 'normal';

// Group data with member emails
const groupData = {
    'admins': {
        name: 'ผู้ดูแลระบบ',
        members: [
            'admin@company.com',
            'sysadmin@company.com',
            'root@company.com',
            'administrator@company.com',
            'security@company.com'
        ]
    },
    'managers': {
        name: 'ผู้จัดการ',
        members: [
            'ceo@company.com',
            'cto@company.com',
            'cfo@company.com',
            'hr-manager@company.com',
            'it-manager@company.com',
            'sales-manager@company.com',
            'marketing-manager@company.com',
            'operations-manager@company.com',
            'finance-manager@company.com',
            'project-manager@company.com',
            'dept-manager-1@company.com',
            'dept-manager-2@company.com'
        ]
    },
    'it-team': {
        name: 'ทีม IT',
        members: [
            'developer1@company.com',
            'developer2@company.com',
            'devops@company.com',
            'dba@company.com',
            'network-admin@company.com',
            'support@company.com',
            'security-analyst@company.com',
            'system-engineer@company.com'
        ]
    },
    'hr-team': {
        name: 'ทีม HR',
        members: [
            'hr1@company.com',
            'hr2@company.com',
            'recruitment@company.com',
            'payroll@company.com',
            'training@company.com',
            'employee-relations@company.com'
        ]
    },
    'finance-team': {
        name: 'ทีมการเงิน',
        members: [
            'accounting@company.com',
            'finance@company.com',
            'budget@company.com',
            'audit@company.com'
        ]
    },
    'executives': {
        name: 'ผู้บริหาร',
        members: [
            'ceo@company.com',
            'president@company.com',
            'board@company.com'
        ]
    }
};

document.addEventListener('DOMContentLoaded', function() {
    loadSavedData();
    updateSummary();
    
    // Add Enter key support for email input
    document.getElementById('emailInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addEmail();
        }
    });
});

function loadSavedData() {
    const saved = sessionStorage.getItem('sql_alert_recipients');
    if (saved) {
        try {
            const data = JSON.parse(saved);
            
            recipients = data.recipients || [];
            selectedGroups = data.selectedGroups || [];
            selectedPriority = data.priority || 'normal';
            
            // Update UI
            updateRecipientsList();
            updateGroupsUI();
            selectPriority(selectedPriority);
            updateSummary();
            
        } catch (e) {
            console.error('Error loading saved recipients:', e);
        }
    }
}

function addEmail() {
    const emailInput = document.getElementById('emailInput');
    const emailType = document.getElementById('emailType').value;
    const email = emailInput.value.trim();
    
    if (!email) {
        alert('กรุณาใส่อีเมล');
        return;
    }
    
    if (!isValidEmail(email)) {
        alert('รูปแบบอีเมลไม่ถูกต้อง');
        return;
    }
    
    // Check for duplicates
    if (recipients.find(r => r.email === email)) {
        alert('อีเมลนี้มีอยู่แล้ว');
        return;
    }
    
    recipients.push({
        email: email,
        type: emailType,
        source: 'manual'
    });
    
    emailInput.value = '';
    updateRecipientsList();
    updateSummary();
    saveData();
}

function processBulkEmails() {
    const bulkText = document.getElementById('bulkEmails').value.trim();
    if (!bulkText) {
        alert('กรุณาใส่อีเมลในช่องข้อความ');
        return;
    }
    
    // Split by various delimiters
    const emails = bulkText
        .split(/[\n\r;,]+/)
        .map(email => email.trim())
        .filter(email => email.length > 0);
    
    let addedCount = 0;
    let duplicateCount = 0;
    let invalidCount = 0;
    
    emails.forEach(email => {
        if (!isValidEmail(email)) {
            invalidCount++;
            return;
        }
        
        if (recipients.find(r => r.email === email)) {
            duplicateCount++;
            return;
        }
        
        recipients.push({
            email: email,
            type: 'to',
            source: 'bulk'
        });
        addedCount++;
    });
    
    if (addedCount > 0) {
        document.getElementById('bulkEmails').value = '';
        updateRecipientsList();
        updateSummary();
        saveData();
    }
    
    // Show summary
    let message = `เพิ่มอีเมล ${addedCount} อีเมล`;
    if (duplicateCount > 0) message += `\nซ้ำ ${duplicateCount} อีเมล`;
    if (invalidCount > 0) message += `\nไม่ถูกต้อง ${invalidCount} อีเมล`;
    
    alert(message);
}

function removeRecipient(index) {
    recipients.splice(index, 1);
    updateRecipientsList();
    updateSummary();
    saveData();
}

function updateRecipientsList() {
    const container = document.getElementById('recipientsList');
    container.innerHTML = '';
    
    if (recipients.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 20px; color: #6b7280;">
                <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>ยังไม่มีผู้รับ</p>
            </div>
        `;
        return;
    }
    
    recipients.forEach((recipient, index) => {
        const item = document.createElement('div');
        item.className = 'recipient-item';
        
        item.innerHTML = `
            <div>
                <div class="recipient-email">${recipient.email}</div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="recipient-type ${recipient.type}">${recipient.type.toUpperCase()}</span>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeRecipient(${index})" title="ลบ">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        container.appendChild(item);
    });
}

function toggleGroup(groupId) {
    const index = selectedGroups.indexOf(groupId);
    
    if (index > -1) {
        selectedGroups.splice(index, 1);
    } else {
        selectedGroups.push(groupId);
    }
    
    updateGroupsUI();
    updateSummary();
    saveData();
}

function updateGroupsUI() {
    document.querySelectorAll('.group-card').forEach(card => {
        const groupId = card.getAttribute('data-group');
        
        if (selectedGroups.includes(groupId)) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    });
}

function selectPriority(priority) {
    selectedPriority = priority;
    
    document.querySelectorAll('.priority-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    document.querySelector(`[data-priority="${priority}"]`).classList.add('selected');
    saveData();
}

function updateSummary() {
    // Count recipients by type
    const toCoun = recipients.filter(r => r.type === 'to').length;
    const ccCount = recipients.filter(r => r.type === 'cc').length;
    const bccCount = recipients.filter(r => r.type === 'bcc').length;
    
    // Count group members
    let groupMembersCount = 0;
    selectedGroups.forEach(groupId => {
        if (groupData[groupId]) {
            groupMembersCount += groupData[groupId].members.length;
        }
    });
    
    const totalCount = recipients.length + groupMembersCount;
    
    document.getElementById('totalRecipients').textContent = totalCount;
    document.getElementById('toRecipients').textContent = toCoun + (selectedGroups.length > 0 ? groupMembersCount : 0);
    document.getElementById('ccRecipients').textContent = ccCount;
    document.getElementById('bccRecipients').textContent = bccCount;
    document.getElementById('selectedGroups').textContent = selectedGroups.length;
}

function validateRecipients() {
    const errors = [];
    
    // Check if there are any recipients
    const totalRecipients = recipients.length + selectedGroups.length;
    if (totalRecipients === 0) {
        errors.push('ต้องมีผู้รับอย่างน้อย 1 คน');
    }
    
    // Check email validity
    recipients.forEach((recipient, index) => {
        if (!isValidEmail(recipient.email)) {
            errors.push(`อีเมลที่ ${index + 1} (${recipient.email}) ไม่ถูกต้อง`);
        }
    });
    
    // Check for too many recipients
    let totalCount = recipients.length;
    selectedGroups.forEach(groupId => {
        if (groupData[groupId]) {
            totalCount += groupData[groupId].members.length;
        }
    });
    
    if (totalCount > 100) {
        errors.push(`ผู้รับทั้งหมด ${totalCount} คน มากเกินไป (ไม่ควรเกิน 100 คน)`);
    }
    
    // Check for urgent priority with too many recipients
    if (selectedPriority === 'urgent' && totalCount > 20) {
        errors.push('อีเมลเร่งด่วนไม่ควรส่งให้ผู้รับมากกว่า 20 คน');
    }
    
    // Display validation results
    const validationSection = document.getElementById('validationSection');
    const validationList = document.getElementById('validationList');
    
    if (errors.length > 0) {
        validationSection.classList.add('show');
        validationList.innerHTML = errors.map(error => `<li>${error}</li>`).join('');
        return false;
    } else {
        validationSection.classList.remove('show');
        alert('✅ ผู้รับถูกต้องทั้งหมด\n\n' + 
              `ผู้รับทั้งหมด: ${totalCount} คน\n` +
              `ระดับความสำคัญ: ${getPriorityLabel(selectedPriority)}`);
        return true;
    }
}

function getPriorityLabel(priority) {
    const labels = {
        'low': '🟢 ต่ำ',
        'normal': '🟡 ปกติ',
        'high': '🟠 สูง',
        'urgent': '🔴 เร่งด่วน'
    };
    return labels[priority] || priority;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function saveData() {
    const data = {
        recipients: recipients,
        selectedGroups: selectedGroups,
        priority: selectedPriority
    };
    
    sessionStorage.setItem('sql_alert_recipients', JSON.stringify(data));
}

function getAllRecipients() {
    let allRecipients = [...recipients];
    
    // Add group members as 'to' recipients
    selectedGroups.forEach(groupId => {
        if (groupData[groupId]) {
            groupData[groupId].members.forEach(email => {
                // Avoid duplicates
                if (!allRecipients.find(r => r.email === email)) {
                    allRecipients.push({
                        email: email,
                        type: 'to',
                        source: 'group',
                        group: groupId
                    });
                }
            });
        }
    });
    
    return allRecipients;
}

function previousStep() {
    saveData();
    window.location.href = '{{ route("sql-alerts.create") }}?step=11';
}

function nextStep() {
    if (!validateRecipients()) {
        return;
    }
    
    saveData();
    sessionStorage.setItem('sql_alert_step', '13');
    window.location.href = '{{ route("sql-alerts.create") }}?step=13';
}

// Auto-update summary when inputs change
document.addEventListener('input', function() {
    updateSummary();
});

// Example function to show group members (could be expanded)
function showGroupMembers(groupId) {
    if (groupData[groupId]) {
        const group = groupData[groupId];
        const membersList = group.members.join('\n');
        alert(`สมาชิกของ ${group.name}:\n\n${membersList}`);
    }
}

// Add context menu for group cards (optional enhancement)
document.querySelectorAll('.group-card').forEach(card => {
    card.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        const groupId = this.getAttribute('data-group');
        showGroupMembers(groupId);
    });
});
</script>
@endpush
@endsection