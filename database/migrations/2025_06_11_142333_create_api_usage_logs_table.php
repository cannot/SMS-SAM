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
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained('api_keys')->onDelete('cascade');
            $table->foreignId('notification_id')->nullable()->constrained('notifications')->onDelete('set null');
            $table->string('endpoint'); // API endpoint called
            $table->string('method', 10); // HTTP method (GET, POST, etc.)
            $table->string('ip_address', 45); // IPv4 or IPv6
            $table->text('user_agent')->nullable();
            $table->integer('response_code'); // HTTP response code
            $table->integer('response_time')->nullable(); // Response time in milliseconds
            $table->json('request_data')->nullable(); // Request payload (sanitized)
            $table->json('response_data')->nullable(); // Response data (if needed)
            $table->text('error_message')->nullable(); // Error message if failed
            $table->string('request_id')->nullable(); // Unique request identifier
            $table->timestamp('created_at')->useCurrent(); // Only created_at needed for logs
            
            // Indexes for performance
            $table->index('api_key_id');
            $table->index('notification_id');
            $table->index('endpoint');
            $table->index('response_code');
            $table->index('created_at');
            $table->index('ip_address');
            $table->index(['api_key_id', 'created_at']);
            $table->index(['api_key_id', 'response_code']);
            $table->index(['created_at', 'response_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
    }
};