<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $emailService = app(\App\Services\EmailService::class);
            $this->info('✅ EmailService: Available');
            
            $teamsService = app(\App\Services\TeamsService::class);
            $this->info('✅ TeamsService: Available');
            
            $notificationService = app(\App\Services\NotificationService::class);
            $this->info('✅ NotificationService: Available');
            
        } catch (\Exception $e) {
            $this->error('❌ Service Error: ' . $e->getMessage());
        }
    }
}
