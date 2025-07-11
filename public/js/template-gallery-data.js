/**
 * Template Gallery Data for Thai Notification System
 * ข้อมูลเทมเพลตสำเร็จรูปสำหรับระบบแจ้งเตือนภาษาไทย
 */

const templateGalleryData = {
    system_alert: {
        name: 'แจ้งเตือนระบบ',
        category: 'system',
        priority: 'urgent',
        description: 'เทมเพลตสำหรับการแจ้งเตือนระบบที่สำคัญและเหตุการณ์ฉุกเฉิน',
        subject_template: '[{{priority}}] แจ้งเตือนระบบ: {{subject}}',
        body_html_template: `<div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #dee2e6;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 25px; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: bold;">⚠️ แจ้งเตือนระบบ</h1>
        <p style="margin: 8px 0 0 0; font-size: 14px; opacity: 0.9;">ระดับความสำคัญ: {{priority}}</p>
    </div>
    
    <!-- Content -->
    <div style="padding: 30px; background: #f8f9fa;">
        <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545;">
            <h2 style="color: #dc3545; margin: 0 0 15px 0; font-size: 20px;">{{subject}}</h2>
            <div style="color: #495057; line-height: 1.6; margin-bottom: 20px;">
                {{message}}
            </div>
            
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin-top: 20px;">
                <p style="margin: 0; color: #856404; font-weight: 500;">
                    <strong>📅 เวลา:</strong> {{current_datetime}}<br>
                    <strong>🔧 สถานะ:</strong> {{status}}
                </p>
            </div>
            
            <div style="text-align: center; margin-top: 25px;">
                <a href="{{url}}" style="background: #dc3545; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500; display: inline-block;">
                    ดำเนินการทันที
                </a>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="background: #6c757d; color: white; padding: 15px; text-align: center; font-size: 12px;">
        <p style="margin: 0;">ส่งจาก {{app_name}} | <a href="{{app_url}}" style="color: #adb5bd;">{{app_url}}</a></p>
        <p style="margin: 5px 0 0 0; opacity: 0.8;">© {{year}} - ระบบแจ้งเตือนอัตโนมัติ</p>
    </div>
</div>`,
        body_text_template: `⚠️ แจ้งเตือนระบบ [{{priority}}]

{{subject}}

{{message}}

📅 เวลา: {{current_datetime}}
🔧 สถานะ: {{status}}

ดำเนินการ: {{url}}

ส่งจาก {{app_name}}
{{app_url}}`,
        supported_channels: ['email', 'teams', 'sms'],
        default_variables: {
            priority: 'เร่งด่วน',
            subject: 'ระบบต้องการบำรุงรักษาเร่งด่วน',
            message: 'ระบบจะหยุดให้บริการชั่วคราวเพื่อการบำรุงรักษาเร่งด่วน เวลา 02:00-04:00 น. กรุณาบันทึกงานและออกจากระบบ',
            status: 'กำลังดำเนินการ',
            url: 'https://status.company.com'
        }
    },

    marketing_email: {
        name: 'อีเมลการตลาด',
        category: 'marketing', 
        priority: 'normal',
        description: 'เทมเพลตสำหรับอีเมลการตลาดและจดหมายข่าวที่สวยงาม',
        subject_template: '{{subject}} - {{company}}',
        body_html_template: `<div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; text-align: center;">
        <h1 style="margin: 0; font-size: 32px; font-weight: 700; letter-spacing: -0.5px;">{{company}}</h1>
        <p style="margin: 12px 0 0 0; font-size: 16px; opacity: 0.95;">{{subject}}</p>
    </div>
    
    <!-- Main Content -->
    <div style="padding: 40px 30px;">
        <h2 style="color: #333; margin: 0 0 20px 0; font-size: 24px;">สวัสดี {{user_name}}! 👋</h2>
        
        <div style="color: #555; line-height: 1.7; font-size: 16px; margin-bottom: 30px;">
            {{message}}
        </div>
        
        <div style="background: #f8f9fa; border-radius: 10px; padding: 25px; margin: 30px 0;">
            <h3 style="color: #495057; margin: 0 0 15px 0;">✨ ไฮไลท์พิเศษ</h3>
            <ul style="margin: 0; padding-left: 20px; color: #6c757d;">
                <li style="margin-bottom: 8px;">โปรโมชั่นสุดพิเศษลดราคา 50%</li>
                <li style="margin-bottom: 8px;">สินค้าใหม่ล่าสุดพร้อมส่ง</li>
                <li style="margin-bottom: 8px;">บริการหลังการขายครบวงจร</li>
            </ul>
        </div>
        
        <!-- CTA Button -->
        <div style="text-align: center; margin: 35px 0;">
            <a href="{{url}}" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px; display: inline-block; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                {{cta_text}}
            </a>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #6c757d; font-size: 14px; margin: 0;">
                หากคุณมีคำถาม โปรดติดต่อเราที่ <a href="mailto:{{contact_email}}" style="color: #667eea;">{{contact_email}}</a>
            </p>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="background: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #dee2e6;">
        <div style="margin-bottom: 15px;">
            <a href="{{social_facebook}}" style="color: #667eea; text-decoration: none; margin: 0 10px;">Facebook</a>
            <a href="{{social_line}}" style="color: #667eea; text-decoration: none; margin: 0 10px;">Line</a>
            <a href="{{social_instagram}}" style="color: #667eea; text-decoration: none; margin: 0 10px;">Instagram</a>
        </div>
        <p style="margin: 0; color: #6c757d; font-size: 14px;">
            © {{year}} {{company}} | <a href="{{app_url}}" style="color: #6c757d;">{{app_url}}</a>
        </p>
        <p style="margin: 8px 0 0 0; color: #adb5bd; font-size: 12px;">
            คุณได้รับอีเมลนี้เพราะเป็นสมาชิกของเรา | <a href="{{unsubscribe_url}}" style="color: #adb5bd;">ยกเลิกการสมัครรับข่าวสาร</a>
        </p>
    </div>
</div>`,
        body_text_template: `{{company}}
{{subject}}

สวัสดี {{user_name}}!

{{message}}

ไฮไลท์พิเศษ:
- โปรโมชั่นสุดพิเศษลดราคา 50%
- สินค้าใหม่ล่าสุดพร้อมส่ง
- บริการหลังการขายครบวงจร

{{cta_text}}: {{url}}

หากคุณมีคำถาม โปรดติดต่อเราที่ {{contact_email}}

ติดตามเราได้ที่:
- Facebook: {{social_facebook}}
- Line: {{social_line}}
- Instagram: {{social_instagram}}

© {{year}} {{company}}
{{app_url}}

ยกเลิกการสมัครรับข่าวสาร: {{unsubscribe_url}}`,
        supported_channels: ['email'],
        default_variables: {
            subject: 'ข่าวสารใหม่และโปรโมชั่นพิเศษ',
            message: 'เรามีข่าวสารดีๆ และโปรโมชั่นสุดพิเศษมาแจ้งให้คุณทราบ ไม่ว่าจะเป็นสินค้าใหม่ การลดราคา หรือกิจกรรมสุดน่าสนใจ',
            company: 'บริษัทตัวอย่าง จำกัด',
            cta_text: 'ดูโปรโมชั่น',
            url: 'https://example.com/promotions',
            contact_email: 'contact@example.com',
            social_facebook: 'https://facebook.com/company',
            social_line: 'https://line.me/@company',
            social_instagram: 'https://instagram.com/company',
            unsubscribe_url: 'https://example.com/unsubscribe'
        }
    },

    meeting_reminder: {
        name: 'แจ้งเตือนประชุม',
        category: 'operational',
        priority: 'normal', 
        description: 'เทมเพลตสำหรับการแจ้งเตือนประชุมและการนัดหมาย',
        subject_template: '📅 แจ้งเตือนประชุม: {{meeting_title}} - {{meeting_date}}',
        body_html_template: `<div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #dee2e6;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 25px; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: bold;">📅 แจ้งเตือนประชุม</h1>
        <p style="margin: 8px 0 0 0; opacity: 0.9;">เตรียมพร้อมสำหรับการประชุม</p>
    </div>
    
    <!-- Meeting Details -->
    <div style="padding: 30px;">
        <h2 style="color: #28a745; margin: 0 0 20px 0; font-size: 22px;">{{meeting_title}}</h2>
        
        <!-- Meeting Info Card -->
        <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin: 20px 0;">
            <div style="margin-bottom: 12px;">
                <span style="color: #28a745;">📅</span>
                <strong style="color: #495057; margin-left: 10px;">วันที่:</strong>
                <span style="color: #212529; margin-left: 10px;">{{meeting_date}}</span>
            </div>
            <div style="margin-bottom: 12px;">
                <span style="color: #28a745;">🕐</span>
                <strong style="color: #495057; margin-left: 10px;">เวลา:</strong>
                <span style="color: #212529; margin-left: 10px;">{{meeting_time}}</span>
            </div>
            <div style="margin-bottom: 12px;">
                <span style="color: #28a745;">📍</span>
                <strong style="color: #495057; margin-left: 10px;">สถานที่:</strong>
                <span style="color: #212529; margin-left: 10px;">{{meeting_location}}</span>
            </div>
            <div>
                <span style="color: #28a745;">👥</span>
                <strong style="color: #495057; margin-left: 10px;">ผู้เข้าร่วม:</strong>
                <span style="color: #212529; margin-left: 10px;">{{attendees}}</span>
            </div>
        </div>
        
        <!-- Agenda -->
        <div style="margin: 25px 0;">
            <h3 style="color: #495057; margin: 0 0 15px 0; font-size: 18px;">📋 วาระการประชุม</h3>
            <div style="background: white; border-left: 4px solid #28a745; padding: 15px; color: #495057; line-height: 1.6;">
                {{agenda}}
            </div>
        </div>
        
        <!-- Preparation -->
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <h4 style="color: #856404; margin: 0 0 10px 0; font-size: 16px;">📝 เตรียมตัวก่อนประชุม</h4>
            <p style="margin: 0; color: #856404;">{{preparation}}</p>
        </div>
        
        <!-- Action Buttons -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{meeting_url}}" style="background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500; margin: 5px; display: inline-block;">
                🚀 เข้าร่วมประชุม
            </a>
            <a href="{{calendar_url}}" style="background: #6c757d; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500; margin: 5px; display: inline-block;">
                📅 เพิ่มในปฏิทิน
            </a>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="background: #f8f9fa; color: #6c757d; padding: 15px; text-align: center; font-size: 12px; border-top: 1px solid #dee2e6;">
        <p style="margin: 0;">ส่งจาก {{app_name}} | {{current_datetime}}</p>
    </div>
</div>`,
        body_text_template: `📅 แจ้งเตือนประชุม

{{meeting_title}}

รายละเอียดการประชุม:
📅 วันที่: {{meeting_date}}
🕐 เวลา: {{meeting_time}}
📍 สถานที่: {{meeting_location}}
👥 ผู้เข้าร่วม: {{attendees}}

📋 วาระการประชุม:
{{agenda}}

📝 เตรียมตัวก่อนประชุม:
{{preparation}}

🚀 เข้าร่วมประชุม: {{meeting_url}}
📅 เพิ่มในปฏิทิน: {{calendar_url}}

ส่งจาก {{app_name}}
{{current_datetime}}`,
        supported_channels: ['email', 'teams'],
        default_variables: {
            meeting_title: 'ประชุมประจำสัปดาห์ทีมพัฒนา',
            meeting_date: 'วันจันทร์ที่ 15 มกราคม 2567',
            meeting_time: '09:00 - 10:30 น.',
            meeting_location: 'ห้องประชุม Innovation Center ชั้น 12',
            attendees: 'ทีมพัฒนาทั้งหมด, Product Manager, QA Team',
            agenda: '1. รายงานความคืบหน้าโครงการ\n2. ปัญหาและอุปสรรคที่พบ\n3. แผนงานสัปดาห์หน้า\n4. การทดสอบและ Deployment\n5. Q&A และข้อเสนอแนะ',
            preparation: 'กรุณาเตรียมรายงานความคืบหน้างานของคุณและสิ่งที่ต้องการความช่วยเหลือ',
            meeting_url: 'https://teams.microsoft.com/l/meetup-join/...',
            calendar_url: 'https://outlook.live.com/calendar/...'
        }
    },

    status_update: {
        name: 'อัปเดตสถานะ',
        category: 'operational',
        priority: 'normal',
        description: 'เทมเพลตสำหรับการแจ้งอัปเดตสถานะโครงการและระบบ',
        subject_template: '📊 อัปเดตสถานะ: {{project_name}} - {{status}}',
        body_html_template: `<div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #dee2e6;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #6f42c1 0%, #6610f2 100%); color: white; padding: 25px; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: bold;">📊 อัปเดตสถานะโครงการ</h1>
        <p style="margin: 8px 0 0 0; opacity: 0.9;">ข้อมูลล่าสุด ณ {{current_date}}</p>
    </div>
    
    <!-- Project Info -->
    <div style="padding: 30px;">
        <div style="background: #f8f9fa; border-left: 4px solid #6f42c1; padding: 20px; margin-bottom: 25px;">
            <h2 style="color: #6f42c1; margin: 0 0 10px 0; font-size: 22px;">{{project_name}}</h2>
            <p style="color: #6c757d; margin: 0; font-size: 14px;">โครงการ ID: {{project_id}} | ผู้รับผิดชอบ: {{project_manager}}</p>
        </div>
        
        <!-- Status Badge -->
        <div style="text-align: center; margin: 20px 0;">
            <span style="background: #ffc107; color: white; padding: 8px 20px; border-radius: 20px; font-weight: 600; font-size: 16px;">
                🟡 {{status}}
            </span>
        </div>
        
        <!-- Progress Bar -->
        <div style="margin: 25px 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-weight: 600; color: #495057;">ความคืบหน้า</span>
                <span style="font-weight: 600; color: #6f42c1;">{{progress}}%</span>
            </div>
            <div style="background: #e9ecef; border-radius: 10px; height: 12px; overflow: hidden;">
                <div style="background: linear-gradient(90deg, #6f42c1, #6610f2); height: 100%; width: {{progress}}%; border-radius: 10px;"></div>
            </div>
        </div>
        
        <!-- Update Message -->
        <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin: 20px 0;">
            <h3 style="color: #495057; margin: 0 0 15px 0; font-size: 18px;">📝 รายละเอียดการอัปเดต</h3>
            <div style="color: #6c757d; line-height: 1.6;">
                {{message}}
            </div>
        </div>
        
        <!-- Next Steps -->
        <div style="background: #e7f3ff; border: 1px solid #bee5eb; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <h4 style="color: #0c5460; margin: 0 0 10px 0; font-size: 16px;">🎯 ขั้นตอนต่อไป</h4>
            <p style="margin: 0; color: #0c5460;">{{next_steps}}</p>
        </div>
        
        <!-- Action Button -->
        <div style="text-align: center; margin: 25px 0;">
            <a href="{{dashboard_url}}" style="background: #6f42c1; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500;">
                📊 ดู Dashboard
            </a>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="background: #f8f9fa; color: #6c757d; padding: 15px; text-align: center; font-size: 12px; border-top: 1px solid #dee2e6;">
        <p style="margin: 0;">อัปเดตอัตโนมัติจาก {{app_name}} | {{current_datetime}}</p>
    </div>
</div>`,
        body_text_template: `📊 อัปเดตสถานะโครงการ

{{project_name}}
สถานะ: {{status}}
ความคืบหน้า: {{progress}}%

📝 รายละเอียดการอัปเดต:
{{message}}

🎯 ขั้นตอนต่อไป:
{{next_steps}}

📊 ดู Dashboard: {{dashboard_url}}

อัปเดตจาก {{app_name}}
{{current_datetime}}`,
        supported_channels: ['email', 'teams'],
        default_variables: {
            project_name: 'ระบบ CRM ใหม่',
            project_id: 'CRM-2024-001',
            project_manager: 'คุณสมชาย วิชัยกิจ',
            status: 'กำลังดำเนินการ',
            progress: '75',
            message: 'โครงการดำเนินไปได้ด้วยดี ในสัปดาห์นี้ทีมได้ทำการพัฒนาโมดูล Customer Management เสร็จสิ้นแล้ว และกำลังทดสอบระบบ Integration กับระบบเดิม คาดว่าจะสามารถ Deploy ในสัปดาห์หน้าได้',
            next_steps: 'สัปดาห์หน้าจะเริ่มการทดสอบ User Acceptance Testing (UAT) และเตรียมการ Training ให้กับผู้ใช้งาน',
            dashboard_url: 'https://project.company.com/crm-dashboard'
        }
    },

    welcome_message: {
        name: 'ข้อความต้อนรับ',
        category: 'marketing',
        priority: 'normal',
        description: 'เทมเพลตสำหรับการต้อนรับผู้ใช้ใหม่และการแนะนำระบบ',
        subject_template: '🎉 ยินดีต้อนรับสู่ {{company}} - {{user_name}}!',
        body_html_template: `<div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%); color: white; padding: 40px 30px; text-align: center;">
        <h1 style="margin: 0; font-size: 28px; font-weight: 700;">🎉 ยินดีต้อนรับ!</h1>
        <p style="margin: 15px 0 0 0; font-size: 18px; opacity: 0.95;">เข้าสู่โลกแห่งความสะดวกกับ {{company}}</p>
    </div>
    
    <!-- Welcome Message -->
    <div style="padding: 40px 30px;">
        <h2 style="color: #333; margin: 0 0 20px 0; font-size: 24px;">สวัสดี {{user_name}}! 👋</h2>
        
        <p style="color: #555; line-height: 1.7; font-size: 16px; margin-bottom: 25px;">
            ขอบคุณที่เข้าร่วมเป็นส่วนหนึ่งของครอบครัว {{company}} เราตื่นเต้นที่ได้ต้อนรับคุณและพร้อมที่จะช่วยให้คุณได้รับประสบการณ์ที่ดีที่สุด
        </p>
        
        <!-- Account Info -->
        <div style="background: #f8f9fa; border-radius: 10px; padding: 25px; margin: 25px 0;">
            <h3 style="color: #495057; margin: 0 0 15px 0; font-size: 18px;">👤 ข้อมูลบัญชีของคุณ</h3>
            <div style="color: #6c757d; line-height: 1.6;">
                <p style="margin: 8px 0;"><strong>ชื่อ:</strong> {{user_name}}</p>
                <p style="margin: 8px 0;"><strong>อีเมล:</strong> {{user_email}}</p>
                <p style="margin: 8px 0;"><strong>แผนก:</strong> {{user_department}}</p>
                <p style="margin: 8px 0;"><strong>วันที่เข้าร่วม:</strong> {{current_date}}</p>
            </div>
        </div>
        
        <!-- Getting Started Steps -->
        <div style="margin: 30px 0;">
            <h3 style="color: #495057; margin: 0 0 20px 0; font-size: 18px;">🚀 เริ่มต้นใช้งาน</h3>
            <div>
                <div style="display: flex; align-items: center; padding: 15px; background: white; border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 10px;">
                    <span style="background: #28a745; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px;">1</span>
                    <div>
                        <strong style="color: #495057;">เข้าสู่ระบบ</strong>
                        <div style="color: #6c757d; font-size: 14px;">ใช้อีเมลและรหัสผ่านที่ได้รับเพื่อเข้าสู่ระบบ</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; padding: 15px; background: white; border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 10px;">
                    <span style="background: #ffc107; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px;">2</span>
                    <div>
                        <strong style="color: #495057;">ตั้งค่าโปรไฟล์</strong>
                        <div style="color: #6c757d; font-size: 14px;">อัปเดตข้อมูลส่วนตัวและการตั้งค่า</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; padding: 15px; background: white; border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 10px;">
                    <span style="background: #17a2b8; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px;">3</span>
                    <div>
                        <strong style="color: #495057;">เรียนรู้การใช้งาน</strong>
                        <div style="color: #6c757d; font-size: 14px;">ดูคู่มือและวิดีโอแนะนำการใช้งาน</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div style="text-align: center; margin: 35px 0;">
            <a href="{{login_url}}" style="background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px; margin: 10px; display: inline-block;">
                🚀 เข้าสู่ระบบ
            </a>
            <a href="{{help_url}}" style="background: white; color: #495057; border: 2px solid #dee2e6; padding: 13px 28px; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px; margin: 10px; display: inline-block;">
                📚 ดูคู่มือ
            </a>
        </div>
        
        <!-- Support Info -->
        <div style="background: #e7f3ff; border: 1px solid #bee5eb; border-radius: 8px; padding: 20px; margin: 30px 0;">
            <h4 style="color: #0c5460; margin: 0 0 15px 0; font-size: 16px;">💬 ต้องการความช่วยเหลือ?</h4>
            <p style="color: #0c5460; margin: 0; line-height: 1.6;">
                หากคุณมีคำถามหรือต้องการความช่วยเหลือ ติดต่อทีมสนับสนุนได้ที่:<br>
                📧 <a href="mailto:{{support_email}}" style="color: #0c5460;">{{support_email}}</a><br>
                📞 {{support_phone}}<br>
                🕐 วันจันทร์-ศุกร์ 08:00-17:00 น.
            </p>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="background: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #dee2e6;">
        <p style="margin: 0 0 10px 0; color: #6c757d; font-size: 14px;">
            © {{year}} {{company}} | <a href="{{app_url}}" style="color: #6c757d;">{{app_url}}</a>
        </p>
        <div style="margin-top: 15px;">
            <a href="{{social_facebook}}" style="color: #6c757d; text-decoration: none; margin: 0 10px;">Facebook</a>
            <a href="{{social_line}}" style="color: #6c757d; text-decoration: none; margin: 0 10px;">Line</a>
            <a href="{{website_url}}" style="color: #6c757d; text-decoration: none; margin: 0 10px;">เว็บไซต์</a>
        </div>
    </div>
</div>`,
        body_text_template: `🎉 ยินดีต้อนรับสู่ {{company}}!

สวัสดี {{user_name}}!

ขอบคุณที่เข้าร่วมเป็นส่วนหนึ่งของครอบครัว {{company}} เราตื่นเต้นที่ได้ต้อนรับคุณและพร้อมที่จะช่วยให้คุณได้รับประสบการณ์ที่ดีที่สุด

👤 ข้อมูลบัญชีของคุณ:
- ชื่อ: {{user_name}}
- อีเมล: {{user_email}}
- แผนก: {{user_department}}
- วันที่เข้าร่วม: {{current_date}}

🚀 เริ่มต้นใช้งาน:
1. เข้าสู่ระบบ - ใช้อีเมลและรหัสผ่านที่ได้รับ
2. ตั้งค่าโปรไฟล์ - อัปเดตข้อมูลส่วนตัว
3. เรียนรู้การใช้งาน - ดูคู่มือและวิดีโอแนะนำ

🔗 ลิงก์สำคัญ:
- เข้าสู่ระบบ: {{login_url}}
- คู่มือการใช้งาน: {{help_url}}

💬 ต้องการความช่วยเหลือ?
- อีเมล: {{support_email}}
- โทรศัพท์: {{support_phone}}
- เวลาทำการ: วันจันทร์-ศุกร์ 08:00-17:00 น.

© {{year}} {{company}}
{{app_url}}`,
        supported_channels: ['email'],
        default_variables: {
            company: 'Smart Notification System',
            user_name: 'สมชาย ใจดี',
            user_email: 'somchai@company.com',
            user_department: 'แผนกเทคโนโลยีสารสนเทศ',
            login_url: 'https://app.company.com/login',
            help_url: 'https://help.company.com',
            support_email: 'support@company.com',
            support_phone: '02-123-4567',
            social_facebook: 'https://facebook.com/company',
            social_line: 'https://line.me/@company',
            website_url: 'https://company.com'
        }
    }
};

// Export for use in other files
if (typeof window !== 'undefined') {
    window.templateGalleryData = templateGalleryData;
} else if (typeof module !== 'undefined' && module.exports) {
    module.exports = templateGalleryData;
}