<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing notification templates to support webhook
        // DB::table('notification_templates')
        //     ->where('supported_channels', '["email"]')
        //     ->update(['supported_channels' => '["email","teams","webhook"]']);
        
        // Set supports_personalization to true for all existing templates
        DB::table('notification_templates')
            ->whereNull('supports_personalization')
            ->update(['supports_personalization' => true]);
        
        // Update existing notifications with default content_version
        DB::table('notifications')
            ->whereNull('content_version')
            ->update(['content_version' => 'v1']);
        
        // Update existing notification_logs with default attempts
        DB::table('notification_logs')
            ->where('attempts', 0)
            ->update(['attempts' => 1]);
    }

    public function down(): void
    {
        // Rollback data changes if needed
        DB::table('notification_templates')
            ->where('supported_channels', '["email","teams","webhook"]')
            ->update(['supported_channels' => '["email"]']);
    }
};