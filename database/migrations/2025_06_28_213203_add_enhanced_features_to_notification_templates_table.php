<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            // Add medium priority support
            $table->dropColumn('priority');
        });
        
        // Recreate priority column with medium support
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->enum('priority', ['low', 'medium', 'normal', 'high', 'urgent'])->default('normal')->after('supported_channels');
        });
        
        Schema::table('notification_templates', function (Blueprint $table) {
            // Add personalization support
            $table->boolean('supports_personalization')->default(true)->after('is_active');
            $table->json('personalization_variables')->nullable()->after('supports_personalization');
            
            // Add usage instructions
            $table->text('usage_instructions')->nullable()->after('personalization_variables');
            
            // Add webhook support to supported_channels default
            $table->json('supported_channels')->default('["email","teams","webhook"]')->change();
            
            // Add indexes for better performance
            $table->index(['supports_personalization']);
            $table->index(['priority']);
        });
    }

    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['supports_personalization']);
            $table->dropIndex(['priority']);
            
            // Drop new columns
            $table->dropColumn([
                'supports_personalization', 
                'personalization_variables', 
                'usage_instructions'
            ]);
            
            // Restore original supported_channels default
            $table->json('supported_channels')->default('["email"]')->change();
            
            // Restore original priority
            $table->dropColumn('priority');
        });
        
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('supported_channels');
        });
    }
};
