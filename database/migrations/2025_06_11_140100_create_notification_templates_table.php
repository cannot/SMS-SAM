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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('category', 50)->default('custom');
            
            // Template content
            $table->string('subject_template');
            $table->longText('body_html_template')->nullable();
            $table->longText('body_text_template')->nullable();
            
            // Variables and configuration
            $table->json('variables')->nullable(); // Required variables
            $table->json('default_variables')->nullable(); // Default values
            $table->json('supported_channels')->default('["email"]'); // Supported channels
            
            // ✅ Fixed: ใช้ enum เดียวกันกับ notifications table
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            
            // Status and versioning
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            
            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['category', 'is_active']);
            $table->index(['slug']);
            $table->index(['created_by']);
            $table->index(['is_active', 'category']);
            
            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};