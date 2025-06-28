<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ApiPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Notifications
            [
                'name' => 'notifications.send',
                'display_name' => 'Send Notifications',
                'description' => 'Send single notification',
                'category' => 'notifications',
                'guard_name' => 'api'
            ],
            [
                'name' => 'notifications.bulk',
                'display_name' => 'Send Bulk Notifications',
                'description' => 'Send multiple notifications',
                'category' => 'notifications',
                'guard_name' => 'api'
            ],
            [
                'name' => 'notifications.schedule',
                'display_name' => 'Schedule Notifications',
                'description' => 'Schedule notifications for later',
                'category' => 'notifications',
                'guard_name' => 'api'
            ],
            [
                'name' => 'notifications.status',
                'display_name' => 'Check Notification Status',
                'description' => 'Check delivery status of notifications',
                'category' => 'notifications',
                'guard_name' => 'api'
            ],
            [
                'name' => 'notifications.history',
                'display_name' => 'View Notification History',
                'description' => 'Access notification history',
                'category' => 'notifications',
                'guard_name' => 'api'
            ],

            // Users
            [
                'name' => 'users.read',
                'display_name' => 'Read Users',
                'description' => 'Read user information from LDAP',
                'category' => 'users',
                'guard_name' => 'api'
            ],
            [
                'name' => 'users.search',
                'display_name' => 'Search Users',
                'description' => 'Search users in LDAP',
                'category' => 'users',
                'guard_name' => 'api'
            ],

            // Groups
            [
                'name' => 'groups.read',
                'display_name' => 'Read Groups',
                'description' => 'Read notification groups',
                'category' => 'groups',
                'guard_name' => 'api'
            ],
            [
                'name' => 'groups.manage',
                'display_name' => 'Manage Groups',
                'description' => 'Create and manage notification groups',
                'category' => 'groups',
                'guard_name' => 'api'
            ],

            // Templates
            [
                'name' => 'templates.read',
                'display_name' => 'Read Templates',
                'description' => 'Access notification templates',
                'category' => 'templates',
                'guard_name' => 'api'
            ],
            [
                'name' => 'templates.render',
                'display_name' => 'Render Templates',
                'description' => 'Render templates with data',
                'category' => 'templates',
                'guard_name' => 'api'
            ],

            // Reports
            [
                'name' => 'reports.read',
                'display_name' => 'Read Reports',
                'description' => 'Access delivery reports',
                'category' => 'reports',
                'guard_name' => 'api'
            ],
            [
                'name' => 'reports.export',
                'display_name' => 'Export Reports',
                'description' => 'Export reports in various formats',
                'category' => 'reports',
                'guard_name' => 'api'
            ],

            // System
            [
                'name' => 'system.health',
                'display_name' => 'System Health',
                'description' => 'Check system health status',
                'category' => 'system',
                'guard_name' => 'api'
            ],
            [
                'name' => 'system.status',
                'display_name' => 'System Status',
                'description' => 'Get system status information',
                'category' => 'system',
                'guard_name' => 'api'
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name']
                ],
                $permission
            );
        }

        $this->command->info('API permissions created successfully.');
    }
}