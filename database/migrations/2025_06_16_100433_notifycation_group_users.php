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
        Schema::create('notification_group_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_group_id')
                  ->constrained('notification_groups')
                  ->onDelete('cascade');
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->timestamp('joined_at')->default(now());
            $table->foreignId('added_by')->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamps();

            // Unique constraint to prevent duplicate entries
            $table->unique(['notification_group_id', 'user_id'], 'unique_group_user');
            
            // Indexes for better performance
            $table->index('notification_group_id');
            $table->index('user_id');
            $table->index('joined_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_group_users');
    }
};