<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            
            // Type with more flexible options
            $table->enum('type', [
                'manual',       // Manual group
                'department',   // Department-based
                'role',         // Role-based
                'ldap_group',   // LDAP group
                'system',       // System-generated
                'dynamic'       // Dynamic criteria-based
            ])->default('manual');

            $table->text('ldap_filter')->nullable(); // For LDAP-based groups
            $table->json('criteria')->nullable(); // For dynamic groups
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('is_active');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_groups');
    }
};