# QUICK_START.md - คู่มือเริ่มต้นใช้งานเร็ว

## 🚀 การติดตั้งแบบเร็ว (Quick Installation)

### ขั้นตอนที่ 1: ดาวน์โหลดและรัน Script

```bash
# สำหรับ Ubuntu/Debian
wget -O install.sh https://raw.githubusercontent.com/your-org/smart-notification-system/main/install.sh
chmod +x install.sh
sudo ./install.sh

# หรือใช้ curl
curl -fsSL https://raw.githubusercontent.com/your-org/smart-notification-system/main/install.sh | sudo bash
```

### ขั้นตอนที่ 2: เริ่มต้นการตั้งค่า

หลังจากติดตั้งเสร็จ ระบบจะแสดงข้อมูลการเข้าถึง:

```
===============================================
    Smart Notification System Installation
===============================================

Access URLs:
- Web Interface: http://your-server-ip
- RabbitMQ Management: http://your-server-ip:15672

Default Credentials:
- Web Login: admin@company.local
- RabbitMQ: smart_notification

Important Files:
- Passwords: /root/smart-notification-passwords.txt
- Environment: /var/www/smart-notification-system/.env
```

### ขั้นตอนที่ 3: การตั้งค่าเบื้องต้น

1. **แก้ไขไฟล์ .env:**
```bash
sudo nano /var/www/smart-notification-system/.env
```

2. **ตั้งค่า LDAP (จำเป็น):**
```env
LDAP_HOST=ldap.company.com
LDAP_USERNAME="cn=admin,dc=company,dc=com"
LDAP_PASSWORD=your_ldap_password
LDAP_BASE_DN="dc=company,dc=com"
```

3. **ตั้งค่า Email:**
```env
MAIL_HOST=smtp.company.com
MAIL_PORT=587
MAIL_USERNAME=noreply@company.com
MAIL_PASSWORD=your_email_password
```

4. **ตั้งค่า Microsoft Teams:**
```env
TEAMS_CLIENT_ID=your-teams-client-id
TEAMS_CLIENT_SECRET=your-teams-client-secret
TEAMS_TENANT_ID=your-tenant-id
```

### ขั้นตอนที่ 4: ทดสอบระบบ

```bash
# ทดสอบการเชื่อมต่อ LDAP
cd /var/www/smart-notification-system
sudo -u www-data php artisan ldap:test

# ทดสอบ Queue System
sudo -u www-data php artisan queue:work --once

# ตรวจสอบสถานะ Services
sudo /usr/local/bin/smart-notification-system-status.sh
```

## 📋 Configuration Templates

### 1. LDAP Configuration Template
```env
# Active Directory
LDAP_HOST=ad.company.com
LDAP_USERNAME="CN=Service Account,OU=Service Accounts,DC=company,DC=com"
LDAP_PASSWORD=service_account_password
LDAP_BASE_DN="DC=company,DC=com"
LDAP_PORT=389
LDAP_SSL=false
LDAP_TLS=true

# OpenLDAP
LDAP_HOST=ldap.company.com
LDAP_USERNAME="cn=admin,dc=company,dc=com"
LDAP_PASSWORD=admin_password
LDAP_BASE_DN="dc=company,dc=com"
LDAP_PORT=389
LDAP_SSL=false
LDAP_TLS=false
```

### 2. Email Configuration Templates

#### Gmail SMTP
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

#### Office 365
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=your-email@company.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

#### Custom SMTP
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.company.com
MAIL_PORT=587
MAIL_USERNAME=noreply@company.com
MAIL_PASSWORD=smtp-password
MAIL_ENCRYPTION=tls
```

### 3. Microsoft Teams Configuration

#### การสร้าง Teams App
1. ไปที่ [Azure Portal](https://portal.azure.com)
2. เลือก "Azure Active Directory" > "App registrations"
3. คลิก "New registration"
4. กรอกข้อมูล:
   - Name: Smart Notification System
   - Supported account types: Single tenant
   - Redirect URI: http://your-domain/auth/teams/callback
5. หลังจากสร้างแล้ว คัดลอก:
   - Application (client) ID → TEAMS_CLIENT_ID
   - Directory (tenant) ID → TEAMS_TENANT_ID
6. ไปที่ "Certificates & secrets" สร้าง client secret → TEAMS_CLIENT_SECRET
7. ไปที่ "API permissions" เพิ่ม permissions:
   - Microsoft Graph > Application permissions:
     - ChannelMessage.Send
     - Chat.ReadWrite.All
     - User.Read.All

## 🛠️ Manual Configuration Steps

### 1. การตั้งค่า PostgreSQL (Manual)

```bash
# สร้าง database และ user
sudo -u postgres psql
```
```sql
CREATE USER smart_notification WITH PASSWORD 'secure_password';
CREATE DATABASE smart_notification OWNER smart_notification;
GRANT ALL PRIVILEGES ON DATABASE smart_notification TO smart_notification;
\q
```

### 2. การตั้งค่า RabbitMQ (Manual)

```bash
# เปิดใช้งาน management plugin
sudo rabbitmq-plugins enable rabbitmq_management

# สร้าง user
sudo rabbitmqctl add_user smart_notification rabbit_password
sudo rabbitmqctl set_user_tags smart_notification administrator
sudo rabbitmqctl set_permissions -p / smart_notification ".*" ".*" ".*"

# ลบ guest user (recommended for production)
sudo rabbitmqctl delete_user guest
```

### 3. การตั้งค่า Redis (Manual)

```bash
# แก้ไขไฟล์ config
sudo nano /etc/redis/redis.conf
```
```
# เพิ่มบรรทัดนี้
requirepass your_redis_password

# สำหรับ production
bind 127.0.0.1
protected-mode yes
```

### 4. การตั้งค่า PHP-FPM (Manual)

```bash
# แก้ไขไฟล์ pool configuration
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```
```ini
; เพิ่มหรือแก้ไข
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Security
php_admin_value[disable_functions] = exec,passthru,shell_exec,system
php_admin_flag[allow_url_fopen] = off
```

### 5. การตั้งค่า Nginx (Manual)

```bash
# สร้างไฟล์ configuration
sudo nano /etc/nginx/sites-available/smart-notification
```
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/smart-notification-system/public;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Hide server version
    server_tokens off;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Buffer settings
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static file caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header X-Content-Type-Options "nosniff" always;
    }
}
```

## 🔧 Troubleshooting Common Issues

### 1. LDAP Connection Issues

```bash
# ทดสอบการเชื่อมต่อ LDAP
ldapsearch -x -H ldap://your-ldap-server -D "cn=admin,dc=company,dc=com" -W -b "dc=company,dc=com"

# ตรวจสอบ PHP LDAP extension
php -m | grep ldap

# ดู LDAP logs
tail -f /var/www/smart-notification-system/storage/logs/laravel.log | grep LDAP
```

### 2. Queue Worker Issues

```bash
# ตรวจสอบสถานะ worker
systemctl status smart-notification-system-worker

# ดู logs ของ worker
journalctl -u smart-notification-system-worker -f

# Restart worker
sudo systemctl restart smart-notification-system-worker

# ตรวจสอบ failed jobs
cd /var/www/smart-notification-system
sudo -u www-data php artisan queue:failed
```

### 3. Database Connection Issues

```bash
# ทดสอบการเชื่อมต่อ database
sudo -u www-data php artisan tinker
# ใน tinker: DB::connection()->getPdo();

# ตรวจสอบ PostgreSQL status