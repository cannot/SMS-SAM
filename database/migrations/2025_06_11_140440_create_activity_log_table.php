<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('activity_log')) {
            Schema::create('activity_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('log_name')->nullable();
                $table->text('description');
                $table->nullableMorphs('subject', 'subject');
                $table->nullableMorphs('causer', 'causer'); 
                $table->json('properties')->nullable();
                $table->string('event')->nullable(); // เพิ่ม column นี้
                $table->uuid('batch_uuid')->nullable();
                $table->timestamps();
                
                $table->index('log_name');
                $table->index(['subject_id', 'subject_type'], 'activity_log_subject_type_id_idx');
                $table->index(['causer_id', 'causer_type'], 'activity_log_causer_type_id_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('activity_log');
    }
};