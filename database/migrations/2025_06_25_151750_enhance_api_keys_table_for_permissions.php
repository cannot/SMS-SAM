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
        
        // สร้างตาราง api_key_permissions สำหรับ many-to-many relationship
        Schema::create('api_key_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained('api_keys')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();

            // Unique constraint เพื่อป้องกันการเพิ่ม permission เดียวกันให้ API key เดียวกันซ้ำ
            $table->unique(['api_key_id', 'permission_id'], 'api_key_permission_unique');
            
            // Indexes สำหรับ performance
            $table->index('api_key_id');
            $table->index('permission_id');
        });

        // สร้างตาราง api_key_events สำหรับ audit trail
        Schema::create('api_key_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained('api_keys')->onDelete('cascade');
            $table->string('event_type')->index(); // created, regenerated, activated, deactivated, deleted, permission_added, permission_removed
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');
            
            // Indexes
            $table->index(['api_key_id', 'event_type']);
            $table->index(['api_key_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index('performed_by');
        });

        // เพิ่ม columns ใน api_usage_logs ที่มีอยู่แล้ว (ถ้าจำเป็น)
        Schema::table('api_usage_logs', function (Blueprint $table) {
            // ตรวจสอบว่า column ที่ต้องการมีอยู่แล้วหรือไม่
            if (!Schema::hasColumn('api_usage_logs', 'request_id')) {
                $table->string('request_id')->nullable()->index()->after('error_message')
                    ->comment('Unique request identifier');
            }
            
            // เพิ่ม composite index ถ้ายังไม่มี
            // if (!$this->hasIndex('api_usage_logs', 'api_usage_composite_index')) {
            //     $table->index(['api_key_id', 'endpoint', 'created_at'], 'api_usage_composite_index');
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop ตารางใหม่ที่สร้าง
        Schema::dropIfExists('api_key_events');
        Schema::dropIfExists('api_key_permissions');
        
        // ลบ columns ที่เพิ่มใน api_usage_logs
        Schema::table('api_usage_logs', function (Blueprint $table) {
            if (Schema::hasColumn('api_usage_logs', 'request_id')) {
                $table->dropColumn('request_id');
            }
            
            // if ($this->hasIndex('api_usage_logs', 'api_usage_composite_index')) {
            //     $table->dropIndex('api_usage_composite_index');
            // }
        });
        
        // ลบ columns ที่เพิ่มเข้าไป
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'key_value',
                'rate_limit_per_hour',
                'rate_limit_per_day',
                'metadata',
                'uuid'
            ]);
            
            // เปลี่ยนชื่อกลับ
            $table->renameColumn('allowed_ips', 'ip_whitelist');
            
            // ลบ indexes
            $table->dropIndex(['uuid']);
            $table->dropIndex(['last_used_at']);
            $table->dropIndex(['usage_count']);
            $table->dropIndex(['deleted_at', 'is_active']);
        });
    }

    /**
     * ตรวจสอบว่ามี index อยู่แล้วหรือไม่
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = collect(Schema::getConnection()->getDoctrineSchemaManager()
            ->listTableIndexes($table));
        
        return $indexes->has($indexName);
    }
};