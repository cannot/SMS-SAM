<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompletePermissionsSeeder::class,
            DefaultUserSeeder::class,
            //NotificationTemplateSeeder::class,
            ApiPermissionsSeeder::class,
            // DefaultGroupSeeder::class,
            // GroupPermissionsSeeder::class,
            
        ]);
    }
}
