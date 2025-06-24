<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GroupPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // สร้างสิทธิ์สำหรับจัดการกลุ่ม
        $groupPermissions = [
            'view-groups' => 'ดูรายการกลุ่มการแจ้งเตือน',
            'create-groups' => 'สร้างกลุ่มการแจ้งเตือนใหม่',
            'edit-groups' => 'แก้ไขกลุ่มการแจ้งเตือน',
            'delete-groups' => 'ลบกลุ่มการแจ้งเตือน',
            'sync-groups' => 'ซิงค์สมาชิกกลุ่มจาก LDAP',
            'export-groups' => 'Export ข้อมูลกลุ่มและสมาชิก',
            'manage-group-members' => 'จัดการสมาชิกในกลุ่ม',
        ];

        echo "Creating group permissions...\n";
        
        foreach ($groupPermissions as $name => $description) {
            $permission = Permission::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
            echo "- {$permission->name}: {$permission->description}\n";
        }

        // กำหนดสิทธิ์ให้ role ต่างๆ
        $this->assignPermissionsToRoles();
        
        echo "Group permissions created and assigned successfully!\n";
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles(): void
    {
        // Super Admin - ทุกสิทธิ์
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo([
            'view-groups',
            'create-groups', 
            'edit-groups',
            'delete-groups',
            'sync-groups',
            'export-groups',
            'manage-group-members',
        ]);

        // Admin - ทุกสิทธิ์ยกเว้นลบ
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'view-groups',
            'create-groups',
            'edit-groups', 
            'sync-groups',
            'export-groups',
            'manage-group-members',
        ]);

        // Notification Manager - จัดการการแจ้งเตือนและกลุ่ม
        $notificationManager = Role::firstOrCreate(['name' => 'notification-manager']);
        $notificationManager->givePermissionTo([
            'view-groups',
            'create-groups',
            'edit-groups',
            'manage-group-members',
            'export-groups',
        ]);

        // IT Support - ดูและซิงค์เท่านั้น
        $itSupport = Role::firstOrCreate(['name' => 'system-admin']);
        $itSupport->givePermissionTo([
            'view-groups',
            'sync-groups',
        ]);

        // User - ดูเท่านั้น (ถ้าต้องการ)
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->givePermissionTo([
            'view-groups',
        ]);

        echo "Permissions assigned to roles:\n";
        echo "- Super Admin: ทุกสิทธิ์\n";
        echo "- Admin: ทุกสิทธิ์ยกเว้นลบ\n";
        echo "- Notification Manager: จัดการกลุ่มและสมาชิก\n";
        echo "- System Admin: ดูและซิงค์\n";
        echo "- User: ดูเท่านั้น\n";
    }
}