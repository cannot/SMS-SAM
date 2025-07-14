<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sync LDAP users every 6 hours
        $schedule->job(\App\Jobs\SyncLdapUsers::class)
                 ->everySixHours()
                 ->withoutOverlapping();

        // Process scheduled notifications every minute
        $schedule->call(function () {
            app(\App\Services\NotificationService::class)->processScheduledNotifications();
        })->everyMinute();

        // Retry failed notifications every 5 minutes
        $schedule->call(function () {
            app(\App\Services\NotificationService::class)->retryFailedNotifications();
        })->everyFiveMinutes();

        // Clean up old logs (older than 3 months)
        $schedule->call(function () {
            \App\Models\ApiUsageLog::where('created_at', '<', now()->subMonths(3))->delete();
            \App\Models\NotificationLog::where('created_at', '<', now()->subYear())->delete();
        })->daily();

        // Generate daily reports
        $schedule->job(\App\Jobs\GenerateReports::class, 'delivery', [
            'start' => now()->subDay()->startOfDay(),
            'end' => now()->subDay()->endOfDay()
        ])->dailyAt('02:00');

        // // Run SQL alerts every minute
        // $schedule->command('sql-alerts:run')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->runInBackground();

        // // Update schedules every hour
        // $schedule->command('sql-alerts:schedule')
        //     ->hourly();

        // // Cleanup old data daily at 2 AM
        // $schedule->command('sql-alerts:cleanup --days=30 --attachments=7')
        //     ->dailyAt('02:00');

        // // Health check every 5 minutes
        // $schedule->command('sql-alerts:run --dry-run')
        //     ->everyFiveMinutes()
        //     ->appendOutputTo(storage_path('logs/sql-alerts-health.log'));

    }
    

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}