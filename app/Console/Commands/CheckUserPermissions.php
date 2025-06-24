<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:check-permissions {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all permissions for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        $this->info("=== User Information ===");
        $this->info("Name: {$user->display_name}");
        $this->info("Email: {$user->email}");
        $this->info("Active: " . ($user->is_active ? 'Yes' : 'No'));
        
        $this->newLine();
        $this->info("=== Roles ===");
        $roles = $user->getRoleNames();
        if ($roles->isEmpty()) {
            $this->warn("No roles assigned");
        } else {
            foreach ($roles as $role) {
                $this->info("- {$role}");
            }
        }
        
        $this->newLine();
        $this->info("=== Direct Permissions ===");
        $directPermissions = $user->getDirectPermissions();
        if ($directPermissions->isEmpty()) {
            $this->warn("No direct permissions");
        } else {
            foreach ($directPermissions as $permission) {
                $this->info("- {$permission->name}");
            }
        }
        
        $this->newLine();
        $this->info("=== All Permissions (including from roles) ===");
        $allPermissions = $user->getAllPermissions();
        if ($allPermissions->isEmpty()) {
            $this->warn("No permissions at all!");
        } else {
            $permissionGroups = [];
            foreach ($allPermissions as $permission) {
                $group = explode('-', $permission->name)[0];
                if (!isset($permissionGroups[$group])) {
                    $permissionGroups[$group] = [];
                }
                $permissionGroups[$group][] = $permission->name;
            }
            
            foreach ($permissionGroups as $group => $permissions) {
                $this->info(ucfirst($group) . " permissions:");
                foreach ($permissions as $permission) {
                    $this->info("  - {$permission}");
                }
            }
        }
        
        $this->newLine();
        $this->info("Total permissions: " . $allPermissions->count());
        
        // Check specific admin menu permissions
        $this->newLine();
        $this->info("=== Admin Menu Visibility Check ===");
        $adminMenuPermissions = [
            'view-roles',
            'view-permissions',
            'view-permission-matrix',
            'view-api-keys',
            'manage-api-keys',
            'view-reports',
            'view-analytics',
            'view-system-logs',
            'manage-settings',
            'system-maintenance'
        ];
        
        $hasAdminAccess = false;
        foreach ($adminMenuPermissions as $permission) {
            if ($user->can($permission)) {
                $this->info("✓ {$permission}");
                $hasAdminAccess = true;
            } else {
                $this->warn("✗ {$permission}");
            }
        }
        
        if (!$hasAdminAccess) {
            $this->newLine();
            $this->error("⚠️  User doesn't have any admin menu permissions!");
            $this->info("Run: php artisan setup:super-admin {$email}");
        }
        
        return 0;
    }
}