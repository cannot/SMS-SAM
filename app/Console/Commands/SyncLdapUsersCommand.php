<?php

// app/Console/Commands/SyncLdapUsersCommand.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LdapService;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class SyncLdapUsersCommand extends Command
{
    protected $signature = 'ldap:sync-users 
                           {--dry-run : Run without making changes}
                           {--assign-role=user : Default role to assign to new users}
                           {--force : Force sync even if LDAP is disabled}';

    protected $description = 'Synchronize users from LDAP directory';

    protected $ldapService;

    public function __construct(LdapService $ldapService)
    {
        parent::__construct();
        $this->ldapService = $ldapService;
    }

    public function handle()
    {
        $this->info('Starting LDAP user synchronization...');

        // Check if LDAP is enabled
        if (!config('ldap.enabled', false) && !$this->option('force')) {
            $this->error('LDAP is disabled. Use --force to override.');
            return 1;
        }

        // Check LDAP connection
        if (!$this->ldapService->testConnection()) {
            $this->error('Cannot connect to LDAP server.');
            return 1;
        }

        $isDryRun = $this->option('dry-run');
        $defaultRoleName = $this->option('assign-role');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Verify default role exists
        $defaultRole = Role::where('name', $defaultRoleName)->first();
        if (!$defaultRole) {
            $this->error("Role '{$defaultRoleName}' does not exist.");
            return 1;
        }

        $this->info("Default role for new users: {$defaultRole->display_name}");

        try {
            // Get all LDAP users
            $ldapUsers = $this->ldapService->getAllUsers();
            $this->info("Found " . count($ldapUsers) . " users in LDAP");

            $stats = [
                'total' => count($ldapUsers),
                'new' => 0,
                'updated' => 0,
                'errors' => 0,
                'skipped' => 0
            ];

            $progressBar = $this->output->createProgressBar(count($ldapUsers));
            $progressBar->start();

            foreach ($ldapUsers as $ldapUser) {
                try {
                    $username = $ldapUser->getFirstAttribute('samaccountname');
                    $email = $ldapUser->getFirstAttribute('mail');

                    if (empty($username) || empty($email)) {
                        $stats['skipped']++;
                        $this->warn("\nSkipping user with missing username or email");
                        continue;
                    }

                    // Check if user exists
                    $existingUser = User::where('ldap_guid', $ldapUser->getConvertedGuid())
                                      ->orWhere('username', $username)
                                      ->first();

                    $userData = [
                        'ldap_guid' => $ldapUser->getConvertedGuid(),
                        'username' => $username,
                        'email' => $email,
                        'first_name' => $ldapUser->getFirstAttribute('givenname') ?: '',
                        'last_name' => $ldapUser->getFirstAttribute('sn') ?: '',
                        'display_name' => $ldapUser->getFirstAttribute('displayname') ?: 
                                        trim(($ldapUser->getFirstAttribute('givenname') ?: '') . ' ' . 
                                             ($ldapUser->getFirstAttribute('sn') ?: '')),
                        'department' => $ldapUser->getFirstAttribute('department'),
                        'title' => $ldapUser->getFirstAttribute('title'),
                        'phone' => $ldapUser->getFirstAttribute('telephonenumber'),
                        'is_active' => !$this->isUserDisabled($ldapUser),
                        'auth_source' => 'ldap',
                        'ldap_synced_at' => now(),
                    ];

                    // Remove null values
                    $userData = array_filter($userData, function($value) {
                        return $value !== null && $value !== '';
                    });

                    if (!$isDryRun) {
                        if ($existingUser) {
                            $existingUser->update($userData);
                            $stats['updated']++;
                            $this->line("\nUpdated: {$username}");
                        } else {
                            $newUser = User::create($userData);
                            $newUser->assignRole($defaultRole);
                            $stats['new']++;
                            $this->line("\nCreated: {$username} (assigned role: {$defaultRoleName})");
                        }
                    } else {
                        if ($existingUser) {
                            $stats['updated']++;
                            $this->line("\n[DRY RUN] Would update: {$username}");
                        } else {
                            $stats['new']++;
                            $this->line("\n[DRY RUN] Would create: {$username} with role: {$defaultRoleName}");
                        }
                    }

                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->error("\nError processing user {$username}: " . $e->getMessage());
                }

                $progressBar->advance();
            }

            $progressBar->finish();

            // Display summary
            $this->info("\n\nSynchronization Summary:");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total LDAP Users', $stats['total']],
                    ['New Users', $stats['new']],
                    ['Updated Users', $stats['updated']],
                    ['Errors', $stats['errors']],
                    ['Skipped', $stats['skipped']]
                ]
            );

            if (!$isDryRun) {
                // Update cache
                cache()->put('ldap_last_sync', now()->toISOString(), 86400);
                $this->info('LDAP synchronization completed successfully!');
            } else {
                $this->warn('DRY RUN completed. Use without --dry-run to apply changes.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('LDAP synchronization failed: ' . $e->getMessage());
            Log::error('LDAP sync command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function isUserDisabled($ldapUser)
    {
        $userAccountControl = $ldapUser->getFirstAttribute('useraccountcontrol');
        
        if (!$userAccountControl) {
            return false;
        }

        // Check if ACCOUNTDISABLE flag (0x0002) is set
        return (intval($userAccountControl) & 0x0002) !== 0;
    }
}