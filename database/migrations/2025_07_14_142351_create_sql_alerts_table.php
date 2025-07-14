<?php
// database/migrations/2025_07_14_create_sql_alerts_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, check if users table exists
        if (!Schema::hasTable('users')) {
            throw new \Exception('Users table does not exist. Please run user migrations first.');
        }

        // ===== SQL ALERTS TABLE =====
        Schema::create('sql_alerts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            
            // Database Connection Config
            $table->json('database_config');
            
            // SQL Query
            $table->text('sql_query');
            $table->json('variables')->nullable();
            
            // Email Configuration
            $table->json('email_config');
            $table->json('recipients');
            
            // Schedule Configuration
            $table->json('schedule_config');
            $table->string('schedule_type', 20)->default('manual');
            
            // Export Configuration
            $table->json('export_config')->nullable();
            
            // Status & Execution Info
            $table->string('status', 20)->default('draft');
            $table->timestamp('last_run')->nullable();
            $table->timestamp('next_run')->nullable();
            $table->integer('total_executions')->default(0);
            $table->integer('successful_executions')->default(0);
            $table->timestamp('last_success')->nullable();
            
            // Creator & Timestamps - Use explicit unsignedBigInteger
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            // Add foreign key constraints AFTER table creation
        });

        // Add foreign keys for sql_alerts
        Schema::table('sql_alerts', function (Blueprint $table) {
            $table->foreign('created_by', 'sql_alerts_created_by_fk')
                  ->references('id')->on('users')
                  ->onDelete('restrict');
            
            $table->foreign('updated_by', 'sql_alerts_updated_by_fk')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });

        // ===== SQL ALERT EXECUTIONS TABLE =====
        Schema::create('sql_alert_executions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sql_alert_id');
            
            // Execution Info
            $table->string('status', 20)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time_ms')->nullable();
            
            // Query Results
            $table->integer('rows_returned')->nullable();
            $table->integer('rows_processed')->nullable();
            $table->json('query_results')->nullable();
            
            // Notification Results
            $table->integer('notifications_sent')->default(0);
            $table->integer('notifications_failed')->default(0);
            $table->json('notification_details')->nullable();
            
            // Error Information
            $table->text('error_message')->nullable();
            $table->text('error_details')->nullable();
            $table->string('error_code', 50)->nullable();
            
            // Trigger Information
            $table->string('trigger_type', 20)->default('scheduled');
            $table->unsignedBigInteger('triggered_by')->nullable();
            
            $table->timestamps();
        });

        // Add foreign keys for sql_alert_executions
        Schema::table('sql_alert_executions', function (Blueprint $table) {
            $table->foreign('sql_alert_id', 'sql_alert_executions_alert_id_fk')
                  ->references('id')->on('sql_alerts')
                  ->onDelete('cascade');
            
            $table->foreign('triggered_by', 'sql_alert_executions_triggered_by_fk')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });

        // ===== SQL ALERT RECIPIENTS TABLE =====
        Schema::create('sql_alert_recipients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sql_alert_id');
            $table->unsignedBigInteger('execution_id');
            
            // Recipient Info
            $table->string('recipient_type', 50);
            $table->string('recipient_id', 100)->nullable();
            $table->string('recipient_email', 255);
            $table->string('recipient_name', 255)->nullable();
            
            // Delivery Status
            $table->string('delivery_status', 20)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Content Info
            $table->text('email_content')->nullable();
            $table->string('email_subject', 500)->nullable();
            $table->json('personalized_variables')->nullable();
            
            // Attachment Info
            $table->json('attachments')->nullable();
            $table->integer('attachment_size')->nullable();
            
            $table->timestamps();
        });

        // Add foreign keys for sql_alert_recipients
        Schema::table('sql_alert_recipients', function (Blueprint $table) {
            $table->foreign('sql_alert_id', 'sql_alert_recipients_alert_id_fk')
                  ->references('id')->on('sql_alerts')
                  ->onDelete('cascade');
            
            $table->foreign('execution_id', 'sql_alert_recipients_execution_id_fk')
                  ->references('id')->on('sql_alert_executions')
                  ->onDelete('cascade');
        });

        // ===== SQL ALERT ATTACHMENTS TABLE =====
        Schema::create('sql_alert_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('execution_id');
            
            // File Info
            $table->string('filename', 255);
            $table->string('original_filename', 255);
            $table->string('file_path', 500);
            $table->string('file_type', 50);
            $table->integer('file_size');
            $table->string('mime_type', 100);
            
            // Generation Info
            $table->string('generation_status', 20)->default('pending');
            $table->timestamp('generated_at')->nullable();
            $table->integer('generation_time_ms')->nullable();
            
            // Content Info
            $table->integer('total_rows')->nullable();
            $table->integer('total_columns')->nullable();
            $table->json('column_headers')->nullable();
            
            // Error Info
            $table->text('error_message')->nullable();
            
            $table->timestamps();
        });

        // Add foreign key for sql_alert_attachments
        Schema::table('sql_alert_attachments', function (Blueprint $table) {
            $table->foreign('execution_id', 'sql_alert_attachments_execution_id_fk')
                  ->references('id')->on('sql_alert_executions')
                  ->onDelete('cascade');
        });

        // ===== Add Indexes =====
        $this->addIndexes();

        // ===== Add PostgreSQL Comments =====
        $this->addPostgreSQLComments();

        // ===== Add Check Constraints =====
        $this->addCheckConstraints();
    }

    /**
     * Add indexes for better performance
     */
    private function addIndexes(): void
    {
        Schema::table('sql_alerts', function (Blueprint $table) {
            $table->index(['status', 'next_run'], 'sql_alerts_status_next_run_idx');
            $table->index(['created_by', 'status'], 'sql_alerts_created_by_status_idx');
            $table->index('schedule_type', 'sql_alerts_schedule_type_idx');
            $table->index('name', 'sql_alerts_name_idx');
        });

        Schema::table('sql_alert_executions', function (Blueprint $table) {
            $table->index(['sql_alert_id', 'status'], 'sql_alert_executions_alert_status_idx');
            $table->index(['status', 'started_at'], 'sql_alert_executions_status_started_idx');
            $table->index('trigger_type', 'sql_alert_executions_trigger_type_idx');
            $table->index('created_at', 'sql_alert_executions_created_at_idx');
        });

        Schema::table('sql_alert_recipients', function (Blueprint $table) {
            $table->index(['execution_id', 'delivery_status'], 'sql_alert_recipients_exec_delivery_idx');
            $table->index(['recipient_email', 'sent_at'], 'sql_alert_recipients_email_sent_idx');
            $table->index('delivery_status', 'sql_alert_recipients_delivery_status_idx');
        });

        Schema::table('sql_alert_attachments', function (Blueprint $table) {
            $table->index(['execution_id', 'generation_status'], 'sql_alert_attachments_exec_gen_idx');
            $table->index('file_type', 'sql_alert_attachments_file_type_idx');
        });
    }

    /**
     * Add PostgreSQL comments
     */
    private function addPostgreSQLComments(): void
    {
        // ===== SQL ALERTS TABLE COMMENTS =====
        DB::statement("COMMENT ON TABLE sql_alerts IS 'ตารางการแจ้งเตือนแบบ SQL'");
        DB::statement("COMMENT ON COLUMN sql_alerts.id IS 'รหัสอ้างอิงหลักของการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN sql_alerts.name IS 'ชื่อการแจ้งเตือน SQL'");
        DB::statement("COMMENT ON COLUMN sql_alerts.description IS 'คำอธิบายการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN sql_alerts.database_config IS 'ข้อมูลการเชื่อมต่อฐานข้อมูลในรูปแบบ JSON'");
        DB::statement("COMMENT ON COLUMN sql_alerts.sql_query IS 'SQL Query ที่ใช้ในการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN sql_alerts.variables IS 'ตัวแปรที่ใช้ใน Query และ Template ในรูปแบบ JSON'");
        DB::statement("COMMENT ON COLUMN sql_alerts.email_config IS 'การตั้งค่าอีเมลในรูปแบบ JSON (template, subject, etc.)'");
        DB::statement("COMMENT ON COLUMN sql_alerts.recipients IS 'รายชื่อผู้รับการแจ้งเตือนในรูปแบบ JSON'");
        DB::statement("COMMENT ON COLUMN sql_alerts.schedule_config IS 'การตั้งค่าตารางเวลาการรันในรูปแบบ JSON'");
        DB::statement("COMMENT ON COLUMN sql_alerts.schedule_type IS 'ประเภทการกำหนดเวลา (manual, once, recurring, cron)'");
        DB::statement("COMMENT ON COLUMN sql_alerts.export_config IS 'การตั้งค่าการส่งออกข้อมูลในรูปแบบ JSON'");
        DB::statement("COMMENT ON COLUMN sql_alerts.status IS 'สถานะการแจ้งเตือน (active, inactive, draft, error)'");
        DB::statement("COMMENT ON COLUMN sql_alerts.last_run IS 'เวลาที่รันครั้งล่าสุด'");
        DB::statement("COMMENT ON COLUMN sql_alerts.next_run IS 'เวลาที่จะรันครั้งถัดไป'");
        DB::statement("COMMENT ON COLUMN sql_alerts.total_executions IS 'จำนวนครั้งที่รันทั้งหมด'");
        DB::statement("COMMENT ON COLUMN sql_alerts.successful_executions IS 'จำนวนครั้งที่รันสำเร็จ'");
        DB::statement("COMMENT ON COLUMN sql_alerts.last_success IS 'เวลาที่รันสำเร็จครั้งล่าสุด'");
        DB::statement("COMMENT ON COLUMN sql_alerts.created_by IS 'รหัสผู้ใช้งานที่สร้างการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN sql_alerts.updated_by IS 'รหัสผู้ใช้งานที่แก้ไขล่าสุด'");

        // ===== SQL ALERT EXECUTIONS TABLE COMMENTS =====
        DB::statement("COMMENT ON TABLE sql_alert_executions IS 'ตารางประวัติการรันการแจ้งเตือน SQL'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.id IS 'รหัสอ้างอิงหลักของการรัน'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.sql_alert_id IS 'รหัสอ้างอิงการแจ้งเตือน SQL'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.status IS 'สถานะการรัน (pending, running, success, failed, cancelled)'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.started_at IS 'เวลาที่เริ่มรัน'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.completed_at IS 'เวลาที่รันเสร็จ'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.execution_time_ms IS 'เวลาที่ใช้ในการรัน (มิลลิวินาที)'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.rows_returned IS 'จำนวนแถวที่ได้จาก Query'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.rows_processed IS 'จำนวนแถวที่ประมวลผล'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.query_results IS 'ผลลัพธ์จาก Query (ตัวอย่าง) ในรูปแบบ JSON'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.notifications_sent IS 'จำนวนการแจ้งเตือนที่ส่งสำเร็จ'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.notifications_failed IS 'จำนวนการแจ้งเตือนที่ส่งไม่สำเร็จ'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.notification_details IS 'รายละเอียดการส่งแจ้งเตือนในรูปแบบ JSON'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.error_message IS 'ข้อความข้อผิดพลาด'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.error_details IS 'รายละเอียดข้อผิดพลาด'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.error_code IS 'รหัสข้อผิดพลาด'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.trigger_type IS 'วิธีการเรียกใช้ (manual, scheduled, webhook, api)'");
        DB::statement("COMMENT ON COLUMN sql_alert_executions.triggered_by IS 'รหัสผู้ใช้งานที่เรียกใช้ (สำหรับ manual)'");

        // ===== SQL ALERT RECIPIENTS TABLE COMMENTS =====
        DB::statement("COMMENT ON TABLE sql_alert_recipients IS 'ตารางผู้รับการแจ้งเตือนแต่ละครั้ง'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.id IS 'รหัสอ้างอิงหลักของผู้รับ'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.sql_alert_id IS 'รหัสอ้างอิงการแจ้งเตือน SQL'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.execution_id IS 'รหัสอ้างอิงการรัน'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.recipient_type IS 'ประเภทผู้รับ (user, group, email)'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.recipient_id IS 'รหัสผู้รับ (user_id, group_id)'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.recipient_email IS 'อีเมลผู้รับ'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.recipient_name IS 'ชื่อผู้รับ'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.delivery_status IS 'สถานะการส่ง (pending, sent, failed, bounced)'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.sent_at IS 'เวลาที่ส่งสำเร็จ'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.failure_reason IS 'เหตุผลที่ส่งไม่สำเร็จ'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.email_content IS 'เนื้อหาอีเมลที่ส่ง'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.email_subject IS 'หัวข้ออีเมล'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.personalized_variables IS 'ตัวแปรที่ปรับแต่งเฉพาะบุคคลในรูปแบบ JSON'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.attachments IS 'ไฟล์แนบในรูปแบบ JSON'");
        DB::statement("COMMENT ON COLUMN sql_alert_recipients.attachment_size IS 'ขนาดไฟล์แนบ (bytes)'");

        // ===== SQL ALERT ATTACHMENTS TABLE COMMENTS =====
        DB::statement("COMMENT ON TABLE sql_alert_attachments IS 'ตารางไฟล์แนบของการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.id IS 'รหัสอ้างอิงหลักของไฟล์แนบ'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.execution_id IS 'รหัสอ้างอิงการรัน'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.filename IS 'ชื่อไฟล์'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.original_filename IS 'ชื่อไฟล์ต้นฉบับ'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.file_path IS 'เส้นทางไฟล์'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.file_type IS 'ประเภทไฟล์'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.file_size IS 'ขนาดไฟล์ (bytes)'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.mime_type IS 'MIME Type ของไฟล์'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.generation_status IS 'สถานะการสร้างไฟล์ (pending, generating, completed, failed)'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.generated_at IS 'เวลาที่สร้างไฟล์เสร็จ'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.generation_time_ms IS 'เวลาที่ใช้ในการสร้างไฟล์ (มิลลิวินาที)'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.total_rows IS 'จำนวนแถวในไฟล์'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.total_columns IS 'จำนวนคอลัมน์'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.column_headers IS 'หัวคอลัมน์ในรูปแบบ JSON'");
        DB::statement("COMMENT ON COLUMN sql_alert_attachments.error_message IS 'ข้อความข้อผิดพลาด'");
    }

    /**
     * Add check constraints for enum-like behavior
     */
    private function addCheckConstraints(): void
    {
        // sql_alerts constraints
        DB::statement("ALTER TABLE sql_alerts ADD CONSTRAINT sql_alerts_schedule_type_check CHECK (schedule_type IN ('manual', 'once', 'recurring', 'cron'))");
        DB::statement("ALTER TABLE sql_alerts ADD CONSTRAINT sql_alerts_status_check CHECK (status IN ('active', 'inactive', 'draft', 'error'))");
        
        // sql_alert_executions constraints
        DB::statement("ALTER TABLE sql_alert_executions ADD CONSTRAINT sql_alert_executions_status_check CHECK (status IN ('pending', 'running', 'success', 'failed', 'cancelled'))");
        DB::statement("ALTER TABLE sql_alert_executions ADD CONSTRAINT sql_alert_executions_trigger_type_check CHECK (trigger_type IN ('manual', 'scheduled', 'webhook', 'api'))");
        
        // sql_alert_recipients constraints
        DB::statement("ALTER TABLE sql_alert_recipients ADD CONSTRAINT sql_alert_recipients_delivery_status_check CHECK (delivery_status IN ('pending', 'sent', 'failed', 'bounced'))");
        
        // sql_alert_attachments constraints
        DB::statement("ALTER TABLE sql_alert_attachments ADD CONSTRAINT sql_alert_attachments_generation_status_check CHECK (generation_status IN ('pending', 'generating', 'completed', 'failed'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sql_alert_attachments');
        Schema::dropIfExists('sql_alert_recipients');
        Schema::dropIfExists('sql_alert_executions');
        Schema::dropIfExists('sql_alerts');
    }
};

/*
=== Quick Fix Commands ===

1. รัน Migration นี้:
php artisan migrate

2. ถ้ายังมีปัญหา ให้ลอง rollback และรันใหม่:
php artisan migrate:rollback
php artisan migrate

3. ตรวจสอบ users table:
php artisan tinker
>>> Schema::hasTable('users');
>>> DB::select('SELECT id FROM users LIMIT 1');

4. ถ้า users table ไม่มี ให้รันก่อน:
php artisan migrate --path=database/migrations/xxxx_create_users_table.php

5. ตรวจสอบ Migration ทั้งหมด:
php artisan migrate:status

=== การแก้ไขหลัก ===

1. ใช้ unsignedBigInteger() แทน foreignId() เพื่อหลีกเลี่ยงปัญหา
2. สร้าง foreign key แยกต่างหาก หลังจากสร้างตารางแล้ว
3. ใช้ชื่อ constraint ที่ชัดเจน
4. เพิ่ม length สำหรับ string fields
5. ใช้ text แทน longText สำหรับ PostgreSQL

=== ถ้ายังมีปัญหา ===

ลองแยก Migration เป็น 4 ไฟล์:
1. สร้างตาราง sql_alerts
2. สร้างตาราง sql_alert_executions  
3. สร้างตาราง sql_alert_recipients
4. สร้างตาราง sql_alert_attachments

แล้วรันทีละไฟล์เพื่อหาจุดที่เกิดปัญหา
*/