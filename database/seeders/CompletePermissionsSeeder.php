<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Complete Permissions Seeder for Smart Notification System (Updated)
 * 
 * รวมการสร้าง permissions, roles และการกำหนดสิทธิ์ทั้งหมดในไฟล์เดียว
 * สำหรับระบบ Smart Notification System ที่พัฒนาด้วย Laravel
 * 
 * Updated Features:
 * - Web Interface Permissions (WEB Guard)
 * - API Permissions (API Guard) 
 * - API Key Management Permissions
 * - Route-based Permission Mapping
 * - Complete Role Assignments
 * 
 * @author Smart Notification Team
 * @version 2.0
 * @since 2025-06-25
 */
class CompletePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear Laravel permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('🚀 Starting Complete Permissions Seeder v2.0...');
        
        // Create all permissions
        $this->createWebPermissions();
        $this->createApiPermissions();
        
        // Create all roles
        $this->createRoles();
        
        // Assign permissions to roles
        $this->assignPermissionsToRoles();
        
        $this->command->info('✅ Complete permissions setup finished successfully!');
        $this->showSummary();
    }

    /**
     * Create WEB interface permissions (guard_name = 'web')
     */
    private function createWebPermissions(): void
    {
        $this->command->info('📝 Creating WEB permissions...');

        $webPermissions = [
            // ===========================================
            // USER MANAGEMENT PERMISSIONS (การจัดการผู้ใช้)
            // ===========================================
            [
                'name' => 'view-users',
                'guard_name' => 'web',
                'display_name' => 'ดูผู้ใช้',
                'description' => 'ดูรายการผู้ใช้ในระบบ',
                'category' => 'User Management'
            ],
            [
                'name' => 'create-users',
                'guard_name' => 'web',
                'display_name' => 'สร้างผู้ใช้',
                'description' => 'สร้างผู้ใช้ใหม่ในระบบ',
                'category' => 'User Management'
            ],
            [
                'name' => 'edit-users',
                'guard_name' => 'web',
                'display_name' => 'แก้ไขผู้ใช้',
                'description' => 'แก้ไขข้อมูลผู้ใช้',
                'category' => 'User Management'
            ],
            [
                'name' => 'delete-users',
                'guard_name' => 'web',
                'display_name' => 'ลบผู้ใช้',
                'description' => 'ลบผู้ใช้ออกจากระบบ',
                'category' => 'User Management'
            ],
            [
                'name' => 'manage-users',
                'guard_name' => 'web',
                'display_name' => 'จัดการผู้ใช้',
                'description' => 'จัดการผู้ใช้ทั่วไป (เปิด/ปิด, รีเซ็ต)',
                'category' => 'User Management'
            ],
            [
                'name' => 'export-users',
                'guard_name' => 'web',
                'display_name' => 'ส่งออกผู้ใช้',
                'description' => 'ส่งออกข้อมูลผู้ใช้เป็นไฟล์',
                'category' => 'User Management'
            ],
            [
                'name' => 'import-users',
                'guard_name' => 'web',
                'display_name' => 'นำเข้าผู้ใช้',
                'description' => 'นำเข้าข้อมูลผู้ใช้จากไฟล์',
                'category' => 'User Management'
            ],
            [
                'name' => 'manage-user-roles',
                'guard_name' => 'web',
                'display_name' => 'จัดการบทบาทผู้ใช้',
                'description' => 'กำหนดและจัดการบทบาทของผู้ใช้',
                'category' => 'User Management'
            ],
            [
                'name' => 'manage-user-permissions',
                'guard_name' => 'web',
                'display_name' => 'จัดการสิทธิ์ผู้ใช้',
                'description' => 'กำหนดและจัดการสิทธิ์ของผู้ใช้',
                'category' => 'User Management'
            ],
            [
                'name' => 'view-user-permissions',
                'guard_name' => 'web',
                'display_name' => 'ดูสิทธิ์ผู้ใช้',
                'description' => 'ดูสิทธิ์ของผู้ใช้',
                'category' => 'User Management'
            ],
            [
                'name' => 'assign-user-permissions',
                'guard_name' => 'web',
                'display_name' => 'กำหนดสิทธิ์ผู้ใช้',
                'description' => 'กำหนดสิทธิ์ให้ผู้ใช้',
                'category' => 'User Management'
            ],
            [
                'name' => 'manage-user-preferences',
                'guard_name' => 'web',
                'display_name' => 'จัดการค่าตั้งผู้ใช้',
                'description' => 'จัดการการตั้งค่าผู้ใช้',
                'category' => 'User Management'
            ],

            // ===========================================
            // NOTIFICATION MANAGEMENT PERMISSIONS (การจัดการการแจ้งเตือน)
            // ===========================================
            [
                'name' => 'view-all-notifications',
                'guard_name' => 'web',
                'display_name' => 'ดูการแจ้งเตือนทั้งหมด',
                'description' => 'ดูการแจ้งเตือนทั้งหมดในระบบ',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'view-received-notifications',
                'guard_name' => 'web',
                'display_name' => 'ดูการแจ้งเตือนที่ได้รับ',
                'description' => 'ดูการแจ้งเตือนที่ได้รับ (ของตนเอง)',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'create-notifications',
                'guard_name' => 'web',
                'display_name' => 'สร้างการแจ้งเตือน',
                'description' => 'สร้างการแจ้งเตือนใหม่',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'edit-notifications',
                'guard_name' => 'web',
                'display_name' => 'แก้ไขการแจ้งเตือน',
                'description' => 'แก้ไขการแจ้งเตือน',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'delete-notifications',
                'guard_name' => 'web',
                'display_name' => 'ลบการแจ้งเตือน',
                'description' => 'ลบการแจ้งเตือน',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'send-notifications',
                'guard_name' => 'web',
                'display_name' => 'ส่งการแจ้งเตือน',
                'description' => 'ส่งการแจ้งเตือนทันที',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'cancel-notifications',
                'guard_name' => 'web',
                'display_name' => 'ยกเลิกการแจ้งเตือน',
                'description' => 'ยกเลิกการแจ้งเตือนที่กำหนดไว้',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'resend-notifications',
                'guard_name' => 'web',
                'display_name' => 'ส่งการแจ้งเตือนซ้ำ',
                'description' => 'ส่งการแจ้งเตือนซ้ำ',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'duplicate-notifications',
                'guard_name' => 'web',
                'display_name' => 'ทำสำเนาการแจ้งเตือน',
                'description' => 'ทำสำเนาการแจ้งเตือน',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'manage-notifications',
                'guard_name' => 'web',
                'display_name' => 'จัดการการแจ้งเตือน',
                'description' => 'จัดการการแจ้งเตือนทั่วไป',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'bulk-notification-actions',
                'guard_name' => 'web',
                'display_name' => 'การดำเนินการแบบกลุ่ม',
                'description' => 'การดำเนินการแบบกลุ่ม (ส่ง/ยกเลิก หลายรายการ)',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'read-notifications',
                'guard_name' => 'web',
                'display_name' => 'ทำเครื่องหมายอ่าน',
                'description' => 'ทำเครื่องหมายอ่าน/ไม่อ่าน การแจ้งเตือน',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'archive-notifications',
                'guard_name' => 'web',
                'display_name' => 'เก็บถาวรการแจ้งเตือน',
                'description' => 'เก็บถาวรและกู้คืนการแจ้งเตือน',
                'category' => 'Notification Management'
            ],
            [
                'name' => 'schedule-notifications',
                'guard_name' => 'web',
                'display_name' => 'กำหนดเวลาการแจ้งเตือน',
                'description' => 'กำหนดเวลาส่งการแจ้งเตือน',
                'category' => 'Notification Management'
            ],

            // ===========================================
            // TEMPLATE MANAGEMENT PERMISSIONS (การจัดการเทมเพลต)
            // ===========================================
            [
                'name' => 'view-notification-templates',
                'guard_name' => 'web',
                'display_name' => 'ดูเทมเพลต',
                'description' => 'ดูเทมเพลตการแจ้งเตือน',
                'category' => 'Template Management'
            ],
            [
                'name' => 'create-notification-templates',
                'guard_name' => 'web',
                'display_name' => 'สร้างเทมเพลต',
                'description' => 'สร้างเทมเพลตการแจ้งเตือนใหม่',
                'category' => 'Template Management'
            ],
            [
                'name' => 'edit-notification-templates',
                'guard_name' => 'web',
                'display_name' => 'แก้ไขเทมเพลต',
                'description' => 'แก้ไขเทมเพลตการแจ้งเตือน',
                'category' => 'Template Management'
            ],
            [
                'name' => 'delete-notification-templates',
                'guard_name' => 'web',
                'display_name' => 'ลบเทมเพลต',
                'description' => 'ลบเทมเพลตการแจ้งเตือน',
                'category' => 'Template Management'
            ],
            [
                'name' => 'duplicate-templates',
                'guard_name' => 'web',
                'display_name' => 'ทำสำเนาเทมเพลต',
                'description' => 'ทำสำเนาเทมเพลต',
                'category' => 'Template Management'
            ],
            [
                'name' => 'export-templates',
                'guard_name' => 'web',
                'display_name' => 'ส่งออกเทมเพลต',
                'description' => 'ส่งออกเทมเพลต',
                'category' => 'Template Management'
            ],
            [
                'name' => 'import-templates',
                'guard_name' => 'web',
                'display_name' => 'นำเข้าเทมเพลต',
                'description' => 'นำเข้าเทมเพลต',
                'category' => 'Template Management'
            ],

            // ===========================================
            // GROUP MANAGEMENT PERMISSIONS (การจัดการกลุ่ม)
            // ===========================================
            [
                'name' => 'view-notification-groups',
                'guard_name' => 'web',
                'display_name' => 'ดูกลุ่ม',
                'description' => 'ดูกลุ่มการแจ้งเตือน',
                'category' => 'Group Management'
            ], 
            [
                'name' => 'create-notification-groups',
                'guard_name' => 'web',
                'display_name' => 'สร้างกลุ่ม',
                'description' => 'สร้างกลุ่มการแจ้งเตือนใหม่',
                'category' => 'Group Management'
            ],
            [
                'name' => 'edit-notification-groups',
                'guard_name' => 'web',
                'display_name' => 'แก้ไขกลุ่ม',
                'description' => 'แก้ไขกลุ่มการแจ้งเตือน',
                'category' => 'Group Management'
            ],
            [
                'name' => 'delete-notification-groups',
                'guard_name' => 'web',
                'display_name' => 'ลบกลุ่ม',
                'description' => 'ลบกลุ่มการแจ้งเตือน',
                'category' => 'Group Management'
            ],
            [
                'name' => 'manage-group-members',
                'guard_name' => 'web',
                'display_name' => 'จัดการสมาชิกกลุ่ม',
                'description' => 'จัดการสมาชิกในกลุ่ม (เพิ่ม/ลบ/แก้ไข)',
                'category' => 'Group Management'
            ],
            [
                'name' => 'sync-groups',
                'guard_name' => 'web',
                'display_name' => 'ซิงค์กลุ่ม',
                'description' => 'ซิงค์สมาชิกกลุ่มจาก LDAP',
                'category' => 'Group Management'
            ],
            [
                'name' => 'export-groups',
                'guard_name' => 'web',
                'display_name' => 'ส่งออกกลุ่ม',
                'description' => 'ส่งออกข้อมูลกลุ่มและสมาชิก',
                'category' => 'Group Management'
            ],

            // ===========================================
            // API MANAGEMENT PERMISSIONS (การจัดการ API)
            // ===========================================
            [
                'name' => 'view-api-keys',
                'guard_name' => 'web',
                'display_name' => 'ดู API Keys',
                'description' => 'ดูรายการ API Keys',
                'category' => 'API Management'
            ],
            [
                'name' => 'create-api-keys',
                'guard_name' => 'web',
                'display_name' => 'สร้าง API Keys',
                'description' => 'สร้าง API Keys ใหม่',
                'category' => 'API Management'
            ],
            [
                'name' => 'edit-api-keys',
                'guard_name' => 'web',
                'display_name' => 'แก้ไข API Keys',
                'description' => 'แก้ไข API Keys (ชื่อ, สิทธิ์, วันหมดอายุ)',
                'category' => 'API Management'
            ],
            [
                'name' => 'delete-api-keys',
                'guard_name' => 'web',
                'display_name' => 'ลบ API Keys',
                'description' => 'ลบ/เพิกถอน API Keys',
                'category' => 'API Management'
            ],
            [
                'name' => 'regenerate-api-keys',
                'guard_name' => 'web',
                'display_name' => 'รีเจนเนเรต API Keys',
                'description' => 'สร้าง API Keys ใหม่ (รีเจนเนเรต)',
                'category' => 'API Management'
            ],
            [
                'name' => 'view-api-usage',
                'guard_name' => 'web',
                'display_name' => 'ดูการใช้งาน API',
                'description' => 'ดูการใช้งาน API และสถิติ',
                'category' => 'API Management'
            ],
            [
                'name' => 'manage-api-rate-limits',
                'guard_name' => 'web',
                'display_name' => 'จัดการ Rate Limits',
                'description' => 'จัดการ Rate Limiting สำหรับ API',
                'category' => 'API Management'
            ],
            [
                'name' => 'manage-api-keys',
                'guard_name' => 'web',
                'display_name' => 'จัดการ API Keys',
                'description' => 'จัดการ API Keys ทั่วไป',
                'category' => 'API Management'
            ],
            [
                'name' => 'view-api-usage-reports',
                'guard_name' => 'web',
                'display_name' => 'ดูรายงาน API',
                'description' => 'ดูรายงานการใช้งาน API',
                'category' => 'API Management'
            ],

            // ===========================================
            // ROLES & PERMISSIONS MANAGEMENT (การจัดการสิทธิ์และบทบาท)
            // ===========================================
            [
                'name' => 'view-roles',
                'guard_name' => 'web',
                'display_name' => 'ดูบทบาท',
                'description' => 'ดูรายการบทบาท (Roles)',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'create-roles',
                'guard_name' => 'web',
                'display_name' => 'สร้างบทบาท',
                'description' => 'สร้างบทบาทใหม่',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'edit-roles',
                'guard_name' => 'web',
                'display_name' => 'แก้ไขบทบาท',
                'description' => 'แก้ไขบทบาท',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'delete-roles',
                'guard_name' => 'web',
                'display_name' => 'ลบบทบาท',
                'description' => 'ลบบทบาท',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'assign-roles',
                'guard_name' => 'web',
                'display_name' => 'กำหนดบทบาท',
                'description' => 'กำหนดบทบาทให้ผู้ใช้',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'view-permissions',
                'guard_name' => 'web',
                'display_name' => 'ดูสิทธิ์',
                'description' => 'ดูรายการสิทธิ์ (Permissions)',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'create-permissions',
                'guard_name' => 'web',
                'display_name' => 'สร้างสิทธิ์',
                'description' => 'สร้างสิทธิ์ใหม่',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'edit-permissions',
                'guard_name' => 'web',
                'display_name' => 'แก้ไขสิทธิ์',
                'description' => 'แก้ไขสิทธิ์',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'delete-permissions',
                'guard_name' => 'web',
                'display_name' => 'ลบสิทธิ์',
                'description' => 'ลบสิทธิ์',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'assign-permissions',
                'guard_name' => 'web',
                'display_name' => 'กำหนดสิทธิ์',
                'description' => 'กำหนดสิทธิ์ให้บทบาท',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'view-permission-matrix',
                'guard_name' => 'web',
                'display_name' => 'ดูตารางสิทธิ์',
                'description' => 'ดูตารางสิทธิ์ (Permission Matrix)',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'export-permissions',
                'guard_name' => 'web',
                'display_name' => 'ส่งออกสิทธิ์',
                'description' => 'ส่งออกข้อมูลสิทธิ์',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'import-permissions',
                'guard_name' => 'web',
                'display_name' => 'นำเข้าสิทธิ์',
                'description' => 'นำเข้าข้อมูลสิทธิ์',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'export-roles',
                'guard_name' => 'web',
                'display_name' => 'ส่งออกบทบาท',
                'description' => 'ส่งออกข้อมูลบทบาท',
                'category' => 'Roles & Permissions'
            ],
            [
                'name' => 'import-roles',
                'guard_name' => 'web',
                'display_name' => 'นำเข้าบทบาท',
                'description' => 'นำเข้าข้อมูลบทบาท',
                'category' => 'Roles & Permissions'
            ],

            // ===========================================
            // REPORTS & ANALYTICS PERMISSIONS (รายงานและสถิติ)
            // ===========================================
            [
                'name' => 'view-reports',
                'guard_name' => 'web',
                'display_name' => 'ดูรายงาน',
                'description' => 'ดูรายงานทั่วไป',
                'category' => 'Reports & Analytics'
            ],
            [
                'name' => 'view-notification-analytics',
                'guard_name' => 'web',
                'display_name' => 'ดูสถิติการแจ้งเตือน',
                'description' => 'ดูสถิติและการวิเคราะห์การแจ้งเตือน',
                'category' => 'Reports & Analytics'
            ],
            [
                'name' => 'export-reports',
                'guard_name' => 'web',
                'display_name' => 'ส่งออกรายงาน',
                'description' => 'ส่งออกรายงานเป็นไฟล์ (PDF/Excel)',
                'category' => 'Reports & Analytics'
            ],
            [
                'name' => 'export-own-notifications',
                'guard_name' => 'web',
                'display_name' => 'ส่งออกการแจ้งเตือนตนเอง',
                'description' => 'ส่งออกการแจ้งเตือนของตนเอง',
                'category' => 'Reports & Analytics'
            ],
            [
                'name' => 'export-notifications',
                'guard_name' => 'web',
                'display_name' => 'ส่งออกการแจ้งเตือน',
                'description' => 'ส่งออกข้อมูลการแจ้งเตือนทั้งหมด',
                'category' => 'Reports & Analytics'
            ],

            // ===========================================
            // LOGGING & MONITORING PERMISSIONS (การบันทึกและติดตาม)
            // ===========================================
            [
                'name' => 'view-notification-logs',
                'guard_name' => 'web',
                'display_name' => 'ดู Logs การแจ้งเตือน',
                'description' => 'ดู logs การส่งการแจ้งเตือน',
                'category' => 'Logging & Monitoring'
            ],
            [
                'name' => 'view-system-logs',
                'guard_name' => 'web',
                'display_name' => 'ดู System Logs',
                'description' => 'ดู System Logs ทั่วไป',
                'category' => 'Logging & Monitoring'
            ],
            [
                'name' => 'view-activity-logs',
                'guard_name' => 'web',
                'display_name' => 'ดูประวัติการใช้งาน',
                'description' => 'ดูประวัติการใช้งาน (Activity Logs)',
                'category' => 'Logging & Monitoring'
            ],
            [
                'name' => 'view-api-logs',
                'guard_name' => 'web',
                'display_name' => 'ดู API Logs',
                'description' => 'ดู API Usage Logs',
                'category' => 'Logging & Monitoring'
            ],
            [
                'name' => 'export-logs',
                'guard_name' => 'web',
                'display_name' => 'ส่งออก Logs',
                'description' => 'ส่งออก Log files',
                'category' => 'Logging & Monitoring'
            ],
            [
                'name' => 'delete-activity-logs',
                'guard_name' => 'web',
                'display_name' => 'ลบประวัติกิจกรรม',
                'description' => 'ลบประวัติการใช้งาน',
                'category' => 'Logging & Monitoring'
            ],

            // ===========================================
            // SYSTEM CONFIGURATION PERMISSIONS (การตั้งค่าระบบ)
            // ===========================================
            [
                'name' => 'view-dashboard',
                'guard_name' => 'web',
                'display_name' => 'ดู Dashboard',
                'description' => 'เข้าถึง Dashboard หลัก',
                'category' => 'System Configuration'
            ],
            [
                'name' => 'manage-notification-settings',
                'guard_name' => 'web',
                'display_name' => 'จัดการการตั้งค่าการแจ้งเตือน',
                'description' => 'จัดการการตั้งค่าระบบการแจ้งเตือน',
                'category' => 'System Configuration'
            ],
            [
                'name' => 'manage-notification-preferences',
                'guard_name' => 'web',
                'display_name' => 'จัดการค่าตั้งส่วนตัว',
                'description' => 'จัดการการตั้งค่าการแจ้งเตือนส่วนตัว',
                'category' => 'System Configuration'
            ],
            [
                'name' => 'test-notification-services',
                'guard_name' => 'web',
                'display_name' => 'ทดสอบบริการแจ้งเตือน',
                'description' => 'ทดสอบบริการการแจ้งเตือน (Teams, Email)',
                'category' => 'System Configuration'
            ],
            [
                'name' => 'view-system-health',
                'guard_name' => 'web',
                'display_name' => 'ดูสถานะระบบ',
                'description' => 'ดูสถานะความแข็งแรงของระบบ',
                'category' => 'System Configuration'
            ],
            [
                'name' => 'manage-system',
                'guard_name' => 'web',
                'display_name' => 'จัดการระบบ',
                'description' => 'จัดการระบบทั่วไป',
                'category' => 'System Configuration'
            ],
            [
                'name' => 'manage-system-settings',
                'guard_name' => 'web',
                'display_name' => 'จัดการการตั้งค่าระบบ',
                'description' => 'จัดการการตั้งค่าระบบทั่วไป',
                'category' => 'System Configuration'
            ],
            [
                'name' => 'system-maintenance',
                'guard_name' => 'web',
                'display_name' => 'บำรุงรักษาระบบ',
                'description' => 'บำรุงรักษาระบบ (ล้างแคช, รีสตาร์ท queue)',
                'category' => 'System Configuration'
            ],

            // ===========================================
            // LDAP INTEGRATION PERMISSIONS (การเชื่อมต่อ LDAP)
            // ===========================================
            [
                'name' => 'manage-ldap',
                'guard_name' => 'web',
                'display_name' => 'จัดการ LDAP',
                'description' => 'จัดการการเชื่อมต่อและตั้งค่า LDAP',
                'category' => 'LDAP Integration'
            ],
            [
                'name' => 'sync-ldap-users',
                'guard_name' => 'web',
                'display_name' => 'ซิงค์ผู้ใช้ LDAP',
                'description' => 'ซิงค์ข้อมูลผู้ใช้จาก LDAP',
                'category' => 'LDAP Integration'
            ],
            [
                'name' => 'test-ldap-connection',
                'guard_name' => 'web',
                'display_name' => 'ทดสอบการเชื่อมต่อ LDAP',
                'description' => 'ทดสอบการเชื่อมต่อ LDAP',
                'category' => 'LDAP Integration'
            ],
            [
                'name' => 'view-ldap-logs',
                'guard_name' => 'web',
                'display_name' => 'ดู LDAP Logs',
                'description' => 'ดู LDAP Connection Logs',
                'category' => 'LDAP Integration'
            ],

            // ===========================================
            // MISCELLANEOUS PERMISSIONS (อื่นๆ)
            // ===========================================
            [
                'name' => 'report-notification-issues',
                'guard_name' => 'web',
                'display_name' => 'รายงานปัญหา',
                'description' => 'รายงานปัญหาการแจ้งเตือน',
                'category' => 'Miscellaneous'
            ],
            [
                'name' => 'view-failed-jobs',
                'guard_name' => 'web',
                'display_name' => 'ดู Failed Jobs',
                'description' => 'ดู Failed Jobs ในระบบ Queue',
                'category' => 'Miscellaneous'
            ],
            [
                'name' => 'retry-failed-jobs',
                'guard_name' => 'web',
                'display_name' => 'ลองทำ Failed Jobs ใหม่',
                'description' => 'ลองทำ Failed Jobs ใหม่',
                'category' => 'Miscellaneous'
            ],
        ];

        // Create all web permissions
        foreach ($webPermissions as $permissionData) {
            Permission::updateOrCreate(
                [
                    'name' => $permissionData['name'],
                    'guard_name' => $permissionData['guard_name']
                ],
                $permissionData
            );
        }

        $this->command->info("  ✅ Created " . count($webPermissions) . " WEB permissions");
    }

    /**
     * Create API permissions (guard_name = 'api')
     */
    private function createApiPermissions(): void
    {
        $this->command->info('🔑 Creating API permissions...');

        $apiPermissions = [
            // ===========================================
            // NOTIFICATION API PERMISSIONS
            // ===========================================
            [
                'name' => 'api:send-notifications',
                'guard_name' => 'api',
                'display_name' => 'ส่งการแจ้งเตือนผ่าน API',
                'description' => 'อนุญาตให้ส่งการแจ้งเตือนผ่าน API endpoint',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:send-bulk-notifications',
                'guard_name' => 'api',
                'display_name' => 'ส่งการแจ้งเตือนหลายรายการ',
                'description' => 'อนุญาตให้ส่งการแจ้งเตือนหลายรายการพร้อมกัน',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:schedule-notifications',
                'guard_name' => 'api',
                'display_name' => 'กำหนดเวลาการแจ้งเตือน',
                'description' => 'อนุญาตให้กำหนดเวลาส่งการแจ้งเตือนผ่าน API',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:check-notification-status',
                'guard_name' => 'api',
                'display_name' => 'ตรวจสอบสถานะการแจ้งเตือน',
                'description' => 'อนุญาตให้ตรวจสอบสถานะการส่งการแจ้งเตือน',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:view-notification-history',
                'guard_name' => 'api',
                'display_name' => 'ดูประวัติการแจ้งเตือน',
                'description' => 'อนุญาตให้ดูประวัติการแจ้งเตือนที่ส่งแล้ว',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:cancel-notifications',
                'guard_name' => 'api',
                'display_name' => 'ยกเลิกการแจ้งเตือน',
                'description' => 'อนุญาตให้ยกเลิกการแจ้งเตือนที่กำหนดไว้',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:retry-notifications',
                'guard_name' => 'api',
                'display_name' => 'ส่งการแจ้งเตือนซ้ำ',
                'description' => 'อนุญาตให้ส่งการแจ้งเตือนที่ล้มเหลวซ้ำ',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:validate-notifications',
                'guard_name' => 'api',
                'display_name' => 'ตรวจสอบการแจ้งเตือน',
                'description' => 'อนุญาตให้ตรวจสอบความถูกต้องของการแจ้งเตือน',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:preview-notifications',
                'guard_name' => 'api',
                'display_name' => 'ดูตัวอย่างการแจ้งเตือน',
                'description' => 'อนุญาตให้ดูตัวอย่างการแจ้งเตือนก่อนส่ง',
                'category' => 'API Endpoints'
            ],

            // ===========================================
            // USER/GROUP API PERMISSIONS
            // ===========================================
            [
                'name' => 'api:view-users',
                'guard_name' => 'api',
                'display_name' => 'ดูรายการผู้ใช้ผ่าน API',
                'description' => 'อนุญาตให้ดูรายการผู้ใช้จาก LDAP ผ่าน API',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:search-users',
                'guard_name' => 'api',
                'display_name' => 'ค้นหาผู้ใช้ผ่าน API',
                'description' => 'อนุญาตให้ค้นหาผู้ใช้ผ่าน API',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:view-user-details',
                'guard_name' => 'api',
                'display_name' => 'ดูรายละเอียดผู้ใช้',
                'description' => 'อนุญาตให้ดูรายละเอียดผู้ใช้รายบุคคล',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:manage-user-preferences',
                'guard_name' => 'api',
                'display_name' => 'จัดการค่าตั้งผู้ใช้',
                'description' => 'อนุญาตให้จัดการการตั้งค่าผู้ใช้ผ่าน API',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:sync-users',
                'guard_name' => 'api',
                'display_name' => 'ซิงค์ผู้ใช้',
                'description' => 'อนุญาตให้ซิงค์ข้อมูลผู้ใช้จาก LDAP',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:view-groups',
                'guard_name' => 'api',
                'display_name' => 'ดูรายการกลุ่มผ่าน API',
                'description' => 'อนุญาตให้ดูรายการกลุ่มการแจ้งเตือนผ่าน API',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:manage-groups',
                'guard_name' => 'api',
                'display_name' => 'จัดการกลุ่มผ่าน API',
                'description' => 'อนุญาตให้สร้างและจัดการกลุ่มการแจ้งเตือนผ่าน API',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:manage-group-members',
                'guard_name' => 'api',
                'display_name' => 'จัดการสมาชิกกลุ่ม',
                'description' => 'อนุญาตให้จัดการสมาชิกในกลุ่มผ่าน API',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:sync-groups',
                'guard_name' => 'api',
                'display_name' => 'ซิงค์กลุ่ม',
                'description' => 'อนุญาตให้ซิงค์สมาชิกกลุ่มจาก LDAP',
                'category' => 'API Endpoints'
            ],

            // ===========================================
            // TEMPLATE API PERMISSIONS
            // ===========================================
            [
                'name' => 'api:view-templates',
                'guard_name' => 'api',
                'display_name' => 'ดูเทมเพลตผ่าน API',
                'description' => 'อนุญาตให้ดูรายการเทมเพลตการแจ้งเตือนผ่าน API',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:use-templates',
                'guard_name' => 'api',
                'display_name' => 'ใช้เทมเพลตผ่าน API',
                'description' => 'อนุญาตให้ใช้เทมเพลตในการส่งการแจ้งเตือนผ่าน API',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:render-templates',
                'guard_name' => 'api',
                'display_name' => 'แปลงเทมเพลต',
                'description' => 'อนุญาตให้แปลงเทมเพลตเป็นข้อความ',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:preview-templates',
                'guard_name' => 'api',
                'display_name' => 'ดูตัวอย่างเทมเพลต',
                'description' => 'อนุญาตให้ดูตัวอย่างเทมเพลตพร้อมข้อมูล',
                'category' => 'API Endpoints'
            ],

            // ===========================================
            // DELIVERY TRACKING API PERMISSIONS
            // ===========================================
            [
                'name' => 'api:track-delivery',
                'guard_name' => 'api',
                'display_name' => 'ติดตามการส่ง',
                'description' => 'อนุญาตให้ติดตามสถานะการส่งการแจ้งเตือน',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:view-delivery-logs',
                'guard_name' => 'api',
                'display_name' => 'ดู Delivery Logs',
                'description' => 'อนุญาตให้ดู logs การส่งการแจ้งเตือน',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:receive-webhooks',
                'guard_name' => 'api',
                'display_name' => 'รับ Webhooks',
                'description' => 'อนุญาตให้รับข้อมูลจาก webhooks',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:view-delivery-stats',
                'guard_name' => 'api',
                'display_name' => 'ดูสถิติการส่ง',
                'description' => 'อนุญาตให้ดูสถิติการส่งการแจ้งเตือน',
                'category' => 'API Endpoints'
            ],

            // ===========================================
            // LDAP API PERMISSIONS
            // ===========================================
            [
                'name' => 'api:ldap-users',
                'guard_name' => 'api',
                'display_name' => 'ดูผู้ใช้ LDAP',
                'description' => 'อนุญาตให้ดูรายการผู้ใช้จาก LDAP',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:ldap-groups',
                'guard_name' => 'api',
                'display_name' => 'ดูกลุ่ม LDAP',
                'description' => 'อนุญาตให้ดูรายการกลุ่มจาก LDAP',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:ldap-sync',
                'guard_name' => 'api',
                'display_name' => 'ซิงค์ LDAP',
                'description' => 'อนุญาตให้ซิงค์ข้อมูลจาก LDAP',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:ldap-test',
                'guard_name' => 'api',
                'display_name' => 'ทดสอบ LDAP',
                'description' => 'อนุญาตให้ทดสอบการเชื่อมต่อ LDAP',
                'category' => 'API Endpoints'
            ],

            // ===========================================
            // SYSTEM API PERMISSIONS
            // ===========================================
            [
                'name' => 'api:health-check',
                'guard_name' => 'api',
                'display_name' => 'ตรวจสอบสถานะระบบ',
                'description' => 'อนุญาตให้ตรวจสอบสถานะการทำงานของระบบ',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:view-system-info',
                'guard_name' => 'api',
                'display_name' => 'ดูข้อมูลระบบ',
                'description' => 'อนุญาตให้ดูข้อมูลระบบ',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:view-statistics',
                'guard_name' => 'api',
                'display_name' => 'ดูสถิติการใช้งาน',
                'description' => 'อนุญาตให้ดูสถิติการใช้งาน API',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:view-queue-status',
                'guard_name' => 'api',
                'display_name' => 'ดูสถานะ Queue',
                'description' => 'อนุญาตให้ดูสถานะระบบ Queue',
                'category' => 'API Endpoints'
            ],

            // ===========================================
            // WEBHOOK API PERMISSIONS
            // ===========================================
            [
                'name' => 'api:handle-teams-webhooks',
                'guard_name' => 'api',
                'display_name' => 'จัดการ Teams Webhooks',
                'description' => 'อนุญาตให้จัดการ webhooks จาก Teams',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:handle-email-webhooks',
                'guard_name' => 'api',
                'display_name' => 'จัดการ Email Webhooks',
                'description' => 'อนุญาตให้จัดการ webhooks จาก Email',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:handle-delivery-webhooks',
                'guard_name' => 'api',
                'display_name' => 'จัดการ Delivery Webhooks',
                'description' => 'อนุญาตให้จัดการ webhooks การส่ง',
                'category' => 'API Endpoints'
            ],

            // ===========================================
            // AUTHENTICATION API PERMISSIONS
            // ===========================================
            [
                'name' => 'api:validate-key',
                'guard_name' => 'api',
                'display_name' => 'ตรวจสอบ API Key',
                'description' => 'อนุญาตให้ตรวจสอบความถูกต้องของ API Key',
                'category' => 'API Endpoints'
            ],
            [
                'name' => 'api:get-key-info',
                'guard_name' => 'api',
                'display_name' => 'ดูข้อมูล API Key',
                'description' => 'อนุญาตให้ดูข้อมูลของ API Key',
                'category' => 'API Endpoints'
            ],

            // ===========================================
            // SPECIAL API PERMISSIONS (สิทธิ์พิเศษ)
            // ===========================================
            [
                'name' => 'api:unlimited-rate',
                'guard_name' => 'api',
                'display_name' => 'ไม่จำกัด Rate Limit',
                'description' => 'ไม่มีข้อจำกัดเรื่อง Rate Limiting (ใช้สำหรับระบบสำคัญ)',
                'category' => 'Special Permissions'
            ],
            [
                'name' => 'api:priority-queue',
                'guard_name' => 'api',
                'display_name' => 'คิวความสำคัญสูง',
                'description' => 'ส่งการแจ้งเตือนผ่านคิวความสำคัญสูง',
                'category' => 'Special Permissions'
            ],
            [
                'name' => 'api:emergency-notifications',
                'guard_name' => 'api',
                'display_name' => 'การแจ้งเตือนฉุกเฉิน',
                'description' => 'ส่งการแจ้งเตือนฉุกเฉินที่ข้ามระบบควบคุมปกติ',
                'category' => 'Special Permissions'
            ],
            [
                'name' => 'api:bypass-validation',
                'guard_name' => 'api',
                'display_name' => 'ข้าม Validation',
                'description' => 'ข้ามการตรวจสอบข้อมูลสำหรับระบบที่เชื่อถือได้',
                'category' => 'Special Permissions'
            ],
            [
                'name' => 'api:admin-access',
                'guard_name' => 'api',
                'display_name' => 'สิทธิ์ Admin',
                'description' => 'สิทธิ์ระดับ Admin สำหรับ API',
                'category' => 'Special Permissions'
            ],
        ];

        // Create all API permissions
        foreach ($apiPermissions as $permissionData) {
            Permission::updateOrCreate(
                [
                    'name' => $permissionData['name'],
                    'guard_name' => $permissionData['guard_name']
                ],
                $permissionData
            );
        }

        $this->command->info("  ✅ Created " . count($apiPermissions) . " API permissions");
    }

    /**
     * Create all roles for the Smart Notification System
     */
    private function createRoles(): void
    {
        $this->command->info('👥 Creating roles...');

        $roles = [
            // ===========================================
            // SUPER ADMIN ROLE (ผู้ดูแลระบบสูงสุด)
            // ===========================================
            [
                'name' => 'super-admin',
                'guard_name' => 'web',
                'display_name' => 'ผู้ดูแลระบบสูงสุด',
                'description' => 'ผู้ดูแลระบบระดับสูงสุดที่มีสิทธิ์ทุกอย่างในระบบ รวมถึงการจัดการ roles และ permissions'
            ],

            // ===========================================
            // ADMIN ROLE (ผู้ดูแลระบบ)
            // ===========================================
            [
                'name' => 'admin',
                'guard_name' => 'web',
                'display_name' => 'ผู้ดูแลระบบ',
                'description' => 'ผู้ดูแลระบบที่มีสิทธิ์เต็มในการจัดการทุกอย่าง ยกเว้นการจัดการ roles และ permissions'
            ],

            // ===========================================
            // NOTIFICATION ADMIN ROLE (ผู้ดูแลระบบการแจ้งเตือน)
            // ===========================================
            [
                'name' => 'notification-admin',
                'guard_name' => 'web',
                'display_name' => 'ผู้ดูแลระบบการแจ้งเตือน',
                'description' => 'ผู้ดูแลระบบการแจ้งเตือนที่มีสิทธิ์เต็มในการจัดการการแจ้งเตือน เทมเพลต กลุ่ม และ API'
            ],

            // ===========================================
            // NOTIFICATION MANAGER ROLE (ผู้จัดการการแจ้งเตือน)
            // ===========================================
            [
                'name' => 'notification-manager',
                'guard_name' => 'web',
                'display_name' => 'ผู้จัดการการแจ้งเตือน',
                'description' => 'สามารถสร้างและจัดการการแจ้งเตือน เทมเพลต และกลุ่มผู้รับ แต่ไม่สามารถจัดการระบบ'
            ],

            // ===========================================
            // API MANAGER ROLE (ผู้จัดการ API)
            // ===========================================
            [
                'name' => 'api-manager',
                'guard_name' => 'web',
                'display_name' => 'ผู้จัดการ API',
                'description' => 'สามารถสร้างและจัดการ API keys การเชื่อมต่อภายนอก และดูสถิติการใช้งาน API'
            ],

            // ===========================================
            // USER MANAGER ROLE (ผู้จัดการผู้ใช้)
            // ===========================================
            [
                'name' => 'user-manager',
                'guard_name' => 'web',
                'display_name' => 'ผู้จัดการผู้ใช้',
                'description' => 'สามารถจัดการผู้ใช้ กลุ่ม และสิทธิ์ของพวกเขา รวมถึงการซิงค์กับ LDAP'
            ],

            // ===========================================
            // IT SUPPORT ROLE (ฝ่ายสนับสนุน IT)
            // ===========================================
            [
                'name' => 'it-support',
                'guard_name' => 'web',
                'display_name' => 'ฝ่ายสนับสนุน IT',
                'description' => 'เจ้าหน้าที่ IT ที่สามารถดู logs ตรวจสอบสถานะระบบ และแก้ไขปัญหาเบื้องต้น'
            ],

            // ===========================================
            // SYSTEM ADMIN ROLE (ผู้ดูแลระบบเทคนิค)
            // ===========================================
            [
                'name' => 'system-admin',
                'guard_name' => 'web',
                'display_name' => 'ผู้ดูแลระบบเทคนิค',
                'description' => 'ผู้ดูแลระบบด้านเทคนิค สามารถจัดการการตั้งค่าระบบ LDAP และบำรุงรักษาระบบ'
            ],

            // ===========================================
            // BASIC USER ROLE (ผู้ใช้ทั่วไป)
            // ===========================================
            [
                'name' => 'user',
                'guard_name' => 'web',
                'display_name' => 'ผู้ใช้ทั่วไป',
                'description' => 'ผู้ใช้งานทั่วไปที่สามารถรับการแจ้งเตือนและจัดการการตั้งค่าส่วนตัว'
            ],

            // ===========================================
            // API USER ROLE (ผู้ใช้งาน API)
            // ===========================================
            [
                'name' => 'api-user',
                'guard_name' => 'web',
                'display_name' => 'ผู้ใช้งาน API',
                'description' => 'ผู้ใช้สำหรับระบบภายนอกที่เรียกใช้งาน API เพื่อส่งการแจ้งเตือน'
            ],
        ];

        // Create all roles
        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                [
                    'name' => $roleData['name'],
                    'guard_name' => $roleData['guard_name']
                ],
                $roleData
            );
        }

        $this->command->info("  ✅ Created " . count($roles) . " roles");
    }

    /**
     * Assign permissions to roles based on their responsibilities
     */
    private function assignPermissionsToRoles(): void
    {
        $this->command->info('🔗 Assigning permissions to roles...');

        // ===========================================
        // SUPER ADMIN - ทุกสิทธิ์ในระบบ (WEB only)
        // ===========================================
        $superAdmin = Role::where('name', 'super-admin')->first();
        $allWebPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();
        $superAdmin->syncPermissions($allWebPermissions);
        $this->command->info("  ✅ Super Admin: " . count($allWebPermissions) . " permissions (ALL WEB)");

        // ===========================================
        // ADMIN - ทุกสิทธิ์ยกเว้นการจัดการ roles/permissions
        // ===========================================
        $admin = Role::where('name', 'admin')->first();
        $adminPermissions = Permission::where('guard_name', 'web')
            ->whereNotIn('name', [
                'view-roles', 'create-roles', 'edit-roles', 'delete-roles',
                'view-permissions', 'create-permissions', 'edit-permissions', 'delete-permissions',
                'assign-roles', 'assign-permissions', 'view-permission-matrix'
            ])
            ->pluck('name')->toArray();
        $admin->syncPermissions($adminPermissions);
        $this->command->info("  ✅ Admin: " . count($adminPermissions) . " permissions");

        // ===========================================
        // NOTIFICATION ADMIN - การจัดการการแจ้งเตือนเต็มรูปแบบ
        // ===========================================
        $notificationAdmin = Role::where('name', 'notification-admin')->first();
        $notificationAdminPermissions = [
            // Dashboard
            'view-dashboard',
            
            // Notification Management (Full)
            'view-all-notifications', 'view-received-notifications', 'create-notifications', 
            'edit-notifications', 'delete-notifications', 'send-notifications', 
            'cancel-notifications', 'resend-notifications', 'duplicate-notifications', 
            'manage-notifications', 'bulk-notification-actions', 'schedule-notifications',
            
            // Template Management (Full)
            'view-notification-templates', 'create-notification-templates',
            'edit-notification-templates', 'delete-notification-templates',
            'duplicate-templates', 'export-templates', 'import-templates',
            
            // Group Management (Full)
            'view-notification-groups', 'create-notification-groups',
            'edit-notification-groups', 'delete-notification-groups',
            'manage-group-members', 'sync-groups', 'export-groups',
            
            // API Management (Full)
            'view-api-keys', 'create-api-keys', 'edit-api-keys', 'delete-api-keys',
            'regenerate-api-keys', 'view-api-usage', 'manage-api-rate-limits',
            'manage-api-keys', 'view-api-usage-reports',
            
            // Reports & Analytics
            'view-reports', 'view-notification-analytics', 'export-reports', 
            'export-notifications', 'export-own-notifications',
            
            // Logging & Monitoring
            'view-notification-logs', 'view-activity-logs', 'view-api-logs', 'export-logs',
            
            // System Configuration
            'manage-notification-settings', 'test-notification-services', 'view-system-health',
            
            // Users (View only for notification purposes)
            'view-users',
            
            // Miscellaneous
            'view-failed-jobs', 'retry-failed-jobs',
        ];
        $notificationAdmin->syncPermissions($notificationAdminPermissions);
        $this->command->info("  ✅ Notification Admin: " . count($notificationAdminPermissions) . " permissions");

        // ===========================================
        // NOTIFICATION MANAGER - การจัดการการแจ้งเตือนพื้นฐาน
        // ===========================================
        $notificationManager = Role::where('name', 'notification-manager')->first();
        $notificationManagerPermissions = [
            // Dashboard
            'view-dashboard',
            
            // Notification Management (No delete)
            'view-all-notifications', 'view-received-notifications', 'create-notifications', 
            'edit-notifications', 'send-notifications', 'cancel-notifications', 
            'resend-notifications', 'duplicate-notifications', 'bulk-notification-actions',
            'schedule-notifications',
            
            // Template Management (No delete)
            'view-notification-templates', 'create-notification-templates',
            'edit-notification-templates', 'duplicate-templates', 'export-templates',
            
            // Group Management (No delete)
            'view-notification-groups', 'create-notification-groups',
            'edit-notification-groups', 'manage-group-members', 'export-groups',
            
            // Reports & Analytics (Limited)
            'view-reports', 'view-notification-analytics', 'export-reports',
            'export-own-notifications',
            
            // Logging (View only)
            'view-notification-logs', 'view-activity-logs',
            
            // System (Test only)
            'test-notification-services',
            
            // Users (View only)
            'view-users',
        ];
        $notificationManager->syncPermissions($notificationManagerPermissions);
        $this->command->info("  ✅ Notification Manager: " . count($notificationManagerPermissions) . " permissions");

        // ===========================================
        // API MANAGER - การจัดการ API และการเชื่อมต่อ
        // ===========================================
        $apiManager = Role::where('name', 'api-manager')->first();
        $apiManagerPermissions = [
            // Dashboard
            'view-dashboard',
            
            // API Management (Full)
            'view-api-keys', 'create-api-keys', 'edit-api-keys', 'delete-api-keys',
            'regenerate-api-keys', 'view-api-usage', 'manage-api-rate-limits',
            'manage-api-keys', 'view-api-usage-reports',
            
            // Notification (Basic for testing)
            'view-all-notifications', 'view-received-notifications', 'create-notifications', 
            'send-notifications',
            
            // Logging (API focused)
            'view-api-logs', 'view-notification-logs', 'export-logs',
            
            // Reports (API focused)
            'view-reports', 'export-reports',
            
            // System (Testing)
            'test-notification-services', 'view-system-health',
            
            // Users (View for API setup)
            'view-users',
            
            // Groups (View for API)
            'view-notification-groups',
        ];
        $apiManager->syncPermissions($apiManagerPermissions);
        $this->command->info("  ✅ API Manager: " . count($apiManagerPermissions) . " permissions");

        // ===========================================
        // USER MANAGER - การจัดการผู้ใช้และกลุ่ม
        // ===========================================
        $userManager = Role::where('name', 'user-manager')->first();
        $userManagerPermissions = [
            // Dashboard
            'view-dashboard',
            
            // User Management (Full)
            'view-users', 'create-users', 'edit-users', 'delete-users', 'manage-users',
            'export-users', 'import-users', 'manage-user-roles', 'manage-user-permissions',
            'view-user-permissions', 'assign-user-permissions', 'manage-user-preferences',
            
            // Group Management (Full)
            'view-notification-groups', 'create-notification-groups',
            'edit-notification-groups', 'delete-notification-groups',
            'manage-group-members', 'sync-groups', 'export-groups',
            
            // LDAP Management
            'manage-ldap', 'sync-ldap-users', 'test-ldap-connection', 'view-ldap-logs',
            
            // Role Assignment (Limited)
            'view-roles', 'assign-roles',
            
            // Reports (User focused)
            'view-reports', 'export-reports',
            
            // Logging (User activity)
            'view-activity-logs', 'export-logs',
            
            // Notifications (View for user management)
            'view-all-notifications', 'view-received-notifications',
        ];
        $userManager->syncPermissions($userManagerPermissions);
        $this->command->info("  ✅ User Manager: " . count($userManagerPermissions) . " permissions");

        // ===========================================
        // IT SUPPORT - การสนับสนุนและแก้ไขปัญหา
        // ===========================================
        $itSupport = Role::where('name', 'it-support')->first();
        $itSupportPermissions = [
            // Dashboard
            'view-dashboard',
            
            // View permissions for troubleshooting
            'view-users', 'view-all-notifications', 'view-received-notifications', 
            'view-notification-groups', 'view-notification-templates', 'view-api-keys',
            
            // Logging (Full access for troubleshooting)
            'view-notification-logs', 'view-system-logs', 'view-activity-logs',
            'view-api-logs', 'view-ldap-logs', 'export-logs',
            
            // System monitoring
            'view-system-health', 'test-notification-services', 'test-ldap-connection',
            
            // Failed jobs management
            'view-failed-jobs', 'retry-failed-jobs',
            
            // Reports for analysis
            'view-reports', 'view-notification-analytics', 'export-reports',
            
            // Basic sync operations
            'sync-groups', 'sync-ldap-users',
            
            // Issue reporting
            'report-notification-issues',
        ];
        $itSupport->syncPermissions($itSupportPermissions);
        $this->command->info("  ✅ IT Support: " . count($itSupportPermissions) . " permissions");

        // ===========================================
        // SYSTEM ADMIN - การจัดการระบบเทคนิค
        // ===========================================
        $systemAdmin = Role::where('name', 'system-admin')->first();
        $systemAdminPermissions = [
            // Dashboard
            'view-dashboard',
            
            // System Configuration (Full)
            'manage-system', 'manage-system-settings', 'system-maintenance', 
            'manage-notification-settings', 'view-system-health', 'test-notification-services',
            
            // LDAP Management (Full)
            'manage-ldap', 'sync-ldap-users', 'test-ldap-connection', 'view-ldap-logs',
            
            // Logging (System focused)
            'view-system-logs', 'view-notification-logs', 'view-activity-logs',
            'view-api-logs', 'export-logs',
            
            // Failed jobs and maintenance
            'view-failed-jobs', 'retry-failed-jobs',
            
            // View access for system understanding
            'view-users', 'view-all-notifications', 'view-received-notifications', 
            'view-api-keys', 'view-api-usage',
            
            // Sync operations
            'sync-groups', 'sync-ldap-users',
            
            // Reports for system analysis
            'view-reports', 'export-reports',
        ];
        $systemAdmin->syncPermissions($systemAdminPermissions);
        $this->command->info("  ✅ System Admin: " . count($systemAdminPermissions) . " permissions");

        // ===========================================
        // USER - ผู้ใช้ทั่วไป
        // ===========================================
        $user = Role::where('name', 'user')->first();
        $userPermissions = [
            // Dashboard (Basic)
            'view-dashboard',
            
            // Personal notifications
            'view-received-notifications', 'read-notifications', 'archive-notifications',
            'export-own-notifications',
            
            // Personal preferences
            'manage-notification-preferences',
            
            // Issue reporting
            'report-notification-issues',
        ];
        $user->syncPermissions($userPermissions);
        $this->command->info("  ✅ User: " . count($userPermissions) . " permissions");

        // ===========================================
        // API USER - สำหรับระบบภายนอก
        // ===========================================
        $apiUser = Role::where('name', 'api-user')->first();
        $apiUserPermissions = [
            // API operations
            'create-notifications', 'view-all-notifications', 'view-received-notifications', 
            'view-notification-logs',
            
            // Group management for API
            'view-notification-groups', 'view-users',
        ];
        $apiUser->syncPermissions($apiUserPermissions);
        $this->command->info("  ✅ API User: " . count($apiUserPermissions) . " permissions");
    }

    /**
     * Show summary of created permissions and roles
     */
    private function showSummary(): void
    {
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('==========================================');
        
        $totalWebPermissions = Permission::where('guard_name', 'web')->count();
        $totalApiPermissions = Permission::where('guard_name', 'api')->count();
        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        
        $this->command->info("Total WEB Permissions: {$totalWebPermissions}");
        $this->command->info("Total API Permissions: {$totalApiPermissions}");
        $this->command->info("Total Permissions: {$totalPermissions}");
        $this->command->info("Total Roles: {$totalRoles}");
        $this->command->info('');
        
        $this->command->info('🎯 Roles Overview:');
        $roles = Role::all();
        foreach ($roles as $role) {
            $permissionCount = $role->permissions()->count();
            $this->command->info("  • {$role->display_name} ({$role->name}): {$permissionCount} permissions");
        }
        
        $this->command->info('');
        $this->command->info('📋 Permission Categories (WEB):');
        $webCategories = Permission::where('guard_name', 'web')
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort();
            
        foreach ($webCategories as $category) {
            $count = Permission::where('guard_name', 'web')->where('category', $category)->count();
            $this->command->info("  • {$category}: {$count} permissions");
        }

        $this->command->info('');
        $this->command->info('📋 Permission Categories (API):');
        $apiCategories = Permission::where('guard_name', 'api')
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort();
            
        foreach ($apiCategories as $category) {
            $count = Permission::where('guard_name', 'api')->where('category', $category)->count();
            $this->command->info("  • {$category}: {$count} permissions");
        }
        
        $this->command->info('');
        $this->command->info('🚀 Smart Notification System permissions setup completed successfully!');
        $this->command->info('');
        $this->command->info('Next steps:');
        $this->command->info('1. Run: php artisan db:seed --class=DefaultUserSeeder');
        $this->command->info('2. Create API Keys and assign API permissions');
        $this->command->info('3. Assign web roles to users');
        $this->command->info('4. Test both web and API permissions');
        
        $this->command->info('');
        $this->command->info('🔑 API Permission Usage:');
        $this->command->info('- API Keys can be assigned permissions with guard_name = "api"');
        $this->command->info('- Web users get permissions with guard_name = "web"');
        $this->command->info('- Use ApiKey->hasPermission("api:send-notifications") for API validation');
        $this->command->info('- Use auth()->user()->can("view-notifications") for web validation');
    }
}