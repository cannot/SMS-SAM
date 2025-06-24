<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view-users' => 'ดูข้อมูลผู้ใช้',
            'create-users' => 'สร้างผู้ใช้ใหม่',
            'edit-users' => 'แก้ไขข้อมูลผู้ใช้',
            'delete-users' => 'ลบผู้ใช้',
            'manage-users' => 'จัดการผู้ใช้',

            // Notification management
            'view-notifications' => 'ดูการแจ้งเตือน',
            'create-notifications' => 'สร้างการแจ้งเตือน',
            'edit-notifications' => 'แก้ไขการแจ้งเตือน',
            'delete-notifications' => 'ลบการแจ้งเตือน',
            'send-notifications' => 'ส่งการแจ้งเตือน',

            // Notification templates
            'view-templates' => 'ดูเทมเพลตการแจ้งเตือน',
            'create-templates' => 'สร้างเทมเพลตการแจ้งเตือน',
            'edit-templates' => 'แก้ไขเทมเพลตการแจ้งเตือน',
            'delete-templates' => 'ลบเทมเพลตการแจ้งเตือน',

            // Notification groups
            'view-groups' => 'ดูกลุ่มการแจ้งเตือน',
            'create-groups' => 'สร้างกลุ่มการแจ้งเตือน',
            'edit-groups' => 'แก้ไขกลุ่มการแจ้งเตือน',
            'delete-groups' => 'ลบกลุ่มการแจ้งเตือน',
            'manage-group-members' => 'จัดการสมาชิกในกลุ่ม',

            // API management
            'view-api-keys' => 'ดู API Keys',
            'create-api-keys' => 'สร้าง API Keys',
            'edit-api-keys' => 'แก้ไข API Keys',
            'delete-api-keys' => 'ลบ API Keys',
            'view-api-usage' => 'ดูการใช้งาน API',

            // Reports and logs
            'view-reports' => 'ดูรายงาน',
            'export-reports' => 'ส่งออกรายงาน',
            'view-logs' => 'ดู System Logs',
            'view-activity-logs' => 'ดูประวัติการใช้งาน',

            // System management
            'view-dashboard' => 'ดู Dashboard',
            'system-settings' => 'จัดการการตั้งค่าระบบ',
            'system-maintenance' => 'บำรุงรักษาระบบ',

            // LDAP management (เพิ่มใหม่)
            'manage-ldap' => 'จัดการ LDAP',
            'manage-user-roles' => 'จัดการสิทธิ์ผู้ใช้',
            'manage-roles-permissions' => 'จัดการสิทธิ์และบทบาท',
            'export-users' => 'ส่งออกข้อมูลผู้ใช้',
            'import-users' => 'นำเข้าข้อมูลผู้ใช้',
            'export-permissions' => 'ส่งออกข้อมูลสิทธิ์',
            'import-permissions' => 'นำเข้าข้อมูลสิทธิ์',
            'view-permission-matrix' => 'ดูตารางสิทธิ์',
            'export-roles' => 'ส่งออกข้อมูลบทบาท',
            'import-roles' => 'นำเข้าข้อมูลบทบาท',
            'view-roles' => 'ดูบทบาท',
            'create-roles' => 'สร้างบทบาท',
            'edit-roles' => 'แก้ไขบทบาท',
            'delete-roles' => 'ลบบทบาท',
            'assign-roles' => 'กำหนดบทบาท',
            'view-permissions' => 'ดูสิทธิ์',
            'create-permissions' => 'สร้างสิทธิ์',
            'edit-permissions' => 'แก้ไขสิทธิ์',
            'delete-permissions' => 'ลบสิทธิ์',
            'assign-permissions' => 'กำหนดสิทธิ์',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $displayName]
            );
        }

        // Create roles with display names
        $roles = [
            'admin' => [
                'display_name' => 'ผู้ดูแลระบบ',
                'description' => 'ผู้ดูแลระบบที่มีสิทธิ์เต็มในการจัดการทุกอย่าง',
                'permissions' => array_keys($permissions), // All permissions
            ],
            'notification_manager' => [
                'display_name' => 'ผู้จัดการการแจ้งเตือน',
                'description' => 'สามารถสร้างและจัดการการแจ้งเตือนและเทมเพลต',
                'permissions' => [
                    'view-dashboard',
                    'view-notifications', 'create-notifications', 'edit-notifications', 'delete-notifications', 'send-notifications',
                    'view-templates', 'create-templates', 'edit-templates', 'delete-templates',
                    'view-groups', 'create-groups', 'edit-groups', 'delete-groups', 'manage-group-members',
                    'view-users',
                    'view-reports', 'export-reports',
                    'view-activity-logs',
                ],
            ],
            'user' => [
                'display_name' => 'ผู้ใช้ทั่วไป',
                'description' => 'ผู้ใช้ทั่วไปที่สามารถรับการแจ้งเตือนได้',
                'permissions' => [
                    'view-dashboard',
                    'view-notifications',
                ],
            ],
            'it_support' => [
                'display_name' => 'ฝ่ายสนับสนุน IT',
                'description' => 'เจ้าหน้าที่ IT ที่สามารถดู logs และสถานะระบบ',
                'permissions' => [
                    'view-dashboard',
                    'view-notifications',
                    'view-users',
                    'view-logs',
                    'view-activity-logs',
                    'view-reports',
                ],
            ],
            'api_manager' => [
                'display_name' => 'ผู้จัดการ API',
                'description' => 'สามารถสร้างและจัดการ API keys และการเชื่อมต่อภายนอก',
                'permissions' => [
                    'view-dashboard',
                    'view-api-keys', 'create-api-keys', 'edit-api-keys', 'delete-api-keys',
                    'view-api-usage',
                    'view-reports',
                    'view-activity-logs',
                ],
            ],
            'user_manager' => [
                'display_name' => 'ผู้จัดการผู้ใช้',
                'description' => 'สามารถจัดการผู้ใช้และสิทธิ์ของพวกเขา',
                'permissions' => [
                    'view-dashboard',
                    'view-users', 'create-users', 'edit-users', 'delete-users', 'manage-users',
                    'view-groups', 'manage-group-members',
                    'view-reports',
                    'view-activity-logs',
                    'manage-ldap',
                    'manage-user-roles',
                    'export-users',
                    'import-users',
                ],
            ],
            'super-admin' => [
                'display_name' => 'ผู้ดูแลระบบสูงสุด',
                'description' => 'ผู้ดูแลระบบระดับสูงสุดที่มีสิทธิ์ทุกอย่างรวมถึงการจัดการ roles และ permissions',
                'permissions' => array_keys($permissions), // All permissions
            ],
        ];

        foreach ($roles as $name => $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'] ?? null,
                ]
            );

            // Assign permissions to role
            $role->syncPermissions($roleData['permissions']);
        }

        $this->command->info('✅ สร้าง Roles และ Permissions สำเร็จแล้ว!');
        
        foreach ($roles as $name => $roleData) {
            $permissionCount = count($roleData['permissions']);
            $this->command->info("  - {$roleData['display_name']} ({$permissionCount} สิทธิ์)");
        }
    }
}