<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIT Test Plan - Smart Notification System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 50px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 15px,
                rgba(255,255,255,0.03) 15px,
                rgba(255,255,255,0.03) 30px
            );
            animation: float 30s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        .header-content {
            position: relative;
            z-index: 2;
        }
        
        .header h1 {
            font-size: 3.2em;
            font-weight: 300;
            margin-bottom: 15px;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.3);
        }
        
        .header .subtitle {
            font-size: 1.3em;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .header-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .stat-item {
            background: rgba(255,255,255,0.1);
            padding: 25px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 10px;
            color: #ffd700;
        }
        
        .stat-label {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .test-overview {
            padding: 60px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .overview-title {
            text-align: center;
            font-size: 2.5em;
            color: #2c3e50;
            margin-bottom: 50px;
            font-weight: 300;
        }
        
        .test-phases {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 40px;
        }
        
        .phase-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            border: 1px solid #e9ecef;
        }
        
        .phase-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0,0,0,0.15);
        }
        
        .phase-header {
            padding: 30px;
            color: white;
            text-align: center;
            position: relative;
        }
        
        .phase1-header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .phase2-header {
            background: linear-gradient(135deg, #f39c12 0%, #d68910 100%);
        }
        
        .phase-number {
            font-size: 4em;
            font-weight: 100;
            opacity: 0.3;
            position: absolute;
            top: 10px;
            right: 20px;
        }
        
        .phase-title {
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .phase-duration {
            background: rgba(255,255,255,0.2);
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 500;
        }
        
        .phase-content {
            padding: 40px;
        }
        
        .test-categories {
            margin-bottom: 30px;
        }
        
        .category-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #ecf0f1;
            transition: all 0.3s ease;
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .category-item:hover {
            background: #f8f9fa;
            padding-left: 15px;
        }
        
        .category-icon {
            font-size: 1.5em;
            margin-right: 15px;
            width: 40px;
            text-align: center;
        }
        
        .category-name {
            font-weight: 600;
            color: #2c3e50;
            flex: 1;
        }
        
        .category-count {
            background: #3498db;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 600;
        }
        
        .detailed-tests {
            margin: 60px 0;
        }
        
        .section-title {
            font-size: 2.2em;
            color: #2c3e50;
            margin-bottom: 40px;
            text-align: center;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2980b9);
            margin: 20px auto;
            border-radius: 2px;
        }
        
        .test-section {
            margin-bottom: 60px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .test-section-header {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
            padding: 30px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .section-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .section-icon {
            font-size: 2em;
        }
        
        .section-details h3 {
            font-size: 1.6em;
            margin-bottom: 5px;
        }
        
        .section-details p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .test-count-badge {
            background: rgba(255,255,255,0.2);
            padding: 15px 25px;
            border-radius: 15px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .test-count-badge .number {
            font-size: 2em;
            font-weight: 700;
            display: block;
        }
        
        .test-count-badge .label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .test-cases-grid {
            padding: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
        }
        
        .test-case {
            background: #f8f9fa;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .test-case:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }
        
        .test-case-header {
            background: white;
            padding: 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .test-id {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .test-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            flex: 1;
            margin-right: 20px;
        }
        
        .test-case-body {
            padding: 25px;
        }
        
        .test-objective {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #3498db;
            margin-bottom: 20px;
        }
        
        .test-objective h4 {
            color: #2980b9;
            margin-bottom: 10px;
            font-size: 1em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .test-objective p {
            color: #34495e;
            font-weight: 500;
        }
        
        .test-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .detail-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        
        .detail-box h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-box ul {
            list-style: none;
            color: #5a6c7d;
        }
        
        .detail-box li {
            padding: 8px 0;
            position: relative;
            padding-left: 25px;
        }
        
        .detail-box li::before {
            content: '→';
            color: #3498db;
            font-weight: bold;
            position: absolute;
            left: 0;
        }
        
        .test-data-section {
            background: #fff9e6;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #f39c12;
            margin-bottom: 20px;
        }
        
        .expected-results {
            background: #e8f8f0;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #27ae60;
        }
        
        .priority-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .priority-high {
            background: #ffebee;
            color: #c62828;
        }
        
        .priority-medium {
            background: #fff3e0;
            color: #ef6c00;
        }
        
        .priority-low {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .test-timeline {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 60px;
        }
        
        .timeline-title {
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 50px;
            font-weight: 300;
        }
        
        .timeline-container {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
        }
        
        .timeline-line {
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(to bottom, #e74c3c, #f39c12, #3498db);
            transform: translateX(-50%);
            border-radius: 2px;
        }
        
        .timeline-day {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .timeline-day:nth-child(odd) .timeline-content {
            margin-right: 60%;
            text-align: right;
        }
        
        .timeline-day:nth-child(even) .timeline-content {
            margin-left: 60%;
            text-align: left;
        }
        
        .timeline-content {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .timeline-marker {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 30px;
            height: 30px;
            background: #3498db;
            border-radius: 50%;
            border: 6px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 10;
        }
        
        .day-number {
            font-size: 1.2em;
            font-weight: 700;
            color: #ffd700;
            margin-bottom: 10px;
        }
        
        .day-title {
            font-size: 1.4em;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .day-activities {
            font-size: 1em;
            opacity: 0.9;
            line-height: 1.8;
        }
        
        .tools-section {
            background: white;
            padding: 60px;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .tool-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .tool-icon {
            font-size: 3em;
            margin-bottom: 20px;
        }
        
        .tool-name {
            font-size: 1.3em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .tool-description {
            color: #5a6c7d;
            line-height: 1.6;
        }
        
        @media (max-width: 1200px) {
            .test-cases-grid {
                grid-template-columns: 1fr;
            }
            
            .test-details-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .header h1 { font-size: 2.5em; }
            .header-stats { grid-template-columns: repeat(2, 1fr); }
            .test-phases { grid-template-columns: 1fr; }
            .timeline-day .timeline-content {
                margin: 0 !important;
                text-align: center !important;
            }
            .timeline-line { display: none; }
            .timeline-marker { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1>🧪 SIT Test Plan</h1>
                <p class="subtitle">System Integration Testing - Smart Notification System</p>
                
                <div class="header-stats">
                    <div class="stat-item">
                        <div class="stat-number">2</div>
                        <div class="stat-label">Test Phases</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">7</div>
                        <div class="stat-label">Testing Days</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">28</div>
                        <div class="stat-label">Test Cases</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">6</div>
                        <div class="stat-label">Test Categories</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="test-overview">
            <h2 class="overview-title">Testing Phases Overview</h2>
            
            <div class="test-phases">
                <div class="phase-card">
                    <div class="phase-header phase1-header">
                        <div class="phase-number">1</div>
                        <div class="phase-title">Foundation & Authentication</div>
                        <div class="phase-duration">3 วัน</div>
                    </div>
                    <div class="phase-content">
                        <div class="test-categories">
                            <div class="category-item">
                                <div class="category-icon">🧪</div>
                                <div class="category-name">Unit Testing</div>
                                <div class="category-count">5</div>
                            </div>
                            <div class="category-item">
                                <div class="category-icon">🔗</div>
                                <div class="category-name">Integration Testing</div>
                                <div class="category-count">4</div>
                            </div>
                            <div class="category-item">
                                <div class="category-icon">🔒</div>
                                <div class="category-name">Security Testing</div>
                                <div class="category-count">3</div>
                            </div>
                        </div>
                        <p style="color: #666; text-align: center; margin-top: 20px;">
                            ทดสอบการเชื่อมต่อ LDAP, ระบบยืนยันตัวตน และความปลอดภัยพื้นฐาน
                        </p>
                    </div>
                </div>
                
                <div class="phase-card">
                    <div class="phase-header phase2-header">
                        <div class="phase-number">2</div>
                        <div class="phase-title">Core System & Database</div>
                        <div class="phase-duration">4 วัน</div>
                    </div>
                    <div class="phase-content">
                        <div class="test-categories">
                            <div class="category-item">
                                <div class="category-icon">🗄️</div>
                                <div class="category-name">Database Testing</div>
                                <div class="category-count">5</div>
                            </div>
                            <div class="category-item">
                                <div class="category-icon">🐰</div>
                                <div class="category-name">RabbitMQ Testing</div>
                                <div class="category-count">4</div>
                            </div>
                            <div class="category-item">
                                <div class="category-icon">📧</div>
                                <div class="category-name">Notification Testing</div>
                                <div class="category-count">4</div>
                            </div>
                            <div class="category-item">
                                <div class="category-icon">⚡</div>
                                <div class="category-name">Performance Testing</div>
                                <div class="category-count">3</div>
                            </div>
                        </div>
                        <p style="color: #666; text-align: center; margin-top: 20px;">
                            ทดสอบฐานข้อมูล, ระบบคิว, การแจ้งเตือน และประสิทธิภาพ
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detailed Test Cases -->
        <div class="detailed-tests">
            <h2 class="section-title">รายละเอียด Test Cases</h2>
            
            <!-- Phase 1 Tests -->
            <div class="test-section">
                <div class="test-section-header">
                    <div class="section-info">
                        <div class="section-icon">🔴</div>
                        <div class="section-details">
                            <h3>Phase 1: Foundation & Authentication Testing</h3>
                            <p>ทดสอบการเชื่อมต่อ LDAP และระบบยืนยันตัวตน</p>
                        </div>
                    </div>
                    <div class="test-count-badge">
                        <span class="number">12</span>
                        <span class="label">Test Cases</span>
                    </div>
                </div>
                
                <div class="test-cases-grid">
                    <!-- Unit Testing Cases -->
                    <div class="test-case">
                        <div class="test-case-header">
                            <div>
                                <div class="test-title">LDAP Connection Pool Testing</div>
                                <span class="priority-badge priority-high">High Priority</span>
                            </div>
                            <div class="test-id">SIT-P1-UT-001</div>
                        </div>
                        <div class="test-case-body">
                            <div class="test-objective">
                                <h4>🎯 Test Objective</h4>
                                <p>ทดสอบการเชื่อมต่อกับ LDAP Server และ Connection Pool Management ให้สามารถรองรับ concurrent users ได้อย่างมีประสิทธิภาพ</p>
                            </div>
                            
                            <div class="test-details-grid">
                                <div class="detail-box">
                                    <h4>📋 Test Steps</h4>
                                    <ul>
                                        <li>Initialize LDAP connection pool</li>
                                        <li>Test multiple concurrent connections</li>
                                        <li>Validate connection timeout handling</li>
                                        <li>Test connection recycling</li>
                                        <li>Monitor resource usage</li>
                                    </ul>
                                </div>
                                
                                <div class="detail-box">
                                    <h4>📊 Success Criteria</h4>
                                    <ul>
                                        <li>Connection established < 3 seconds</li>
                                        <li>Handle 100 concurrent connections</li>
                                        <li>Proper error handling for failures</li>
                                        <li>Memory leak prevention</li>
                                        <li>Graceful degradation</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="test-data-section">
                                <h4>🔧 Test Configuration</h4>
                                <p><strong>LDAP Server:</strong> ldap://test-server.company.com:389<br>
                                <strong>Pool Size:</strong> 10-50 connections<br>
                                <strong>Timeout:</strong> 5 seconds<br>
                                <strong>Test Users:</strong> 100 concurrent connections</p>
                            </div>
                            
                            <div class="expected-results">
                                <h4>✅ Expected Results</h4>
                                <p>• Connection pool จัดการ resources อย่างมีประสิทธิภาพ<br>
                                • ไม่มี memory leaks หรือ connection hanging<br>
                                • Error logging ทำงานถูกต้องสำหรับ connection failures</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="test-case">
                        <div class="test-case-header">
                            <div>
                                <div class="test-title">LDAP Authentication Methods</div>
                                <span class="priority-badge priority-high">High Priority</span>
                            </div>
                            <div class="test-id">SIT-P1-UT-002</div>
                        </div>
                        <div class="test-case-body">
                            <div class="test-objective">
                                <h4>🎯 Test Objective</h4>
                                <p>ทดสอบ LDAP Bind Authentication ในสถานการณ์ต่างๆ รวมถึงการจัดการ credentials และ security measures</p>
                            </div>
                            
                            <div class="test-details-grid">
                                <div class="detail-box">
                                    <h4>🔐 Authentication Scenarios</h4>
                                    <ul>
                                        <li>Valid username/password authentication</li>
                                        <li>Invalid credentials handling</li>
                                        <li>Empty/null credentials</li>
                                        <li>Special characters in credentials</li>
                                        <li>Account lockout prevention</li>
                                    </ul>
                                </div>
                                
                                <div class="detail-box">
                                    <h4>🛡️ Security Tests</h4>
                                    <ul>
                                        <li>LDAP injection prevention</li>
                                        <li>Brute force attack protection</li>
                                        <li>Session security validation</li>
                                        <li>Password complexity enforcement</li>
                                        <li>Audit trail logging</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="expected-results">
                                <h4>✅ Expected Results</h4>
                                <p>• Authentication สำเร็จสำหรับ valid credentials<br>
                                • Proper error messages แสดงสำหรับ invalid cases<br>
                                • Security logging บันทึกทุก authentication attempts</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="test-case">
                        <div class="test-case-header">
                            <div>
                                <div class="test-title">User Data Synchronization</div>
                                <span class="priority-badge priority-medium">Medium Priority</span>
                            </div>
                            <div class="test-id">SIT-P1-UT-003</div>
                        </div>
                        <div class="test-case-body">
                            <div class="test-objective">
                                <h4>🎯 Test Objective</h4>
                                <p>ทดสอบการดึงข้อมูลผู้ใช้จาก LDAP และ mapping ไปยัง Laravel User Model</p>
                            </div>
                            
                            <div class="test-details-grid">
                                <div class="detail-box">
                                    <h4>📝 LDAP Attributes</h4>
                                    <ul>
                                        <li>cn (Common Name)</li>
                                        <li>mail (Email Address)</li>
                                        <li>department</li>
                                        <li>title (Job Title)</li>
                                        <li>telephoneNumber</li>
                                    </ul>
                                </div>
                                
                                <div class="detail-box">
                                    <h4>🔄 Data Mapping Tests</h4>
                                    <ul>
                                        <li>Complete attribute mapping</li>
                                        <li>Missing attributes handling</li>
                                        <li>Data type conversion</li>
                                        <li>Character encoding validation</li>
                                        <li>Database storage verification</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="expected-results">
                                <h4>✅ Expected Results</h4>
                                <p>• ข้อมูล LDAP map ถูกต้องไปยัง Laravel User Model<br>
                                • Handle missing attributes without errors<br>
                                • Character encoding preserved correctly</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="test-case">
                        <div class="test-case-header">
                            <div>
                                <div class="test-title">Laravel Authentication Integration</div>
                                <span class="priority-badge priority-high">High Priority</span>
                            </div>
                            <div class="test-id">SIT-P1-IT-001</div>
                        </div>
                        <div class="test-case-body">
                            <div class="test-objective">
                                <h4>🎯 Test Objective</h4>
                                <p>ทดสอบการทำงานร่วมกันระหว่าง Laravel Auth Guard กับ LDAP Service Provider</p>
                            </div>
                            
                            <div class="test-details-grid">
                                <div class="detail-box">
                                    <h4>🔗 Integration Points</h4>
                                    <ul>
                                        <li>Custom Auth Guard registration</li>
                                        <li>LDAP Service Provider binding</li>
                                        <li>Middleware authentication flow</li>
                                        <li>Session management integration</li>
                                        <li>Route protection validation</li>
                                    </ul>
                                </div>
                                
                                <div class="detail-box">
                                    <h4>🔄 Workflow Testing</h4>
                                    <ul>
                                        <li>Login form submission</li>
                                        <li>Authentication middleware execution</li>
                                        <li>User object population</li>
                                        <li>Session creation and storage</li>
                                        <li>Redirect after authentication</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="expected-results">
                                <h4>✅ Expected Results</h4>
                                <p>• Complete login workflow functions seamlessly<br>
                                • User data properly synced from LDAP to session<br>
                                • Protected routes require valid authentication</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="test-case">
                        <div class="test-case-header">
                            <div>
                                <div class="test-title">JWT Token Management</div>
                                <span class="priority-badge priority-high">High Priority</span>
                            </div>
                            <div class="test-id">SIT-P1-IT-002</div>
                        </div>
                        <div class="test-case-body">
                            <div class="test-objective">
                                <h4>🎯 Test Objective</h4>
                                <p>ทดสอบการสร้าง, การตรวจสอบ และการจัดการ JWT Tokens สำหรับ API Authentication</p>
                            </div>
                            
                            <div class="test-details-grid">
                                <div class="detail-box">
                                    <h4>🎫 Token Features</h4>
                                    <ul>
                                        <li>Token generation with user claims</li>
                                        <li>Token expiration (8 hours)</li>
                                        <li>Token validation middleware</li>
                                        <li>Refresh token mechanism</li>
                                        <li>Token revocation capability</li>
                                    </ul>
                                </div>
                                
                                <div class="detail-box">
                                    <h4>🔒 Security Validation</h4>
                                    <ul>
                                        <li>Secret key security</li>
                                        <li>Token tampering detection</li>
                                        <li>Expired token rejection</li>
                                        <li>Invalid signature handling</li>
                                        <li>Concurrent session management</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="expected-results">
                                <h4>✅ Expected Results</h4>
                                <p>• JWT tokens generated with proper claims and security<br>
                                • Token validation works correctly in API requests<br>
                                • Refresh mechanism functions without security gaps</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="test-case">
                        <div class="test-case-header">
                            <div>
                                <div class="test-title">Security Vulnerability Assessment</div>
                                <span class="priority-badge priority-high">High Priority</span>
                            </div>
                            <div class="test-id">SIT-P1-ST-001</div>
                        </div>
                        <div class="test-case-body">
                            <div class="test-objective">
                                <h4>🎯 Test Objective</h4>
                                <p>ทดสอบช่องโหว่ความปลอดภัยในระบบ Authentication และป้องกัน common attacks</p>
                            </div>
                            
                            <div class="test-details-grid">
                                <div class="detail-box">
                                    <h4>🛡️ Security Tests</h4>
                                    <ul>
                                        <li>LDAP Injection attacks</li>
                                        <li>SQL Injection prevention</li>
                                        <li>Cross-Site Scripting (XSS)</li>
                                        <li>Cross-Site Request Forgery (CSRF)</li>
                                        <li>Session hijacking prevention</li>
                                    </ul>
                                </div>
                                
                                <div class="detail-box">
                                    <h4>🔧 Testing Tools</h4>
                                    <ul>
                                        <li>OWASP ZAP Scanner</li>
                                        <li>Burp Suite Professional</li>
                                        <li>Custom penetration scripts</li>
                                        <li>Manual security testing</li>
                                        <li>Code security analysis</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="expected-results">
                                <h4>✅ Expected Results</h4>
                                <p>• No critical security vulnerabilities detected<br>
                                • Rate limiting prevents brute force attacks<br>
                                • Proper input validation and sanitization</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Phase 2 Tests -->
            <div class="test-section">
                <div class="test-section-header">
                    <div class="section-info">
                        <div class="section-icon">🟠</div>
                        <div class="section-details">
                            <h3>Phase 2: Core System & Database Testing</h3>
                            <p>ทดสอบฐานข้อมูล, ระบบคิว และการแจ้งเตือน</p>
                        </div>
                    </div>
                    <div class="test-count-badge">
                        <span class="number">16</span>
                        <span class="label">Test Cases</span>
                    </div>
                </div>
                
                <div class="test-cases-grid">
                    <div class="test-case">
                        <div class="test-case-header">
                            <div>
                                <div class="test-title">PostgreSQL Performance Testing</div>
                                <span class="priority-badge priority-high">High Priority</span>
                            </div>
                            <div class="test-id">SIT-P2-DB-001</div>
                        </div>
                        <div class="test-case-body">
                            <div class="test-objective">
                                <h4>🎯 Test Objective</h4>
                                <p>ทดสอบประสิทธิภาพฐานข้อมูล PostgreSQL และ Connection Pool management ภายใต้ load ต่างๆ</p>
                            </div>
                            
                            <div class="test-details-grid">
                                <div class="detail-box">
                                    <h4>📊 Performance Metrics</h4>
                                    <ul>
                                        <li>Query response time < 500ms</li>
                                        <li>100 concurrent connections</li>
                                        <li>Transaction throughput testing</li>
                                        <li>Connection pool efficiency</li>
                                        <li>Resource utilization monitoring</li>
                                    </ul>
                                </div>
                                
                                <div class="detail-box">
                                    <h4>🔧 Test Scenarios</h4>
                                    <ul>
                                        <li>Light load (10 concurrent users)</li>
                                        <li>Normal load (50 concurrent users)</li>
                                        <li>Heavy load (100 concurrent users)</li>
                                        <li>Stress test (150+ concurrent users)</li>
                                        <li>Long-running query testing</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="test-data-section">
                                <h4>🔧 Test Configuration</h4>
                                <p><strong>Database:</strong> PostgreSQL 13+<br>
                                <strong>Connection Pool:</strong> 10-50 connections<br>
                                <strong>Test Duration:</strong> 30 minutes per scenario<br>
                                <strong>Query Types:</strong> SELECT, INSERT, UPDATE, DELETE</p>
                            </div>
                            
                            <div class="expected-results">
                                <h4>✅ Expected Results</h4>
                                <p>• 95% of queries execute within 500ms<br>
                                • Connection pool handles concurrent access efficiently<br>
                                • No connection leaks or deadlocks detected</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="test-case">
                        <div class="test-case-header">
                            <div>
                                <div class="test-title">Laravel Model Relationships</div>
                                <span class="priority-badge priority-high">High Priority</span>
                            </div>
                            <div class="test-id">SIT-P2-DB-002</div>
                        </div>
                        <div class="test-case-body">
                            <div class="test-objective">
                                <h4>🎯 Test Objective</h4>
                                <p>ทดสอบ Laravel Eloquent Models และ relationships ระหว่าง tables ต่างๆ</p>
                            </div>
                            
                            <div class="test-details-grid">
                                <div class="detail-box">
                                    <h4>🗄️ Model Testing</h4>
                                    <ul>
                                        <li>User Model CRUD operations</li>
                                        <li>NotificationTemplate Model</li>
                                        <li>NotificationGroup Model</li>
                                        <li>Notification Model</li>
                                        <li>ApiKey Model validation</li>
                                    </ul>
                                </div>
                                
                                <div class="detail-box">
                                    <h4>🔗 Relationship Testing</h4>
                                    <ul>
                                        <li>User hasMany Notifications</li>
                                        <li>Notification belongsTo Template</li>
                                        <li>NotificationGroup belongsToMany Users</li>
                                        <li>Eager loading performance</li>
                                        <li>Cascade delete operations</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="expected-results">
                                <h4>✅ Expected Results</h4>
                                <p>• All model relationships function correctly<br>
                                • CRUD operations successful for all models<br>
                                • Data integrity constraints enforced properly</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="test-case">
                        <div class="test-case-header">
                            <div>
                                <div class="test-title">RabbitMQ Queue Configuration</div>
                                <span class="priority-badge priority-high">High Priority</span>
                            </div>
                            <div class="test-id">SIT-P2-MQ-001</div>
                        </div>
                        <div class="test-case-body">
                            <div class="test-objective">
                                <h4>🎯 Test Objective</h4>
                                <p>ทดสอบการกำหนดค่า RabbitMQ exchanges, queues และ message routing</p>
                            </div>
                            
                            <div class="test-details-grid">
                                <div class="detail-box">
                                    <h4>🐰 Queue Setup</h4>
                                    <ul>
                                        <li>Topic Exchange creation</li>
                                        <li>Queue durability settings</li>
                                        <li>Message TTL (24 hours)</li>
                                        <li>Dead Letter Queue setup</li>
                                        <li>Priority queue configuration</li>
                                    </ul>
                                </div>
                                
                                <div class="detail-box">
                                    <h4>📨 Message Routing</h4>
                                    <ul>
                                        <li>Email notification routing</li>
                                        <li>Teams notification routing</li>
                                        <li>Priority message handling</li>
                                        <li>Failed message redirection</li>
                                        <li>Queue monitoring setup</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="test-data-section">
                                <h4>🔧 Queue Configuration</h4>
                                <p><strong>Exchange:</strong> notifications.topic<br>
                                <strong>Queues:</strong> notification.email, notification.teams<br>
                                <strong>TTL:</strong> 86400000ms (24 hours)<br>
                                <strong>DLQ:</strong> notification.failed</p>
                            </div>
                            
                            <div class="expected-results">
                                <h4>✅ Expected Results</h4>
                                <p>• All exchanges and queues created successfully<br>
                                • Message routing works according to routing keys<br>
                                • Failed messages properly sent to DLQ</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="test-case">
                        <div class="test-case-header">
                            <div>
                                <div class="test-title">Message Processing Performance</div>
                                <span class="priority-badge priority-high">High Priority</span>
                            </div>
                            <div class="test-id">SIT-P2-MQ-002</div>
                        </div>
                        <div class="test-case-body">
                            <div class="test-objective">
                                <h4>🎯 Test Objective</h4>
                                <p>ทดสอบประสิทธิภาพการประมวลผล messages และ worker performance</p>
                            </div>
                            
                            <div class="test-details-grid">
                                <div class="detail-box">
                                    <h4>⚡ Performance Targets</h4>
                                    <ul>
                                        <li>100 messages/minute processing</li>
                                        <li>10,000 jobs/hour throughput</li>
                                        <li>5 concurrent worker processes</li>
                                        <li>Memory usage < 512MB per worker</li>
                                        <li>Message processing < 30 seconds</li>
                                    </ul>
                                </div>
                                
                                <div class="detail-box">
                                    <h4>📊 Load Testing</h4>
                                    <ul>
                                        <li>Light load: 100 messages</li>
                                        <li>Medium load: 1,000 messages</li>
                                        <li>Heavy load: 10,000 messages</li>
                                        <li>Stress test: 50,000 messages</li>
                                        <li>Sustained load: 2 hours continuous</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="expected-results">
                                <h4>✅ Expected Results</h4>
                                <p>• Maintain target processing rate under all loads<br>
                                • Memory usage stays within defined limits<br>
                                • No message loss or corruption detected</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="test-case">
                        <div class="test-case-header">
                            <div>
                                <div class="test-title">Notification Template Engine</div>
                                <span class="priority-badge priority-medium">Medium Priority</span>
                            </div>
                            <div class="test-id">SIT-P2-NT-001</div>
                        </div>
                        <div class="test-case-body">
                            <div class="test-objective">
                                <h4>🎯 Test Objective</h4>
                                <p>ทดสอบ Template Engine สำหรับการสร้าง dynamic notifications และ variable substitution</p>
                            </div>
                            
                            <div class="test-details-grid">
                                <div class="detail-box">
                                    <h4>📝 Template Features</h4>
                                    <ul>
                                        <li>Rich Text และ Markdown support</li>
                                        <li>Dynamic variable replacement</li>
                                        <li>Template validation rules</li>
                                        <li>Multi-language support</li>
                                        <li>Template versioning system</li>
                                    </ul>
                                </div>
                                
                                <div class="detail-box">
                                    <h4>🔄 Variable Testing</h4>
                                    <ul>
                                        <li>User-specific variables</li>
                                        <li>System-generated variables</li>
                                        <li>Date/time formatting</li>
                                        <li>Conditional content blocks</li>
                                        <li>HTML escape validation</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="test-data-section">
                                <h4>🔧 Test Templates</h4>
                                <p><strong>Alert:</strong> "System @{{system_name}} requires attention at @{{timestamp}}"<br>
                                <strong>Welcome:</strong> "Welcome @{{user_name}} to @{{organization}}"<br>
                                <strong>Reminder:</strong> "Meeting @{{meeting_title}} starts in @{{time_remaining}}"</p>
                            </div>
                            
                            <div class="expected-results">
                                <h4>✅ Expected Results</h4>
                                <p>• Variables replaced correctly in all templates<br>
                                • Markdown rendered properly to HTML<br>
                                • Template validation catches syntax errors</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="test-case">
                        <div class="test-case-header">
                            <div>
                                <div class="test-title">Scheduling System Testing</div>
                                <span class="priority-badge priority-medium">Medium Priority</span>
                            </div>
                            <div class="test-id">SIT-P2-NT-002</div>
                        </div>
                        <div class="test-case-body">
                            <div class="test-objective">
                                <h4>🎯 Test Objective</h4>
                                <p>ทดสอบระบบ scheduling และ cron expression parsing สำหรับการส่งข้อความตามเวลาที่กำหนด</p>
                            </div>
                            
                            <div class="test-details-grid">
                                <div class="detail-box">
                                    <h4>⏰ Scheduling Types</h4>
                                    <ul>
                                        <li>Immediate delivery</li>
                                        <li>Scheduled delivery</li>
                                        <li>Recurring notifications</li>
                                        <li>Cron expression parsing</li>
                                        <li>Timezone handling</li>
                                    </ul>
                                </div>
                                
                                <div class="detail-box">
                                    <h4>🕐 Cron Testing</h4>
                                    <ul>
                                        <li>Daily at specific time</li>
                                        <li>Weekly on specific day</li>
                                        <li>Monthly scheduling</li>
                                        <li>Custom intervals</li>
                                        <li>Holiday/weekend handling</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="expected-results">
                                <h4>✅ Expected Results</h4>
                                <p>• Cron expressions parsed and executed correctly<br>
                                • Timezone conversions accurate<br>
                                • Scheduled jobs execute at precise times</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Testing Timeline -->
        <div class="test-timeline">
            <h2 class="timeline-title">📅 7-Day Testing Schedule</h2>
            
            <div class="timeline-container">
                <div class="timeline-line"></div>
                
                <div class="timeline-day">
                    <div class="timeline-content">
                        <div class="day-number">Day 1</div>
                        <div class="day-title">🔴 Phase 1 Kickoff</div>
                        <div class="day-activities">
                            • LDAP Connection Testing<br>
                            • Authentication Methods Validation<br>
                            • Basic Integration Setup
                        </div>
                    </div>
                    <div class="timeline-marker"></div>
                </div>
                
                <div class="timeline-day">
                    <div class="timeline-content">
                        <div class="day-number">Day 2</div>
                        <div class="day-title">🔗 Integration & Security</div>
                        <div class="day-activities">
                            • Laravel + LDAP Integration<br>
                            • JWT Token Management<br>
                            • Security Vulnerability Testing
                        </div>
                    </div>
                    <div class="timeline-marker"></div>
                </div>
                
                <div class="timeline-day">
                    <div class="timeline-content">
                        <div class="day-number">Day 3</div>
                        <div class="day-title">✅ Phase 1 Completion</div>
                        <div class="day-activities">
                            • Bug Fixes & Refinements<br>
                            • Phase 1 Test Report<br>
                            • Stakeholder Sign-off
                        </div>
                    </div>
                    <div class="timeline-marker"></div>
                </div>
                
                <div class="timeline-day">
                    <div class="timeline-content">
                        <div class="day-number">Day 4</div>
                        <div class="day-title">🟠 Phase 2 Database</div>
                        <div class="day-activities">
                            • PostgreSQL Performance Testing<br>
                            • Laravel Models & Relationships<br>
                            • Migration & Seeding Validation
                        </div>
                    </div>
                    <div class="timeline-marker"></div>
                </div>
                
                <div class="timeline-day">
                    <div class="timeline-content">
                        <div class="day-number">Day 5</div>
                        <div class="day-title">🐰 RabbitMQ & Queues</div>
                        <div class="day-activities">
                            • Queue Configuration Testing<br>
                            • Message Processing Performance<br>
                            • Retry & Error Handling
                        </div>
                    </div>
                    <div class="timeline-marker"></div>
                </div>
                
                <div class="timeline-day">
                    <div class="timeline-content">
                        <div class="day-number">Day 6</div>
                        <div class="day-title">📧 Notification System</div>
                        <div class="day-activities">
                            • Template Engine Testing<br>
                            • Scheduling System Validation<br>
                            • Recipient Management Testing
                        </div>
                    </div>
                    <div class="timeline-marker"></div>
                </div>
                
                <div class="timeline-day">
                    <div class="timeline-content">
                        <div class="day-number">Day 7</div>
                        <div class="day-title">🚀 Final Testing & Report</div>
                        <div class="day-activities">
                            • End-to-End Performance Testing<br>
                            • Load Testing & Optimization<br>
                            • Comprehensive Test Report Generation
                        </div>
                    </div>
                    <div class="timeline-marker"></div>
                </div>
            </div>
        </div>
        
        <!-- Testing Tools Section -->
        <div class="tools-section">
            <h2 class="section-title">🛠️ Testing Tools & Environment</h2>
            
            <div class="tools-grid">
                <div class="tool-card">
                    <div class="tool-icon">🧪</div>
                    <div class="tool-name">PHPUnit</div>
                    <div class="tool-description">
                        Framework หลักสำหรับ Unit Testing และ Integration Testing ใน Laravel
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">🌐</div>
                    <div class="tool-name">Laravel Dusk</div>
                    <div class="tool-description">
                        Browser Testing สำหรับทดสอบ Web Interface และ User Interactions
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">⚡</div>
                    <div class="tool-name">Apache JMeter</div>
                    <div class="tool-description">
                        Performance และ Load Testing สำหรับ API และ Database
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">🔒</div>
                    <div class="tool-name">OWASP ZAP</div>
                    <div class="tool-description">
                        Security Testing และ Vulnerability Scanning
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">📊</div>
                    <div class="tool-name">Postman/Insomnia</div>
                    <div class="tool-description">
                        API Testing และ Documentation สำหรับ REST Endpoints
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">🐰</div>
                    <div class="tool-name">RabbitMQ Management</div>
                    <div class="tool-description">
                        Queue Monitoring และ Message Flow Testing
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>