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
        Schema::table('notifications', function (Blueprint $table) {
            // เพิ่ม notification_group_id
            $table->foreignId('notification_group_id')->nullable()->after('template_id')
                  ->constrained('notification_groups')
                  ->onDelete('set null');
                  
            // เพิ่ม index
            $table->index('notification_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['notification_group_id']);
            $table->dropColumn('notification_group_id');
        });
    }
};