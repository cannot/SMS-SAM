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
        Schema::table('notification_logs', function (Blueprint $table) {
            // Add user_id column
            $table->unsignedBigInteger('user_id')->nullable()->after('notification_id');
            
            // Add read tracking columns
            $table->timestamp('read_at')->nullable()->after('delivered_at');
            $table->timestamp('archived_at')->nullable()->after('read_at');
            
            // Add attempts column for retry tracking
            $table->integer('attempts')->default(1)->after('retry_count');
            
            // Add foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Add indexes for better performance
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'archived_at']);
            $table->index(['recipient_email', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['user_id']);
            
            // Drop indexes
            $table->dropIndex(['user_id', 'read_at']);
            $table->dropIndex(['user_id', 'archived_at']);
            $table->dropIndex(['recipient_email', 'status']);
            
            // Drop columns
            $table->dropColumn(['user_id', 'read_at', 'archived_at', 'attempts']);
        });
    }
};