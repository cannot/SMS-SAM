<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('key_hash')->unique();
            $table->string('key_value')->nullable(); // ชั่วคราวสำหรับแสดงผลครั้งแรกหลังสร้าง
            $table->boolean('is_active')->default(true);
            $table->integer('rate_limit_per_minute')->default(60);
            $table->integer('rate_limit_per_hour')->default(3600);
            $table->integer('rate_limit_per_day')->default(86400);
            $table->bigInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('permissions')->nullable(); // deprecated - ใช้ relationship แทน
            $table->json('allowed_ips')->nullable(); // Array of allowed IP addresses
            $table->json('metadata')->nullable(); // Additional metadata
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('auto_notifications')->default(false); // Auto send notifications about API key events
            $table->string('notification_webhook')->nullable(); // Webhook URL for notifications
            
            // Status tracking
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('status_changed_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Regeneration tracking
            $table->timestamp('regenerated_at')->nullable();
            $table->foreignId('regenerated_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Usage reset tracking
            $table->timestamp('usage_reset_at')->nullable();
            $table->foreignId('usage_reset_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete support
            
            // Indexes for performance
            $table->index('uuid');
            $table->index('key_hash');
            $table->index('is_active');
            $table->index('expires_at');
            $table->index('created_by');
            $table->index('assigned_to');
            $table->index(['is_active', 'expires_at']);
            $table->index('created_at');
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};