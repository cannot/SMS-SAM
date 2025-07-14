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

    .connection-summary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
    }

    .connection-summary h5 {
        margin-bottom: 20px;
        font-weight: 600;
    }

    .connection-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .detail-item {
        background: rgba(255, 255, 255, 0.1);
        padding: 12px 16px;
        border-radius: 8px;
    }

    .detail-label {
        font-size: 0.875rem;
        opacity: 0.8;
        margin-bottom: 4px;
    }

    .detail-value {
        font-weight: 600;
        font-size: 1rem;
    }

    .test-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
    }

    .test-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .test-progress {
        max-width: 400px;
        margin: 0 auto 30px;
    }

    .progress {
        height: 8px;
        border-radius: 4px;
        background: #e5e7eb;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #4f46e5, #7c3aed);
        border-radius: 4px;
        transition: width 0.3s ease;
        width: 0%;
    }

    .test-steps {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .test-step {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
    }

    .test-step.pending {
        border-color: #e5e7eb;
        background: white;
    }

    .test-step.running {
        border-color: #f59e0b;
        background: #fef3c7;
    }

    .test-step.success {
        border-color: #10b981;
        background: #d1fae5;
    }

    .test-step.error {
        border-color: #ef4444;
        background: #fee2e2;
    }

    .test-step-icon {
        width: 50px;
        height: 50px;
        margin: 0 auto 15px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        transition: all 0.3s ease;
    }

    .test-step.pending .test-step-icon {
        background: #e5e7eb;
        color: #6b7280;
    }

    .test-step.running .test-step-icon {
        background: #f59e0b;
        color: white;
        animation: pulse 1.5s infinite;
    }

    .test-step.success .test-step-icon {
        background: #10b981;
        color: white;
    }

    .test-step.error .test-step-icon {
        background: #ef4444;
        color: white;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    .test-step-title {
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    }

    .test-step-description {
        font-size: 0.875rem;
        color: #6b7280;
        line-height: 1.4;
    }

    .test-step.running .test-step-description {
        color: #92400e;
    }

    .test-step.success .test-step-description {
        color: #065f46;
    }

    .test-step.error .test-step-description {
        color: #991b1b;
    }

    .test-controls {
        text-align: center;
        margin-bottom: 30px;
    }

    .test-result {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 25px;
        margin-top: 20px;
        display: none;
    }

    .test-result.show {
        display: block;
        animation: fadeIn 0.5s ease;
    }

    .test-result.success {
        border-color: #10b981;
        background: #f0fdf4;
    }

    .test-result.error {
        border-color: #ef4444;
        background: #fef2f2;
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

    .result-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
    }

    .result-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white;
    }

    .result-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin: 0;
    }

    .test-result.success .result-icon {
        background: #10b981;
    }

    .test-result.success .result-title {
        color: #065f46;
    }

    .test-result.error .result-icon {
        background: #ef4444;
    }

    .test-result.error .result-title {
        color: #991b1b;
    }

    .result-details {
        margin-top: 15px;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    .detail-box {
        background: rgba(255, 255, 255, 0.5);
        padding: 12px;
        border-radius: 8px;
    }

    .detail-box strong {
        display: block;
        margin-bottom: 4px;
        color: #374151;
    }

    .error-details {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid #fecaca;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
    }

    .error-code {
        font-family: 'Courier New', monospace;
        font-size: 0.875rem;
        color: #991b1b;
        background: rgba(255, 255, 255, 0.5);
        padding: 8px;
        border-radius: 4px;
        margin-top: 8px;
    }

    .btn {
        padding: 12px 24px;
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

    .btn-warning {
        background: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background: #d97706;
    }

    .btn-lg {
        padding: 16px 32px;
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

        .connection-details {
            grid-template-columns: 1fr;
        }

        .test-steps {
            grid-template-columns: 1fr;
        }

        .detail-grid {
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
        <div class="wizard-title">🔍 ทดสอบการเชื่อมต่อฐานข้อมูล</div>
        <div class="wizard-subtitle">ตรวจสอบการเชื่อมต่อก่อนดำเนินการต่อ</div>

        <!-- Step Indicator -->
        <div class="step-indicator">
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
            <div class="step"></div>
        </div>
    </div>

    <!-- Wizard Content -->
    <div class="wizard-content">
        <!-- Step 3: Test Connection -->
        <div class="section-title">
            <div class="section-icon">3</div>
            ทดสอบการเชื่อมต่อ
        </div>

        <!-- Connection Summary -->
        <div class="connection-summary">
            <h5>
                <i class="fas fa-info-circle me-2"></i>
                ข้อมูลการเชื่อมต่อ
            </h5>
            <div class="connection-details">
                <div class="detail-item">
                    <div class="detail-label">ประเภทฐานข้อมูล</div>
                    <div class="detail-value" id="summaryDbType">MySQL</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">เซิร์ฟเวอร์</div>
                    <div class="detail-value" id="summaryHost">localhost</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">พอร์ต</div>
                    <div class="detail-value" id="summaryPort">3306</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">ฐานข้อมูล</div>
                    <div class="detail-value" id="summaryDatabase">company_db</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">ผู้ใช้</div>
                    <div class="detail-value" id="summaryUsername">admin</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">SSL</div>
                    <div class="detail-value" id="summarySSL">ปิดใช้งาน</div>
                </div>
            </div>
        </div>

        <!-- Test Section -->
        <div class="test-section">
            <div class="test-header">
                <h4>การทดสอบการเชื่อมต่อ</h4>
                <p class="text-muted">ระบบจะทำการทดสอบการเชื่อมต่อไปยังฐานข้อมูลของคุณ</p>
            </div>

            <!-- Test Progress -->
            <div class="test-progress">
                <div class="progress">
                    <div class="progress-bar" id="testProgress"></div>
                </div>
                <div class="text-center mt-2">
                    <small class="text-muted" id="progressText">พร้อมทดสอบ</small>
                </div>
            </div>

            <!-- Test Steps -->
            <div class="test-steps">
                <div class="test-step pending" id="step-network">
                    <div class="test-step-icon">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div class="test-step-title">ทดสอบเครือข่าย</div>
                    <div class="test-step-description">ตรวจสอบการเชื่อมต่อไปยังเซิร์ฟเวอร์</div>
                </div>

                <div class="test-step pending" id="step-auth">
                    <div class="test-step-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="test-step-title">ตรวจสอบสิทธิ์</div>
                    <div class="test-step-description">ทดสอบ Username และ Password</div>
                </div>

                <div class="test-step pending" id="step-database">
                    <div class="test-step-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="test-step-title">เข้าถึงฐานข้อมูล</div>
                    <div class="test-step-description">ตรวจสอบการเข้าถึงฐานข้อมูล</div>
                </div>

                <div class="test-step pending" id="step-permissions">
                    <div class="test-step-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="test-step-title">สิทธิ์การใช้งาน</div>
                    <div class="test-step-description">ตรวจสอบสิทธิ์ SELECT</div>
                </div>
            </div>

            <!-- Test Controls -->
            <div class="test-controls">
                <button type="button" class="btn btn-success btn-lg" id="startTestBtn" onclick="startConnectionTest()">
                    <i class="fas fa-play"></i>
                    เริ่มทดสอบการเชื่อมต่อ
                </button>

                <button type="button" class="btn btn-warning btn-lg" id="retryTestBtn" onclick="retryConnectionTest()"
                    style="display: none;">
                    <i class="fas fa-redo"></i>
                    ทดสอบใหม่
                </button>
            </div>

            <!-- Test Result -->
            <div class="test-result" id="testResult">
                <div class="result-header">
                    <div class="result-icon">
                        <i class="fas fa-check" id="resultIcon"></i>
                    </div>
                    <h4 class="result-title" id="resultTitle">การเชื่อมต่อสำเร็จ!</h4>
                </div>

                <div class="result-details" id="resultDetails">
                    <p id="resultMessage">การเชื่อมต่อไปยังฐานข้อมูลสำเร็จ พร้อมดำเนินการขั้นตอนต่อไป</p>

                    <div class="detail-grid" id="connectionStats">
                        <div class="detail-box">
                            <strong>เวลาในการเชื่อมต่อ</strong>
                            <span id="connectionTime">0.25 วินาที</span>
                        </div>
                        <div class="detail-box">
                            <strong>เวอร์ชันฐานข้อมูล</strong>
                            <span id="dbVersion">MySQL 8.0.35</span>
                        </div>
                        <div class="detail-box">
                            <strong>Character Set</strong>
                            <span id="dbCharset">utf8mb4</span>
                        </div>
                        <div class="detail-box">
                            <strong>สถานะ SSL</strong>
                            <span id="sslStatus">ปิดใช้งาน</span>
                        </div>
                    </div>
                </div>

                <!-- Error Details (shown only on error) -->
                <div class="error-details" id="errorDetails" style="display: none;">
                    <strong>รายละเอียดข้อผิดพลาด:</strong>
                    <div class="error-code" id="errorCode">
                        SQLSTATE[HY000] [2002] Connection refused
                    </div>
                    <p class="mt-2">
                        <strong>แนวทางแก้ไข:</strong>
                    </p>
                    <ul id="errorSolutions">
                        <li>ตรวจสอบว่าเซิร์ฟเวอร์ฐานข้อมูลทำงานอยู่</li>
                        <li>ตรวจสอบ Host และ Port ให้ถูกต้อง</li>
                        <li>ตรวจสอบ Firewall ที่อาจบล็อกการเชื่อมต่อ</li>
                    </ul>
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
                ขั้นตอนที่ 3 จาก 14
            </div>

            <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()" disabled>
                ถัดไป (วาง SQL Scripts)
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>
