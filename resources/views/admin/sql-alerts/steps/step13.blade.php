@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนแบบ SQL - ตั้งเวลาส่ง')

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

.schedule-options {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.options-header {
    font-weight: 600;
    margin-bottom: 20px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.schedule-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.schedule-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.schedule-card:hover {
    border-color: #4f46e5;
    background: #f0f9ff;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.15);
}

.schedule-card.selected {
    border-color: #10b981;
    background: #f0fdf4;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.schedule-icon {
    width: 50px;
    height: 50px;
    margin: 0 auto 15px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: all 0.3s ease;
}

.schedule-card:not(.selected) .schedule-icon {
    background: #e5e7eb;
    color: #6b7280;
}

.schedule-card.selected .schedule-icon {
    background: #10b981;
    color: white;
}

.schedule-title {
    font-weight: 600;
    margin-bottom: 8px;
    color: #374151;
    text-align: center;
}

.schedule-description {
    font-size: 0.875rem;
    color: #6b7280;
    text-align: center;
    line-height: 1.4;
}

.selected-indicator {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 24px;
    height: 24px;
    background: #10b981;
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}

.schedule-card.selected .selected-indicator {
    display: flex;
}

.schedule-settings {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    display: none;
}

.schedule-settings.show {
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

.settings-header {
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.cron-builder {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    margin-top: 10px;
}

.cron-fields {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

.cron-field {
    text-align: center;
}

.cron-field label {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 5px;
    display: block;
}

.cron-field input {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    text-align: center;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

.cron-preview {
    background: #1f2937;
    color: #e5e7eb;
    padding: 10px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    text-align: center;
}

.quick-presets {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 8px;
    margin-bottom: 15px;
}

.preset-btn {
    padding: 6px 12px;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.75rem;
    transition: all 0.3s ease;
}

.preset-btn:hover {
    border-color: #4f46e5;
    background: #f0f9ff;
}

.timezone-section {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 15px;
    border-radius: 0 8px 8px 0;
    margin-bottom: 20px;
}

.timezone-header {
    font-weight: 600;
    margin-bottom: 10px;
    color: #92400e;
    font-size: 0.875rem;
}

.next-runs {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.next-runs-header {
    font-weight: 600;
    margin-bottom: 15px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.runs-list {
    max-height: 200px;
    overflow-y: auto;
}

.run-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f3f4f6;
}

.run-item:last-child {
    border-bottom: none;
}

.run-date {
    font-weight: 500;
    color: #374151;
}

.run-relative {
    font-size: 0.875rem;
    color: #6b7280;
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

.btn-sm {
    padding: 6px 12px;
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
    
    .schedule-cards {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .cron-fields {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    
    .quick-presets {
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
            <div class="wizard-title">⏰ ตั้งเวลาส่ง</div>
            <div class="wizard-subtitle">กำหนดเวลาและความถี่ในการส่งการแจ้งเตือน</div>
            
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
                <div class="step active"></div>
                <div class="step"></div>
            </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
            <!-- Step 13: Schedule Configuration -->
            <div class="section-title">
                <div class="section-icon">13</div>
                ตั้งเวลาส่ง
            </div>

            <!-- Schedule Options -->
            <div class="schedule-options">
                <div class="options-header">
                    <i class="fas fa-clock"></i>
                    เลือกรูปแบบการส่ง
                </div>

                <div class="schedule-cards">
                    <div class="schedule-card selected" data-type="manual" onclick="selectScheduleType('manual')">
                        <div class="selected-indicator">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="schedule-icon">
                            <i class="fas fa-hand-pointer"></i>
                        </div>
                        <div class="schedule-title">ส่งด้วยตนเอง</div>
                        <div class="schedule-description">
                            ส่งทันทีหรือเมื่อต้องการ ไม่มีการส่งอัตโนมัติ
                        </div>
                    </div>

                    <div class="schedule-card" data-type="once" onclick="selectScheduleType('once')">
                        <div class="selected-indicator">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="schedule-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="schedule-title">ส่งครั้งเดียว</div>
                        <div class="schedule-description">
                            กำหนดวันที่และเวลาเฉพาะ ส่งเพียงครั้งเดียว
                        </div>
                    </div>

                    <div class="schedule-card" data-type="recurring" onclick="selectScheduleType('recurring')">
                        <div class="selected-indicator">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="schedule-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="schedule-title">ส่งแบบประจำ</div>
                        <div class="schedule-description">
                            ส่งซ้ำตามช่วงเวลาที่กำหนด เช่น ทุกวัน, ทุกสัปดาห์
                        </div>
                    </div>

                    <div class="schedule-card" data-type="cron" onclick="selectScheduleType('cron')">
                        <div class="selected-indicator">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="schedule-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="schedule-title">Cron Expression</div>
                        <div class="schedule-description">
                            ใช้ Cron Expression สำหรับการตั้งเวลาแบบละเอียด
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timezone Section -->
            <div class="timezone-section">
                <div class="timezone-header">
                    <i class="fas fa-globe me-1"></i>
                    เขตเวลา
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="timezone">เขตเวลาที่ใช้</label>
                        <select class="form-control form-select" id="timezone">
                            <option value="Asia/Bangkok" selected>Asia/Bangkok (UTC+7)</option>
                            <option value="UTC">UTC (GMT+0)</option>
                            <option value="Asia/Tokyo">Asia/Tokyo (UTC+9)</option>
                            <option value="Asia/Singapore">Asia/Singapore (UTC+8)</option>
                            <option value="America/New_York">America/New_York (UTC-5)</option>
                            <option value="Europe/London">Europe/London (UTC+0)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">เวลาปัจจุบัน</label>
                        <input type="text" class="form-control" id="currentTime" readonly>
                    </div>
                </div>
            </div>

            <!-- Schedule Settings -->
            <div class="schedule-settings" id="scheduleSettings">
                <div class="settings-header">
                    <i class="fas fa-cogs"></i>
                    ตั้งค่าการส่ง
                </div>

                <!-- Manual Settings -->
                <div id="manualSettings" class="schedule-content">
                    <div class="form-group">
                        <label class="form-label">การส่งด้วยตนเอง</label>
                        <div style="background: #f0f9ff; padding: 15px; border-radius: 6px; border: 1px solid #bae6fd;">
                            <p style="margin: 0; color: #0369a1;">
                                <i class="fas fa-info-circle me-2"></i>
                                การแจ้งเตือนจะไม่ถูกส่งอัตโนมัติ คุณสามารถส่งได้ทันทีหลังจากบันทึก หรือส่งในภายหลังผ่านหน้าจัดการการแจ้งเตือน
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Once Settings -->
                <div id="onceSettings" class="schedule-content" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="scheduleDate">วันที่ส่ง</label>
                            <input type="date" class="form-control" id="scheduleDate" onchange="updateNextRuns()">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="scheduleTime">เวลาที่ส่ง</label>
                            <input type="time" class="form-control" id="scheduleTime" value="09:00" onchange="updateNextRuns()">
                        </div>
                    </div>
                </div>

                <!-- Recurring Settings -->
                <div id="recurringSettings" class="schedule-content" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="intervalType">ความถี่</label>
                            <select class="form-control form-select" id="intervalType" onchange="updateIntervalOptions()">
                                <option value="minutes">ทุกกี่นาที</option>
                                <option value="hours">ทุกกี่ชั่วโมง</option>
                                <option value="daily" selected>ทุกวัน</option>
                                <option value="weekly">ทุกสัปดาห์</option>
                                <option value="monthly">ทุกเดือน</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="intervalValue">ทุก</label>
                            <input type="number" class="form-control" id="intervalValue" value="1" min="1" onchange="updateNextRuns()">
                        </div>
                    </div>

                    <div id="recurringDetails">
                        <!-- Daily options -->
                        <div id="dailyOptions" class="interval-options">
                            <div class="form-group">
                                <label class="form-label" for="dailyTime">เวลา</label>
                                <input type="time" class="form-control" id="dailyTime" value="09:00" onchange="updateNextRuns()">
                            </div>
                        </div>

                        <!-- Weekly options -->
                        <div id="weeklyOptions" class="interval-options" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">วันในสัปดาห์</label>
                                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-top: 8px;">
                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 0.875rem;">
                                        <input type="checkbox" value="0" onchange="updateNextRuns()"> อา
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 0.875rem;">
                                        <input type="checkbox" value="1" checked onchange="updateNextRuns()"> จ
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 0.875rem;">
                                        <input type="checkbox" value="2" onchange="updateNextRuns()"> อ
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 0.875rem;">
                                        <input type="checkbox" value="3" onchange="updateNextRuns()"> พ
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 0.875rem;">
                                        <input type="checkbox" value="4" onchange="updateNextRuns()"> พฤ
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 0.875rem;">
                                        <input type="checkbox" value="5" onchange="updateNextRuns()"> ศ
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 0.875rem;">
                                        <input type="checkbox" value="6" onchange="updateNextRuns()"> ส
                                    </label>
                                </div>
                                <input type="time" class="form-control" id="weeklyTime" value="09:00" style="margin-top: 10px;" onchange="updateNextRuns()">
                            </div>
                        </div>

                        <!-- Monthly options -->
                        <div id="monthlyOptions" class="interval-options" style="display: none;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="monthlyDay">วันที่ในเดือน</label>
                                    <input type="number" class="form-control" id="monthlyDay" value="1" min="1" max="31" onchange="updateNextRuns()">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="monthlyTime">เวลา</label>
                                    <input type="time" class="form-control" id="monthlyTime" value="09:00" onchange="updateNextRuns()">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cron Settings -->
                <div id="cronSettings" class="schedule-content" style="display: none;">
                    <div class="cron-builder">
                        <div class="quick-presets">
                            <button type="button" class="preset-btn" onclick="setCronPreset('0 9 * * *')">ทุกวัน 09:00</button>
                            <button type="button" class="preset-btn" onclick="setCronPreset('0 9 * * 1-5')">วันจันทร์-ศุกร์ 09:00</button>
                            <button type="button" class="preset-btn" onclick="setCronPreset('0 9 1 * *')">วันที่ 1 ของเดือน</button>
                            <button type="button" class="preset-btn" onclick="setCronPreset('0 */6 * * *')">ทุก 6 ชั่วโมง</button>
                            <button type="button" class="preset-btn" onclick="setCronPreset('*/30 * * * *')">ทุก 30 นาที</button>
                        </div>

                        <div class="cron-fields">
                            <div class="cron-field">
                                <label>นาที</label>
                                <input type="text" id="cronMinute" value="0" onchange="updateCronExpression()">
                                <div style="font-size: 0.65rem; color: #9ca3af; margin-top: 2px;">0-59</div>
                            </div>
                            <div class="cron-field">
                                <label>ชั่วโมง</label>
                                <input type="text" id="cronHour" value="9" onchange="updateCronExpression()">
                                <div style="font-size: 0.65rem; color: #9ca3af; margin-top: 2px;">0-23</div>
                            </div>
                            <div class="cron-field">
                                <label>วัน</label>
                                <input type="text" id="cronDay" value="*" onchange="updateCronExpression()">
                                <div style="font-size: 0.65rem; color: #9ca3af; margin-top: 2px;">1-31</div>
                            </div>
                            <div class="cron-field">
                                <label>เดือน</label>
                                <input type="text" id="cronMonth" value="*" onchange="updateCronExpression()">
                                <div style="font-size: 0.65rem; color: #9ca3af; margin-top: 2px;">1-12</div>
                            </div>
                            <div class="cron-field">
                                <label>วันสัปดาห์</label>
                                <input type="text" id="cronDayOfWeek" value="*" onchange="updateCronExpression()">
                                <div style="font-size: 0.65rem; color: #9ca3af; margin-top: 2px;">0-6</div>
                            </div>
                        </div>

                        <div class="cron-preview" id="cronPreview">
                            0 9 * * *
                        </div>
                        
                        <div style="font-size: 0.75rem; color: #6b7280; margin-top: 8px;">
                            ใช้ * สำหรับทุกค่า, ใช้ , สำหรับหลายค่า, ใช้ - สำหรับช่วง, ใช้ / สำหรับทุกๆ
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Runs Preview -->
            <div class="next-runs">
                <div class="next-runs-header">
                    <i class="fas fa-calendar-check"></i>
                    การส่งครั้งถัดไป
                    <button type="button" class="btn btn-success btn-sm" onclick="updateNextRuns()" style="margin-left: auto;">
                        <i class="fas fa-sync-alt"></i>
                        รีเฟรช
                    </button>
                </div>

                <div class="runs-list" id="nextRunsList">
                    <!-- Next runs will be populated here -->
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
                    ขั้นตอนที่ 13 จาก 14
                </div>
                
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    ถัดไป (บันทึกและส่ง)
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let selectedScheduleType = 'manual';

document.addEventListener('DOMContentLoaded', function() {
    loadSavedSchedule();
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
    setMinDateToToday();
    updateNextRuns();
});

function loadSavedSchedule() {
    const saved = sessionStorage.getItem('sql_alert_schedule');
    if (saved) {
        try {
            const schedule = JSON.parse(saved);
            
            selectedScheduleType = schedule.type || 'manual';
            selectScheduleType(selectedScheduleType, false);
            
            // Load specific settings based on type
            if (schedule.type === 'once') {
                document.getElementById('scheduleDate').value = schedule.date || '';
                document.getElementById('scheduleTime').value = schedule.time || '09:00';
            } else if (schedule.type === 'recurring') {
                document.getElementById('intervalType').value = schedule.intervalType || 'daily';
                document.getElementById('intervalValue').value = schedule.intervalValue || 1;
                updateIntervalOptions();
                
                if (schedule.intervalType === 'daily') {
                    document.getElementById('dailyTime').value = schedule.time || '09:00';
                } else if (schedule.intervalType === 'weekly') {
                    document.getElementById('weeklyTime').value = schedule.time || '09:00';
                    if (schedule.weekDays) {
                        schedule.weekDays.forEach(day => {
                            const checkbox = document.querySelector(`input[value="${day}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                } else if (schedule.intervalType === 'monthly') {
                    document.getElementById('monthlyDay').value = schedule.monthDay || 1;
                    document.getElementById('monthlyTime').value = schedule.time || '09:00';
                }
            } else if (schedule.type === 'cron') {
                if (schedule.cronExpression) {
                    setCronFromExpression(schedule.cronExpression);
                }
            }
            
            if (schedule.timezone) {
                document.getElementById('timezone').value = schedule.timezone;
            }
            
        } catch (e) {
            console.error('Error loading saved schedule:', e);
        }
    }
}

function selectScheduleType(type, updatePreview = true) {
    selectedScheduleType = type;
    
    // Update UI
    document.querySelectorAll('.schedule-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`[data-type="${type}"]`).classList.add('selected');
    
    // Show/hide settings
    document.querySelectorAll('.schedule-content').forEach(content => {
        content.style.display = 'none';
    });
    
    document.getElementById(`${type}Settings`).style.display = 'block';
    
    // Show settings container
    const settingsContainer = document.getElementById('scheduleSettings');
    settingsContainer.classList.add('show');
    
    if (updatePreview) {
        updateNextRuns();
    }
    
    saveSchedule();
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

function setMinDateToToday() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('scheduleDate').min = today;
    
    // Set default date to today if empty
    if (!document.getElementById('scheduleDate').value) {
        document.getElementById('scheduleDate').value = today;
    }
}

function updateIntervalOptions() {
    const intervalType = document.getElementById('intervalType').value;
    
    // Hide all options
    document.querySelectorAll('.interval-options').forEach(option => {
        option.style.display = 'none';
    });
    
    // Show relevant options
    if (intervalType === 'daily') {
        document.getElementById('dailyOptions').style.display = 'block';
    } else if (intervalType === 'weekly') {
        document.getElementById('weeklyOptions').style.display = 'block';
    } else if (intervalType === 'monthly') {
        document.getElementById('monthlyOptions').style.display = 'block';
    }
    
    updateNextRuns();
}

function setCronPreset(expression) {
    setCronFromExpression(expression);
    updateCronExpression();
    updateNextRuns();
}

function setCronFromExpression(expression) {
    const parts = expression.split(' ');
    if (parts.length >= 5) {
        document.getElementById('cronMinute').value = parts[0];
        document.getElementById('cronHour').value = parts[1];
        document.getElementById('cronDay').value = parts[2];
        document.getElementById('cronMonth').value = parts[3];
        document.getElementById('cronDayOfWeek').value = parts[4];
    }
}

function updateCronExpression() {
    const minute = document.getElementById('cronMinute').value;
    const hour = document.getElementById('cronHour').value;
    const day = document.getElementById('cronDay').value;
    const month = document.getElementById('cronMonth').value;
    const dayOfWeek = document.getElementById('cronDayOfWeek').value;
    
    const expression = `${minute} ${hour} ${day} ${month} ${dayOfWeek}`;
    document.getElementById('cronPreview').textContent = expression;
    
    saveSchedule();
}

function updateNextRuns() {
    const container = document.getElementById('nextRunsList');
    const timezone = document.getElementById('timezone').value;
    
    let nextRuns = [];
    
    try {
        nextRuns = calculateNextRuns();
    } catch (error) {
        console.error('Error calculating next runs:', error);
        container.innerHTML = `
            <div style="text-align: center; padding: 20px; color: #ef4444;">
                <i class="fas fa-exclamation-triangle"></i>
                <p>ไม่สามารถคำนวณเวลาการส่งถัดไปได้</p>
                <p style="font-size: 0.875rem;">${error.message}</p>
            </div>
        `;
        return;
    }
    
    if (nextRuns.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 20px; color: #6b7280;">
                <i class="fas fa-info-circle"></i>
                <p>ไม่มีการส่งที่กำหนดไว้</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = '';
    nextRuns.forEach(run => {
        const item = document.createElement('div');
        item.className = 'run-item';
        
        const relativeTime = getRelativeTime(run);
        
        item.innerHTML = `
            <div class="run-date">${run.toLocaleString('th-TH', { timeZone: timezone })}</div>
            <div class="run-relative">${relativeTime}</div>
        `;
        
        container.appendChild(item);
    });
    
    saveSchedule();
}

function calculateNextRuns() {
    const now = new Date();
    const runs = [];
    
    switch (selectedScheduleType) {
        case 'manual':
            return []; // No scheduled runs for manual
            
        case 'once':
            const date = document.getElementById('scheduleDate').value;
            const time = document.getElementById('scheduleTime').value;
            
            if (date && time) {
                const scheduledDate = new Date(`${date}T${time}`);
                if (scheduledDate > now) {
                    runs.push(scheduledDate);
                }
            }
            break;
            
        case 'recurring':
            const intervalType = document.getElementById('intervalType').value;
            const intervalValue = parseInt(document.getElementById('intervalValue').value) || 1;
            
            runs.push(...calculateRecurringRuns(intervalType, intervalValue, now));
            break;
            
        case 'cron':
            const cronExpression = document.getElementById('cronPreview').textContent;
            runs.push(...calculateCronRuns(cronExpression, now));
            break;
    }
    
    return runs.slice(0, 10); // Show next 10 runs
}

function calculateRecurringRuns(intervalType, intervalValue, startDate) {
    const runs = [];
    let nextRun = new Date(startDate);
    
    switch (intervalType) {
        case 'minutes':
            for (let i = 0; i < 10; i++) {
                nextRun = new Date(nextRun.getTime() + intervalValue * 60 * 1000);
                runs.push(new Date(nextRun));
            }
            break;
            
        case 'hours':
            for (let i = 0; i < 10; i++) {
                nextRun = new Date(nextRun.getTime() + intervalValue * 60 * 60 * 1000);
                runs.push(new Date(nextRun));
            }
            break;
            
        case 'daily':
            const dailyTime = document.getElementById('dailyTime').value;
            const [hour, minute] = dailyTime.split(':').map(Number);
            
            nextRun = new Date(startDate);
            nextRun.setHours(hour, minute, 0, 0);
            
            if (nextRun <= startDate) {
                nextRun.setDate(nextRun.getDate() + intervalValue);
            }
            
            for (let i = 0; i < 10; i++) {
                runs.push(new Date(nextRun));
                nextRun.setDate(nextRun.getDate() + intervalValue);
            }
            break;
            
        case 'weekly':
            const weeklyTime = document.getElementById('weeklyTime').value;
            const [weekHour, weekMinute] = weeklyTime.split(':').map(Number);
            const selectedDays = Array.from(document.querySelectorAll('#weeklyOptions input[type="checkbox"]:checked'))
                .map(cb => parseInt(cb.value));
            
            if (selectedDays.length === 0) break;
            
            nextRun = new Date(startDate);
            nextRun.setHours(weekHour, weekMinute, 0, 0);
            
            let addedRuns = 0;
            let dayOffset = 0;
            
            while (addedRuns < 10) {
                const testDate = new Date(nextRun);
                testDate.setDate(testDate.getDate() + dayOffset);
                
                if (selectedDays.includes(testDate.getDay()) && testDate > startDate) {
                    runs.push(new Date(testDate));
                    addedRuns++;
                }
                
                dayOffset++;
                if (dayOffset > 70) break; // Safety limit
            }
            break;
            
        case 'monthly':
            const monthlyDay = parseInt(document.getElementById('monthlyDay').value) || 1;
            const monthlyTime = document.getElementById('monthlyTime').value;
            const [monthHour, monthMinute] = monthlyTime.split(':').map(Number);
            
            nextRun = new Date(startDate);
            nextRun.setDate(monthlyDay);
            nextRun.setHours(monthHour, monthMinute, 0, 0);
            
            if (nextRun <= startDate) {
                nextRun.setMonth(nextRun.getMonth() + intervalValue);
            }
            
            for (let i = 0; i < 10; i++) {
                runs.push(new Date(nextRun));
                nextRun.setMonth(nextRun.getMonth() + intervalValue);
            }
            break;
    }
    
    return runs;
}

function calculateCronRuns(cronExpression, startDate) {
    // Simple cron parser for common patterns
    const parts = cronExpression.split(' ');
    if (parts.length !== 5) return [];
    
    const [minute, hour, day, month, dayOfWeek] = parts;
    const runs = [];
    
    // This is a simplified implementation
    // In production, you'd want to use a proper cron parser library
    
    let nextRun = new Date(startDate);
    nextRun.setSeconds(0, 0);
    
    for (let i = 0; i < 10; i++) {
        nextRun = getNextCronDate(nextRun, parts);
        if (nextRun) {
            runs.push(new Date(nextRun));
            nextRun = new Date(nextRun.getTime() + 60000); // Add 1 minute
        } else {
            break;
        }
    }
    
    return runs;
}

function getNextCronDate(fromDate, cronParts) {
    // Very simplified cron calculation
    // This would need a proper cron library in production
    const [minute, hour, day, month, dayOfWeek] = cronParts;
    
    let nextDate = new Date(fromDate);
    
    // Handle simple patterns
    if (minute !== '*') {
        nextDate.setMinutes(parseInt(minute));
    }
    
    if (hour !== '*') {
        nextDate.setHours(parseInt(hour));
    }
    
    // If we're past the target time today, move to tomorrow
    if (nextDate <= fromDate) {
        nextDate.setDate(nextDate.getDate() + 1);
    }
    
    return nextDate;
}

function getRelativeTime(date) {
    const now = new Date();
    const diffMs = date.getTime() - now.getTime();
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    const diffHours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    
    if (diffDays > 0) {
        return `ใน ${diffDays} วัน ${diffHours} ชั่วโมง`;
    } else if (diffHours > 0) {
        return `ใน ${diffHours} ชั่วโมง ${diffMinutes} นาที`;
    } else if (diffMinutes > 0) {
        return `ใน ${diffMinutes} นาที`;
    } else {
        return 'ในไม่ช้า';
    }
}

function saveSchedule() {
    const schedule = {
        type: selectedScheduleType,
        timezone: document.getElementById('timezone').value
    };
    
    switch (selectedScheduleType) {
        case 'once':
            schedule.date = document.getElementById('scheduleDate').value;
            schedule.time = document.getElementById('scheduleTime').value;
            break;
            
        case 'recurring':
            schedule.intervalType = document.getElementById('intervalType').value;
            schedule.intervalValue = document.getElementById('intervalValue').value;
            
            if (schedule.intervalType === 'daily') {
                schedule.time = document.getElementById('dailyTime').value;
            } else if (schedule.intervalType === 'weekly') {
                schedule.time = document.getElementById('weeklyTime').value;
                schedule.weekDays = Array.from(document.querySelectorAll('#weeklyOptions input[type="checkbox"]:checked'))
                    .map(cb => parseInt(cb.value));
            } else if (schedule.intervalType === 'monthly') {
                schedule.monthDay = document.getElementById('monthlyDay').value;
                schedule.time = document.getElementById('monthlyTime').value;
            }
            break;
            
        case 'cron':
            schedule.cronExpression = document.getElementById('cronPreview').textContent;
            break;
    }
    
    sessionStorage.setItem('sql_alert_schedule', JSON.stringify(schedule));
}

function validateSchedule() {
    switch (selectedScheduleType) {
        case 'once':
            const date = document.getElementById('scheduleDate').value;
            const time = document.getElementById('scheduleTime').value;
            
            if (!date || !time) {
                return 'กรุณาระบุวันที่และเวลาที่ต้องการส่ง';
            }
            
            const scheduledDate = new Date(`${date}T${time}`);
            if (scheduledDate <= new Date()) {
                return 'วันที่และเวลาที่กำหนดต้องเป็นอนาคต';
            }
            break;
            
        case 'recurring':
            const intervalType = document.getElementById('intervalType').value;
            
            if (intervalType === 'weekly') {
                const selectedDays = document.querySelectorAll('#weeklyOptions input[type="checkbox"]:checked');
                if (selectedDays.length === 0) {
                    return 'กรุณาเลือกวันในสัปดาห์อย่างน้อย 1 วัน';
                }
            }
            break;
            
        case 'cron':
            const cronExpression = document.getElementById('cronPreview').textContent;
            if (!cronExpression || cronExpression.split(' ').length !== 5) {
                return 'Cron Expression ไม่ถูกต้อง';
            }
            break;
    }
    
    return null; // No errors
}

function previousStep() {
    saveSchedule();
    window.location.href = '{{ route("sql-alerts.create") }}?step=12';
}

function nextStep() {
    const validationError = validateSchedule();
    if (validationError) {
        alert(validationError);
        return;
    }
    
    saveSchedule();
    sessionStorage.setItem('sql_alert_step', '14');
    window.location.href = '{{ route("sql-alerts.create") }}?step=14';
}

// Auto-update next runs when inputs change
document.addEventListener('input', function(e) {
    if (e.target.matches('#scheduleDate, #scheduleTime, #intervalValue, #dailyTime, #weeklyTime, #monthlyTime, #monthlyDay, .cron-field input')) {
        updateNextRuns();
    }
});

document.addEventListener('change', function(e) {
    if (e.target.matches('#timezone, #intervalType, input[type="checkbox"]')) {
        if (e.target.id === 'timezone') {
            updateCurrentTime();
        }
        updateNextRuns();
    }
});
</script>
@endpush
@endsection