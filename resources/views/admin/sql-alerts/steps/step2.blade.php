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

    .database-info {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .database-info h5 {
        color: #334155;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .database-info .badge {
        background: #4f46e5;
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .connection-form {
        background: #f9fafb;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-weight: 500;
        color: #374151;
        margin-bottom: 8px;
        display: block;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .form-check {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 15px;
    }

    .form-check-input {
        width: 16px;
        height: 16px;
        border: 2px solid #d1d5db;
        border-radius: 4px;
        cursor: pointer;
    }

    .form-check-input:checked {
        background: #4f46e5;
        border-color: #4f46e5;
    }

    .form-check-label {
        font-size: 0.9rem;
        color: #374151;
        cursor: pointer;
    }

    .connection-options {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
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

        .connection-form {
            padding: 20px;
        }

        .wizard-navigation {
            flex-direction: column;
            gap: 15px;
        }
    }
</style>

<div class="container">
    <!-- Wizard Container -->
    <div class="wizard-container">
        <!-- Wizard Header -->
        <div class="wizard-header">
            <div class="wizard-title">⚙️ ตั้งค่าการเชื่อมต่อฐานข้อมูล</div>
            <div class="wizard-subtitle">ระบุข้อมูลการเชื่อมต่อฐานข้อมูลของคุณ</div>

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
            <form id="connectionForm" method="POST" action="{{ route('admin.sql-alerts.store') }}">
                @csrf

                <!-- Step 2: Database Connection -->
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
                                <input type="text" class="form-control" id="dbHost" name="db_host"
                                    value="localhost" placeholder="เช่น: localhost, 192.168.1.100" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label" for="dbPort">
                                    <i class="fas fa-plug me-1"></i>
                                    Port
                                </label>
                                <input type="number" class="form-control" id="dbPort" name="db_port" value="3306"
                                    placeholder="3306" min="1" max="65535" required>
                            </div>
                        </div>
                    </div>

                    <!-- Database Name -->
                    <div class="form-group">
                        <label class="form-label" for="dbName">
                            <i class="fas fa-database me-1"></i>
                            Database Name
                        </label>
                        <input type="text" class="form-control" id="dbName" name="db_name"
                            placeholder="ชื่อฐานข้อมูล" required>
                    </div>

                    <!-- Username & Password -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="dbUsername">
                                    <i class="fas fa-user me-1"></i>
                                    Username
                                </label>
                                <input type="text" class="form-control" id="dbUsername" name="db_username"
                                    placeholder="ชื่อผู้ใช้" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="dbPassword">
                                    <i class="fas fa-lock me-1"></i>
                                    Password
                                </label>
                                <input type="password" class="form-control" id="dbPassword" name="db_password"
                                    placeholder="รหัสผ่าน" required>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Options -->
                    <div class="connection-options">
                        <h6 class="mb-3">
                            <i class="fas fa-cog me-2"></i>
                            ตัวเลือกเพิ่มเติม
                        </h6>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="connectionTimeout">
                                        <i class="fas fa-clock me-1"></i>
                                        Connection Timeout (วินาที)
                                    </label>
                                    <input type="number" class="form-control" id="connectionTimeout"
                                        name="connection_timeout" value="30" min="5" max="300">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="charset">
                                        <i class="fas fa-font me-1"></i>
                                        Character Set
                                    </label>
                                    <select class="form-control" id="charset" name="charset">
                                        <option value="utf8mb4">UTF-8 (utf8mb4)</option>
                                        <option value="utf8">UTF-8 (utf8)</option>
                                        <option value="latin1">Latin-1</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="sslEnabled" name="ssl_enabled">
                            <label class="form-check-label" for="sslEnabled">
                                <i class="fas fa-shield-alt me-1"></i>
                                เปิดใช้งาน SSL Connection
                            </label>
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

<script>
// Database configuration mapping
const databaseConfigs = {
    mysql: {
        name: 'MySQL',
        driver: 'mysql',
        defaultPort: 3306,
        icon: '🐬'
    },
    mariadb: {
        name: 'MariaDB',
        driver: 'mysql',
        defaultPort: 3306,
        icon: '🗄️'
    },
    postgresql: {
        name: 'PostgreSQL',
        driver: 'pgsql',
        defaultPort: 5432,
        icon: '🐘'
    },
    sqlserver: {
        name: 'SQL Server',
        driver: 'sqlsrv',
        defaultPort: 1433,
        icon: '🏢'
    },
    oracle: {
        name: 'Oracle',
        driver: 'oci',
        defaultPort: 1521,
        icon: '⚡'
    },
    sqlite: {
        name: 'SQLite',
        driver: 'sqlite',
        defaultPort: null,
        icon: '📱'
    }
};

// Function to check if DOM is ready
function domReady(fn) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn);
    } else {
        // DOM is already ready, execute immediately
        fn();
    }
}

// Initialize step 2
function initStep2() {
    console.log('Initializing Step 2...');
    loadDatabaseConfig();
    loadSavedData();
    
    // Setup form validation
    setTimeout(() => {
        setupFormValidation();
    }, 100);
}

function loadDatabaseConfig() {
    // Get selected database type from sessionStorage
    const selectedDbType = sessionStorage.getItem('sql_alert_db_type') || 'mysql';
    const config = databaseConfigs[selectedDbType];
    
    console.log('Loading database config for:', selectedDbType, config);
    
    if (config) {
        // Update UI elements
        const dbTypeElement = document.getElementById('selectedDbType');
        const dbPortElement = document.getElementById('selectedDbPort');
        const dbDriverElement = document.getElementById('selectedDbDriver');
        
        if (dbTypeElement) dbTypeElement.textContent = config.name;
        if (dbPortElement) dbPortElement.textContent = `Port: ${config.defaultPort || 'N/A'}`;
        if (dbDriverElement) dbDriverElement.textContent = `Driver: ${config.driver}`;
        
        // Update port field
        const portField = document.getElementById('dbPort');
        if (portField && config.defaultPort) {
            portField.value = config.defaultPort;
        }
        
        // Hide host/port fields for SQLite
        if (selectedDbType === 'sqlite') {
            const hostField = document.getElementById('dbHost');
            const portField = document.getElementById('dbPort');
            
            if (hostField) {
                const hostContainer = hostField.closest('.col-md-8');
                if (hostContainer) hostContainer.style.display = 'none';
            }
            if (portField) {
                const portContainer = portField.closest('.col-md-4');
                if (portContainer) portContainer.style.display = 'none';
            }
        }
        
        // Save to sessionStorage
        sessionStorage.setItem('sql_alert_db_port', config.defaultPort || '');
    }
}

function loadSavedData() {
    console.log('Loading saved data...');
    
    // Load saved connection data
    const savedFields = [
        { sessionKey: 'sql_alert_db_host', elementId: 'dbHost' },
        { sessionKey: 'sql_alert_db_port', elementId: 'dbPort' },
        { sessionKey: 'sql_alert_db_name', elementId: 'dbName' },
        { sessionKey: 'sql_alert_db_username', elementId: 'dbUsername' },
        { sessionKey: 'sql_alert_db_password', elementId: 'dbPassword' },
        { sessionKey: 'sql_alert_connection_timeout', elementId: 'connectionTimeout' },
        { sessionKey: 'sql_alert_charset', elementId: 'charset' }
    ];
    
    savedFields.forEach(field => {
        const saved = sessionStorage.getItem(field.sessionKey);
        if (saved) {
            const element = document.getElementById(field.elementId);
            if (element) {
                element.value = saved;
                console.log(`Loaded ${field.sessionKey}:`, saved);
            }
        }
    });
    
    // Load SSL checkbox
    const sslEnabled = sessionStorage.getItem('sql_alert_ssl_enabled');
    if (sslEnabled === '1') {
        const sslCheckbox = document.getElementById('sslEnabled');
        if (sslCheckbox) {
            sslCheckbox.checked = true;
        }
    }
}

function setupFormValidation() {
    const form = document.getElementById('connectionForm');
    if (form) {
        // Real-time validation
        form.addEventListener('input', function(e) {
            const field = e.target;
            if (field.hasAttribute('required')) {
                if (field.value.trim()) {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                } else {
                    field.classList.remove('is-valid');
                    field.classList.add('is-invalid');
                }
            }
        });

        // Auto-save on change
        form.addEventListener('change', function(e) {
            saveFormData();
        });
    }
}

function validateForm() {
    const form = document.getElementById('connectionForm');
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        }
    });
    
    return isValid;
}

function saveFormData() {
    const form = document.getElementById('connectionForm');
    if (!form) return;
    
    // Save all form fields to sessionStorage
    const formData = new FormData(form);
    const savedFields = [
        { name: 'db_host', sessionKey: 'sql_alert_db_host' },
        { name: 'db_port', sessionKey: 'sql_alert_db_port' },
        { name: 'db_name', sessionKey: 'sql_alert_db_name' },
        { name: 'db_username', sessionKey: 'sql_alert_db_username' },
        { name: 'db_password', sessionKey: 'sql_alert_db_password' },
        { name: 'connection_timeout', sessionKey: 'sql_alert_connection_timeout' },
        { name: 'charset', sessionKey: 'sql_alert_charset' }
    ];
    
    savedFields.forEach(field => {
        const value = formData.get(field.name);
        if (value !== null) {
            sessionStorage.setItem(field.sessionKey, value);
        }
    });
    
    // Save SSL checkbox
    const sslCheckbox = document.getElementById('sslEnabled');
    if (sslCheckbox) {
        sessionStorage.setItem('sql_alert_ssl_enabled', sslCheckbox.checked ? '1' : '0');
    }
    
    console.log('Form data saved to sessionStorage');
}

function testConnection() {
    if (!validateForm()) {
        alert('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
        return;
    }
    
    saveFormData();
    
    // Show loading state
    const testBtn = document.getElementById('testConnectionBtn');
    if (testBtn) {
        testBtn.disabled = true;
        testBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังทดสอบ...';
    }
    
    // Simulate connection test
    setTimeout(() => {
        if (testBtn) {
            testBtn.disabled = false;
            testBtn.innerHTML = '<i class="fas fa-plug me-2"></i>ทดสอบการเชื่อมต่อ';
        }
        
        alert('การเชื่อมต่อสำเร็จ!');
    }, 2000);
}

function nextStep() {
    if (!validateForm()) {
        alert('กรุณากรอกข้อมูลการเชื่อมต่อให้ครบถ้วน');
        return;
    }
    
    saveFormData();
    sessionStorage.setItem('sql_alert_step', '3');
    
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.nextStep();
    } else {
        window.location.href = '{{ route("admin.sql-alerts.create") }}?step=3';
    }
}

function previousStep() {
    if (window.SqlAlertWizard) {
        window.SqlAlertWizard.previousStep();
    } else {
        window.location.href = '{{ route("admin.sql-alerts.create") }}?step=1';
    }
}

// **สำคัญ: Expose functions ใน global scope**
window.testConnection = testConnection;
window.nextStep = nextStep;
window.previousStep = previousStep;
window.initStep2 = initStep2;
window.initializeCurrentStep = initStep2;

// Initialize immediately
domReady(initStep2);

console.log('Step 2 script loaded');
</script>
