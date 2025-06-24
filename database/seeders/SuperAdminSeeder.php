<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions needed for admin menu
        $permissions = [
            // User Management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'manage-users',
            
            // Role & Permission Management
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
            'export-roles',
            
            'view-permissions',
            'create-permissions',
            'edit-permissions',
            'delete-permissions',
            'export-permissions',
            'view-permission-matrix',
            'manage-user-permissions',
            
            // API Management
            'view-api-keys',
            'manage-api-keys',
            'create-api-keys',
            'edit-api-keys',
            'delete-api-keys',
            'view-api-usage',
            
            // Reports & Analytics
            'view-reports',
            'view-analytics',
            'view-system-logs',
            'export-user-reports',
            'export-notification-reports',
            'export-api-reports',
            
            // System Management
            'manage-settings',
            'system-maintenance',
            'view-system-status',
            'bulk-operations',
            
            // Dashboard & General
            'view-dashboard',
            'view-notifications',
            
            // Activity Logs
            'view-activity-logs',
            'export-activity-logs',
        ];

        // Create or update permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create super-admin role if not exists
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);

        // Give all permissions to super-admin role
        $superAdminRole->syncPermissions(Permission::all());

        // Find or create super admin user
        $superAdmin = User::where('email', 'admin@company.com')->first();

        if (!$superAdmin) {
            $superAdmin = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@company.com',
                'password' => bcrypt('password'), // Change this!
                'is_active' => true,
            ]);
        }

        // Assign super-admin role to the user
        $superAdmin->assignRole('super-admin');

        // Also create other common roles
        $roles = [
            'admin' => [
                'view-users',
                'edit-users',
                'view-roles',
                'view-permissions',
                'view-api-keys',
                'view-reports',
                'view-analytics',
                'view-system-logs',
                'view-dashboard',
                'view-notifications',
                'view-activity-logs',
            ],
            'manager' => [
                'view-users',
                'view-reports',
                'view-dashboard',
                'view-notifications',
                'view-activity-logs',
            ],
            'user' => [
                'view-dashboard',
                'view-notifications',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($permissions);
        }

        $this->command->info('Super Admin and permissions created successfully.');
        $this->command->info('Super Admin Email: admin@company.com');
        $this->command->info('Default Password: password (Please change this!)');
    }
}