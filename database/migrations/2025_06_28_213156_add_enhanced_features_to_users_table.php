<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add Teams webhook URL for personal notifications
            $table->string('teams_webhook_url')->nullable()->after('is_active');
            
            // Add notification preferences
            $table->json('notification_preferences')->nullable()->after('teams_webhook_url');
            
            // Add position field (mentioned in Admin controller)
            $table->string('position')->nullable()->after('title');
            
            // Add indexes for better performance
            $table->index(['email', 'is_active']);
            $table->index(['department']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['email', 'is_active']);
            $table->dropIndex(['department']);
            
            // Drop columns
            $table->dropColumn([
                'teams_webhook_url', 
                'notification_preferences', 
                'position'
            ]);
        });
    }
};