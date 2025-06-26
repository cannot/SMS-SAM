<?php
// สร้างไฟล์: app/Console/Commands/FixPermissionsCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class FixPermissionsCommand extends Command
{
    protected $signature = 'permissions:fix 
                           {--check : Only check for missing permissions}
                           {--create : Create missing permissions}
                           {--assign : Assign permissions to roles}
                           {--user= : Assign admin role to specific user email}';

    protected $description = 'Fix missing permissions and roles';

    public function handle()
    {
        $this->info('🔍 Checking permissions system...');

        if ($this->option('check') || !$this->hasOptions()) {
            $this->checkPermissions();
        }

        if ($this->option('create')) {
            $this->createMissingPermissions();
        }

        if ($this->option('assign')) {
            $this->assignPermissions();
        }

        if ($this->option('user')) {
            $this->assignAdminToUser($this->option('user'));
        }

        $this->info('✅ Done!');
    }

    protected function hasOptions()
    {
        return $this->option('check') || 
               $this->option('create') || 
               $this->option('assign') || 
               $this->option('user');
    }

    protected function checkPermissions()
    {
        $this->info('📋 Checking current permissions...');

        // ตรวจสอบ permissions ที่มีอยู่
        $existingPermissions = Permission::pluck('name')->toArray();
        $this->info('Current permissions: ' . count($existingPermissions));

        // ตรวจสอบ roles ที่มีอยู่
        $existingRoles = Role::pluck('name')->toArray();
        $this->info('Current roles: ' . count($existingRoles));

        // รายการ permissions ที่ควรมี
        $requiredPermissions = [
            'api:admin-access',
            'api:user-access',
            'admin-access',
            'users-manage',
            'roles-manage',
            'notifications-manage',
            'groups-manage',
            'templates-manage',
            'settings-manage',
            'reports-view',
            'logs-view',
            'view-notification-groups',
            'edit-notification-groups',
        ];

        $missingPermissions = array_diff($requiredPermissions, $existingPermissions);
        
        if (!empty($missingPermissions)) {
            $this->warn('❌ Missing permissions:');
            foreach ($missingPermissions as $permission) {
                $this->line("  - {$permission}");
            }
        } else {
            $this->info('✅ All required permissions exist');
        }

        // ตรวจสอบ roles ที่ควรมี
        $requiredRoles = ['super-admin', 'admin', 'manager', 'user'];
        $missingRoles = array_diff($requiredRoles, $existingRoles);

        if (!empty($missingRoles)) {
            $this->warn('❌ Missing roles:');
            foreach ($missingRoles as $role) {
                $this->line("  - {$role}");
            }
        } else {
            $this->info('✅ All required roles exist');
        }

        // ตรวจสอบว่า admin user มีหรือไม่
        $adminUsers = User::role('super-admin')->count();
        if ($adminUsers === 0) {
            $this->warn('❌ No super-admin users found');
        } else {
            $this->info("✅ Found {$adminUsers} super-admin user(s)");
        }
    }

    protected function createMissingPermissions()
    {
        $this->info('🔧 Creating missing permissions...');

        $permissions = [
            'api:admin-access' => 'API Admin Access',
            'api:user-access' => 'API User Access',
            'api:read' => 'API Read Access',
            'api:write' => 'API Write Access',
            'admin-access' => 'Admin Panel Access',
            'users-manage' => 'Manage Users',
            'roles-manage' => 'Manage Roles',
            'permissions-manage' => 'Manage Permissions',
            'notifications-manage' => 'Manage Notifications',
            'groups-manage' => 'Manage Groups',
            'templates-manage' => 'Manage Templates',
            'settings-manage' => 'Manage Settings',
            'reports-view' => 'View Reports',
            'logs-view' => 'View Logs',
        ];

        foreach ($permissions as $name => $description) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );

            if ($permission->wasRecentlyCreated) {
                $this->info("✅ Created permission: {$name}");
            } else {
                $this->line("   Permission exists: {$name}");
            }
        }

        // สร้าง roles
        $roles = [
            'super-admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'user' => 'Regular User',
        ];

        foreach ($roles as $name => $displayName) {
            $role = Role::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => $displayName]
            );

            if ($role->wasRecentlyCreated) {
                $this->info("✅ Created role: {$name}");
            } else {
                $this->line("   Role exists: {$name}");
            }
        }
    }

    protected function assignPermissions()
    {
        $this->info('🔗 Assigning permissions to roles...');

        // Super Admin gets all permissions
        $superAdmin = Role::findByName('super-admin');
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
            $this->info('✅ Assigned all permissions to super-admin');
        }

        // Admin gets most permissions
        $admin = Role::findByName('admin');
        if ($admin) {
            $adminPermissions = Permission::where('name', 'not like', '%system%')->get();
            $admin->syncPermissions($adminPermissions);
            $this->info('✅ Assigned admin permissions to admin');
        }

        // Manager gets limited permissions
        $manager = Role::findByName('manager');
        if ($manager) {
            $managerPermissions = Permission::whereIn('name', [
                'users-manage',
                'notifications-manage', 
                'groups-manage',
                'reports-view'
            ])->get();
            $manager->syncPermissions($managerPermissions);
            $this->info('✅ Assigned manager permissions to manager');
        }

        // User gets basic permissions
        $user = Role::findByName('user');
        if ($user) {
            $userPermissions = Permission::whereIn('name', [
                'api:user-access'
            ])->get();
            $user->syncPermissions($userPermissions);
            $this->info('✅ Assigned user permissions to user');
        }
    }

    protected function assignAdminToUser($email)
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("❌ User with email {$email} not found");
            return;
        }

        $superAdminRole = Role::findByName('super-admin');
        if (!$superAdminRole) {
            $this->error('❌ super-admin role not found');
            return;
        }

        $user->assignRole('super-admin');
        $this->info("✅ Assigned super-admin role to {$user->email}");
    }
}