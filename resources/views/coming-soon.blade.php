<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Notification System - เร็วๆ นี้</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .coming-soon-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .animated-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="rgba(255,255,255,0.1)"/><stop offset="100%" stop-color="rgba(255,255,255,0)"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23a)" opacity="0.6"><animateTransform attributeName="transform" type="translate" values="0,0;800,600;0,0" dur="20s" repeatCount="indefinite"/></circle><circle cx="800" cy="300" r="150" fill="url(%23a)" opacity="0.4"><animateTransform attributeName="transform" type="translate" values="0,0;-600,400;0,0" dur="25s" repeatCount="indefinite"/></circle><circle cx="500" cy="700" r="80" fill="url(%23a)" opacity="0.5"><animateTransform attributeName="transform" type="translate" values="0,0;300,-500;0,0" dur="18s" repeatCount="indefinite"/></circle></svg>') center/cover;
            z-index: 1;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            text-align: center;
            max-width: 600px;
            width: 90%;
            position: relative;
            z-index: 2;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .main-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            font-size: 1.2rem;
            color: #4a5568;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .feature-list {
            text-align: left;
            margin: 2rem 0;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .feature-item:hover {
            transform: translateX(10px);
        }

        .feature-icon {
            color: #667eea;
            margin-right: 1rem;
            font-size: 1.2rem;
            width: 24px;
        }

        .feature-text {
            color: #2d3748;
            font-weight: 500;
        }

        .progress-section {
            margin: 2rem 0;
        }

        .progress-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .progress {
            height: 12px;
            border-radius: 10px;
            background-color: rgba(102, 126, 234, 0.2);
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 10px;
            transition: width 2s ease-in-out;
            animation: progressAnimation 3s ease-in-out;
        }

        @keyframes progressAnimation {
            0% { width: 0%; }
            100% { width: 85%; }
        }

        .contact-info {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 15px;
            border-left: 4px solid #667eea;
        }

        .contact-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            color: #4a5568;
        }

        .contact-icon {
            color: #667eea;
            margin-right: 0.75rem;
            width: 20px;
        }

        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-element {
            position: absolute;
            color: rgba(255, 255, 255, 0.3);
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            top: 20%;
            right: 15%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        .floating-element:nth-child(4) {
            bottom: 15%;
            right: 10%;
            animation-delay: 1s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }

        @media (max-width: 768px) {
            .content-card {
                padding: 2rem;
                margin: 1rem;
            }
            
            .main-title {
                font-size: 2rem;
            }
            
            .subtitle {
                font-size: 1.1rem;
            }
        }

        .notification-demo {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            border-radius: 10px;
            padding: 1rem;
            margin: 1.5rem 0;
            border: 1px solid rgba(102, 126, 234, 0.3);
        }

        .demo-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
        }

        .demo-channels {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
        }

        .demo-channel {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.5rem;
            margin: 0.25rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            min-width: 80px;
        }

        .demo-channel-icon {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }

        .demo-channel-text {
            font-size: 0.8rem;
            font-weight: 500;
            color: #4a5568;
        }

        .teams-icon { color: #5059c9; }
        .email-icon { color: #ea4335; }
        .sms-icon { color: #25d366; }
        .slack-icon { color: #4a154b; }
    </style>
</head>
<body>
    <div class="animated-bg"></div>
    
    <div class="floating-elements">
        <i class="fas fa-bell floating-element" style="font-size: 2rem;"></i>
        <i class="fas fa-envelope floating-element" style="font-size: 1.5rem;"></i>
        <i class="fas fa-users floating-element" style="font-size: 1.8rem;"></i>
        <i class="fas fa-cog floating-element" style="font-size: 1.6rem;"></i>
    </div>

    <div class="coming-soon-container">
        <div class="content-card">
            <div class="logo-icon">
                <i class="fas fa-bell"></i>
            </div>
            
            <h1 class="main-title">Smart Notification System</h1>
            <p class="subtitle">
                ระบบแจ้งเตือนอัจฉริยะสำหรับองค์กร<br>
                <strong>เร็วๆ นี้</strong>
            </p>

            <div class="notification-demo">
                <div class="demo-title">
                    <i class="fas fa-paper-plane" style="margin-right: 0.5rem; color: #667eea;"></i>
                    ช่องทางการแจ้งเตือน
                </div>
                <div class="demo-channels">
                    <div class="demo-channel">
                        <i class="fab fa-microsoft teams-icon demo-channel-icon"></i>
                        <span class="demo-channel-text">Teams</span>
                    </div>
                    <div class="demo-channel">
                        <i class="fas fa-envelope email-icon demo-channel-icon"></i>
                        <span class="demo-channel-text">Email</span>
                    </div>
                    <div class="demo-channel">
                        <i class="fab fa-whatsapp sms-icon demo-channel-icon"></i>
                        <span class="demo-channel-text">SMS</span>
                    </div>
                    <div class="demo-channel">
                        <i class="fab fa-slack slack-icon demo-channel-icon"></i>
                        <span class="demo-channel-text">Slack</span>
                    </div>
                </div>
            </div>

            <div class="feature-list">
                <div class="feature-item">
                    <i class="fas fa-users feature-icon"></i>
                    <span class="feature-text">เชื่อมต่อกับระบบ LDAP ขององค์กร</span>
                </div>
                <div class="feature-item">
                    <i class="fab fa-microsoft feature-icon"></i>
                    <span class="feature-text">ส่งการแจ้งเตือนผ่าน Microsoft Teams</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-envelope feature-icon"></i>
                    <span class="feature-text">ระบบอีเมลแบบอัตโนมัติ</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-clock feature-icon"></i>
                    <span class="feature-text">กำหนดเวลาส่งล่วงหน้าได้</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-bar feature-icon"></i>
                    <span class="feature-text">รายงานและสถิติแบบ Real-time</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt feature-icon"></i>
                    <span class="feature-text">API Gateway พร้อมระบบรักษาความปลอดภัย</span>
                </div>
            </div>

            <div class="progress-section">
                <div class="progress-label">ความคืบหน้าการพัฒนา</div>
                <div class="progress">
                    <div class="progress-bar" style="width: 85%;"></div>
                </div>
                <small class="text-muted mt-1 d-block">85% เสร็จสมบูรณ์</small>
            </div>

            <div class="contact-info">
                <div class="contact-title">
                    <i class="fas fa-info-circle" style="margin-right: 0.5rem; color: #667eea;"></i>
                    ข้อมูลติดต่อ
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope contact-icon"></i>
                    <span>support@smartnotification.com</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone contact-icon"></i>
                    <span>02-xxx-xxxx</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-building contact-icon"></i>
                    <span>ฝ่ายเทคโนโลยีสารสนเทศ</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-calendar-alt contact-icon"></i>
                    <span>เปิดใช้งาน: กรกฎาคม 2025</span>
                </div>
            </div>

            <div class="mt-4">
                <small class="text-muted">
                    <i class="fas fa-code"></i>
                    พัฒนาด้วย Laravel Framework & Vue.js
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bar
            setTimeout(() => {
                const progressBar = document.querySelector('.progress-bar');
                progressBar.style.width = '85%';
            }, 500);

            // Add hover effects to feature items
            const featureItems = document.querySelectorAll('.feature-item');
            featureItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(10px)';
                    this.style.boxShadow = '0 5px 15px rgba(102, 126, 234, 0.3)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                    this.style.boxShadow = 'none';
                });
            });

            // Demo channel hover effects
            const demoChannels = document.querySelectorAll('.demo-channel');
            demoChannels.forEach(channel => {
                channel.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.1)';
                    this.style.transition = 'transform 0.3s ease';
                });
                
                channel.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>