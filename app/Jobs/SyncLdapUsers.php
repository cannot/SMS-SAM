<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\LdapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SyncLdapUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [30, 60, 120]; // Retry after 30s, 60s, 120s

    protected $batchSize = 100;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Set queue and connection if needed
        $this->onQueue('ldap-sync');
    }

    /**
     * Execute the job.
     */
    public function handle(LdapService $ldapService): void
    {
        Log::info('LDAP sync job started');
        
        try {
            // Set sync status in cache
            Cache::put('ldap_sync_status', 'running', 3600);
            Cache::put('ldap_sync_progress', 0, 3600);

            // Get all users from LDAP
            $ldapUsers = $ldapService->getAllUsers();
            $totalUsers = count($ldapUsers);

            if ($totalUsers === 0) {
                Log::warning('No users found in LDAP');
                Cache::put('ldap_sync_status', 'completed', 3600);
                return;
            }

            Log::info("Found {$totalUsers} users in LDAP");

            $syncedCount = 0;
            $createdCount = 0;
            $updatedCount = 0;
            $errors = [];

            // Process users in batches
            $batches = array_chunk($ldapUsers, $this->batchSize);
            $totalBatches = count($batches);

            foreach ($batches as $batchIndex => $batch) {
                try {
                    DB::beginTransaction();

                    foreach ($batch as $ldapUser) {
                        try {
                            $result = $this->syncUser($ldapUser);
                            
                            if ($result['action'] === 'created') {
                                $createdCount++;
                            } elseif ($result['action'] === 'updated') {
                                $updatedCount++;
                            }
                            
                            $syncedCount++;

                        } catch (\Exception $e) {
                            $errors[] = [
                                'username' => $ldapUser['username'] ?? 'unknown',
                                'error' => $e->getMessage()
                            ];
                            Log::error('Error syncing individual user', [
                                'username' => $ldapUser['username'] ?? 'unknown',
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    DB::commit();

                    // Update progress
                    $progress = round((($batchIndex + 1) / $totalBatches) * 100);
                    Cache::put('ldap_sync_progress', $progress, 3600);

                    Log::info("Completed batch " . ($batchIndex + 1) . "/{$totalBatches}");

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error processing batch', [
                        'batch_index' => $batchIndex,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            // Mark inactive users (users not found in LDAP)
            $this->markInactiveUsers($ldapUsers);

            // Update sync completion status
            Cache::put('ldap_sync_status', 'completed', 3600);
            Cache::put('ldap_sync_progress', 100, 3600);
            Cache::put('ldap_last_sync', now(), 86400); // 24 hours
            Cache::put('ldap_sync_stats', [
                'total_processed' => $totalUsers,
                'synced' => $syncedCount,
                'created' => $createdCount,
                'updated' => $updatedCount,
                'errors' => count($errors),
                'completed_at' => now()
            ], 86400);

            Log::info('LDAP sync completed successfully', [
                'total_processed' => $totalUsers,
                'synced' => $syncedCount,
                'created' => $createdCount,
                'updated' => $updatedCount,
                'errors' => count($errors)
            ]);

            // Send notification to admins about sync completion
            $this->notifyAdmins($syncedCount, $createdCount, $updatedCount, $errors);

        } catch (\Exception $e) {
            Cache::put('ldap_sync_status', 'failed', 3600);
            Log::error('LDAP sync job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Sync individual user from LDAP data
     */
    private function syncUser(array $ldapUser): array
    {
        $username = $ldapUser['username'];
        $existingUser = User::where('username', $username)->first();

        $userData = [
            'username' => $username,
            'name' => $ldapUser['name'] ?? $username,
            'display_name' => $ldapUser['display_name'] ?? $ldapUser['name'] ?? $username,
            'email' => $ldapUser['email'] ?? null,
            'department' => $ldapUser['department'] ?? null,
            'title' => $ldapUser['title'] ?? null,
            'phone' => $ldapUser['phone'] ?? null,
            'ldap_dn' => $ldapUser['dn'] ?? null,
            'ldap_guid' => $ldapUser['guid'] ?? null,
            'employee_id' => $ldapUser['employee_id'] ?? null,
            'manager' => $ldapUser['manager'] ?? null,
            'is_active' => true,
            'ldap_synced_at' => now()
        ];

        if ($existingUser) {
            // Update existing user
            $existingUser->update($userData);
            
            Log::debug('Updated user from LDAP', [
                'username' => $username,
                'user_id' => $existingUser->id
            ]);

            return ['action' => 'updated', 'user' => $existingUser];
        } else {
            // Create new user
            $userData['password'] = bcrypt(str()->random(32)); // Random password, won't be used
            $userData['email_verified_at'] = now();
            
            $user = User::create($userData);
            
            Log::info('Created new user from LDAP', [
                'username' => $username,
                'user_id' => $user->id
            ]);

            return ['action' => 'created', 'user' => $user];
        }
    }

    /**
     * Mark users as inactive if they're not found in LDAP
     */
    private function markInactiveUsers(array $ldapUsers): void
    {
        $ldapUsernames = array_column($ldapUsers, 'username');
        
        $inactiveCount = User::whereNotIn('username', $ldapUsernames)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ldap_synced_at' => now()
            ]);

        if ($inactiveCount > 0) {
            Log::info("Marked {$inactiveCount} users as inactive (not found in LDAP)");
        }
    }

    /**
     * Send notification to admins about sync completion
     */
    private function notifyAdmins(int $syncedCount, int $createdCount, int $updatedCount, array $errors): void
    {
        try {
            // Get admin users
            $admins = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'system-admin']);
            })->get();

            if ($admins->isEmpty()) {
                return;
            }

            // Create notification data
            $subject = 'LDAP Sync Completed';
            $message = "LDAP synchronization has been completed successfully.\n\n";
            $message .= "Summary:\n";
            $message .= "- Total synced: {$syncedCount} users\n";
            $message .= "- New users created: {$createdCount}\n";
            $message .= "- Existing users updated: {$updatedCount}\n";
            
            if (!empty($errors)) {
                $message .= "- Errors encountered: " . count($errors) . "\n";
                $message .= "\nErrors:\n";
                foreach (array_slice($errors, 0, 5) as $error) {
                    $message .= "- {$error['username']}: {$error['error']}\n";
                }
                if (count($errors) > 5) {
                    $message .= "- ... and " . (count($errors) - 5) . " more errors\n";
                }
            }

            // Create notification record
            $notification = \App\Models\Notification::create([
                'uuid' => \Str::uuid(),
                'subject' => $subject,
                'body_text' => $message,
                'body_html' => nl2br($message),
                'channels' => ['email'],
                'recipients' => $admins->pluck('id')->toArray(),
                'priority' => !empty($errors) ? 'high' : 'normal',
                'status' => 'queued',
                'total_recipients' => $admins->count(),
                'created_by' => null // System generated
            ]);

            // Dispatch notification
            \App\Jobs\SendNotification::dispatch($notification);

        } catch (\Exception $e) {
            Log::error('Failed to send LDAP sync notification to admins', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Cache::put('ldap_sync_status', 'failed', 3600);
        Cache::put('ldap_sync_error', $exception->getMessage(), 3600);

        Log::error('LDAP sync job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Notify admins about failure
        try {
            $admins = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'system-admin']);
            })->get();

            if (!$admins->isEmpty()) {
                $notification = \App\Models\Notification::create([
                    'uuid' => \Str::uuid(),
                    'subject' => 'LDAP Sync Failed',
                    'body_text' => "LDAP synchronization has failed.\n\nError: " . $exception->getMessage(),
                    'body_html' => "LDAP synchronization has failed.<br><br><strong>Error:</strong> " . $exception->getMessage(),
                    'channels' => ['email'],
                    'recipients' => $admins->pluck('id')->toArray(),
                    'priority' => 'urgent',
                    'status' => 'queued',
                    'total_recipients' => $admins->count(),
                    'created_by' => null
                ]);

                \App\Jobs\SendNotification::dispatch($notification);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send LDAP sync failure notification', [
                'error' => $e->getMessage()
            ]);
        }
    }
}