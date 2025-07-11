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
        // เพิ่ม index สำหรับการ query สถิติให้เร็วขึ้น
        Schema::table('notifications', function (Blueprint $table) {
            // Index สำหรับ filter ตามวันที่และสถานะ
            $table->index(['created_at', 'status'], 'idx_notifications_date_status');
            
            // Index สำหรับ filter ตาม template และวันที่
            $table->index(['template_id', 'created_at'], 'idx_notifications_template_date');
            
            // Index สำหรับ filter ตามกลุ่มและวันที่
            $table->index(['notification_group_id', 'created_at'], 'idx_notifications_group_date');
            
            // เพิ่มคอลัมน์สำหรับเก็บข้อมูลเพิ่มเติม
            $table->integer('retry_count')->default(0)->after('sent_at');
            $table->text('error_message')->nullable()->after('retry_count');
            $table->json('delivery_details')->nullable()->after('error_message');
        });

        // สร้างตารางสำหรับเก็บสถิติที่คำนวณไว้ล่วงหน้า (Optional)
        Schema::create('notification_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('channel')->nullable();
            $table->string('status');
            $table->integer('count')->default(0);
            $table->timestamps();
            
            // Index สำหรับการ query ที่เร็ว
            $table->unique(['date', 'channel', 'status'], 'uk_stats_date_channel_status');
            $table->index(['date', 'status'], 'idx_stats_date_status');
        });

        // สร้างตารางสำหรับเก็บ Log การเข้าถึงหน้าสถิติ
        Schema::create('statistics_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('page');
            $table->string('date_range');
            $table->json('filters')->nullable();
            $table->timestamp('accessed_at');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'accessed_at'], 'idx_access_logs_user_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // ลบ index
            $table->dropIndex('idx_notifications_date_status');
            $table->dropIndex('idx_notifications_template_date');
            $table->dropIndex('idx_notifications_group_date');
            
            // ลบคอลัมน์ที่เพิ่มเข้ามา
            $table->dropColumn(['retry_count', 'error_message', 'delivery_details']);
        });

        Schema::dropIfExists('statistics_access_logs');
        Schema::dropIfExists('notification_statistics');
    }
};