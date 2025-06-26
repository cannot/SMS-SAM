<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessPendingNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-pending-notifications';

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
        $pendingNotifications = \App\Models\Notification::where('status', 'queued')
            ->where('created_at', '>', now()->subHours(24))
            ->get();
        
        foreach ($pendingNotifications as $notification) {
            $this->info("Processing notification: {$notification->uuid}");
            
            $notificationService = app(\App\Services\NotificationService::class);
            $result = $notificationService->processNotification($notification);
            
            if ($result) {
                $this->info("✅ Processed successfully");
            } else {
                $this->error("❌ Failed to process");
            }
        }
    }
}
