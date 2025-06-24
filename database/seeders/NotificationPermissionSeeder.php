<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NotificationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear Laravel cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // User Permissions - สำหรับผู้ใช้ทั่วไป
        $userPermissions = [
            [
                'name' => 'view-received-notifications',
                'guard_name' => 'web',
                'description' => 'ดูการแจ้งเตือนที่ได้รับ',
                'category' => 'User Notifications'
            ],
            [
                'name' => 'read-notifications',
                'guard_name' => 'web',
                'description' => 'ทำเครื่องหมายอ่าน/ไม่อ่าน การแจ้งเตือน',
                'category' => 'User Notifications'
            ],
            [
                'name' => 'manage-notification-preferences',
                'guard_name' => 'web',
                'description' => 'จัดการการตั้งค่าการแจ้งเตือนส่วนตัว',
                'category' => 'User Notifications'
            ],
            [
                'name' => 'archive-notifications',
                'guard_name' => 'web',
                'description' => 'เก็บถาวรและกู้คืนการแจ้งเตือน',
                'category' => 'User Notifications'
            ],
            [
                'name' => 'export-own-notifications',
                'guard_name' => 'web',
                'description' => 'ส่งออกการแจ้งเตือนของตนเอง',
                'category' => 'User Notifications'
            ],
            [
                'name' => 'report-notification-issues',
                'guard_name' => 'web',
                'description' => 'รายงานปัญหาการแจ้งเตือน',
                'category' => 'User Notifications'
            ]
        ];

        // Admin Permissions - สำหรับผู้ดูแลระบบ
        $adminPermissions = [
            [
                'name' => 'view-all-notifications',
                'guard_name' => 'web',
                'description' => 'ดูการแจ้งเตือนทั้งหมดในระบบ',
                'category' => 'Admin Notifications'
            ],
            [
                'name' => 'create-notifications',
                'guard_name' => 'web',
                'description' => 'สร้างการแจ้งเตือนใหม่',
                'category' => 'Admin Notifications'
            ],
            [
                'name' => 'edit-notifications',
                'guard_name' => 'web',
                'description' => 'แก้ไขการแจ้งเตือน',
                'category' => 'Admin Notifications'
            ],
            [
                'name' => 'delete-notifications',
                'guard_name' => 'web',
                'description' => 'ลบการแจ้งเตือน',
                'category' => 'Admin Notifications'
            ],
            [
                'name' => 'manage-notifications',
                'guard_name' => 'web',
                'description' => 'จัดการการแจ้งเตือนทั่วไป',
                'category' => 'Admin Notifications'
            ],
            [
                'name' => 'cancel-notifications',
                'guard_name' => 'web',
                'description' => 'ยกเลิกการแจ้งเตือนที่กำหนดไว้',
                'category' => 'Admin Notifications'
            ],
            [
                'name' => 'resend-notifications',
                'guard_name' => 'web',
                'description' => 'ส่งการแจ้งเตือนใหม่',
                'category' => 'Admin Notifications'
            ],
            [
                'name' => 'view-notification-logs',
                'guard_name' => 'web',
                'description' => 'ดู logs การส่งการแจ้งเตือน',
                'category' => 'Admin Notifications'
            ],
            [
                'name' => 'view-notification-analytics',
                'guard_name' => 'web',
                'description' => 'ดูสถิติและการวิเคราะห์การแจ้งเตือน',
                'category' => 'Admin Notifications'
            ],
            [
                'name' => 'export-notifications',
                'guard_name' => 'web',
                'description' => 'ส่งออกข้อมูลการแจ้งเตือน',
                'category' => 'Admin Notifications'
            ],
            [
                'name' => 'bulk-notification-actions',
                'guard_name' => 'web',
                'description' => 'การดำเนินการแบบกลุ่ม',
                'category' => 'Admin Notifications'
            ],
            [
                'name' => 'duplicate-notifications',
                'guard_name' => 'web',
                'description' => 'ทำสำเนาการแจ้งเตือน',
                'category' => 'Admin Notifications'
            ]
        ];

        // Template Management Permissions
        $templatePermissions = [
            [
                'name' => 'view-notification-templates',
                'guard_name' => 'web',
                'description' => 'ดูเทมเพลตการแจ้งเตือน',
                'category' => 'Template Management'
            ],
            [
                'name' => 'create-notification-templates',
                'guard_name' => 'web',
                'description' => 'สร้างเทมเพลตการแจ้งเตือน',
                'category' => 'Template Management'
            ],
            [
                'name' => 'edit-notification-templates',
                'guard_name' => 'web',
                'description' => 'แก้ไขเทมเพลตการแจ้งเตือน',
                'category' => 'Template Management'
            ],
            [
                'name' => 'delete-notification-templates',
                'guard_name' => 'web',
                'description' => 'ลบเทมเพลตการแจ้งเตือน',
                'category' => 'Template Management'
            ]
        ];

        // Group Management Permissions
        $groupPermissions = [
            [
                'name' => 'view-notification-groups',
                'guard_name' => 'web',
                'description' => 'ดูกลุ่มการแจ้งเตือน',
                'category' => 'Group Management'
            ],
            [
                'name' => 'create-notification-groups',
                'guard_name' => 'web',
                'description' => 'สร้างกลุ่มการแจ้งเตือน',
                'category' => 'Group Management'
            ],
            [
                'name' => 'edit-notification-groups',
                'guard_name' => 'web',
                'description' => 'แก้ไขกลุ่มการแจ้งเตือน',
                'category' => 'Group Management'
            ],
            [
                'name' => 'delete-notification-groups',
                'guard_name' => 'web',
                'description' => 'ลบกลุ่มการแจ้งเตือน',
                'category' => 'Group Management'
            ],
            [
                'name' => 'manage-group-members',
                'guard_name' => 'web',
                'description' => 'จัดการสมาชิกในกลุ่ม',
                'category' => 'Group Management'
            ]
        ];

        // API Management Permissions
        $apiPermissions = [
            [
                'name' => 'view-api-keys',
                'guard_name' => 'web',
                'description' => 'ดู API Keys',
                'category' => 'API Management'
            ],
            [
                'name' => 'create-api-keys',
                'guard_name' => 'web',
                'description' => 'สร้าง API Keys',
                'category' => 'API Management'
            ],
            [
                'name' => 'edit-api-keys',
                'guard_name' => 'web',
                'description' => 'แก้ไข API Keys',
                'category' => 'API Management'
            ],
            [
                'name' => 'delete-api-keys',
                'guard_name' => 'web',
                'description' => 'ลบ API Keys',
                'category' => 'API Management'
            ],
            [
                'name' => 'regenerate-api-keys',
                'guard_name' => 'web',
                'description' => 'สร้าง API Keys ใหม่',
                'category' => 'API Management'
            ],
            [
                'name' => 'view-api-usage',
                'guard_name' => 'web',
                'description' => 'ดูการใช้งาน API',
                'category' => 'API Management'
            ]
        ];

        // System Configuration Permissions
        $systemPermissions = [
            [
                'name' => 'manage-notification-settings',
                'guard_name' => 'web',
                'description' => 'จัดการการตั้งค่าระบบการแจ้งเตือน',
                'category' => 'System Configuration'
            ],
            [
                'name' => 'test-notification-services',
                'guard_name' => 'web',
                'description' => 'ทดสอบบริการการแจ้งเตือน',
                'category' => 'System Configuration'
            ],
            [
                'name' => 'view-system-health',
                'guard_name' => 'web',
                'description' => 'ดูสถานะระบบการแจ้งเตือน',
                'category' => 'System Configuration'
            ]
        ];

        // Create all permissions
        $allPermissions = array_merge(
            $userPermissions,
            $adminPermissions,
            $templatePermissions,
            $groupPermissions,
            $apiPermissions,
            $systemPermissions
        );

        foreach ($allPermissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                $permission
            );
        }

        // Create Roles and assign permissions
        $this->createRoles();

        $this->command->info('Notification permissions created successfully!');
    }

    /**
     * Create roles and assign permissions
     */
    private function createRoles(): void
    {
        // Create Super Admin Role
        $superAdminRole = Role::updateOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            ['description' => 'ผู้ดูแลระบบสูงสุด - มีสิทธิ์ทั้งหมด']
        );

        // Create Notification Admin Role
        $notificationAdminRole = Role::updateOrCreate(
            ['name' => 'notification-admin', 'guard_name' => 'web'],
            ['description' => 'ผู้ดูแลระบบการแจ้งเตือน']
        );

        // Create Notification Manager Role
        $notificationManagerRole = Role::updateOrCreate(
            ['name' => 'notification-manager', 'guard_name' => 'web'],
            ['description' => 'ผู้จัดการการแจ้งเตือน']
        );

        // Create Basic User Role
        $userRole = Role::updateOrCreate(
            ['name' => 'user', 'guard_name' => 'web'],
            ['description' => 'ผู้ใช้งานทั่วไป']
        );

        // Create API User Role
        $apiUserRole = Role::updateOrCreate(
            ['name' => 'api-user', 'guard_name' => 'web'],
            ['description' => 'ผู้ใช้งาน API']
        );

        // Assign permissions to Super Admin (all permissions)
        $superAdminRole->syncPermissions(Permission::all());

        // Assign permissions to Notification Admin
        $notificationAdminPermissions = [
            'view-all-notifications',
            'create-notifications',
            'edit-notifications',
            'delete-notifications',
            'manage-notifications',
            'cancel-notifications',
            'resend-notifications',
            'view-notification-logs',
            'view-notification-analytics',
            'export-notifications',
            'bulk-notification-actions',
            'duplicate-notifications',
            'view-notification-templates',
            'create-notification-templates',
            'edit-notification-templates',
            'delete-notification-templates',
            'view-notification-groups',
            'create-notification-groups',
            'edit-notification-groups',
            'delete-notification-groups',
            'manage-group-members',
            'view-api-keys',
            'create-api-keys',
            'edit-api-keys',
            'delete-api-keys',
            'regenerate-api-keys',
            'view-api-usage',
            'manage-notification-settings',
            'test-notification-services',
            'view-system-health'
        ];
        $notificationAdminRole->syncPermissions($notificationAdminPermissions);

        // Assign permissions to Notification Manager
        $notificationManagerPermissions = [
            'view-all-notifications',
            'create-notifications',
            'edit-notifications',
            'manage-notifications',
            'cancel-notifications',
            'resend-notifications',
            'view-notification-logs',
            'view-notification-analytics',
            'export-notifications',
            'bulk-notification-actions',
            'duplicate-notifications',
            'view-notification-templates',
            'create-notification-templates',
            'edit-notification-templates',
            'view-notification-groups',
            'create-notification-groups',
            'edit-notification-groups',
            'manage-group-members',
            'test-notification-services'
        ];
        $notificationManagerRole->syncPermissions($notificationManagerPermissions);

        // Assign permissions to User
        $userPermissions = [
            'view-received-notifications',
            'read-notifications',
            'manage-notification-preferences',
            'archive-notifications',
            'export-own-notifications',
            'report-notification-issues'
        ];
        $userRole->syncPermissions($userPermissions);

        // Assign permissions to API User
        $apiUserPermissions = [
            'create-notifications',
            'view-all-notifications',
            'view-notification-logs'
        ];
        $apiUserRole->syncPermissions($apiUserPermissions);

        $this->command->info('Notification roles created and permissions assigned successfully!');
    }
}