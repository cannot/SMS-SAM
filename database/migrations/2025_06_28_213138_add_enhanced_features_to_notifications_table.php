<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Add personalized content support (API feature)
            $table->json('processed_content')->nullable()->after('variables');
            $table->integer('personalized_recipients_count')->default(0)->after('total_recipients');
            $table->string('content_version', 10)->default('v1')->after('processed_content');
            
            // Add medium priority support (mentioned in NotificationService)
            $table->dropColumn('priority');
        });
        
        // Recreate priority column with medium support
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('priority', ['low', 'medium', 'normal', 'high', 'urgent'])->default('normal')->after('webhook_url');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['processed_content', 'personalized_recipients_count', 'content_version']);
            $table->dropColumn('priority');
        });
        
        // Restore original priority column
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('webhook_url');
        });
    }
};