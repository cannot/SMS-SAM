# README.md
# Smart Notification System

ระบบแจ้งเตือนอัตโนมัติที่พัฒนาด้วย Laravel สำหรับองค์กร รองรับการส่งการแจ้งเตือนผ่าน Microsoft Teams และ Email พร้อมระบบ API Gateway สำหรับการเชื่อมต่อกับระบบภายนอก

## ✨ Features

### 🔐 การจัดการผู้ใช้
- เชื่อมต่อกับระบบ LDAP สำหรับ Authentication
- ระบบ Role-based Access Control
- การจัดการ User Preferences
- การซิงค์ข้อมูลผู้ใช้อัตโนมัติ

### 📧 ระบบการแจ้งเตือน
- ส่งการแจ้งเตือนผ่าน Microsoft Teams และ Email
- รองรับ Notification Templates
- ระบบ Scheduling และ Priority Queue
- การติดตาม Delivery Status

### 👥 การจัดการกลุ่มผู้รับ
- สร้างกลุ่มแบบ Static, Dynamic และ LDAP-based
- การกรองผู้รับตามเงื่อนไข
- การจัดการสมาชิกกลุ่มอัตโนมัติ

### 🔌 API Gateway
- RESTful API สำหรับระบบภายนอก
- ระบบ API Key Authentication
- Rate Limiting และ Usage Tracking
- การจัดการสิทธิการเข้าถึง

### 📊 รายงานและสถิติ
- รายงานการส่งข้อความ
- สถิติการใช้งาน API
- การติดตาม System Performance
- Export รายงานเป็น Excel/PDF

## 🏗️ สถาปัตยกรรมระบบ

```
┌─────────────────┐   ┌──────────────────┐   ┌─────────────────┐
│   Web Browser   │──▶│   Laravel App    │──▶│   PostgreSQL    │
└─────────────────┘   └──────────────────┘   └─────────────────┘
                              │
                      ┌───────┴───────┐
┌─────────────────┐   │   RabbitMQ    │
│ External Systems│──▶│               │
│   (API Calls)   │   └───────┬───────┘
└─────────────────┘           │
                      ┌───────┼───────┐
                      │       │       │
              ┌───────▼─┐  ┌──▼───┐  ┌─▼────┐
              │MS Teams │  │Email │  │ LDAP │
              │   API   │  │Server│  │Server│
              └─────────┘  └──────┘  └──────┘
```
## 🛠️ เทคโนโลยีที่ใช้

### Backend
- **PHP 8.2+** - Programming Language
- **Laravel 10** - Web Framework
- **PostgreSQL 13+** - Primary Database
- **RabbitMQ 3.8+** - Message Queue
- **Redis** - Caching & Session Storage

### Authentication & Integration
- **LDAP** - User Authentication
- **Microsoft Graph API** - Teams Integration
- **SMTP** - Email Delivery
- **JWT** - Token Authentication

### Frontend
- **Bootstrap 5** - UI Framework
- **jQuery** - JavaScript Library
- **DataTables** - Data Grid

## 🚀 การติดตั้งและใช้งาน

### ข้อกำหนดของระบบ
- Docker & Docker Compose
- หรือ PHP 8.2+, PostgreSQL, RabbitMQ, Redis

### 1. Clone Repository
```bash
git clone https://github.com/your-org/smart-notification-system.git
cd smart-notification-system
```

### 2. ติดตั้งด้วย Docker (แนะนำ)
```bash
# Copy environment file
cp .env.example .env

# สร้าง containers
docker-compose up -d

# เข้าไปใน container
docker-compose exec app bash

# ติดตั้ง dependencies
composer install

# Generate application key
php artisan key:generate

# รัน migrations และ seeders
php artisan migrate --seed

# Generate JWT secret
php artisan jwt:secret

# สร้าง storage links
php artisan storage:link
```

### 3. การตั้งค่า Environment Variables

แก้ไขไฟล์ `.env` ตามค่าที่เหมาะสม:

```bash
# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=smart_notification
DB_USERNAME=postgres
DB_PASSWORD=password

# RabbitMQ Configuration
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
QUEUE_CONNECTION=rabbitmq

# LDAP Configuration
LDAP_HOST=your-ldap-server.com
LDAP_USERNAME="cn=admin,dc=company,dc=com"
LDAP_PASSWORD=your-ldap-password
LDAP_BASE_DN="dc=company,dc=com"

# Microsoft Teams Configuration
TEAMS_CLIENT_ID=your-teams-client-id
TEAMS_CLIENT_SECRET=your-teams-client-secret
TEAMS_TENANT_ID=your-tenant-id

# Email Configuration
MAIL_HOST=your-smtp-server.com
MAIL_PORT=587
MAIL_USERNAME=your-email@company.com
MAIL_PASSWORD=your-email-password
```

### 4. เข้าถึงระบบ
- **Web Interface**: http://localhost:8000
- **RabbitMQ Management**: http://localhost:15672 (guest/guest)
- **Default Login**: admin/password (ผ่าน LDAP)

## 📝 การใช้งาน API

### Authentication
ใช้ API Key ในการ Authentication:
```bash
curl -H "X-API-Key: your-api-key" \
     -H "Content-Type: application/json" \
     http://localhost:8000/api/v1/notifications/send
```

### ส่งการแจ้งเตือน
```bash
curl -X POST \
  http://localhost:8000/api/v1/notifications/send \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "subject": "ทดสอบการแจ้งเตือน",
    "message": "นี่คือข้อความทดสอบ",
    "recipients": ["user@company.com"],
    "channels": ["email", "teams"],
    "priority": "normal"
  }'
```

### ตรวจสอบสถานะ
```bash
curl -H "X-API-Key: your-api-key" \
     http://localhost:8000/api/v1/notifications/{notification-id}/status
```

### API Endpoints หลัก

#### Notification APIs
- `POST /api/v1/notifications/send` - ส่งการแจ้งเตือน
- `POST /api/v1/notifications/bulk` - ส่งการแจ้งเตือนหลายรายการ
- `GET /api/v1/notifications/{id}/status` - ตรวจสอบสถานะ
- `GET /api/v1/notifications/history` - ประวัติการแจ้งเตือน

#### User APIs
- `GET /api/v1/users` - รายการผู้ใช้
- `GET /api/v1/users/departments` - รายการแผนก

#### Group APIs
- `GET /api/v1/groups` - รายการกลุ่ม
- `POST /api/v1/groups` - สร้างกลุ่มใหม่
- `GET /api/v1/groups/{id}/members` - สมาชิกในกลุ่ม

## 🔧 การจัดการระบบ

### การรัน Queue Workers
```bash
# รัน queue worker
php artisan queue:work rabbitmq --tries=3 --timeout=90

# รัน scheduler (สำหรับ cron jobs)
php artisan schedule:work
```

### การซิงค์ข้อมูล LDAP
```bash
# ซิงค์ผู้ใช้จาก LDAP
php artisan ldap:sync-users

# ซิงค์แบบบังคับ
php artisan ldap:sync-users --force
```

### การสร้าง API Key
```bash
# เข้าสู่ระบบ Web Interface
# ไปที่ Admin > API Keys > Create New
# หรือใช้ API endpoint POST /api/admin/api-keys
```

### การตรวจสอบ Logs
```bash
# Application logs
tail -f storage/logs/laravel.log

# Queue jobs
php artisan queue:monitor

# Failed jobs
php artisan queue:failed
```

## 📊 การติดตาม Monitoring

### Health Check Endpoints
- `GET /health` - สถานะระบบทั่วไป
- `GET /health/database` - สถานะฐานข้อมูล
- `GET /health/queue` - สถานะ Queue
- `GET /health/ldap` - สถานะ LDAP

### Performance Metrics
- Response time monitoring
- Queue processing rates
- API usage statistics
- Database performance

## 🔒 ความปลอดภัย

### การตั้งค่าความปลอดภัย
- HTTPS สำหรับ Production
- API Rate Limiting
- Input Validation
- SQL Injection Protection
- XSS Protection

### การสำรองข้อมูล
```bash
# สำรองฐานข้อมูล
pg_dump smart_notification > backup.sql

# Restore ฐานข้อมูล
psql smart_notification < backup.sql
```

## 🧪 การทดสอบ

### รัน Tests
```bash
# Unit tests
php artisan test

# Feature tests
php artisan test --feature

# Test coverage
php artisan test --coverage
```

### Load Testing
```bash
# ใช้ Apache Bench
ab -n 1000 -c 10 http://localhost:8000/api/v1/notifications/send

# ใช้ Artillery
artillery quick --count 100 --num 10 http://localhost:8000/
```

## 📈 การปรับแต่งประสิทธิภาพ

### Database Optimization
```sql
-- สร้าง indexes สำหรับการค้นหา
CREATE INDEX idx_notifications_status ON notifications(status);
CREATE INDEX idx_notification_logs_status ON notification_logs(status, created_at);
CREATE INDEX idx_users_department ON users(department, is_active);
```

### Queue Optimization
```bash
# เพิ่มจำนวน workers
php artisan queue:work rabbitmq --processes=4

# ใช้ Horizon สำหรับ monitoring
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

### Caching
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

## 🔄 การ Deploy Production

### การเตรียม Production Environment
```bash
# ตั้งค่า environment
APP_ENV=production
APP_DEBUG=false

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force
```

### Docker Production Setup
```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.prod
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    deploy:
      replicas: 3
```

## 🆘 การแก้ไขปัญหา

### ปัญหาที่พบบ่อย

#### 1. LDAP Connection Failed
```bash
# ตรวจสอบการเชื่อมต่อ
php artisan ldap:test

# ตรวจสอบ configuration
php artisan config:show adldap
```

#### 2. Queue Jobs Failed
```bash
# ดู failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

#### 3. Teams API Errors
```bash
# ตรวจสอบ permissions
# ตรวจสอบ access token
# ตรวจสอบ rate limits
```

#### 4. Performance Issues
```bash
# ดู slow queries
tail -f storage/logs/laravel.log | grep "slow"

# Monitor queue
php artisan queue:monitor

# Check memory usage
php artisan tinker
> memory_get_usage(true)
```

## 📚 การพัฒนาเพิ่มเติม

### การเพิ่ม Features ใหม่
1. สร้าง Migration สำหรับ Database changes
2. สร้าง Model และ Relationships
3. สร้าง Controller และ API endpoints
4. เพิ่ม Tests
5. อัพเดท Documentation

### การเพิ่ม Notification Channels
```php
// สร้าง Service ใหม่
php artisan make:service SlackService

// สร้าง Job ใหม่
php artisan make:job SendSlackNotification

// เพิ่มใน NotificationService
```

## 🤝 การมีส่วนร่วม

### การ Contribute
1. Fork repository
2. สร้าง feature branch
3. เขียน tests
4. Submit pull request

### Coding Standards
- ใช้ PSR-12 coding standard
- เขียน tests สำหรับ features ใหม่
- เขียน documentation
- ใช้ meaningful commit messages

## 📞 การสนับสนุน

### ติดต่อ
- **Email**: support@company.com
- **Teams**: IT Support Channel
- **Documentation**: https://docs.company.com/smart-notification

### การรายงานปัญหา
1. ตรวจสอบ logs ก่อน
2. Reproduce ปัญหา
3. รวบรวมข้อมูล error messages
4. สร้าง issue ใน repository

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Credits

- Laravel Framework
- Microsoft Graph API
- RabbitMQ
- PostgreSQL
- Bootstrap

---

© 2025 Smart Notification System. All rights reserved.