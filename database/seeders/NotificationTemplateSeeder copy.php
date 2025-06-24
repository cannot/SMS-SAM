<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // System maintenance template
        NotificationTemplate::create([
            'name' => 'System Maintenance Alert',
            'slug' => 'system-maintenance',
            'description' => 'Template for system maintenance notifications',
            'type' => 'both',
            'subject' => 'ระบบจะปิดปรับปรุง: {{maintenance_date}}',
            'body_html' => '
                <h3>แจ้งเตือนการปิดปรับปรุงระบบ</h3>
                <p>เรียน {{recipient_name}}</p>
                <p>ขอแจ้งให้ทราบว่าระบบ <strong>{{system_name}}</strong> จะปิดปรับปรุงในวันที่ <strong>{{maintenance_date}}</strong> 
                เวลา <strong>{{maintenance_time}}</strong></p>
                <p><strong>ระยะเวลาการปิดปรับปรุง:</strong> {{duration}}</p>
                <p><strong>เหตุผล:</strong> {{reason}}</p>
                <p>ขออภัยในความไม่สะดวก</p>
                <p>ขอบคุณครับ<br>ทีม IT</p>
            ',
            'body_text' => '
แจ้งเตือนการปิดปรับปรุงระบบ

เรียน {{recipient_name}}

ขอแจ้งให้ทราบว่าระบบ {{system_name}} จะปิดปรับปรุงในวันที่ {{maintenance_date}} เวลา {{maintenance_time}}

ระยะเวลาการปิดปรับปรุง: {{duration}}
เหตุผล: {{reason}}

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
                'reason' => 'เหตุผลการปิดปรับปรุง'
            ],
            'teams_card_template' => [
                'type' => 'AdaptiveCard',
                'version' => '1.3',
                'body' => [
                    [
                        'type' => 'TextBlock',
                        'text' => '🔧 ระบบจะปิดปรับปรุง',
                        'weight' => 'Bolder',
                        'size' => 'Medium',
                        'color' => 'Warning'
                    ],
                    [
                        'type' => 'FactSet',
                        'facts' => [
                            ['title' => 'ระบบ:', 'value' => '{{system_name}}'],
                            ['title' => 'วันที่:', 'value' => '{{maintenance_date}}'],
                            ['title' => 'เวลา:', 'value' => '{{maintenance_time}}'],
                            ['title' => 'ระยะเวลา:', 'value' => '{{duration}}']
                        ]
                    ],
                    [
                        'type' => 'TextBlock',
                        'text' => '{{reason}}',
                        'wrap' => true
                    ]
                ]
            ],
            'is_active' => true,
            'created_by' => 1,
        ]);

        // Meeting reminder template
        NotificationTemplate::create([
            'name' => 'Meeting Reminder',
            'slug' => 'meeting-reminder',
            'description' => 'Template for meeting reminders',
            'type' => 'both',
            'subject' => 'แจ้งเตือน: ประชุม {{meeting_title}} วันที่ {{meeting_date}}',
            'body_html' => '
                <h3>เตือนการประชุม</h3>
                <p>เรียน {{recipient_name}}</p>
                <p>ขอเตือนให้ทราบว่าท่านมีนัดประชุม <strong>{{meeting_title}}</strong></p>
                <ul>
                    <li><strong>วันที่:</strong> {{meeting_date}}</li>
                    <li><strong>เวลา:</strong> {{meeting_time}}</li>
                    <li><strong>สถานที่:</strong> {{location}}</li>
                    <li><strong>ผู้จัด:</strong> {{organizer}}</li>
                </ul>
                <p>{{additional_notes}}</p>
                <p>ขอบคุณครับ</p>
            ',
            'body_text' => '
เตือนการประชุม

เรียน {{recipient_name}}

ขอเตือนให้ทราบว่าท่านมีนัดประชุม {{meeting_title}}

วันที่: {{meeting_date}}
เวลา: {{meeting_time}}
สถานที่: {{location}}
ผู้จัด: {{organizer}}

{{additional_notes}}

ขอบคุณครับ
            ',
            'variables' => [
                'recipient_name' => 'ชื่อผู้รับ',
                'meeting_title' => 'หัวข้อการประชุม',
                'meeting_date' => 'วันที่ประชุม',
                'meeting_time' => 'เวลาประชุม',
                'location' => 'สถานที่',
                'organizer' => 'ผู้จัดประชุม',
                'additional_notes' => 'หมายเหตุเพิ่มเติม'
            ],
            'teams_card_template' => [
                'type' => 'AdaptiveCard',
                'version' => '1.3',
                'body' => [
                    [
                        'type' => 'TextBlock',
                        'text' => '📅 เตือนการประชุม',
                        'weight' => 'Bolder',
                        'size' => 'Medium',
                        'color' => 'Accent'
                    ],
                    [
                        'type' => 'TextBlock',
                        'text' => '{{meeting_title}}',
                        'weight' => 'Bolder',
                        'size' => 'Large'
                    ],
                    [
                        'type' => 'FactSet',
                        'facts' => [
                            ['title' => '📅 วันที่:', 'value' => '{{meeting_date}}'],
                            ['title' => '🕐 เวลา:', 'value' => '{{meeting_time}}'],
                            ['title' => '📍 สถานที่:', 'value' => '{{location}}'],
                            ['title' => '👤 ผู้จัด:', 'value' => '{{organizer}}']
                        ]
                    ]
                ]
            ],
            'is_active' => true,
            'created_by' => 1,
        ]);

        // Security alert template
        NotificationTemplate::create([
            'name' => 'Security Alert',
            'slug' => 'security-alert',
            'description' => 'Template for security alerts',
            'type' => 'both',
            'subject' => '🚨 เตือนความปลอดภัย: {{alert_type}}',
            'body_html' => '
                <div style="border: 2px solid #dc3545; border-radius: 5px; padding: 15px; background-color: #f8d7da;">
                    <h3 style="color: #721c24;">🚨 แจ้งเตือนความปลอดภัย</h3>
                    <p><strong>ประเภทการแจ้งเตือน:</strong> {{alert_type}}</p>
                    <p><strong>เวลาที่เกิดเหตุ:</strong> {{incident_time}}</p>
                    <p><strong>รายละเอียด:</strong></p>
                    <p>{{description}}</p>
                    <p><strong>ผลกระทบ:</strong> {{impact}}</p>
                    <p><strong>การดำเนินการ:</strong> {{action_required}}</p>
                </div>
                <p style="margin-top: 15px;">หากมีข้อสงสัย กรุณาติดต่อทีม IT Security ทันที</p>
            ',
            'body_text' => '
🚨 แจ้งเตือนความปลอดภัย

ประเภทการแจ้งเตือน: {{alert_type}}
เวลาที่เกิดเหตุ: {{incident_time}}

รายละเอียด:
{{description}}

ผลกระทบ: {{impact}}
การดำเนินการ: {{action_required}}

หากมีข้อสงสัย กรุณาติดต่อทีม IT Security ทันที
            ',
            'variables' => [
                'alert_type' => 'ประเภทการแจ้งเตือน',
                'incident_time' => 'เวลาที่เกิดเหตุ',
                'description' => 'รายละเอียด',
                'impact' => 'ผลกระทบ',
                'action_required' => 'การดำเนินการที่ต้องทำ'
            ],
            'teams_card_template' => [
                'type' => 'AdaptiveCard',
                'version' => '1.3',
                'body' => [
                    [
                        'type' => 'TextBlock',
                        'text' => '🚨 SECURITY ALERT',
                        'weight' => 'Bolder',
                        'size' => 'Large',
                        'color' => 'Attention'
                    ],
                    [
                        'type' => 'Container',
                        'style' => 'attention',
                        'items' => [
                            [
                                'type' => 'FactSet',
                                'facts' => [
                                    ['title' => 'Alert Type:', 'value' => '{{alert_type}}'],
                                    ['title' => 'Time:', 'value' => '{{incident_time}}'],
                                    ['title' => 'Impact:', 'value' => '{{impact}}']
                                ]
                            ],
                            [
                                'type' => 'TextBlock',
                                'text' => '{{description}}',
                                'wrap' => true
                            ],
                            [
                                'type' => 'TextBlock',
                                'text' => 'Action Required: {{action_required}}',
                                'weight' => 'Bolder',
                                'wrap' => true
                            ]
                        ]
                    ]
                ]
            ],
            'is_active' => true,
            'created_by' => 1,
        ]);
    }
}