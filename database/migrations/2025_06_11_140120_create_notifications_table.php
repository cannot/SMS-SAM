<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            
            // ✅ Fixed: template_id อ้างอิง notification_templates ที่สร้างไปแล้ว
            $table->unsignedBigInteger('template_id')->nullable();
            $table->foreign('template_id')->references('id')->on('notification_templates')->onDelete('set null');
            
            $table->string('subject');
            $table->text('body_html')->nullable();
            $table->text('body_text')->nullable();
            $table->json('channels'); // ['email', 'teams']
            $table->json('recipients'); // Array of user IDs or email addresses
            $table->json('recipient_groups')->nullable(); // Array of group IDs
            $table->json('variables')->nullable(); // Template variables
            $table->text('webhook_url')->nullable();
            
            // ✅ Fixed: ใช้ enum เดียวกันกับ notification_templates
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['draft', 'scheduled', 'queued', 'processing', 'sent', 'failed', 'cancelled']);
            
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->text('failure_reason')->nullable();
            $table->integer('api_key_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['status', 'scheduled_at']);
            $table->index(['created_by', 'created_at']);
            $table->index(['template_id']);
            $table->index('api_key_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};