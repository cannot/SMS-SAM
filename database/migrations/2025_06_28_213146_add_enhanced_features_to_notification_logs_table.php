<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            // Add webhook channel support (missing in original)
            $table->dropColumn('channel');
        });
        
        // Recreate channel column with webhook support
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->enum('channel', ['email', 'teams', 'webhook'])->after('recipient_name');
        });
        
        Schema::table('notification_logs', function (Blueprint $table) {
            // Add personalized content support
            $table->json('personalized_content')->nullable()->after('error_message');
            $table->json('content_sent')->nullable()->after('personalized_content');
            
            // Add webhook specific fields
            $table->string('webhook_url')->nullable()->after('content_sent');
            $table->integer('webhook_response_code')->nullable()->after('webhook_url');
            
            // Add variables field (missing in original)
            $table->json('variables')->nullable()->after('webhook_response_code');
            
            // Add attempts field (from your existing migration)
            #$table->integer('attempts')->default(1)->after('retry_count');
            
            // Add additional indexes for performance
            $table->index(['recipient_email', 'channel']);
            $table->index(['webhook_url']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['recipient_email', 'channel']);
            $table->dropIndex(['webhook_url']);
            $table->dropIndex(['status', 'created_at']);
            
            // Drop new columns
            $table->dropColumn([
                'personalized_content', 
                'content_sent', 
                'webhook_url', 
                'webhook_response_code',
                'variables',
                'attempts'
            ]);
            
            // Restore original channel enum
            $table->dropColumn('channel');
        });
        
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->enum('channel', ['email', 'teams'])->after('recipient_name');
        });
    }
};
