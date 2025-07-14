{{-- @extends('layouts.app') --}}

{{-- @section('title', 'สร้างการแจ้งเตือนแบบ SQL - เลือกฐานข้อมูล')

@push('styles') --}}
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

.database-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.database-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    background: #f9fafb;
    position: relative;
}

.database-card:hover {
    border-color: #4f46e5;
    background: #f0f9ff;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.15);
}

.database-card.selected {
    border-color: #4f46e5;
    background: #ede9fe;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.database-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 15px;
    background: #e5e7eb;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #6b7280;
    transition: all 0.3s ease;
}

.database-card:hover .database-icon,
.database-card.selected .database-icon {
    background: #4f46e5;
    color: white;
}

.database-name {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: #374151;
}

.database-description {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.4;
}

.database-port {
    position: absolute;
    top: 10px;
    right: 15px;
    background: #e5e7eb;
    color: #6b7280;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}

.database-card.selected .database-port {
    background: #4f46e5;
    color: white;
}

.selected-info {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
    display: none;
}

.selected-info.show {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #e5e7eb;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: #374151;
}

.info-value {
    color: #6b7280;
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
    
    .database-grid {
        grid-template-columns: 1fr;
    }
    
    .wizard-navigation {
        flex-direction: column;
        gap: 15px;
    }
}
</style>
{{-- @endpush

@section('content') --}}
{{-- <div class="container"> --}}

    <!-- Wizard Container -->
    <div class="wizard-container">
        <!-- Wizard Header -->
        <div class="wizard-header">
            <div class="wizard-title">🚀 SQL Alert Builder</div>
            <div class="wizard-subtitle">สร้างการแจ้งเตือนอัตโนมัติจากฐานข้อมูล</div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
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
                <div class="step"></div>
            </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
            <form id="sqlAlertForm" method="POST" action="{{ route('sql-alerts.store') }}">
                @csrf
                
                <!-- Step 1: Database Selection -->
                <div class="section-title">
                    <div class="section-icon">1</div>
                    เลือกชนิดฐานข้อมูล
                </div>
                
                <p class="text-muted mb-4">
                    เลือกประเภทฐานข้อมูลที่ต้องการเชื่อมต่อสำหรับสร้างการแจ้งเตือน
                </p>

                <!-- Database Selection Grid -->
                <div class="database-grid">
                    <!-- MySQL -->
                    <div class="database-card" data-db-type="mysql" data-port="3306" onclick="selectDatabase(this)">
                        <div class="database-port">:3306</div>
                        <div class="database-icon">
                            <i class="fab fa-mysql"></i>
                        </div>
                        <div class="database-name">MySQL</div>
                        <div class="database-description">
                            ฐานข้อมูลโอเพนซอร์สยอดนิยม เหมาะสำหรับเว็บแอปพลิเคชัน
                        </div>
                    </div>

                    <!-- PostgreSQL -->
                    <div class="database-card" data-db-type="postgresql" data-port="5432" onclick="selectDatabase(this)">
                        <div class="database-port">:5432</div>
                        <div class="database-icon">
                            <i class="fas fa-elephant"></i>
                        </div>
                        <div class="database-name">PostgreSQL</div>
                        <div class="database-description">
                            ฐานข้อมูลขั้นสูงพร้อมฟีเจอร์ครบครัน เหมาะสำหรับองค์กร
                        </div>
                    </div>

                    <!-- SQL Server -->
                    <div class="database-card" data-db-type="sqlserver" data-port="1433" onclick="selectDatabase(this)">
                        <div class="database-port">:1433</div>
                        <div class="database-icon">
                            <i class="fab fa-microsoft"></i>
                        </div>
                        <div class="database-name">SQL Server</div>
                        <div class="database-description">
                            ฐานข้อมูลของ Microsoft เหมาะสำหรับ Enterprise
                        </div>
                    </div>

                    <!-- Oracle -->
                    <div class="database-card" data-db-type="oracle" data-port="1521" onclick="selectDatabase(this)">
                        <div class="database-port">:1521</div>
                        <div class="database-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="database-name">Oracle</div>
                        <div class="database-description">
                            ฐานข้อมูลระดับองค์กรสำหรับงานหนัก
                        </div>
                    </div>

                    <!-- SQLite -->
                    <div class="database-card" data-db-type="sqlite" data-port="" onclick="selectDatabase(this)">
                        <div class="database-port">File</div>
                        <div class="database-icon">
                            <i class="fas fa-file-archive"></i>
                        </div>
                        <div class="database-name">SQLite</div>
                        <div class="database-description">
                            ฐานข้อมูลแบบไฟล์ เหมาะสำหรับแอปพลิเคชันขนาดเล็ก
                        </div>
                    </div>

                    <!-- MariaDB -->
                    <div class="database-card" data-db-type="mariadb" data-port="3306" onclick="selectDatabase(this)">
                        <div class="database-port">:3306</div>
                        <div class="database-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <div class="database-name">MariaDB</div>
                        <div class="database-description">
                            ฐานข้อมูลโอเพนซอร์สที่พัฒนาจาก MySQL
                        </div>
                    </div>
                </div>

                <!-- Selected Database Info -->
                <div class="selected-info" id="selectedInfo">
                    <h5 class="mb-3">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        ข้อมูลฐานข้อมูลที่เลือก
                    </h5>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">ประเภท:</span>
                            <span class="info-value" id="selectedType">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Port เริ่มต้น:</span>
                            <span class="info-value" id="selectedPort">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Driver:</span>
                            <span class="info-value" id="selectedDriver">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">การเชื่อมต่อ:</span>
                            <span class="info-value" id="selectedConnection">-</span>
                        </div>
                    </div>
                </div>

                <!-- Hidden Input -->
                <input type="hidden" name="database_type" id="databaseType" value="">

                <!-- Navigation -->
                <div class="wizard-navigation">
                    <a href="{{ route('notifications.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        ยกเลิก
                    </a>
                    
                    <div class="status-indicator">
                        <i class="fas fa-info-circle"></i>
                        ขั้นตอนที่ 1 จาก 14
                    </div>
                    
                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()" disabled>
                        ถัดไป
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
{{-- </div> --}}

{{-- @push('scripts') --}}

{{-- @endpush
@endsection --}}