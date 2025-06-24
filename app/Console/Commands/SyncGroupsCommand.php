<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NotificationGroup;
use App\Services\LdapService;
use Illuminate\Support\Facades\Log;

class SyncGroupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'groups:sync 
                            {--type= : Sync specific group type (department, ldap_group, dynamic)}
                            {--group= : Sync specific group ID}
                            {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Sync notification group members from LDAP and other sources';

    protected $ldapService;

    /**
     * Create a new command instance.
     */
    public function __construct(LdapService $ldapService)
    {
        parent::__construct();
        $this->ldapService = $ldapService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $this->info('🔄 Starting group synchronization...');

        try {
            $query = NotificationGroup::active();

            // Filter by type if specified
            if ($this->option('type')) {
                $type = $this->option('type');
                if (!in_array($type, ['department', 'ldap_group', 'dynamic', 'role'])) {
                    $this->error("❌ Invalid group type: {$type}");
                    return 1;
                }
                $query->byType($type);
                $this->info("🎯 Filtering by type: {$type}");
            }

            // Filter by specific group if specified
            if ($this->option('group')) {
                $groupId = $this->option('group');
                $query->where('id', $groupId);
                $this->info("🎯 Syncing specific group ID: {$groupId}");
            }

            // Only sync auto-managed groups
            $groups = $query->whereIn('type', ['department', 'ldap_group', 'dynamic', 'role'])->get();

            if ($groups->isEmpty()) {
                $this->warn('⚠️  No auto-managed groups found to sync');
                return 0;
            }

            $this->info("📊 Found {$groups->count()} groups to sync");

            $totalUpdated = 0;
            $results = [];

            foreach ($groups as $group) {
                $this->line("🔄 Syncing: {$group->name} ({$group->type})");
                
                try {
                    $updated = $this->syncGroup($group);
                    $totalUpdated += $updated;
                    
                    $results[] = [
                        'group' => $group->name,
                        'type' => $group->type,
                        'updated' => $updated,
                        'status' => 'success'
                    ];

                    if ($updated > 0) {
                        $this->info("  ✅ Updated {$updated} members");
                    } else {
                        $this->line("  ℹ️  No changes needed");
                    }

                } catch (\Exception $e) {
                    $this->error("  ❌ Failed: " . $e->getMessage());
                    Log::error("Group sync error for {$group->name}: " . $e->getMessage());
                    
                    $results[] = [
                        'group' => $group->name,
                        'type' => $group->type,
                        'updated' => 0,
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                }
            }

            $this->displaySummary($results, $totalUpdated, $startTime);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Sync failed: " . $e->getMessage());
            Log::error("Groups sync command failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Sync individual group
     */
    protected function syncGroup(NotificationGroup $group): int
    {
        if ($this->option('dry-run')) {
            return $this->dryRunSync($group);
        }

        $beforeCount = $group->users()->count();
        
        // Get eligible users based on group criteria
        $eligibleUsers = $this->getEligibleUsers($group);
        
        // Sync users
        $result = $group->syncUsers($eligibleUsers);
        
        $afterCount = $group->users()->count();
        $updated = count($result['attached']) + count($result['detached']);

        // Log the sync
        Log::info("Group sync completed", [
            'group_id' => $group->id,
            'group_name' => $group->name,
            'before_count' => $beforeCount,
            'after_count' => $afterCount,
            'attached' => count($result['attached']),
            'detached' => count($result['detached']),
            'updated' => $updated
        ]);

        return $updated;
    }

    /**
     * Dry run sync - show what would change
     */
    protected function dryRunSync(NotificationGroup $group): int
    {
        $currentUsers = $group->users()->pluck('users.id')->toArray();
        $eligibleUsers = $this->getEligibleUsers($group);
        
        $toAttach = array_diff($eligibleUsers, $currentUsers);
        $toDetach = array_diff($currentUsers, $eligibleUsers);
        
        $this->line("  🔍 DRY RUN for {$group->name}:");
        $this->line("    Current members: " . count($currentUsers));
        $this->line("    Eligible members: " . count($eligibleUsers));
        $this->line("    Would add: " . count($toAttach));
        $this->line("    Would remove: " . count($toDetach));
        
        if (count($toAttach) > 0) {
            $addUsers = \App\Models\User::whereIn('id', array_slice($toAttach, 0, 5))->pluck('display_name')->toArray();
            $this->line("    Adding: " . implode(', ', $addUsers) . (count($toAttach) > 5 ? ' and ' . (count($toAttach) - 5) . ' more...' : ''));
        }
        
        if (count($toDetach) > 0) {
            $removeUsers = \App\Models\User::whereIn('id', array_slice($toDetach, 0, 5))->pluck('display_name')->toArray();
            $this->line("    Removing: " . implode(', ', $removeUsers) . (count($toDetach) > 5 ? ' and ' . (count($toDetach) - 5) . ' more...' : ''));
        }

        return count($toAttach) + count($toDetach);
    }

    /**
     * Get eligible users for a group based on its criteria
     */
    protected function getEligibleUsers(NotificationGroup $group): array
    {
        $query = \App\Models\User::active();

        switch ($group->type) {
            case 'department':
                if (isset($group->criteria['department'])) {
                    $query->where('department', $group->criteria['department']);
                } else {
                    $this->warn("  ⚠️  No department criteria set for group: {$group->name}");
                    return [];
                }
                break;

            case 'role':
                if (isset($group->criteria['title'])) {
                    $query->where('title', 'ILIKE', '%' . $group->criteria['title'] . '%');
                } else {
                    $this->warn("  ⚠️  No role criteria set for group: {$group->name}");
                    return [];
                }
                break;

            case 'ldap_group':
                if (isset($group->criteria['ldap_group'])) {
                    try {
                        $ldapMembers = $this->ldapService->getGroupMembers($group->criteria['ldap_group']);
                        if (!empty($ldapMembers)) {
                            $query->whereIn('email', $ldapMembers);
                        } else {
                            $this->warn("  ⚠️  No members found in LDAP group: {$group->criteria['ldap_group']}");
                            return [];
                        }
                    } catch (\Exception $e) {
                        $this->error("  ❌ LDAP error for group {$group->name}: " . $e->getMessage());
                        return [];
                    }
                } else {
                    $this->warn("  ⚠️  No LDAP group criteria set for group: {$group->name}");
                    return [];
                }
                break;

            case 'dynamic':
                if (isset($group->criteria['department'])) {
                    $query->where('department', $group->criteria['department']);
                }
                if (isset($group->criteria['title'])) {
                    $query->where('title', 'ILIKE', '%' . $group->criteria['title'] . '%');
                }
                
                // If no criteria set, warn
                if (empty($group->criteria)) {
                    $this->warn("  ⚠️  No criteria set for dynamic group: {$group->name}");
                    return [];
                }
                break;

            default:
                $this->warn("  ⚠️  Unknown group type: {$group->type} for group: {$group->name}");
                return [];
        }

        return $query->pluck('id')->toArray();
    }

    /**
     * Display sync summary
     */
    protected function displaySummary(array $results, int $totalUpdated, float $startTime): void
    {
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->newLine();
        $this->info('📋 SYNC SUMMARY');
        $this->info('=' . str_repeat('=', 50));

        $successful = collect($results)->where('status', 'success')->count();
        $failed = collect($results)->where('status', 'error')->count();

        $this->table(
            ['Group', 'Type', 'Updated', 'Status'],
            collect($results)->map(function ($result) {
                return [
                    $result['group'],
                    ucfirst($result['type']),
                    $result['updated'],
                    $result['status'] === 'success' ? '✅ Success' : '❌ Error'
                ];
            })->toArray()
        );

        $this->newLine();
        $this->info("📊 Statistics:");
        $this->line("  • Total groups processed: " . count($results));
        $this->line("  • Successful: {$successful}");
        $this->line("  • Failed: {$failed}");
        $this->line("  • Total member updates: {$totalUpdated}");
        $this->line("  • Duration: {$duration} seconds");

        if ($failed > 0) {
            $this->newLine();
            $this->error("❌ Errors occurred during sync:");
            foreach ($results as $result) {
                if ($result['status'] === 'error') {
                    $this->line("  • {$result['group']}: {$result['error']}");
                }
            }
        }

        $this->newLine();
        if ($this->option('dry-run')) {
            $this->warn('🔍 This was a DRY RUN - no actual changes were made');
        } else {
            $this->info('✅ Group synchronization completed successfully!');
        }
    }
}