<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('display_name');
            }
        });

        // อัพเดทข้อมูล display_name สำหรับ roles ที่มีอยู่
        $this->updateRoleDisplayNames();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'display_name')) {
                $table->dropColumn('display_name');
            }
            if (Schema::hasColumn('roles', 'description')) {
                $table->dropColumn('description');
            }
        });
    }

    /**
     * Update display names for existing roles
     */
    private function updateRoleDisplayNames()
    {
        $roleDisplayNames = [
            'admin' => 'Administrator',
            'notification_manager' => 'Notification Manager',
            'end_user' => 'End User',
            'it_support' => 'IT Support',
            'api_manager' => 'API Manager',
            'user_manager' => 'User Manager',
            'super-admin' => 'Super Administrator',
        ];

        $roleDescriptions = [
            'admin' => 'Full system administrator with all permissions',
            'notification_manager' => 'Can create and manage notifications and templates',
            'end_user' => 'Regular user who can receive notifications',
            'it_support' => 'IT support staff who can view logs and system status',
            'api_manager' => 'Can create and manage API keys and external integrations',
            'user_manager' => 'Can manage users and their permissions',
            'super-admin' => 'Super administrator with unrestricted access',
        ];

        foreach ($roleDisplayNames as $name => $displayName) {
            DB::table('roles')
                ->where('name', $name)
                ->update([
                    'display_name' => $displayName,
                    'description' => $roleDescriptions[$name] ?? null,
                ]);
        }
    }
};