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
        Schema::table('notification_groups', function (Blueprint $table) {
            // Add personalization support
            $table->string('webhook_url')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_groups', function (Blueprint $table) {
            $table->dropColumn('webhook_url');
        });
    }
};
