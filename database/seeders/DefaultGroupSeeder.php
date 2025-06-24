<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationGroup;
use App\Models\User;

class DefaultGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // สร้างกลุ่มเริ่มต้น
        $defaultGroups = [
            [
                'name' => 'ผู้ดูแลระบบ',
                'description' => 'กลุ่มผู้ดูแลระบบทั้งหมด',
                'type' => 'role',
                'criteria' => ['title' => 'Admin'],
                'is_active' => true,
                'created_by' => 2,
            ],
            [
                'name' => 'แผนก IT',
                'description' => 'พนักงานแผนกเทคโนโลยีสารสนเทศ',
                'type' => 'department',
                'criteria' => ['department' => 'IT'],
                'is_active' => true,
                'created_by' => 2,
            ],
            [
                'name' => 'ผู้บริหาร',
                'description' => 'ผู้บริหารระดับสูงของบริษัท',
                'type' => 'role',
                'criteria' => ['title' => 'Manager'],
                'is_active' => true,
                'created_by' => 2,
            ],
            [
                'name' => 'การแจ้งเตือนทั่วไป',
                'description' => 'กลุ่มสำหรับการแจ้งเตือนทั่วไปให้พนักงานทุกคน',
                'type' => 'manual',
                'criteria' => null,
                'is_active' => true,
                'created_by' => 2,
            ],
            [
                'name' => 'ทีมพัฒนา',
                'description' => 'นักพัฒนาซอฟต์แวร์และโปรแกรมเมอร์',
                'type' => 'role',
                'criteria' => ['title' => 'Developer'],
                'is_active' => true,
                'created_by' => 2,
            ],
        ];

        foreach ($defaultGroups as $groupData) {
            $group = NotificationGroup::create($groupData);
            
            // สำหรับกลุ่มแบบ manual เพิ่มผู้ใช้งานทั้งหมดเข้าไป
            if ($group->type === 'manual' && $group->name === 'การแจ้งเตือนทั่วไป') {
                $allUsers = User::active()->pluck('id')->toArray();
                if (!empty($allUsers)) {
                    $group->addUsers($allUsers, 2);
                }
            }
            
            echo "Created group: {$group->name}\n";
        }

        echo "Default notification groups created successfully!\n";
    }
}