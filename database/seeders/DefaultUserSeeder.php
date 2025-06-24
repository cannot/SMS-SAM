<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Str;

class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create system admin user
        $adminUser = User::create([
            'ldap_guid' => Str::uuid(),
            'username' => 'admin',
            'email' => 'admin@smart-notification.local',
            'first_name' => 'ระบบ',
            'last_name' => 'ผู้ดูแล',
            'display_name' => 'ผู้ดูแลระบบ',
            'department' => 'ฝ่าย IT',
            'title' => 'ผู้ดูแลระบบ',
            'is_active' => true,
            'auth_source' => 'manual',
            'password' => bcrypt('admin123'),
            'ldap_synced_at' => now(),
        ]);

        $adminUser->assignRole('admin');

        // Create default preferences
        // UserPreference::create([
        //     'user_id' => $adminUser->id,
        //     'email_preferences' => json_encode(['all']),
        //     'teams_preferences' => json_encode(['all']),
        //     'timezone' => 'Asia/Bangkok',
        //     'quiet_hours' => json_encode([
        //         'start' => '22:00',
        //         'end' => '08:00'
        //     ]),
        //     'weekend_notifications' => true,
        // ]);

        // Create super admin user
        $superAdminUser = User::create([
            'ldap_guid' => Str::uuid(),
            'username' => 'superadmin',
            'email' => 'superadmin@smart-notification.local',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'display_name' => 'Super Administrator',
            'department' => 'ฝ่าย IT',
            'title' => 'ผู้ดูแลระบบสูงสุด',
            'is_active' => true,
            'auth_source' => 'manual',
            'password' => bcrypt('superadmin123'),
            'ldap_synced_at' => now(),
        ]);

        $superAdminUser->assignRole('super-admin');

        // UserPreference::create([
        //     'user_id' => $superAdminUser->id,
        //     'email_preferences' => json_encode(['all']),
        //     'teams_preferences' => json_encode(['all']),
        //     'timezone' => 'Asia/Bangkok',
        //     'quiet_hours' => json_encode([
        //         'start' => '22:00',
        //         'end' => '08:00'
        //     ]),
        //     'weekend_notifications' => true,
        // ]);

        // Create test notification manager (uncomment if needed)
        // $managerUser = User::create([
        //     'ldap_guid' => Str::uuid(),
        //     'username' => 'notification.manager',
        //     'email' => 'notifications@company.com',
        //     'first_name' => 'ผู้จัดการ',
        //     'last_name' => 'การแจ้งเตือน',
        //     'display_name' => 'ผู้จัดการการแจ้งเตือน',
        //     'department' => 'ฝ่าย IT',
        //     'title' => 'ผู้จัดการการแจ้งเตือน',
        //     'is_active' => true,
        //     'auth_source' => 'manual',
        //     'password' => bcrypt('manager123'),
        //     'ldap_synced_at' => now(),
        // ]);

        // $managerUser->assignRole('notification_manager');

        // UserPreference::create([
        //     'user_id' => $managerUser->id,
        //     'email_preferences' => json_encode(['all']),
        //     'teams_preferences' => json_encode(['all']),
        //     'timezone' => 'Asia/Bangkok',
        //     'quiet_hours' => json_encode([
        //         'start' => '22:00',
        //         'end' => '08:00'
        //     ]),
        //     'weekend_notifications' => true,
        // ]);

        // // Create test end user
        // $endUser = User::create([
        //     'ldap_guid' => Str::uuid(),
        //     'username' => 'testuser',
        //     'email' => 'testuser@company.com',
        //     'first_name' => 'ผู้ใช้',
        //     'last_name' => 'ทดสอบ',
        //     'display_name' => 'ผู้ใช้ทดสอบ',
        //     'department' => 'ฝ่ายทั่วไป',
        //     'title' => 'พนักงาน',
        //     'is_active' => true,
        //     'auth_source' => 'manual',
        //     'password' => bcrypt('user123'),
        //     'ldap_synced_at' => now(),
        // ]);

        // $endUser->assignRole('user');

        // UserPreference::create([
        //     'user_id' => $endUser->id,
        //     'email_preferences' => json_encode(['urgent', 'important']),
        //     'teams_preferences' => json_encode(['urgent']),
        //     'timezone' => 'Asia/Bangkok',
        //     'quiet_hours' => json_encode([
        //         'start' => '20:00',
        //         'end' => '07:00'
        //     ]),
        //     'weekend_notifications' => false,
        // ]);

        $this->command->info('✅ สร้างผู้ใช้เริ่มต้นสำเร็จแล้ว!');
        $this->command->info('  - admin@smart-notification.local (รหัส: admin123)');
        $this->command->info('  - superadmin@smart-notification.local (รหัส: superadmin123)');
        // $this->command->info('  - notifications@company.com (รหัส: manager123)');
        // $this->command->info('  - testuser@company.com (รหัส: user123)');
    }
}