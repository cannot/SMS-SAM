/**
 * System Variables Configuration - Thai Version
 * ตัวแปรระบบที่ตรงกับระบบส่งอีเมลจริง
 */

export const systemVariables = {
    // ตัวแปรผู้ใช้ (User Variables) - จาก LDAP หรือ Database
    user: {
        user_name: {
            label: 'ชื่อผู้ใช้',
            description: 'ชื่อเต็มของผู้รับ',
            example: 'นายสมชาย ใจดี',
            category: 'user'
        },
        user_email: {
            label: 'อีเมลผู้ใช้',
            description: 'ที่อยู่อีเมลของผู้รับ',
            example: 'somchai@company.com',
            category: 'user'
        },
        user_first_name: {
            label: 'ชื่อ',
            description: 'ชื่อแรกของผู้รับ',
            example: 'สมชาย',
            category: 'user'
        },
        user_last_name: {
            label: 'นามสกุล',
            description: 'นามสกุลของผู้รับ',
            example: 'ใจดี',
            category: 'user'
        },
        user_department: {
            label: 'แผนก',
            description: 'แผนกของผู้รับ',
            example: 'เทคโนโลยีสารสนเทศ',
            category: 'user'
        },
        user_title: {
            label: 'ตำแหน่ง',
            description: 'ตำแหน่งงานของผู้รับ',
            example: 'นักพัฒนาระบบ',
            category: 'user'
        },
        // Alternative names สำหรับ compatibility
        recipient_name: {
            label: 'ชื่อผู้รับ',
            description: 'ชื่อเต็มของผู้รับ (เหมือน user_name)',
            example: 'นายสมชาย ใจดี',
            category: 'user'
        },
        recipient_email: {
            label: 'อีเมลผู้รับ',
            description: 'ที่อยู่อีเมลของผู้รับ (เหมือน user_email)',
            example: 'somchai@company.com',
            category: 'user'
        },
        recipient_first_name: {
            label: 'ชื่อผู้รับ',
            description: 'ชื่อแรกของผู้รับ (เหมือน user_first_name)',
            example: 'สมชาย',
            category: 'user'
        },
        recipient_last_name: {
            label: 'นามสกุลผู้รับ',
            description: 'นามสกุลของผู้รับ (เหมือน user_last_name)',
            example: 'ใจดี',
            category: 'user'
        }
    },

    // ตัวแปรระบบ (System Variables) - จาก application
    system: {
        current_date: {
            label: 'วันที่ปัจจุบัน',
            description: 'วันที่ปัจจุบันในรูปแบบ Y-m-d',
            example: '2025-07-05',
            category: 'system'
        },
        current_time: {
            label: 'เวลาปัจจุบัน',
            description: 'เวลาปัจจุบันในรูปแบบ H:i:s',
            example: '14:30:25',
            category: 'system'
        },
        current_datetime: {
            label: 'วันที่และเวลาปัจจุบัน',
            description: 'วันที่และเวลาปัจจุบันในรูปแบบ Y-m-d H:i:s',
            example: '2025-07-05 14:30:25',
            category: 'system'
        },
        app_name: {
            label: 'ชื่อระบบ',
            description: 'ชื่อของระบบ application',
            example: 'Smart Notification System',
            category: 'system'
        },
        app_url: {
            label: 'URL ระบบ',
            description: 'URL หลักของระบบ',
            example: 'https://notification.company.com',
            category: 'system'
        },
        system_name: {
            label: 'ชื่อระบบ',
            description: 'ชื่อของระบบ (เหมือน app_name)',
            example: 'Smart Notification System',
            category: 'system'
        },
        year: {
            label: 'ปี',
            description: 'ปีปัจจุบัน',
            example: '2025',
            category: 'system'
        },
        month: {
            label: 'เดือน',
            description: 'เดือนปัจจุบันในรูปแบบ 2 หลัก',
            example: '07',
            category: 'system'
        },
        day: {
            label: 'วัน',
            description: 'วันปัจจุบันในรูปแบบ 2 หลัก',
            example: '05',
            category: 'system'
        }
    },

    // ตัวแปรการแจ้งเตือน (Notification Variables) - จาก notification data
    notification: {
        notification_id: {
            label: 'รหัสการแจ้งเตือน',
            description: 'UUID ของการแจ้งเตือน',
            example: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            category: 'notification'
        },
        subject: {
            label: 'หัวข้อ',
            description: 'หัวข้อของการแจ้งเตือน',
            example: 'การแจ้งเตือนสำคัญ',
            category: 'notification'
        },
        priority: {
            label: 'ระดับความสำคัญ',
            description: 'ระดับความสำคัญของการแจ้งเตือน',
            example: 'สูง',
            category: 'notification'
        }
    },

    // ตัวแปรกำหนดเอง (Custom Variables) - ที่ผู้ใช้กำหนดเอง
    custom: {
        message: {
            label: 'ข้อความ',
            description: 'เนื้อหาข้อความหลัก',
            example: 'นี่คือข้อความแจ้งเตือนตัวอย่าง',
            category: 'custom'
        },
        company: {
            label: 'บริษัท',
            description: 'ชื่อบริษัทหรือองค์กร',
            example: 'บริษัท เทคโนโลยี จำกัด',
            category: 'custom'
        },
        url: {
            label: 'ลิงก์',
            description: 'URL สำหรับการดำเนินการ',
            example: 'https://example.com/action',
            category: 'custom'
        },
        action_url: {
            label: 'ลิงก์ปุ่มกดดำเนินการ',
            description: 'URL สำหรับปุ่มในอีเมล',
            example: 'https://example.com/action',
            category: 'custom'
        },
        action_text: {
            label: 'ข้อความปุ่ม',
            description: 'ข้อความที่แสดงบนปุ่ม',
            example: 'คลิกที่นี่',
            category: 'custom'
        },
        status: {
            label: 'สถานะ',
            description: 'สถานะของกิจกรรมหรือโครงการ',
            example: 'เสร็จสิ้น',
            category: 'custom'
        },
        deadline: {
            label: 'กำหนดเวลา',
            description: 'วันกำหนดเวลาสิ้นสุด',
            example: '2025-12-31',
            category: 'custom'
        },
        amount: {
            label: 'จำนวนเงิน',
            description: 'จำนวนเงินในรูปแบบที่มี comma',
            example: '1,250.00',
            category: 'custom'
        },
        // ตัวแปรสำหรับประชุม
        meeting_title: {
            label: 'หัวข้อประชุม',
            description: 'ชื่อหรือหัวข้อของการประชุม',
            example: 'ประชุมทีมประจำสัปดาห์',
            category: 'custom'
        },
        meeting_date: {
            label: 'วันที่ประชุม',
            description: 'วันที่ของการประชุม',
            example: '2025-07-10',
            category: 'custom'
        },
        meeting_time: {
            label: 'เวลาประชุม',
            description: 'เวลาของการประชุม',
            example: '10:00 - 11:00 น.',
            category: 'custom'
        },
        meeting_location: {
            label: 'สถานที่ประชุม',
            description: 'สถานที่จัดการประชุม',
            example: 'ห้องประชุม A',
            category: 'custom'
        },
        agenda: {
            label: 'วาระการประชุม',
            description: 'รายการวาระการประชุม',
            example: '1. ทบทวนงาน\n2. วางแผนใหม่',
            category: 'custom'
        },
        // ตัวแปรสำหรับโครงการ
        project_name: {
            label: 'ชื่อโครงการ',
            description: 'ชื่อของโครงการ',
            example: 'โครงการพัฒนาระบบ',
            category: 'custom'
        },
        progress: {
            label: 'ความคืบหน้า',
            description: 'เปอร์เซ็นต์ความคืบหน้าของโครงการ',
            example: '75',
            category: 'custom'
        },
        updated_by: {
            label: 'อัพเดทโดย',
            description: 'ผู้ที่ทำการอัพเดท',
            example: 'ทีมพัฒนา',
            category: 'custom'
        },
        welcome_message: {
            label: 'ข้อความต้อนรับ',
            description: 'ข้อความต้อนรับสำหรับผู้ใช้ใหม่',
            example: 'ยินดีต้อนรับเข้าสู่ระบบของเรา',
            category: 'custom'
        }
    }
};

// รวมตัวแปรทั้งหมดเป็น flat object สำหรับการใช้งาน
export const allSystemVariables = {
    ...systemVariables.user,
    ...systemVariables.system,
    ...systemVariables.notification,
    ...systemVariables.custom
};

// รายการตัวแปรระบบที่ไม่ต้องให้ผู้ใช้กำหนดค่า
export const reservedSystemVariables = [
    'current_date', 'current_time', 'current_datetime',
    'app_name', 'app_url', 'system_name',
    'year', 'month', 'day',
    'notification_id'
];

// ฟังก์ชันสำหรับได้รับตัวแปรตามหมวดหมู่
export function getVariablesByCategory(category) {
    if (!systemVariables[category]) {
        return {};
    }
    return systemVariables[category];
}

// ฟังก์ชันสำหรับตรวจสอบว่าเป็นตัวแปรระบบหรือไม่
export function isSystemVariable(variableName) {
    return reservedSystemVariables.includes(variableName) || 
           allSystemVariables.hasOwnProperty(variableName);
}

// ฟังก์ชันสำหรับได้รับข้อมูลตัวแปร
export function getVariableInfo(variableName) {
    return allSystemVariables[variableName] || null;
}

// ฟังก์ชันสำหรับสร้างข้อมูลตัวอย่าง
export function generateSampleData() {
    const sampleData = {};
    
    Object.keys(allSystemVariables).forEach(key => {
        const variable = allSystemVariables[key];
        
        // ข้ามตัวแปรที่เป็น system variables ที่มีค่าจริง
        if (reservedSystemVariables.includes(key)) {
            switch(key) {
                case 'current_date':
                    sampleData[key] = new Date().toISOString().split('T')[0];
                    break;
                case 'current_time':
                    sampleData[key] = new Date().toTimeString().split(' ')[0];
                    break;
                case 'current_datetime':
                    sampleData[key] = new Date().toISOString().replace('T', ' ').split('.')[0];
                    break;
                case 'year':
                    sampleData[key] = new Date().getFullYear().toString();
                    break;
                case 'month':
                    sampleData[key] = (new Date().getMonth() + 1).toString().padStart(2, '0');
                    break;
                case 'day':
                    sampleData[key] = new Date().getDate().toString().padStart(2, '0');
                    break;
                case 'app_name':
                case 'system_name':
                    sampleData[key] = 'Smart Notification System';
                    break;
                case 'app_url':
                    sampleData[key] = window.location.origin;
                    break;
                case 'notification_id':
                    sampleData[key] = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
                    break;
            }
        } else {
            // ใช้ตัวอย่างจาก variable definition
            sampleData[key] = variable.example || `ตัวอย่าง ${variable.label}`;
        }
    });
    
    return sampleData;
}