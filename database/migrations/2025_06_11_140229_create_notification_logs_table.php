<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_id');
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->enum('channel', ['email', 'teams']);
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'bounced']);
            $table->text('response_data')->nullable(); // API response
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();
            
            $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
            $table->index(['notification_id', 'status']);
            $table->index(['channel', 'status']);
            $table->index('next_retry_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_logs');
    }
};