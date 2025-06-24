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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            
            // Notification Channels
            $table->boolean('enable_teams')->default(true);
            $table->boolean('enable_email')->default(true);
            $table->boolean('enable_sms')->default(false);
            
            // Teams Settings
            $table->string('teams_user_id')->nullable();
            $table->enum('teams_channel_preference', ['direct', 'channel'])->default('direct');
            $table->string('teams_channel_id')->nullable();
            
            // Email Settings
            $table->string('email_address')->nullable();
            $table->enum('email_format', ['html', 'plain'])->default('html');
            
            // SMS Settings
            $table->string('sms_number')->nullable();
            
            // Priority Settings
            $table->enum('min_priority', ['low', 'normal', 'high', 'urgent'])->default('low');
            
            // Quiet Hours
            $table->boolean('enable_quiet_hours')->default(false);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->json('quiet_days')->nullable(); // ['monday', 'tuesday', ...]
            $table->boolean('override_high_priority')->default(false);
            
            // Notification Grouping
            $table->boolean('enable_grouping')->default(true);
            $table->enum('grouping_method', ['sender', 'type', 'time'])->default('sender');
            $table->integer('grouping_interval')->default(5); // minutes
            
            // Frequency Settings
            $table->enum('email_frequency', ['immediate', 'hourly', 'daily', 'weekly'])->default('immediate');
            $table->enum('teams_frequency', ['immediate', 'hourly', 'daily'])->default('immediate');
            
            // Localization
            $table->string('language', 10)->default('th');
            $table->string('timezone', 50)->default('Asia/Bangkok');
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('time_format', 10)->default('H:i');
            
            // Advanced Settings
            $table->boolean('enable_read_receipts')->default(true);
            $table->boolean('enable_delivery_reports')->default(true);
            $table->json('custom_filters')->nullable(); // Custom notification filters
            
            // Digest Settings
            $table->boolean('enable_digest')->default(false);
            $table->enum('digest_frequency', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->time('digest_time')->default('08:00');
            $table->enum('digest_day', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->default('monday');
            
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes
            $table->index('user_id');
            $table->index(['enable_teams', 'enable_email']);
            $table->index('language');
            $table->index('timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};