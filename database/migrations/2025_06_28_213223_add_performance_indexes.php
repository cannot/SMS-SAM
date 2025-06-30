<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Add compound indexes for common queries
            $table->index(['api_key_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['priority', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['personalized_recipients_count']);
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            // Add compound indexes for common queries
            $table->index(['notification_id', 'channel']);
            $table->index(['user_id', 'status']);
            $table->index(['sent_at']);
            $table->index(['attempts', 'status']);
        });

        Schema::table('notification_templates', function (Blueprint $table) {
            // Add compound indexes for common queries
            $table->index(['is_active', 'supports_personalization']);
            $table->index(['category', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['api_key_id', 'status']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['priority', 'status']);
            $table->dropIndex(['scheduled_at', 'status']);
            $table->dropIndex(['personalized_recipients_count']);
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropIndex(['notification_id', 'channel']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['sent_at']);
            $table->dropIndex(['attempts', 'status']);
        });

        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'supports_personalization']);
            $table->dropIndex(['category', 'priority']);
        });
    }
};