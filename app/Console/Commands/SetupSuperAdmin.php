<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Setup Super Admin Command for Smart Notification System
 * 
 * คำสั่งสำหรับตั้งค่า Super Admin พร้อมสิทธิ์ทั้งหมดในระบบ
 * ใช้งานร่วมกับ CompletePermissionsSeeder
 * 
 * @author Smart Notification Team
 * @version 2.0
 * @since 2025-06-23
 */
class SetupSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:super-admin 
                            {email? : Email address of the user to promote to super admin}
                            {--create : Create a new user if not found}
                            {--force : Force update existing super admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup super admin with all permissions based on CompletePermissionsSeeder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Smart Notification System - Super Admin Setup');
        $this->info('==================================================');

        // Get email from argument or prompt
        $email = $this->argument('email') ?? $this->ask('Enter super admin email');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ Invalid email format!');
            return 1;
        }

        // Find or create user
        $user = $this->findOrCreateUser($email);
        if (!$user) {
            return 1;
        }

        $this->info("🎯 Setting up super admin for: {$user->name} ({$user->email})");

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Check if permissions exist (run seeder if needed)
        $this->ensurePermissionsExist();

        // Setup super admin role and permissions
        $this->setupSuperAdminRole();

        // Assign super admin role to user
        $this->assignSuperAdminToUser($user);

        // Show completion summary
        $this->showCompletionSummary($user);

        return 0;
    }

    /**
     * Find or create user based on email
     */
    private function findOrCreateUser(string $email): ?User
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            if ($this->option('create')) {
                $user = $this->createNewUser($email);
            } else {
                $this->error("❌ User with email {$email} not found!");
                $this->info("💡 Use --create option to create a new user automatically");
                return null;
            }
        }

        return $user;
    }

    /**
     * Create a new user
     */
    private function createNewUser(string $email): User
    {
        $this->info('👤 Creating new user...');
        
        $name = $this->ask('Enter user full name');
        $password = $this->secret('Enter password (leave empty for auto-generated)') ?: \Str::random(12);
        
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->info("✅ User created successfully");
        $this->info("📧 Email: {$email}");
        $this->info("🔑 Password: {$password}");
        $this->warn("⚠️  Please save this password - it won't be shown again!");

        return $user;
    }

    /**
     * Ensure all permissions exist in the system
     */
    private function ensurePermissionsExist(): void
    {
        $expectedPermissions = $this->getAllSystemPermissions();
        $existingPermissions = Permission::pluck('name')->toArray();
        
        $missingPermissions = array_diff($expectedPermissions, $existingPermissions);
        
        if (!empty($missingPermissions)) {
            $this->warn("⚠️  Some permissions are missing from the database.");
            $this->info("Missing permissions: " . count($missingPermissions));
            
            if ($this->confirm('Do you want to run CompletePermissionsSeeder to create all permissions?', true)) {
                $this->call('db:seed', ['--class' => 'CompletePermissionsSeeder']);
            } else {
                $this->info('📝 Creating missing permissions...');
                $this->createMissingPermissions($missingPermissions);
            }
        } else {
            $this->info('✅ All permissions exist in the database');
        }
    }

    /**
     * Get all system permissions (same as CompletePermissionsSeeder)
     */
    private function getAllSystemPermissions(): array
    {
        return [
            // User Management (7 permissions)
            'view-users', 'create-users', 'edit-users', 'delete-users', 'manage-users',
            'export-users', 'import-users',
            
            // Notification Management (12 permissions)
            'view-all-notifications', 'view-received-notifications', 'create-notifications',
            'edit-notifications', 'delete-notifications', 'send-notifications', 'cancel-notifications',
            'resend-notifications', 'duplicate-notifications', 'bulk-notification-actions',
            'read-notifications', 'archive-notifications', 'manage-notifications',
            
            // Template Management (7 permissions)
            'view-notification-templates', 'create-notification-templates', 'edit-notification-templates',
            'delete-notification-templates', 'duplicate-templates', 'export-templates', 'import-templates',
            
            // Group Management (7 permissions)
            'view-notification-groups', 'create-notification-groups', 'edit-notification-groups',
            'delete-notification-groups', 'manage-group-members', 'sync-groups', 'export-groups',
            
            // API Management (7 permissions)
            'view-api-keys', 'create-api-keys', 'edit-api-keys', 'delete-api-keys',
            'regenerate-api-keys', 'view-api-usage', 'manage-api-rate-limits',
            
            // Roles & Permissions Management (11 permissions)
            'view-roles', 'create-roles', 'edit-roles', 'delete-roles', 'assign-roles',
            'view-permissions', 'create-permissions', 'edit-permissions', 'delete-permissions',
            'assign-permissions', 'view-permission-matrix',
            
            // Reports & Analytics (5 permissions)
            'view-reports', 'view-notification-analytics', 'export-reports',
            'export-own-notifications', 'export-notifications',
            
            // Logging & Monitoring (5 permissions)
            'view-notification-logs', 'view-system-logs', 'view-activity-logs',
            'view-api-logs', 'export-logs',
            
            // System Configuration (7 permissions)
            'view-dashboard', 'manage-notification-settings', 'manage-notification-preferences',
            'test-notification-services', 'view-system-health', 'system-settings', 'system-maintenance',
            
            // LDAP Integration (4 permissions)
            'manage-ldap', 'sync-ldap-users', 'test-ldap-connection', 'view-ldap-logs',
            
            // Miscellaneous (3 permissions)
            'report-notification-issues', 'view-failed-jobs', 'retry-failed-jobs',
        ];
    }

    /**
     * Create missing permissions with proper structure
     */
    private function createMissingPermissions(array $missingPermissions): void
    {
        $permissionCategories = $this->getPermissionCategories();
        
        $bar = $this->output->createProgressBar(count($missingPermissions));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        
        foreach ($missingPermissions as $permissionName) {
            $category = $permissionCategories[$permissionName] ?? 'Miscellaneous';
            $displayName = $this->generateDisplayName($permissionName);
            $description = $this->generateDescription($permissionName);
            
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'display_name' => $displayName,
                    'description' => $description,
                    'category' => $category
                ]
            );
            
            $bar->setMessage("Creating: {$permissionName}");
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("✅ Created " . count($missingPermissions) . " missing permissions");
    }

    /**
     * Get permission categories mapping
     */
    private function getPermissionCategories(): array
    {
        return [
            // User Management
            'view-users' => 'User Management', 'create-users' => 'User Management',
            'edit-users' => 'User Management', 'delete-users' => 'User Management',
            'manage-users' => 'User Management', 'export-users' => 'User Management',
            'import-users' => 'User Management',
            
            // Notification Management
            'view-all-notifications' => 'Notification Management', 'view-received-notifications' => 'Notification Management',
            'create-notifications' => 'Notification Management', 'edit-notifications' => 'Notification Management',
            'delete-notifications' => 'Notification Management', 'send-notifications' => 'Notification Management',
            'cancel-notifications' => 'Notification Management', 'resend-notifications' => 'Notification Management',
            'duplicate-notifications' => 'Notification Management', 'bulk-notification-actions' => 'Notification Management',
            'read-notifications' => 'Notification Management', 'archive-notifications' => 'Notification Management', 'manage-notifications' => 'Notification Management',
            
            // Template Management
            'view-notification-templates' => 'Template Management', 'create-notification-templates' => 'Template Management',
            'edit-notification-templates' => 'Template Management', 'delete-notification-templates' => 'Template Management',
            'duplicate-templates' => 'Template Management', 'export-templates' => 'Template Management',
            'import-templates' => 'Template Management',
            
            // Group Management
            'view-notification-groups' => 'Group Management', 'create-notification-groups' => 'Group Management',
            'edit-notification-groups' => 'Group Management', 'delete-notification-groups' => 'Group Management',
            'manage-group-members' => 'Group Management', 'sync-groups' => 'Group Management',
            'export-groups' => 'Group Management',
            
            // API Management
            'view-api-keys' => 'API Management', 'create-api-keys' => 'API Management',
            'edit-api-keys' => 'API Management', 'delete-api-keys' => 'API Management',
            'regenerate-api-keys' => 'API Management', 'view-api-usage' => 'API Management',
            'manage-api-rate-limits' => 'API Management',
            
            // Roles & Permissions
            'view-roles' => 'Roles & Permissions', 'create-roles' => 'Roles & Permissions',
            'edit-roles' => 'Roles & Permissions', 'delete-roles' => 'Roles & Permissions',
            'assign-roles' => 'Roles & Permissions', 'view-permissions' => 'Roles & Permissions',
            'create-permissions' => 'Roles & Permissions', 'edit-permissions' => 'Roles & Permissions',
            'delete-permissions' => 'Roles & Permissions', 'assign-permissions' => 'Roles & Permissions',
            'view-permission-matrix' => 'Roles & Permissions',
            
            // Reports & Analytics
            'view-reports' => 'Reports & Analytics', 'view-notification-analytics' => 'Reports & Analytics',
            'export-reports' => 'Reports & Analytics', 'export-own-notifications' => 'Reports & Analytics',
            'export-notifications' => 'Reports & Analytics',
            
            // Logging & Monitoring
            'view-notification-logs' => 'Logging & Monitoring', 'view-system-logs' => 'Logging & Monitoring',
            'view-activity-logs' => 'Logging & Monitoring', 'view-api-logs' => 'Logging & Monitoring',
            'export-logs' => 'Logging & Monitoring',
            
            // System Configuration
            'view-dashboard' => 'System Configuration', 'manage-notification-settings' => 'System Configuration',
            'manage-notification-preferences' => 'System Configuration', 'test-notification-services' => 'System Configuration',
            'view-system-health' => 'System Configuration', 'system-settings' => 'System Configuration',
            'system-maintenance' => 'System Configuration',
            
            // LDAP Integration
            'manage-ldap' => 'LDAP Integration', 'sync-ldap-users' => 'LDAP Integration',
            'test-ldap-connection' => 'LDAP Integration', 'view-ldap-logs' => 'LDAP Integration',
            
            // Miscellaneous
            'report-notification-issues' => 'Miscellaneous', 'view-failed-jobs' => 'Miscellaneous',
            'retry-failed-jobs' => 'Miscellaneous',
        ];
    }

    /**
     * Generate display name from permission name
     */
    private function generateDisplayName(string $permissionName): string
    {
        $displayNames = [
            'view-users' => 'ดูผู้ใช้', 'create-users' => 'สร้างผู้ใช้',
            'edit-users' => 'แก้ไขผู้ใช้', 'delete-users' => 'ลบผู้ใช้',
            'manage-users' => 'จัดการผู้ใช้', 'export-users' => 'ส่งออกผู้ใช้',
            'import-users' => 'นำเข้าผู้ใช้',
            
            'view-all-notifications' => 'ดูการแจ้งเตือนทั้งหมด',
            'view-received-notifications' => 'ดูการแจ้งเตือนที่ได้รับ',
            'create-notifications' => 'สร้างการแจ้งเตือน',
            'edit-notifications' => 'แก้ไขการแจ้งเตือน',
            'delete-notifications' => 'ลบการแจ้งเตือน',
            'send-notifications' => 'ส่งการแจ้งเตือน',
            'cancel-notifications' => 'ยกเลิกการแจ้งเตือน',
            'resend-notifications' => 'ส่งการแจ้งเตือนซ้ำ',
            'duplicate-notifications' => 'ทำสำเนาการแจ้งเตือน',
            'bulk-notification-actions' => 'การดำเนินการแบบกลุ่ม',
            'read-notifications' => 'ทำเครื่องหมายอ่าน',
            'archive-notifications' => 'เก็บถาวรการแจ้งเตือน',
            
            'view-dashboard' => 'ดู Dashboard',
            'view-system-health' => 'ดูสถานะระบบ',
            'system-settings' => 'ตั้งค่าระบบ',
            'system-maintenance' => 'บำรุงรักษาระบบ',
        ];
        
        return $displayNames[$permissionName] ?? ucwords(str_replace('-', ' ', $permissionName));
    }

    /**
     * Generate description from permission name
     */
    private function generateDescription(string $permissionName): string
    {
        $descriptions = [
            'view-users' => 'ดูรายการผู้ใช้ในระบบ',
            'create-users' => 'สร้างผู้ใช้ใหม่ในระบบ',
            'edit-users' => 'แก้ไขข้อมูลผู้ใช้',
            'delete-users' => 'ลบผู้ใช้ออกจากระบบ',
            'manage-users' => 'จัดการผู้ใช้ทั่วไป (เปิด/ปิด, รีเซ็ต)',
            'export-users' => 'ส่งออกข้อมูลผู้ใช้เป็นไฟล์',
            'import-users' => 'นำเข้าข้อมูลผู้ใช้จากไฟล์',
            
            'view-dashboard' => 'เข้าถึง Dashboard หลัก',
            'view-system-health' => 'ดูสถานะความแข็งแรงของระบบ',
            'system-settings' => 'จัดการการตั้งค่าระบบทั่วไป',
            'system-maintenance' => 'บำรุงรักษาระบบ (ล้างแคช, รีสตาร์ท queue)',
        ];
        
        return $descriptions[$permissionName] ?? "Permission to {$permissionName}";
    }

    /**
     * Setup super admin role
     */
    private function setupSuperAdminRole(): void
    {
        $this->info('👑 Setting up super-admin role...');
        
        // Create or update super-admin role
        $superAdminRole = Role::updateOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            [
                'display_name' => 'ผู้ดูแลระบบสูงสุด',
                'description' => 'ผู้ดูแลระบบระดับสูงสุดที่มีสิทธิ์ทุกอย่างในระบบ รวมถึงการจัดการ roles และ permissions'
            ]
        );

        // Sync all permissions to super-admin role
        $this->info('🔗 Assigning ALL permissions to super-admin role...');
        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);
        
        $this->info("✅ Super-admin role setup completed with {$allPermissions->count()} permissions");
    }

    /**
     * Assign super admin role to user
     */
    private function assignSuperAdminToUser(User $user): void
    {
        $this->info('👤 Assigning super-admin role to user...');
        
        // Check if user already has super-admin role
        if ($user->hasRole('super-admin') && !$this->option('force')) {
            $this->warn("⚠️  User already has super-admin role!");
            if (!$this->confirm('Do you want to continue and refresh permissions?')) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        // Remove all existing roles (if force update)
        if ($this->option('force')) {
            $this->info('🔄 Removing existing roles...');
            $user->syncRoles([]);
        }

        // Assign super-admin role
        $user->assignRole('super-admin');

        // Make sure user is active
        $user->update([
            'is_active' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        $this->info('✅ Super-admin role assigned successfully');
    }

    /**
     * Show completion summary
     */
    private function showCompletionSummary(User $user): void
    {
        $this->newLine(2);
        $this->info('🎉 SUPER ADMIN SETUP COMPLETED SUCCESSFULLY!');
        $this->info('==================================================');
        
        $totalPermissions = Permission::count();
        $userPermissions = $user->getAllPermissions()->count();
        
        $this->table(['Field', 'Value'], [
            ['User Name', $user->name],
            ['Email', $user->email],
            ['Role', 'super-admin'],
            ['Status', $user->is_active ? '✅ Active' : '❌ Inactive'],
            ['Email Verified', $user->email_verified_at ? '✅ Verified' : '❌ Not Verified'],
            ['Total System Permissions', $totalPermissions],
            ['User Permissions', $userPermissions],
            ['Access Level', $userPermissions === $totalPermissions ? '🔥 FULL ACCESS' : '⚠️ LIMITED ACCESS'],
        ]);

        $this->newLine();
        $this->info('📋 What you can do now:');
        $this->info('• Login to the system with full administrative privileges');
        $this->info('• Manage all users, roles, and permissions');
        $this->info('• Configure notification templates and groups');
        $this->info('• Monitor system health and logs');
        $this->info('• Manage API keys and integrations');
        
        $this->newLine();
        $this->info('🔐 Security Reminder:');
        $this->warn('• Keep super admin credentials secure');
        $this->warn('• Use strong passwords and enable 2FA if available');
        $this->warn('• Regularly audit super admin activities');
        
        $this->newLine();
        $this->info('🚀 System is ready for production use!');
    }
}