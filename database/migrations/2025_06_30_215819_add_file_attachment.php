<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('variables');
            $table->json('attachment_urls')->nullable()->after('attachments');
            $table->bigInteger('attachments_size')->default(0)->after('attachment_urls');
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->json('attachment_paths')->nullable()->after('content_sent');
            $table->json('attachment_info')->nullable()->after('attachment_paths');
        });

    }

    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['attachments', 'attachments_size']);
            $table->dropColumn('attachment_urls');
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropColumn(['attachment_paths', 'attachment_info']);
        });
    }
};