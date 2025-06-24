<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // System Alert - Urgent
        NotificationTemplate::create([
            'name' => 'แจ้งเตือนระบบด่วน',
            'slug' => 'system-alert-urgent',
            'description' => 'เทมเพลตสำหรับแจ้งเตือนระบบด่วน',
            'category' => 'system',
            'subject_template' => '🚨 แจ้งเตือนด่วน: {{alert_title}}',
            'body_html_template' => '
                <div style="border: 2px solid #dc3545; border-radius: 5px; padding: 15px; background-color: #f8d7da;">
                    <h3 style="color: #721c24;">🚨 แจ้งเตือนด่วน</h3>
                    <p><strong>หัวข้อ:</strong> {{alert_title}}</p>
                    <p><strong>ระดับความสำคัญ:</strong> {{severity_level}}</p>
                    <p><strong>เวลาที่เกิดเหตุ:</strong> {{incident_time}}</p>
                    <p><strong>รายละเอียด:</strong></p>
                    <p>{{description}}</p>
                    <p><strong>ผลกระทบ:</strong> {{impact}}</p>
                    <p><strong>การดำเนินการ:</strong> {{action_required}}</p>
                </div>
                <p style="margin-top: 15px;">หากมีข้อสงสัย กรุณาติดต่อทีม IT Support ทันที</p>
            ',
            'body_text_template' => '
🚨 แจ้งเตือนด่วน

หัวข้อ: {{alert_title}}
ระดับความสำคัญ: {{severity_level}}
เวลาที่เกิดเหตุ: {{incident_time}}

รายละเอียด:
{{description}}

ผลกระทบ: {{impact}}
การดำเนินการ: {{action_required}}

หากมีข้อสงสัย กรุณาติดต่อทีม IT Support ทันที
            ',
            'variables' => [
                'alert_title' => 'หัวข้อการแจ้งเตือน',
                'severity_level' => 'ระดับความสำคัญ',
                'incident_time' => 'เวลาที่เกิดเหตุ',
                'description' => 'รายละเอียด',
                'impact' => 'ผลกระทบ',
                'action_required' => 'การดำเนินการที่ต้องทำ'
            ],
            'supported_channels' => ['email', 'teams'],
            'priority' => 'urgent', // ✅ ใช้ 'urgent' แทน
            'is_active' => true,
            'created_by' => null
        ]);

        // General Notification
        NotificationTemplate::create([
            'name' => 'แจ้งเตือนทั่วไป',
            'slug' => 'general-notification',
            'description' => 'เทมเพลตสำหรับแจ้งเตือนทั่วไป',
            'category' => 'general',
            'subject_template' => '📢 {{notification_title}}',
            'body_html_template' => '
                <h3>{{notification_title}}</h3>
                <p>เรียน {{recipient_name}}</p>
                <p>{{content}}</p>
                <p><strong>รายละเอียดเพิ่มเติม:</strong></p>
                <p>{{additional_info}}</p>
                <p>ขอบคุณครับ</p>
            ',
            'body_text_template' => '
{{notification_title}}

เรียน {{recipient_name}}

{{content}}

รายละเอียดเพิ่มเติม:
{{additional_info}}

ขอบคุณครับ
            ',
            'variables' => [
                'notification_title' => 'หัวข้อการแจ้งเตือน',
                'recipient_name' => 'ชื่อผู้รับ',
                'content' => 'เนื้อหา',
                'additional_info' => 'รายละเอียดเพิ่มเติม'
            ],
            'supported_channels' => ['email', 'teams'],
            'priority' => 'normal', // ✅ ใช้ 'normal' แทน
            'is_active' => true,
            'created_by' => null
        ]);

        // Meeting Reminder
        NotificationTemplate::create([
            'name' => 'แจ้งเตือนการประชุม',
            'slug' => 'meeting-reminder',
            'description' => 'เทมเพลตสำหรับแจ้งเตือนการประชุม',
            'category' => 'meeting',
            'subject_template' => '📅 แจ้งเตือน: ประชุม {{meeting_title}} วันที่ {{meeting_date}}',
            'body_html_template' => '
                <h3>แจ้งเตือนการประชุม</h3>
                <p>เรียน {{recipient_name}}</p>
                <p>ขอแจ้งให้ทราบว่าท่านมีนัดประชุม <strong>{{meeting_title}}</strong></p>
                <ul>
                    <li><strong>วันที่:</strong> {{meeting_date}}</li>
                    <li><strong>เวลา:</strong> {{meeting_time}}</li>
                    <li><strong>สถานที่:</strong> {{location}}</li>
                    <li><strong>ผู้จัด:</strong> {{organizer}}</li>
                </ul>
                <p><strong>รายละเอียด:</strong></p>
                <p>{{meeting_description}}</p>
                <p><strong>หมายเหตุ:</strong> {{additional_notes}}</p>
                <p>ขอบคุณครับ</p>
            ',
            'body_text_template' => '
แจ้งเตือนการประชุม

เรียน {{recipient_name}}

ขอแจ้งให้ทราบว่าท่านมีนัดประชุม {{meeting_title}}

วันที่: {{meeting_date}}
เวลา: {{meeting_time}}
สถานที่: {{location}}
ผู้จัด: {{organizer}}

รายละเอียด:
{{meeting_description}}

หมายเหตุ: {{additional_notes}}

ขอบคุณครับ
            ',
            'variables' => [
                'recipient_name' => 'ชื่อผู้รับ',
                'meeting_title' => 'หัวข้อการประชุม',
                'meeting_date' => 'วันที่ประชุม',
                'meeting_time' => 'เวลาประชุม',
                'location' => 'สถานที่',
                'organizer' => 'ผู้จัดประชุม',
                'meeting_description' => 'รายละเอียดการประชุม',
                'additional_notes' => 'หมายเหตุเพิ่มเติม'
            ],
            'supported_channels' => ['email', 'teams'],
            'priority' => 'normal', // ✅ เปลี่ยนจาก 'medium' เป็น 'normal'
            'is_active' => true,
            'created_by' => null
        ]);

        // Maintenance Notification
        NotificationTemplate::create([
            'name' => 'แจ้งเตือนการปิดปรับปรุงระบบ',
            'slug' => 'maintenance-notification',
            'description' => 'เทมเพลตสำหรับแจ้งเตือนการปิดปรับปรุงระบบ',
            'category' => 'maintenance',
            'subject_template' => '🔧 แจ้งเตือน: ระบบจะปิดปรับปรุงในวันที่ {{maintenance_date}}',
            'body_html_template' => '
                <h3>แจ้งเตือนการปิดปรับปรุงระบบ</h3>
                <p>เรียน {{recipient_name}}</p>
                <p>ขอแจ้งให้ทราบว่าระบบ <strong>{{system_name}}</strong> จะปิดปรับปรุงในวันที่ <strong>{{maintenance_date}}</strong> 
                เวลา <strong>{{maintenance_time}}</strong></p>
                <p><strong>ระยะเวลาการปิดปรับปรุง:</strong> {{duration}}</p>
                <p><strong>เหตุผล:</strong> {{reason}}</p>
                <p><strong>ผลกระทบ:</strong> {{impact}}</p>
                <p><strong>การเตรียมการ:</strong> {{preparation_steps}}</p>
                <p>ขออภัยในความไม่สะดวก</p>
                <p>ขอบคุณครับ<br>ทีม IT</p>
            ',
            'body_text_template' => '
แจ้งเตือนการปิดปรับปรุงระบบ

เรียน {{recipient_name}}

ขอแจ้งให้ทราบว่าระบบ {{system_name}} จะปิดปรับปรุงในวันที่ {{maintenance_date}} เวลา {{maintenance_time}}

ระยะเวลาการปิดปรับปรุง: {{duration}}
เหตุผล: {{reason}}
ผลกระทบ: {{impact}}
การเตรียมการ: {{preparation_steps}}

ขออภัยในความไม่สะดวก

ขอบคุณครับ
ทีม IT
            ',
            'variables' => [
                'recipient_name' => 'ชื่อผู้รับ',
                'system_name' => 'ชื่อระบบ',
                'maintenance_date' => 'วันที่ปิดปรับปรุง',
                'maintenance_time' => 'เวลาปิดปรับปรุง',
                'duration' => 'ระยะเวลา',
                'reason' => 'เหตุผลการปิดปรับปรุง',
                'impact' => 'ผลกระทบ',
                'preparation_steps' => 'ขั้นตอนการเตรียมการ'
            ],
            'supported_channels' => ['email', 'teams'],
            'priority' => 'high', // ✅ ใช้ 'high' แทน
            'is_active' => true,
            'created_by' => null
        ]);

        // Welcome New User
        NotificationTemplate::create([
            'name' => 'ต้อนรับผู้ใช้ใหม่',
            'slug' => 'welcome-new-user',
            'description' => 'เทมเพลตสำหรับต้อนรับผู้ใช้ใหม่',
            'category' => 'welcome',
            'subject_template' => '👋 ยินดีต้อนรับ {{user_name}} เข้าสู่ระบบ {{system_name}}',
            'body_html_template' => '
                <h3>ยินดีต้อนรับเข้าสู่ระบบ {{system_name}}</h3>
                <p>เรียน {{user_name}}</p>
                <p>ยินดีต้อนรับเข้าสู่ระบบ {{system_name}} ครับ/ค่ะ</p>
                <p><strong>ข้อมูลการเข้าสู่ระบบ:</strong></p>
                <ul>
                    <li><strong>ชื่อผู้ใช้:</strong> {{username}}</li>
                    <li><strong>อีเมล:</strong> {{email}}</li>
                </ul>
                <p><strong>ขั้นตอนการเริ่มต้นใช้งาน:</strong></p>
                <ol>
                    <li>เปลี่ยนรหัสผ่านครั้งแรก</li>
                    <li>ตั้งค่าข้อมูลส่วนตัว</li>
                    <li>ศึกษาคู่มือการใช้งาน</li>
                </ol>
                <p><strong>ลิงก์ที่เกี่ยวข้อง:</strong></p>
                <ul>
                    <li><a href="{{login_url}}">เข้าสู่ระบบ</a></li>
                    <li><a href="{{manual_url}}">คู่มือการใช้งาน</a></li>
                    <li><a href="{{support_url}}">ติดต่อฝ่ายสนับสนุน</a></li>
                </ul>
                <p>หากมีข้อสงสัย สามารถติดต่อทีมสนับสนุนได้ตลอดเวลา</p>
                <p>ขอบคุณครับ<br>ทีม {{system_name}}</p>
            ',
            'body_text_template' => '
ยินดีต้อนรับเข้าสู่ระบบ {{system_name}}

เรียน {{user_name}}

ยินดีต้อนรับเข้าสู่ระบบ {{system_name}} ครับ/ค่ะ

ข้อมูลการเข้าสู่ระบบ:
- ชื่อผู้ใช้: {{username}}
- อีเมล: {{email}}

ขั้นตอนการเริ่มต้นใช้งาน:
1. เปลี่ยนรหัสผ่านครั้งแรก
2. ตั้งค่าข้อมูลส่วนตัว
3. ศึกษาคู่มือการใช้งาน

ลิงก์ที่เกี่ยวข้อง:
- เข้าสู่ระบบ: {{login_url}}
- คู่มือการใช้งาน: {{manual_url}}
- ติดต่อฝ่ายสนับสนุน: {{support_url}}

หากมีข้อสงสัย สามารถติดต่อทีมสนับสนุนได้ตลอดเวลา

ขอบคุณครับ
ทีม {{system_name}}
            ',
            'variables' => [
                'user_name' => 'ชื่อผู้ใช้',
                'system_name' => 'ชื่อระบบ',
                'username' => 'ชื่อผู้ใช้',
                'email' => 'อีเมล',
                'login_url' => 'URL เข้าสู่ระบบ',
                'manual_url' => 'URL คู่มือการใช้งาน',
                'support_url' => 'URL ติดต่อฝ่ายสนับสนุน'
            ],
            'supported_channels' => ['email', 'teams'],
            'priority' => 'normal', // ✅ ใช้ 'normal' แทน
            'is_active' => true,
            'created_by' => null
        ]);

        $this->command->info('✅ NotificationTemplate seeder completed successfully!');
        $this->command->info('📊 Created 5 notification templates:');
        $this->command->info('  • แจ้งเตือนระบบด่วน (system-alert-urgent) - Priority: urgent');
        $this->command->info('  • แจ้งเตือนทั่วไป (general-notification) - Priority: normal');
        $this->command->info('  • แจ้งเตือนการประชุม (meeting-reminder) - Priority: normal');
        $this->command->info('  • แจ้งเตือนการปิดปรับปรุงระบบ (maintenance-notification) - Priority: high');
        $this->command->info('  • ต้อนรับผู้ใช้ใหม่ (welcome-new-user) - Priority: normal');
    }
}