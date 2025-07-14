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

.connection-form {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
}

.form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-control.is-invalid {
    border-color: #ef4444;
}

.form-control.is-valid {
    border-color: #10b981;
}

.form-text {
    color: #6b7280;
    font-size: 0.875rem;
    margin-top: 4px;
}

.input-group {
    display: flex;
    align-items: center;
}

.input-group .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-right: none;
}

.input-group-text {
    background: #f3f4f6;
    border: 2px solid #e5e7eb;
    border-left: none;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
    padding: 12px 16px;
    color: #6b7280;
    font-weight: 500;
}

.row {
    margin-left: -10px;
    margin-right: -10px;
}

.row > * {
    padding-left: 10px;
    padding-right: 10px;
}

.database-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.database-info h5 {
    margin-bottom: 15px;
    font-weight: 600;
}

.database-info .badge {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
}

.connection-examples {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 15px;
    border-radius: 0 8px 8px 0;
    margin-top: 20px;
}

.connection-examples h6 {
    color: #92400e;
    font-weight: 600;
    margin-bottom: 10px;
}

.connection-examples code {
    background: rgba(0,0,0,0.1);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.875rem;
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

.password-toggle {
    position: relative;
}

.password-toggle .toggle-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
}

.password-toggle .toggle-btn:hover {
    color: #4f46e5;
}

.validation-feedback {
    margin-top: 5px;
    font-size: 0.875rem;
}

.invalid-feedback {
    color: #ef4444;
}

.valid-feedback {
    color: #10b981;
}

@media (max-width: 768px) {
    .wizard-content {
        padding: 25px;
    }
    
    .row {
        margin-left: 0;
        margin-right: 0;
    }
    
    .row > * {
        padding-left: 0;
        padding-right: 0;
        margin-bottom: 15px;
    }
    
    .wizard-navigation {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

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
            <div class="wizard-title">🔗 ข้อมูลการเชื่อมต่อฐานข้อมูล</div>
            <div class="wizard-subtitle">กรอกรายละเอียดการเชื่อมต่อไปยังฐานข้อมูลของคุณ</div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
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
                <div class="step"></div>
            </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
            <form id="connectionForm" method="POST">
                @csrf
                
                <!-- Step 2: Connection Details -->
                <div class="section-title">
                    <div class="section-icon">2</div>
                    ระบุข้อมูลการเชื่อมต่อ
                </div>

                <!-- Selected Database Info -->
                <div class="database-info">
                    <h5>
                        <i class="fas fa-info-circle me-2"></i>
                        ฐานข้อมูลที่เลือก
                    </h5>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge" id="selectedDbType">MySQL</span>
                        <span class="badge" id="selectedDbPort">Port: 3306</span>
                        <span class="badge" id="selectedDbDriver">Driver: mysql</span>
                    </div>
                </div>

                <!-- Connection Form -->
                <div class="connection-form">
                    <!-- Server & Port -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="form-label" for="dbHost">
                                    <i class="fas fa-server me-1"></i>
                                    Host / Server Address
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="dbHost" 
                                       name="db_host" 
                                       value="localhost" 
                                       placeholder="localhost หรือ IP Address"
                                       required>
                                <div class="form-text">
                                    ระบุ IP Address หรือ Domain Name ของเซิร์ฟเวอร์ฐานข้อมูล
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label" for="dbPort">
                                    <i class="fas fa-plug me-1"></i>
                                    Port
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="dbPort" 
                                       name="db_port" 
                                       value="3306" 
                                       placeholder="3306"
                                       min="1" 
                                       max="65535"
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- Database Name -->
                    <div class="form-group">
                        <label class="form-label" for="dbName">
                            <i class="fas fa-database me-1"></i>
                            Database Name
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="dbName" 
                               name="db_name" 
                               placeholder="ชื่อฐานข้อมูล"
                               required>
                        <div class="form-text">
                            ชื่อฐานข้อมูลที่ต้องการเชื่อมต่อ
                        </div>
                    </div>

                    <!-- Username & Password -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="dbUser">
                                    <i class="fas fa-user me-1"></i>
                                    Username
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="dbUser" 
                                       name="db_username" 
                                       placeholder="ชื่อผู้ใช้ฐานข้อมูล"
                                       autocomplete="username"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="dbPassword">
                                    <i class="fas fa-lock me-1"></i>
                                    Password
                                </label>
                                <div class="password-toggle">
                                    <input type="password" 
                                           class="form-control" 
                                           id="dbPassword" 
                                           name="db_password" 
                                           placeholder="รหัสผ่าน"
                                           autocomplete="current-password">
                                    <button type="button" class="toggle-btn" onclick="togglePassword('dbPassword')">
                                        <i class="fas fa-eye" id="passwordIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Connection Options -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="connectionTimeout">
                                    <i class="fas fa-clock me-1"></i>
                                    Connection Timeout (วินาที)
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="connectionTimeout" 
                                       name="connection_timeout" 
                                       value="30" 
                                       min="5" 
                                       max="300">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="charset">
                                    <i class="fas fa-language me-1"></i>
                                    Character Set
                                </label>
                                <select class="form-control" id="charset" name="charset">
                                    <option value="utf8mb4">UTF-8 (utf8mb4)</option>
                                    <option value="utf8">UTF-8 (utf8)</option>
                                    <option value="latin1">Latin1</option>
                                    <option value="tis620">TIS-620</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SSL Options -->
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="sslEnabled" 
                                   name="ssl_enabled" 
                                   value="1">
                            <label class="form-check-label" for="sslEnabled">
                                <i class="fas fa-shield-alt me-1"></i>
                                เปิดใช้งาน SSL/TLS
                            </label>
                        </div>
                        <div class="form-text">
                            แนะนำสำหรับการเชื่อมต่อผ่านอินเทอร์เน็ต
                        </div>
                    </div>
                </div>

                <!-- Connection Examples -->
                <div class="connection-examples">
                    <h6>
                        <i class="fas fa-lightbulb me-1"></i>
                        ตัวอย่างการเชื่อมต่อ
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>การเชื่อมต่อภายใน:</strong><br>
                            Host: <code>localhost</code> หรือ <code>127.0.0.1</code><br>
                            Port: <code>3306</code> (MySQL), <code>5432</code> (PostgreSQL)
                        </div>
                        <div class="col-md-6">
                            <strong>การเชื่อมต่อภายนอก:</strong><br>
                            Host: <code>db.company.com</code> หรือ <code>192.168.1.100</code><br>
                            SSL: แนะนำให้เปิดใช้งาน
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
                        ขั้นตอนที่ 2 จาก 14
                    </div>
                    
                    <button type="button" class="btn btn-primary" onclick="nextStep()">
                        ถัดไป (ทดสอบการเชื่อมต่อ)
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

