/**
 * Template Gallery Data - Thai Version
 * ข้อมูลเทมเพลตสำเร็จรูปสำหรับ Smart Notification System
 */

export const templateGallery = {
    system_alert: {
        name: 'เทมเพลตการแจ้งเตือนระบบ',
        category: 'system',
        priority: 'high',
        description: 'การแจ้งเตือนระบบที่สำคัญและเหตุการณ์ฉุกเฉิน',
        subject_template: '[{{priority}}] การแจ้งเตือนระบบ: {{subject}}',
        body_html_template: `<div style="font-family: 'Sarabun', Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #dc3545; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0; font-size: 24px;">🚨 การแจ้งเตือนระบบ</h1>
    </div>
    <div style="padding: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6;">
        <h2 style="color: #dc3545; margin-top: 0;">{{subject}}</h2>
        <p style="font-size: 16px; line-height: 1.5;">{{message}}</p>
        <div style="background-color: white; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;">
            <strong>ระดับความสำคัญ:</strong> {{priority}}<br>
            <strong>เวลา:</strong> {{current_datetime}}<br>
            <strong>ระบบ:</strong> {{system_name}}
        </div>
        <p style="margin-bottom: 0;">กรุณาดำเนินการหากจำเป็น</p>
    </div>
</div>`,
        body_text_template: 'การแจ้งเตือนระบบ [{{priority}}]: {{subject}}\n\n{{message}}\n\nเวลา: {{current_datetime}}\nระดับความสำคัญ: {{priority}}\nระบบ: {{system_name}}\n\nกรุณาดำเนินการหากจำเป็น',
        default_variables_json: JSON.stringify({
            subject: 'ปัญหาการเชื่อมต่อฐานข้อมูล',
            message: 'เซิร์ฟเวอร์ฐานข้อมูลหลักมีปัญหาการเชื่อมต่อ',
            priority: 'สูง'
        }, null, 2),
        supported_channels: ['email', 'teams']
    },

    marketing_email: {
        name: 'เทมเพลตอีเมลการตลาด',
        category: 'marketing',
        priority: 'normal',
        description: 'อีเมลส่งเสริมการขายและจดหมายข่าวสารที่มีดีไซน์สวยงาม',
        subject_template: '{{subject}} - {{company}}',
        body_html_template: `<div style="font-family: 'Sarabun', Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;">
        <h1 style="margin: 0; font-size: 28px;">{{company}}</h1>
    </div>
    <div style="padding: 30px; background-color: white;">
        <h2 style="color: #333; margin-top: 0;">สวัสดีครับ/ค่ะ คุณ{{user_name}}</h2>
        <p style="font-size: 16px; line-height: 1.6; color: #555;">{{message}}</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{action_url}}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">{{action_text}}</a>
        </div>
        
        <p style="font-size: 14px; color: #888; margin-bottom: 0;">ขอบคุณครับ/ค่ะ<br>ทีมงาน {{company}}</p>
    </div>
</div>`,
        body_text_template: 'สวัสดีครับ/ค่ะ คุณ{{user_name}}\n\n{{message}}\n\n{{action_text}}: {{action_url}}\n\nขอบคุณครับ/ค่ะ\nทีมงาน {{company}}',
        default_variables_json: JSON.stringify({
            subject: 'ข้อเสนอพิเศษเฉพาะสำหรับคุณ!',
            company: 'Smart Notify',
            message: 'เรามีข้อเสนอพิเศษที่คิดว่าคุณจะชอบ!',
            action_text: 'ดูข้อเสนอ',
            action_url: 'https://example.com/offer'
        }, null, 2),
        supported_channels: ['email']
    },

    meeting_reminder: {
        name: 'เทมเพลตการแจ้งเตือนประชุม',
        category: 'operational',
        priority: 'medium',
        description: 'การแจ้งเตือนประชุมและการนัดหมาย',
        subject_template: 'เตือนความจำ: {{meeting_title}} - {{meeting_date}}',
        body_html_template: `<div style="font-family: 'Sarabun', Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #0d6efd; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0; font-size: 24px;">📅 เตือนความจำการประชุม</h1>
    </div>
    <div style="padding: 20px; background-color: white; border: 1px solid #dee2e6;">
        <h2 style="color: #0d6efd; margin-top: 0;">{{meeting_title}}</h2>
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px 0; font-weight: bold; width: 100px;">วันที่:</td>
                    <td style="padding: 5px 0;">{{meeting_date}}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: bold;">เวลา:</td>
                    <td style="padding: 5px 0;">{{meeting_time}}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: bold;">สถานที่:</td>
                    <td style="padding: 5px 0;">{{meeting_location}}</td>
                </tr>
            </table>
        </div>
        <h3 style="color: #333;">วาระการประชุม:</h3>
        <p style="font-size: 16px; line-height: 1.5;">{{agenda}}</p>
    </div>
</div>`,
        body_text_template: 'เตือนความจำการประชุม\n\n{{meeting_title}}\n\nวันที่: {{meeting_date}}\nเวลา: {{meeting_time}}\nสถานที่: {{meeting_location}}\n\nวาระการประชุม:\n{{agenda}}',
        default_variables_json: JSON.stringify({
            meeting_title: 'ประชุมทีมประจำสัปดาห์',
            meeting_date: '2025-06-20',
            meeting_time: '10:00 - 11:00 น.',
            meeting_location: 'ห้องประชุม A',
            agenda: '1. ทบทวนความคืบหน้า\n2. หารือปัญหาที่พบ\n3. วางแผนสัปดาห์หน้า'
        }, null, 2),
        supported_channels: ['email', 'teams']
    },

    status_update: {
        name: 'เทมเพลตอัพเดทสถานะ',
        category: 'operational',
        priority: 'medium',
        description: 'การอัพเดทสถานะโครงการและระบบ',
        subject_template: 'อัพเดทสถานะ: {{project_name}}',
        body_html_template: `<div style="font-family: 'Sarabun', Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #198754; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0; font-size: 24px;">📊 อัพเดทสถานะ</h1>
    </div>
    <div style="padding: 20px; background-color: white; border: 1px solid #dee2e6;">
        <h2 style="color: #198754; margin-top: 0;">{{project_name}}</h2>
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <strong>สถานะปัจจุบัน:</strong> {{status}}<br>
            <strong>เปอร์เซ็นต์ความสำเร็จ:</strong> {{progress}}%<br>
            <strong>อัพเดทโดย:</strong> {{updated_by}}<br>
            <strong>วันที่อัพเดท:</strong> {{current_date}}
        </div>
        <h3 style="color: #333;">รายละเอียด:</h3>
        <p style="font-size: 16px; line-height: 1.5;">{{message}}</p>
    </div>
</div>`,
        body_text_template: 'อัพเดทสถานะ: {{project_name}}\n\nสถานะปัจจุบัน: {{status}}\nเปอร์เซ็นต์ความสำเร็จ: {{progress}}%\nอัพเดทโดย: {{updated_by}}\nวันที่อัพเดท: {{current_date}}\n\nรายละเอียด:\n{{message}}',
        default_variables_json: JSON.stringify({
            project_name: 'โครงการ Smart Notification',
            status: 'กำลังดำเนินการ',
            progress: '75',
            updated_by: 'ทีมพัฒนา',
            message: 'โครงการดำเนินไปได้ด้วยดี อยู่ในช่วงการทดสอบระบบ'
        }, null, 2),
        supported_channels: ['email', 'teams']
    },

    welcome_message: {
        name: 'เทมเพลตข้อความต้อนรับ',
        category: 'operational',
        priority: 'normal',
        description: 'ข้อความต้อนรับผู้ใช้ใหม่และการปฐมนิเทศ',
        subject_template: 'ยินดีต้อนรับ คุณ{{user_name}}!',
        body_html_template: `<div style="font-family: 'Sarabun', Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #20c997 0%, #0d6efd 100%); color: white; padding: 30px; text-align: center;">
        <h1 style="margin: 0; font-size: 28px;">🎉 ยินดีต้อนรับ!</h1>
    </div>
    <div style="padding: 30px; background-color: white;">
        <h2 style="color: #333; margin-top: 0;">สวัสดีครับ/ค่ะ คุณ{{user_name}}</h2>
        <p style="font-size: 16px; line-height: 1.6; color: #555;">{{welcome_message}}</p>
        
        <div style="background-color: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="color: #0d6efd; margin-top: 0;">ข้อมูลบัญชีของคุณ:</h3>
            <p style="margin: 5px 0;"><strong>ชื่อ:</strong> {{user_name}}</p>
            <p style="margin: 5px 0;"><strong>อีเมล:</strong> {{user_email}}</p>
            <p style="margin: 5px 0;"><strong>แผนก:</strong> {{user_department}}</p>
            <p style="margin: 5px 0;"><strong>ตำแหน่ง:</strong> {{user_title}}</p>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{app_url}}" style="display: inline-block; background: linear-gradient(135deg, #20c997 0%, #0d6efd 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">เข้าสู่ระบบ</a>
        </div>
        
        <p style="font-size: 14px; color: #888; margin-bottom: 0;">หากมีคำถามใดๆ สามารถติดต่อทีมสนับสนุนได้<br>ทีมงาน {{company}}</p>
    </div>
</div>`,
        body_text_template: 'ยินดีต้อนรับ คุณ{{user_name}}!\n\n{{welcome_message}}\n\nข้อมูลบัญชีของคุณ:\nชื่อ: {{user_name}}\nอีเมล: {{user_email}}\nแผนก: {{user_department}}\nตำแหน่ง: {{user_title}}\n\nเข้าสู่ระบบ: {{app_url}}\n\nหากมีคำถามใดๆ สามารถติดต่อทีมสนับสนุนได้\nทีมงาน {{company}}',
        default_variables_json: JSON.stringify({
            welcome_message: 'ยินดีต้อนรับเข้าสู่ระบบแจ้งเตือนอัจฉริยะ! เราหวังว่าคุณจะได้รับประสบการณ์ที่ดีในการใช้งานระบบของเรา',
            company: 'Smart Notify',
            user_department: 'เทคโนโลยีสารสนเทศ',
            user_title: 'นักพัฒนาระบบ'
        }, null, 2),
        supported_channels: ['email', 'teams']
    }
};