<?php

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
        // ===== USERS TABLE =====
        DB::statement("COMMENT ON TABLE users IS 'ตารางข้อมูลผู้ใช้งานระบบ'");
        DB::statement("COMMENT ON COLUMN users.id IS 'รหัสอ้างอิงหลักของผู้ใช้งาน'");
        DB::statement("COMMENT ON COLUMN users.ldap_guid IS 'รหัส Global Unique Identifier สำหรับการเชื่อมต่อกับ Active Directory'");
        DB::statement("COMMENT ON COLUMN users.username IS 'ชื่อผู้ใช้งานสำหรับเข้าสู่ระบบ'");
        DB::statement("COMMENT ON COLUMN users.email IS 'ที่อยู่อีเมลผู้ใช้งาน'");
        DB::statement("COMMENT ON COLUMN users.password IS 'รหัสผ่านที่เข้ารหัสแล้ว (Hash)'");
        DB::statement("COMMENT ON COLUMN users.first_name IS 'ชื่อจริงของผู้ใช้งาน'");
        DB::statement("COMMENT ON COLUMN users.last_name IS 'นามสกุลของผู้ใช้งาน'");
        DB::statement("COMMENT ON COLUMN users.display_name IS 'ชื่อที่แสดงในระบบ'");
        DB::statement("COMMENT ON COLUMN users.department IS 'หน่วยงานหรือแผนกที่สังกัด'");
        DB::statement("COMMENT ON COLUMN users.title IS 'ตำแหน่งงานหรือหน้าที่'");
        DB::statement("COMMENT ON COLUMN users.phone IS 'หมายเลขโทรศัพท์ติดต่อ'");
        DB::statement("COMMENT ON COLUMN users.is_active IS 'สถานะการใช้งานบัญชี (true=เปิดใช้งาน, false=ปิดใช้งาน)'");
        DB::statement("COMMENT ON COLUMN users.last_login_at IS 'วันที่และเวลาที่เข้าสู่ระบบครั้งล่าสุด'");
        DB::statement("COMMENT ON COLUMN users.ldap_synced_at IS 'วันที่และเวลาที่ซิงโครไนซ์ข้อมูลจาก Active Directory ครั้งล่าสุด'");
        DB::statement("COMMENT ON COLUMN users.remember_token IS 'โทเคนสำหรับจดจำการเข้าสู่ระบบ (Remember Me)'");
        DB::statement("COMMENT ON COLUMN users.created_at IS 'วันที่และเวลาที่สร้างบัญชีผู้ใช้งาน'");
        DB::statement("COMMENT ON COLUMN users.updated_at IS 'วันที่และเวลาที่อัปเดตข้อมูลล่าสุด'");

        // ===== NOTIFICATIONS TABLE =====
        DB::statement("COMMENT ON TABLE notifications IS 'ตารางข้อมูลการแจ้งเตือนทั้งหมด'");
        DB::statement("COMMENT ON COLUMN notifications.id IS 'รหัสอ้างอิงหลักของการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notifications.uuid IS 'รหัส UUID สำหรับการอ้างอิงภายนอกระบบ'");
        DB::statement("COMMENT ON COLUMN notifications.template_id IS 'รหัสอ้างอิงเทมเพลตที่ใช้ในการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notifications.subject IS 'หัวข้อหรือชื่อเรื่องของการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notifications.body_html IS 'เนื้อหาการแจ้งเตือนในรูปแบบ HTML'");
        DB::statement("COMMENT ON COLUMN notifications.body_text IS 'เนื้อหาการแจ้งเตือนในรูปแบบข้อความธรรมดา'");
        DB::statement("COMMENT ON COLUMN notifications.channels IS 'ช่องทางการส่งการแจ้งเตือน (email, teams, webhook)'");
        DB::statement("COMMENT ON COLUMN notifications.recipients IS 'รายชื่อผู้รับการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notifications.recipient_groups IS 'กลุ่มผู้รับการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notifications.variables IS 'ตัวแปรและค่าที่ใช้ในเทมเพลต'");
        DB::statement("COMMENT ON COLUMN notifications.webhook_url IS 'URL สำหรับการส่งข้อมูลผ่าน Webhook'");
        DB::statement("COMMENT ON COLUMN notifications.priority IS 'ระดับความสำคัญของการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notifications.status IS 'สถานะปัจจุบันของการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notifications.scheduled_at IS 'วันที่และเวลาที่กำหนดให้ส่งการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notifications.sent_at IS 'วันที่และเวลาที่ส่งการแจ้งเตือนสำเร็จ'");
        DB::statement("COMMENT ON COLUMN notifications.total_recipients IS 'จำนวนผู้รับการแจ้งเตือนทั้งหมด'");
        DB::statement("COMMENT ON COLUMN notifications.delivered_count IS 'จำนวนผู้รับที่ได้รับการแจ้งเตือนสำเร็จ'");
        DB::statement("COMMENT ON COLUMN notifications.failed_count IS 'จำนวนผู้รับที่ไม่สามารถส่งการแจ้งเตือนได้'");
        DB::statement("COMMENT ON COLUMN notifications.failure_reason IS 'สาเหตุหรือรายละเอียดของความล้มเหลวในการส่ง'");
        DB::statement("COMMENT ON COLUMN notifications.api_key_id IS 'รหัสอ้างอิง API Key ที่ใช้ในการส่งการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notifications.created_by IS 'รหัสผู้ใช้งานที่สร้างการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notifications.created_at IS 'วันที่และเวลาที่สร้างการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notifications.updated_at IS 'วันที่และเวลาที่อัปเดตข้อมูลล่าสุด'");

        // ===== NOTIFICATION_TEMPLATES TABLE =====
        DB::statement("COMMENT ON TABLE notification_templates IS 'ตารางเทมเพลตสำหรับการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notification_templates.id IS 'รหัสอ้างอิงหลักของเทมเพลต'");
        DB::statement("COMMENT ON COLUMN notification_templates.name IS 'ชื่อเทมเพลตการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notification_templates.description IS 'คำอธิบายและรายละเอียดของเทมเพลต'");
        DB::statement("COMMENT ON COLUMN notification_templates.slug IS 'รหัสสำหรับการอ้างอิงแบบ URL-friendly'");
        DB::statement("COMMENT ON COLUMN notification_templates.category IS 'หมวดหมู่หรือประเภทของเทมเพลต'");
        DB::statement("COMMENT ON COLUMN notification_templates.subject_template IS 'เทมเพลตสำหรับหัวข้อการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notification_templates.body_html_template IS 'เทมเพลตเนื้อหาในรูปแบบ HTML'");
        DB::statement("COMMENT ON COLUMN notification_templates.body_text_template IS 'เทมเพลตเนื้อหาในรูปแบบข้อความธรรมดา'");
        DB::statement("COMMENT ON COLUMN notification_templates.variables IS 'รายการตัวแปรที่จำเป็นสำหรับเทมเพลต'");
        DB::statement("COMMENT ON COLUMN notification_templates.default_variables IS 'ค่าเริ่มต้นของตัวแปรต่างๆ'");
        DB::statement("COMMENT ON COLUMN notification_templates.supported_channels IS 'ช่องทางการส่งที่รองรับ'");
        DB::statement("COMMENT ON COLUMN notification_templates.priority IS 'ระดับความสำคัญเริ่มต้น'");
        DB::statement("COMMENT ON COLUMN notification_templates.is_active IS 'สถานะการใช้งานเทมเพลต'");
        DB::statement("COMMENT ON COLUMN notification_templates.version IS 'หมายเลขเวอร์ชันของเทมเพลต'");
        DB::statement("COMMENT ON COLUMN notification_templates.created_by IS 'รหัสผู้ใช้งานที่สร้างเทมเพลต'");
        DB::statement("COMMENT ON COLUMN notification_templates.updated_by IS 'รหัสผู้ใช้งานที่อัปเดตเทมเพลตล่าสุด'");
        DB::statement("COMMENT ON COLUMN notification_templates.created_at IS 'วันที่และเวลาที่สร้างเทมเพลต'");
        DB::statement("COMMENT ON COLUMN notification_templates.updated_at IS 'วันที่และเวลาที่อัปเดตเทมเพลตล่าสุด'");

        // ===== NOTIFICATION_GROUPS TABLE =====
        DB::statement("COMMENT ON TABLE notification_groups IS 'ตารางกลุ่มผู้รับการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notification_groups.id IS 'รหัสอ้างอิงหลักของกลุ่ม'");
        DB::statement("COMMENT ON COLUMN notification_groups.name IS 'ชื่อกลุ่มผู้รับการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notification_groups.description IS 'คำอธิบายและรายละเอียดของกลุ่ม'");
        DB::statement("COMMENT ON COLUMN notification_groups.type IS 'ประเภทการจัดกลุ่มผู้รับ'");
        DB::statement("COMMENT ON COLUMN notification_groups.ldap_filter IS 'เงื่อนไขการกรองสำหรับกลุ่ม Active Directory'");
        DB::statement("COMMENT ON COLUMN notification_groups.criteria IS 'เงื่อนไขสำหรับการจัดกลุ่มแบบไดนามิก'");
        DB::statement("COMMENT ON COLUMN notification_groups.is_active IS 'สถานะการใช้งานกลุ่ม'");
        DB::statement("COMMENT ON COLUMN notification_groups.created_by IS 'รหัสผู้ใช้งานที่สร้างกลุ่ม'");
        DB::statement("COMMENT ON COLUMN notification_groups.created_at IS 'วันที่และเวลาที่สร้างกลุ่ม'");
        DB::statement("COMMENT ON COLUMN notification_groups.updated_at IS 'วันที่และเวลาที่อัปเดตกลุ่มล่าสุด'");

        // ===== NOTIFICATION_LOGS TABLE =====
        DB::statement("COMMENT ON TABLE notification_logs IS 'ตารางบันทึกประวัติการส่งการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notification_logs.id IS 'รหัสอ้างอิงหลักของบันทึก'");
        DB::statement("COMMENT ON COLUMN notification_logs.notification_id IS 'รหัสอ้างอิงการแจ้งเตือนที่เกี่ยวข้อง'");
        DB::statement("COMMENT ON COLUMN notification_logs.recipient_email IS 'ที่อยู่อีเมลของผู้รับ'");
        DB::statement("COMMENT ON COLUMN notification_logs.recipient_name IS 'ชื่อของผู้รับการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notification_logs.channel IS 'ช่องทางที่ใช้ในการส่งการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notification_logs.status IS 'สถานะการส่งการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notification_logs.response_data IS 'ข้อมูลการตอบกลับจากระบบภายนอก'");
        DB::statement("COMMENT ON COLUMN notification_logs.error_message IS 'ข้อความแสดงข้อผิดพลาด (หากมี)'");
        DB::statement("COMMENT ON COLUMN notification_logs.sent_at IS 'วันที่และเวลาที่ส่งการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notification_logs.delivered_at IS 'วันที่และเวลาที่ยืนยันการส่งสำเร็จ'");
        DB::statement("COMMENT ON COLUMN notification_logs.retry_count IS 'จำนวนครั้งที่พยายามส่งซ้ำ'");
        DB::statement("COMMENT ON COLUMN notification_logs.next_retry_at IS 'วันที่และเวลาที่จะพยายามส่งครั้งถัดไป'");
        DB::statement("COMMENT ON COLUMN notification_logs.created_at IS 'วันที่และเวลาที่สร้างบันทึก'");
        DB::statement("COMMENT ON COLUMN notification_logs.updated_at IS 'วันที่และเวลาที่อัปเดตบันทึกล่าสุด'");

        // ===== API_KEYS TABLE =====
        DB::statement("COMMENT ON TABLE api_keys IS 'ตารางข้อมูลรหัส API สำหรับการเข้าถึงระบบ'");
        DB::statement("COMMENT ON COLUMN api_keys.id IS 'รหัสอ้างอิงหลักของ API Key'");
        DB::statement("COMMENT ON COLUMN api_keys.uuid IS 'รหัส UUID สำหรับการอ้างอิงภายนอก'");
        DB::statement("COMMENT ON COLUMN api_keys.name IS 'ชื่อหรือคำอธิบายของ API Key'");
        DB::statement("COMMENT ON COLUMN api_keys.description IS 'รายละเอียดและวัตถุประสงค์การใช้งาน'");
        DB::statement("COMMENT ON COLUMN api_keys.key_hash IS 'รหัส API ที่เข้ารหัสแล้ว (Hash)'");
        DB::statement("COMMENT ON COLUMN api_keys.key_value IS 'ค่ารหัส API (แสดงครั้งเดียวเมื่อสร้างใหม่)'");
        DB::statement("COMMENT ON COLUMN api_keys.is_active IS 'สถานะการใช้งาน API Key'");
        DB::statement("COMMENT ON COLUMN api_keys.rate_limit_per_minute IS 'จำกัดจำนวนการเรียกใช้ต่อนาที'");
        DB::statement("COMMENT ON COLUMN api_keys.rate_limit_per_hour IS 'จำกัดจำนวนการเรียกใช้ต่อชั่วโมง'");
        DB::statement("COMMENT ON COLUMN api_keys.rate_limit_per_day IS 'จำกัดจำนวนการเรียกใช้ต่อวัน'");
        DB::statement("COMMENT ON COLUMN api_keys.usage_count IS 'จำนวนครั้งการใช้งานทั้งหมด'");
        DB::statement("COMMENT ON COLUMN api_keys.last_used_at IS 'วันที่และเวลาที่ใช้งานล่าสุด'");
        DB::statement("COMMENT ON COLUMN api_keys.expires_at IS 'วันที่และเวลาที่หมดอายุ'");
        DB::statement("COMMENT ON COLUMN api_keys.permissions IS 'ข้อมูลสิทธิ์การใช้งาน (เลิกใช้แล้ว)'");
        DB::statement("COMMENT ON COLUMN api_keys.allowed_ips IS 'รายการ IP Address ที่อนุญาตให้ใช้งาน'");
        DB::statement("COMMENT ON COLUMN api_keys.metadata IS 'ข้อมูลเพิ่มเติมต่างๆ'");
        DB::statement("COMMENT ON COLUMN api_keys.assigned_to IS 'รหัสผู้ใช้งานที่ได้รับมอบหมาย'");
        DB::statement("COMMENT ON COLUMN api_keys.created_by IS 'รหัสผู้ใช้งานที่สร้าง API Key'");
        DB::statement("COMMENT ON COLUMN api_keys.auto_notifications IS 'เปิดใช้งานการแจ้งเตือนอัตโนมัติ'");
        DB::statement("COMMENT ON COLUMN api_keys.notification_webhook IS 'URL สำหรับรับการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN api_keys.status_changed_at IS 'วันที่และเวลาที่เปลี่ยนสถานะ'");
        DB::statement("COMMENT ON COLUMN api_keys.status_changed_by IS 'รหัสผู้ใช้งานที่เปลี่ยนสถานะ'");
        DB::statement("COMMENT ON COLUMN api_keys.regenerated_at IS 'วันที่และเวลาที่สร้างรหัสใหม่'");
        DB::statement("COMMENT ON COLUMN api_keys.regenerated_by IS 'รหัสผู้ใช้งานที่สร้างรหัสใหม่'");
        DB::statement("COMMENT ON COLUMN api_keys.usage_reset_at IS 'วันที่และเวลาที่รีเซ็ตการใช้งาน'");
        DB::statement("COMMENT ON COLUMN api_keys.usage_reset_by IS 'รหัสผู้ใช้งานที่รีเซ็ตการใช้งาน'");
        DB::statement("COMMENT ON COLUMN api_keys.created_at IS 'วันที่และเวลาที่สร้าง API Key'");
        DB::statement("COMMENT ON COLUMN api_keys.updated_at IS 'วันที่และเวลาที่อัปเดตข้อมูลล่าสุด'");
        DB::statement("COMMENT ON COLUMN api_keys.deleted_at IS 'วันที่และเวลาที่ลบ API Key (Soft Delete)'");

        // ===== API_USAGE_LOGS TABLE =====
        DB::statement("COMMENT ON TABLE api_usage_logs IS 'ตารางบันทึกการใช้งาน API'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.id IS 'รหัสอ้างอิงหลักของบันทึก'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.api_key_id IS 'รหัสอ้างอิง API Key ที่ใช้งาน'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.notification_id IS 'รหัสอ้างอิงการแจ้งเตือนที่เกี่ยวข้อง'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.endpoint IS 'Endpoint หรือ URL ที่เรียกใช้'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.method IS 'HTTP Method ที่ใช้ (GET, POST, PUT, DELETE)'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.ip_address IS 'ที่อยู่ IP ของผู้เรียกใช้'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.user_agent IS 'ข้อมูล User Agent ของเบราว์เซอร์หรือแอปพลิเคชัน'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.response_code IS 'รหัสสถานะ HTTP Response'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.response_time IS 'เวลาในการตอบกลับ (หน่วยมิลลิวินาที)'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.request_data IS 'ข้อมูลที่ส่งมาในคำขอ'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.response_data IS 'ข้อมูลที่ตอบกลับไป'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.error_message IS 'ข้อความแสดงข้อผิดพลาด (หากมี)'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.request_id IS 'รหัสอ้างอิงคำขอเฉพาะ'");
        DB::statement("COMMENT ON COLUMN api_usage_logs.created_at IS 'วันที่และเวลาที่บันทึกการใช้งาน'");

        // ===== USER_PREFERENCES TABLE =====
        DB::statement("COMMENT ON TABLE user_preferences IS 'ตารางการตั้งค่าส่วนบุคคลของผู้ใช้งาน'");
        DB::statement("COMMENT ON COLUMN user_preferences.id IS 'รหัสอ้างอิงหลักของการตั้งค่า'");
        DB::statement("COMMENT ON COLUMN user_preferences.user_id IS 'รหัสอ้างอิงผู้ใช้งานเจ้าของการตั้งค่า'");
        DB::statement("COMMENT ON COLUMN user_preferences.enable_teams IS 'เปิดใช้งานการแจ้งเตือนผ่าน Microsoft Teams'");
        DB::statement("COMMENT ON COLUMN user_preferences.enable_email IS 'เปิดใช้งานการแจ้งเตือนผ่านอีเมล'");
        DB::statement("COMMENT ON COLUMN user_preferences.enable_sms IS 'เปิดใช้งานการแจ้งเตือนผ่าน SMS'");
        DB::statement("COMMENT ON COLUMN user_preferences.teams_user_id IS 'รหัสผู้ใช้งานใน Microsoft Teams'");
        DB::statement("COMMENT ON COLUMN user_preferences.teams_channel_preference IS 'รูปแบบการส่งการแจ้งเตือนใน Teams'");
        DB::statement("COMMENT ON COLUMN user_preferences.teams_channel_id IS 'รหัส Channel ใน Microsoft Teams'");
        DB::statement("COMMENT ON COLUMN user_preferences.email_address IS 'ที่อยู่อีเมลสำหรับรับการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN user_preferences.email_format IS 'รูปแบบอีเมลที่ต้องการ'");
        DB::statement("COMMENT ON COLUMN user_preferences.sms_number IS 'หมายเลขโทรศัพท์สำหรับรับ SMS'");
        DB::statement("COMMENT ON COLUMN user_preferences.min_priority IS 'ระดับความสำคัญขั้นต่ำที่จะรับการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN user_preferences.enable_quiet_hours IS 'เปิดใช้งานโหมดช่วงเวลาเงียบ'");
        DB::statement("COMMENT ON COLUMN user_preferences.quiet_hours_start IS 'เวลาเริ่มต้นของช่วงเวลาเงียบ'");
        DB::statement("COMMENT ON COLUMN user_preferences.quiet_hours_end IS 'เวลาสิ้นสุดของช่วงเวลาเงียบ'");
        DB::statement("COMMENT ON COLUMN user_preferences.quiet_days IS 'รายการวันที่ต้องการใช้โหมดเงียบ'");
        DB::statement("COMMENT ON COLUMN user_preferences.override_high_priority IS 'อนุญาตให้การแจ้งเตือนความสำคัญสูงข้ามช่วงเวลาเงียบ'");
        DB::statement("COMMENT ON COLUMN user_preferences.enable_grouping IS 'เปิดใช้งานการจัดกลุ่มการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN user_preferences.grouping_method IS 'วิธีการจัดกลุ่มการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN user_preferences.grouping_interval IS 'ช่วงเวลาสำหรับการจัดกลุ่ม (หน่วยนาที)'");
        DB::statement("COMMENT ON COLUMN user_preferences.email_frequency IS 'ความถี่การส่งอีเมลการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN user_preferences.teams_frequency IS 'ความถี่การส่งการแจ้งเตือนผ่าน Teams'");
        DB::statement("COMMENT ON COLUMN user_preferences.language IS 'ภาษาที่ใช้ในการแสดงผล'");
        DB::statement("COMMENT ON COLUMN user_preferences.timezone IS 'เขตเวลาที่ใช้งาน'");
        DB::statement("COMMENT ON COLUMN user_preferences.date_format IS 'รูปแบบการแสดงวันที่'");
        DB::statement("COMMENT ON COLUMN user_preferences.time_format IS 'รูปแบบการแสดงเวลา'");
        DB::statement("COMMENT ON COLUMN user_preferences.enable_read_receipts IS 'เปิดใช้งานการแจ้งเตือนเมื่ออ่านข้อความแล้ว'");
        DB::statement("COMMENT ON COLUMN user_preferences.enable_delivery_reports IS 'เปิดใช้งานรายงานสถานะการส่ง'");
        DB::statement("COMMENT ON COLUMN user_preferences.custom_filters IS 'เงื่อนไขการกรองแบบกำหนดเอง'");
        DB::statement("COMMENT ON COLUMN user_preferences.enable_digest IS 'เปิดใช้งานการส่งสรุปการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN user_preferences.digest_frequency IS 'ความถี่การส่งสรุปการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN user_preferences.digest_time IS 'เวลาที่ต้องการรับสรุปการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN user_preferences.digest_day IS 'วันที่ต้องการรับสรุปการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN user_preferences.created_at IS 'วันที่และเวลาที่สร้างการตั้งค่า'");
        DB::statement("COMMENT ON COLUMN user_preferences.updated_at IS 'วันที่และเวลาที่อัปเดตการตั้งค่าล่าสุด'");

        // ===== NOTIFICATION_GROUP_USERS TABLE =====
        DB::statement("COMMENT ON TABLE notification_group_users IS 'ตารางความสัมพันธ์ระหว่างผู้ใช้งานและกลุ่มการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notification_group_users.id IS 'รหัสอ้างอิงหลักของความสัมพันธ์'");
        DB::statement("COMMENT ON COLUMN notification_group_users.notification_group_id IS 'รหัสอ้างอิงกลุ่มการแจ้งเตือน'");
        DB::statement("COMMENT ON COLUMN notification_group_users.user_id IS 'รหัสอ้างอิงผู้ใช้งาน'");
        DB::statement("COMMENT ON COLUMN notification_group_users.joined_at IS 'วันที่และเวลาที่เข้าร่วมกลุ่ม'");
        DB::statement("COMMENT ON COLUMN notification_group_users.added_by IS 'รหัสผู้ใช้งานที่เพิ่มสมาชิกเข้ากลุ่ม'");
        DB::statement("COMMENT ON COLUMN notification_group_users.created_at IS 'วันที่และเวลาที่สร้างความสัมพันธ์'");
        DB::statement("COMMENT ON COLUMN notification_group_users.updated_at IS 'วันที่และเวลาที่อัปเดตความสัมพันธ์ล่าสุด'");

        // ===== JOBS TABLE =====
        DB::statement("COMMENT ON TABLE jobs IS 'ตารางงานในระบบคิว (Queue Jobs)'");
        DB::statement("COMMENT ON COLUMN jobs.id IS 'รหัสอ้างอิงหลักของงาน'");
        DB::statement("COMMENT ON COLUMN jobs.queue IS 'ชื่อคิวที่งานถูกส่งไป'");
        DB::statement("COMMENT ON COLUMN jobs.payload IS 'ข้อมูลและพารามิเตอร์ของงาน'");
        DB::statement("COMMENT ON COLUMN jobs.attempts IS 'จำนวนครั้งที่พยายามประมวลผลงาน'");
        DB::statement("COMMENT ON COLUMN jobs.reserved_at IS 'เวลาที่งานถูกจองสำหรับการประมวลผล'");
        DB::statement("COMMENT ON COLUMN jobs.available_at IS 'เวลาที่งานพร้อมสำหรับการประมวลผล'");
        DB::statement("COMMENT ON COLUMN jobs.created_at IS 'เวลาที่สร้างงาน'");

        // ===== FAILED_JOBS TABLE =====
        DB::statement("COMMENT ON TABLE failed_jobs IS 'ตารางงานที่ประมวลผลล้มเหลว'");
        DB::statement("COMMENT ON COLUMN failed_jobs.id IS 'รหัสอ้างอิงหลักของงานที่ล้มเหลว'");
        DB::statement("COMMENT ON COLUMN failed_jobs.uuid IS 'รหัส UUID ของงานที่ล้มเหลว'");
        DB::statement("COMMENT ON COLUMN failed_jobs.connection IS 'การเชื่อมต่อฐานข้อมูลที่ใช้'");
        DB::statement("COMMENT ON COLUMN failed_jobs.queue IS 'ชื่อคิวที่งานล้มเหลว'");
        DB::statement("COMMENT ON COLUMN failed_jobs.payload IS 'ข้อมูลและพารามิเตอร์ของงานที่ล้มเหลว'");
        DB::statement("COMMENT ON COLUMN failed_jobs.exception IS 'ข้อความและรายละเอียดข้อผิดพลาด'");
        DB::statement("COMMENT ON COLUMN failed_jobs.failed_at IS 'วันที่และเวลาที่งานล้มเหลว'");

        // ===== CACHE TABLE =====
        DB::statement("COMMENT ON TABLE cache IS 'ตารางเก็บข้อมูลแคชของระบบ'");
        DB::statement("COMMENT ON COLUMN cache.key IS 'คีย์สำหรับการอ้างอิงข้อมูลแคช'");
        DB::statement("COMMENT ON COLUMN cache.value IS 'ค่าข้อมูลที่ถูกแคช'");
        DB::statement("COMMENT ON COLUMN cache.expiration IS 'เวลาหมดอายุของข้อมูลแคช'");

        // ===== CACHE_LOCKS TABLE =====
        DB::statement("COMMENT ON TABLE cache_locks IS 'ตารางการล็อคข้อมูลแคช'");
        DB::statement("COMMENT ON COLUMN cache_locks.key IS 'คีย์ของการล็อค'");
        DB::statement("COMMENT ON COLUMN cache_locks.owner IS 'เจ้าของการล็อค'");
        DB::statement("COMMENT ON COLUMN cache_locks.expiration IS 'เวลาหมดอายุของการล็อค'");

        // ===== ACTIVITY_LOG TABLE =====
        DB::statement("COMMENT ON TABLE activity_log IS 'ตารางบันทึกประวัติกิจกรรมของผู้ใช้งาน'");
        DB::statement("COMMENT ON COLUMN activity_log.id IS 'รหัสอ้างอิงหลักของกิจกรรม'");
        DB::statement("COMMENT ON COLUMN activity_log.log_name IS 'ชื่อหรือประเภทของบันทึกกิจกรรม'");
        DB::statement("COMMENT ON COLUMN activity_log.description IS 'คำอธิบายรายละเอียดของกิจกรรม'");
        DB::statement("COMMENT ON COLUMN activity_log.subject_type IS 'ประเภทของวัตถุที่เกี่ยวข้อง'");
        DB::statement("COMMENT ON COLUMN activity_log.subject_id IS 'รหัสอ้างอิงวัตถุที่เกี่ยวข้อง'");
        DB::statement("COMMENT ON COLUMN activity_log.causer_type IS 'ประเภทของผู้ก่อให้เกิดกิจกรรม'");
        DB::statement("COMMENT ON COLUMN activity_log.causer_id IS 'รหัสอ้างอิงผู้ก่อให้เกิดกิจกรรม'");
        DB::statement("COMMENT ON COLUMN activity_log.properties IS 'คุณสมบัติและข้อมูลเพิ่มเติมของกิจกรรม'");
        DB::statement("COMMENT ON COLUMN activity_log.event IS 'ประเภทของเหตุการณ์ที่เกิดขึ้น'");
        DB::statement("COMMENT ON COLUMN activity_log.batch_uuid IS 'รหัส UUID สำหรับจัดกลุ่มกิจกรรมที่เกิดขึ้นพร้อมกัน'");
        DB::statement("COMMENT ON COLUMN activity_log.created_at IS 'วันที่และเวลาที่บันทึกกิจกรรม'");
        DB::statement("COMMENT ON COLUMN activity_log.updated_at IS 'วันที่และเวลาที่อัปเดตบันทึกกิจกรรมล่าสุด'");

        // ===== PERMISSIONS TABLE =====
        DB::statement("COMMENT ON TABLE permissions IS 'ตารางข้อมูลสิทธิ์การใช้งานระบบ'");
        DB::statement("COMMENT ON COLUMN permissions.id IS 'รหัสอ้างอิงหลักของสิทธิ์'");
        DB::statement("COMMENT ON COLUMN permissions.name IS 'ชื่อของสิทธิ์ในระบบ'");
        DB::statement("COMMENT ON COLUMN permissions.guard_name IS 'ชื่อของ Guard ที่ดูแลสิทธิ์'");
        DB::statement("COMMENT ON COLUMN permissions.display_name IS 'ชื่อแสดงผลสิทธิ์ในภาษาไทย'");
        DB::statement("COMMENT ON COLUMN permissions.description IS 'คำอธิบายรายละเอียดของสิทธิ์'");
        DB::statement("COMMENT ON COLUMN permissions.category IS 'หมวดหมู่หรือกลุ่มของสิทธิ์'");
        DB::statement("COMMENT ON COLUMN permissions.created_at IS 'วันที่และเวลาที่สร้างสิทธิ์'");
        DB::statement("COMMENT ON COLUMN permissions.updated_at IS 'วันที่และเวลาที่อัปเดตสิทธิ์ล่าสุด'");

        // ===== ROLES TABLE =====
        DB::statement("COMMENT ON TABLE roles IS 'ตารางข้อมูลบทบาทผู้ใช้งาน'");
        DB::statement("COMMENT ON COLUMN roles.id IS 'รหัสอ้างอิงหลักของบทบาท'");
        DB::statement("COMMENT ON COLUMN roles.name IS 'ชื่อของบทบาทในระบบ'");
        DB::statement("COMMENT ON COLUMN roles.guard_name IS 'ชื่อของ Guard ที่ดูแลบทบาท'");
        DB::statement("COMMENT ON COLUMN roles.display_name IS 'ชื่อแสดงผลบทบาทในภาษาไทย'");
        DB::statement("COMMENT ON COLUMN roles.description IS 'คำอธิบายรายละเอียดของบทบาท'");
        DB::statement("COMMENT ON COLUMN roles.created_at IS 'วันที่และเวลาที่สร้างบทบาท'");
        DB::statement("COMMENT ON COLUMN roles.updated_at IS 'วันที่และเวลาที่อัปเดตบทบาทล่าสุด'");

        // ===== MODEL_HAS_PERMISSIONS TABLE =====
        DB::statement("COMMENT ON TABLE model_has_permissions IS 'ตารางความสัมพันธ์ระหว่างโมเดลและสิทธิ์'");
        DB::statement("COMMENT ON COLUMN model_has_permissions.permission_id IS 'รหัสอ้างอิงสิทธิ์ที่กำหนด'");
        DB::statement("COMMENT ON COLUMN model_has_permissions.model_type IS 'ประเภทของโมเดลที่รับสิทธิ์'");
        DB::statement("COMMENT ON COLUMN model_has_permissions.model_id IS 'รหัสอ้างอิงโมเดลที่รับสิทธิ์'");

        // ===== MODEL_HAS_ROLES TABLE =====
        DB::statement("COMMENT ON TABLE model_has_roles IS 'ตารางความสัมพันธ์ระหว่างโมเดลและบทบาท'");
        DB::statement("COMMENT ON COLUMN model_has_roles.role_id IS 'รหัสอ้างอิงบทบาทที่กำหนด'");
        DB::statement("COMMENT ON COLUMN model_has_roles.model_type IS 'ประเภทของโมเดลที่รับบทบาท'");
        DB::statement("COMMENT ON COLUMN model_has_roles.model_id IS 'รหัสอ้างอิงโมเดลที่รับบทบาท'");

        // ===== ROLE_HAS_PERMISSIONS TABLE =====
        DB::statement("COMMENT ON TABLE role_has_permissions IS 'ตารางความสัมพันธ์ระหว่างบทบาทและสิทธิ์'");
        DB::statement("COMMENT ON COLUMN role_has_permissions.permission_id IS 'รหัสอ้างอิงสิทธิ์ที่รวมอยู่ในบทบาท'");
        DB::statement("COMMENT ON COLUMN role_has_permissions.role_id IS 'รหัสอ้างอิงบทบาทที่มีสิทธิ์'");

        // USERS TABLE - เพิ่ม comments สำหรับ columns ที่เพิ่มทีหลัง
        if (Schema::hasColumn('users', 'auth_source')) {
            DB::statement("COMMENT ON COLUMN users.auth_source IS 'แหล่งที่มาของการยืนยันตัวตน (ldap, fallback)'");
        }
        if (Schema::hasColumn('users', 'deleted_at')) {
            DB::statement("COMMENT ON COLUMN users.deleted_at IS 'วันที่และเวลาที่ลบผู้ใช้งาน (Soft Delete)'");
        }
        if (Schema::hasColumn('users', 'teams_webhook_url')) {
            DB::statement("COMMENT ON COLUMN users.teams_webhook_url IS 'URL Webhook สำหรับการแจ้งเตือนส่วนบุคคลผ่าน Microsoft Teams'");
        }
        if (Schema::hasColumn('users', 'notification_preferences')) {
            DB::statement("COMMENT ON COLUMN users.notification_preferences IS 'การตั้งค่าการแจ้งเตือนส่วนบุคคลในรูปแบบ JSON'");
        }
        if (Schema::hasColumn('users', 'position')) {
            DB::statement("COMMENT ON COLUMN users.position IS 'ตำแหน่งงานหรือระดับชั้นในองค์กร'");
        }

        // NOTIFICATIONS TABLE - เพิ่ม comments สำหรับ columns ที่เพิ่มทีหลัง
        if (Schema::hasColumn('notifications', 'notifiable_type')) {
            DB::statement("COMMENT ON COLUMN notifications.notifiable_type IS 'ประเภทของวัตถุที่ได้รับการแจ้งเตือน (Polymorphic Relationship)'");
        }
        if (Schema::hasColumn('notifications', 'notifiable_id')) {
            DB::statement("COMMENT ON COLUMN notifications.notifiable_id IS 'รหัสอ้างอิงวัตถุที่ได้รับการแจ้งเตือน (Polymorphic Relationship)'");
        }
        if (Schema::hasColumn('notifications', 'data')) {
            DB::statement("COMMENT ON COLUMN notifications.data IS 'ข้อมูลเพิ่มเติมของการแจ้งเตือนในรูปแบบ JSON'");
        }
        if (Schema::hasColumn('notifications', 'read_at')) {
            DB::statement("COMMENT ON COLUMN notifications.read_at IS 'วันที่และเวลาที่อ่านการแจ้งเตือนแล้ว'");
        }
        if (Schema::hasColumn('notifications', 'notification_group_id')) {
            DB::statement("COMMENT ON COLUMN notifications.notification_group_id IS 'รหัสอ้างอิงกลุ่มผู้รับการแจ้งเตือน'");
        }
        if (Schema::hasColumn('notifications', 'processed_content')) {
            DB::statement("COMMENT ON COLUMN notifications.processed_content IS 'เนื้อหาที่ประมวลผลแล้วสำหรับการส่งแบบ Personalized'");
        }
        if (Schema::hasColumn('notifications', 'personalized_recipients_count')) {
            DB::statement("COMMENT ON COLUMN notifications.personalized_recipients_count IS 'จำนวนผู้รับที่ได้รับเนื้อหาแบบ Personalized'");
        }
        if (Schema::hasColumn('notifications', 'content_version')) {
            DB::statement("COMMENT ON COLUMN notifications.content_version IS 'เวอร์ชันของเนื้อหาการแจ้งเตือน'");
        }
        if (Schema::hasColumn('notifications', 'attachments')) {
            DB::statement("COMMENT ON COLUMN notifications.attachments IS 'รายการไฟล์แนบในรูปแบบ JSON'");
        }
        if (Schema::hasColumn('notifications', 'attachment_urls')) {
            DB::statement("COMMENT ON COLUMN notifications.attachment_urls IS 'รายการ URL ของไฟล์แนบในรูปแบบ JSON'");
        }
        if (Schema::hasColumn('notifications', 'attachments_size')) {
            DB::statement("COMMENT ON COLUMN notifications.attachments_size IS 'ขนาดรวมของไฟล์แนบทั้งหมด (หน่วยไบต์)'");
        }

        // NOTIFICATION_LOGS TABLE - เพิ่ม comments สำหรับ columns ที่เพิ่มทีหลัง
        if (Schema::hasColumn('notification_logs', 'user_id')) {
            DB::statement("COMMENT ON COLUMN notification_logs.user_id IS 'รหัสอ้างอิงผู้ใช้งานที่เป็นผู้รับการแจ้งเตือน'");
        }
        if (Schema::hasColumn('notification_logs', 'read_at')) {
            DB::statement("COMMENT ON COLUMN notification_logs.read_at IS 'วันที่และเวลาที่ผู้รับอ่านการแจ้งเตือนแล้ว'");
        }
        if (Schema::hasColumn('notification_logs', 'archived_at')) {
            DB::statement("COMMENT ON COLUMN notification_logs.archived_at IS 'วันที่และเวลาที่เก็บการแจ้งเตือนเข้าคลัง'");
        }
        if (Schema::hasColumn('notification_logs', 'attempts')) {
            DB::statement("COMMENT ON COLUMN notification_logs.attempts IS 'จำนวนครั้งที่พยายามส่งการแจ้งเตือน'");
        }
        if (Schema::hasColumn('notification_logs', 'personalized_content')) {
            DB::statement("COMMENT ON COLUMN notification_logs.personalized_content IS 'เนื้อหาที่ปรับแต่งเฉพาะบุคคลในรูปแบบ JSON'");
        }
        if (Schema::hasColumn('notification_logs', 'content_sent')) {
            DB::statement("COMMENT ON COLUMN notification_logs.content_sent IS 'เนื้อหาที่ส่งจริงในรูปแบบ JSON'");
        }
        if (Schema::hasColumn('notification_logs', 'webhook_url')) {
            DB::statement("COMMENT ON COLUMN notification_logs.webhook_url IS 'URL Webhook ที่ใช้ในการส่งการแจ้งเตือน'");
        }
        if (Schema::hasColumn('notification_logs', 'webhook_response_code')) {
            DB::statement("COMMENT ON COLUMN notification_logs.webhook_response_code IS 'รหัสสถานะ HTTP Response จาก Webhook'");
        }
        if (Schema::hasColumn('notification_logs', 'variables')) {
            DB::statement("COMMENT ON COLUMN notification_logs.variables IS 'ตัวแปรที่ใช้ในการส่งการแจ้งเตือนในรูปแบบ JSON'");
        }
        if (Schema::hasColumn('notification_logs', 'attachment_paths')) {
            DB::statement("COMMENT ON COLUMN notification_logs.attachment_paths IS 'เส้นทางของไฟล์แนบที่ส่งในรูปแบบ JSON'");
        }
        if (Schema::hasColumn('notification_logs', 'attachment_info')) {
            DB::statement("COMMENT ON COLUMN notification_logs.attachment_info IS 'ข้อมูลรายละเอียดของไฟล์แนบในรูปแบบ JSON'");
        }

        // NOTIFICATION_TEMPLATES TABLE - เพิ่ม comments สำหรับ columns ที่เพิ่มทีหลัง
        if (Schema::hasColumn('notification_templates', 'supports_personalization')) {
            DB::statement("COMMENT ON COLUMN notification_templates.supports_personalization IS 'รองรับการปรับแต่งเนื้อหาเฉพาะบุคคล'");
        }
        if (Schema::hasColumn('notification_templates', 'personalization_variables')) {
            DB::statement("COMMENT ON COLUMN notification_templates.personalization_variables IS 'รายการตัวแปรสำหรับการปรับแต่งเฉพาะบุคคลในรูปแบบ JSON'");
        }
        if (Schema::hasColumn('notification_templates', 'usage_instructions')) {
            DB::statement("COMMENT ON COLUMN notification_templates.usage_instructions IS 'คำแนะนำการใช้งานเทมเพลต'");
        }

        // NOTIFICATION_GROUPS TABLE - เพิ่ม comments สำหรับ columns ที่เพิ่มทีหลัง
        if (Schema::hasColumn('notification_groups', 'webhook_url')) {
            DB::statement("COMMENT ON COLUMN notification_groups.webhook_url IS 'URL Webhook เริ่มต้นสำหรับกลุ่มนี้'");
        }

        // ROLES TABLE - เพิ่ม comments สำหรับ columns ที่เพิ่มทีหลัง
        if (Schema::hasColumn('roles', 'display_name')) {
            DB::statement("COMMENT ON COLUMN roles.display_name IS 'ชื่อแสดงผลบทบาทในภาษาไทย'");
        }
        if (Schema::hasColumn('roles', 'description')) {
            DB::statement("COMMENT ON COLUMN roles.description IS 'คำอธิบายรายละเอียดของบทบาท'");
        }

        // PERMISSIONS TABLE - เพิ่ม comments สำหรับ columns ที่เพิ่มทีหลัง (ถ้ามี)
        if (Schema::hasColumn('permissions', 'display_name')) {
            DB::statement("COMMENT ON COLUMN permissions.display_name IS 'ชื่อแสดงผลสิทธิ์ในภาษาไทย'");
        }
        if (Schema::hasColumn('permissions', 'description')) {
            DB::statement("COMMENT ON COLUMN permissions.description IS 'คำอธิบายรายละเอียดของสิทธิ์'");
        }
        if (Schema::hasColumn('permissions', 'category')) {
            DB::statement("COMMENT ON COLUMN permissions.category IS 'หมวดหมู่หรือกลุ่มของสิทธิ์'");
        }

        // API_KEYS TABLE - เพิ่ม comments สำหรับ columns ที่อาจเพิ่มทีหลัง
        if (Schema::hasTable('api_keys')) {
            if (Schema::hasColumn('api_keys', 'deleted_at')) {
                DB::statement("COMMENT ON COLUMN api_keys.deleted_at IS 'วันที่และเวลาที่ลบ API Key (Soft Delete)'");
            }
            if (Schema::hasColumn('api_keys', 'rate_limit_per_hour')) {
                DB::statement("COMMENT ON COLUMN api_keys.rate_limit_per_hour IS 'จำกัดจำนวนการเรียกใช้ต่อชั่วโมง'");
            }
            if (Schema::hasColumn('api_keys', 'rate_limit_per_day')) {
                DB::statement("COMMENT ON COLUMN api_keys.rate_limit_per_day IS 'จำกัดจำนวนการเรียกใช้ต่อวัน'");
            }
            if (Schema::hasColumn('api_keys', 'usage_count')) {
                DB::statement("COMMENT ON COLUMN api_keys.usage_count IS 'จำนวนครั้งการใช้งานทั้งหมด'");
            }
            if (Schema::hasColumn('api_keys', 'metadata')) {
                DB::statement("COMMENT ON COLUMN api_keys.metadata IS 'ข้อมูลเพิ่มเติมต่างๆ ในรูปแบบ JSON'");
            }
            if (Schema::hasColumn('api_keys', 'assigned_to')) {
                DB::statement("COMMENT ON COLUMN api_keys.assigned_to IS 'รหัsมาชิกที่ได้รับมอบหมาย API Key'");
            }
            if (Schema::hasColumn('api_keys', 'auto_notifications')) {
                DB::statement("COMMENT ON COLUMN api_keys.auto_notifications IS 'เปิดใช้งานการแจ้งเตือนอัตโนมัติ'");
            }
            if (Schema::hasColumn('api_keys', 'notification_webhook')) {
                DB::statement("COMMENT ON COLUMN api_keys.notification_webhook IS 'URL Webhook สำหรับรับการแจ้งเตือนสถานะ'");
            }
            if (Schema::hasColumn('api_keys', 'status_changed_at')) {
                DB::statement("COMMENT ON COLUMN api_keys.status_changed_at IS 'วันที่และเวลาที่เปลี่ยนสถานะล่าสุด'");
            }
            if (Schema::hasColumn('api_keys', 'status_changed_by')) {
                DB::statement("COMMENT ON COLUMN api_keys.status_changed_by IS 'รหัสผู้ใช้งานที่เปลี่ยนสถานะ'");
            }
            if (Schema::hasColumn('api_keys', 'regenerated_at')) {
                DB::statement("COMMENT ON COLUMN api_keys.regenerated_at IS 'วันที่และเวลาที่สร้างรหัสใหม่ครั้งล่าสุด'");
            }
            if (Schema::hasColumn('api_keys', 'regenerated_by')) {
                DB::statement("COMMENT ON COLUMN api_keys.regenerated_by IS 'รหัสผู้ใช้งานที่สร้างรหัสใหม่'");
            }
            if (Schema::hasColumn('api_keys', 'usage_reset_at')) {
                DB::statement("COMMENT ON COLUMN api_keys.usage_reset_at IS 'วันที่และเวลาที่รีเซ็ตการนับการใช้งาน'");
            }
            if (Schema::hasColumn('api_keys', 'usage_reset_by')) {
                DB::statement("COMMENT ON COLUMN api_keys.usage_reset_by IS 'รหัสผู้ใช้งานที่รีเซ็ตการใช้งาน'");
            }
        }

        // เพิ่ม comments สำหรับตารางอื่นๆ ที่อาจมี (ถ้าต้องการ)
        $this->addCommentsIfTableExists('api_key_permissions');
        $this->addCommentsIfTableExists('api_key_events');
        $this->addCommentsIfTableExists('api_usage_logs');
    }

    /**
     * เพิ่ม comments สำหรับตารางที่อาจมีหรือไม่มี
     */
    private function addCommentsIfTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        switch ($tableName) {
            case 'api_key_permissions':
                DB::statement("COMMENT ON TABLE api_key_permissions IS 'ตารางความสัมพันธ์ระหว่าง API Key และสิทธิ์'");
                if (Schema::hasColumn($tableName, 'id')) {
                    DB::statement("COMMENT ON COLUMN api_key_permissions.id IS 'รหัสอ้างอิงหลักของความสัมพันธ์'");
                }
                if (Schema::hasColumn($tableName, 'api_key_id')) {
                    DB::statement("COMMENT ON COLUMN api_key_permissions.api_key_id IS 'รหัสอ้างอิง API Key'");
                }
                if (Schema::hasColumn($tableName, 'permission_id')) {
                    DB::statement("COMMENT ON COLUMN api_key_permissions.permission_id IS 'รหัสอ้างอิงสิทธิ์ที่กำหนดให้ API Key'");
                }
                break;

            case 'api_key_events':
                DB::statement("COMMENT ON TABLE api_key_events IS 'ตารางบันทึกเหตุการณ์ของ API Key'");
                if (Schema::hasColumn($tableName, 'id')) {
                    DB::statement("COMMENT ON COLUMN api_key_events.id IS 'รหัสอ้างอิงหลักของเหตุการณ์'");
                }
                if (Schema::hasColumn($tableName, 'api_key_id')) {
                    DB::statement("COMMENT ON COLUMN api_key_events.api_key_id IS 'รหัสอ้างอิง API Key ที่เกี่ยวข้อง'");
                }
                if (Schema::hasColumn($tableName, 'event_type')) {
                    DB::statement("COMMENT ON COLUMN api_key_events.event_type IS 'ประเภทของเหตุการณ์ที่เกิดขึ้น'");
                }
                if (Schema::hasColumn($tableName, 'description')) {
                    DB::statement("COMMENT ON COLUMN api_key_events.description IS 'คำอธิบายรายละเอียดของเหตุการณ์'");
                }
                if (Schema::hasColumn($tableName, 'performed_by')) {
                    DB::statement("COMMENT ON COLUMN api_key_events.performed_by IS 'รหัสผู้ใช้งานที่ทำเหตุการณ์'");
                }
                break;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ลบคอมเมนต์ทั้งหมดออกจากตารางและคอลัมน์
        $tables = [
            'users', 'notifications', 'notification_templates', 'notification_groups', 
            'notification_logs', 'api_keys', 'api_usage_logs', 'user_preferences',
            'notification_group_users', 'jobs', 'failed_jobs', 'cache', 'cache_locks',
            'activity_log', 'permissions', 'roles', 'model_has_permissions', 
            'model_has_roles', 'role_has_permissions', 'api_key_permissions', 'api_key_events'
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                // ลบคอมเมนต์ของตาราง
                DB::statement("COMMENT ON TABLE {$table} IS ''");
                
                // ลบคอมเมนต์ของคอลัมน์ทั้งหมดในตาราง
                $columns = Schema::getColumnListing($table);
                foreach ($columns as $column) {
                    try {
                        DB::statement("COMMENT ON COLUMN {$table}.{$column} IS ''");
                    } catch (\Exception $e) {
                        // ข้ามคอลัมน์ที่ไม่สามารถแก้ไขได้
                        continue;
                    }
                }
            } catch (\Exception $e) {
                // ข้ามตารางที่ไม่สามารถแก้ไขได้
                continue;
            }
        }
    }
};