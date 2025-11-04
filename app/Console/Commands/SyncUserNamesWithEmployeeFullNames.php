<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUserNamesWithEmployeeFullNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:sync-names
                           {--force : Force synchronization even if user name already exists}
                           {--show-diff : Show what changes would be made without executing}
                           {--batch-size=100 : Process records in batches to avoid memory issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize user names with employee full names including extensions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting user name synchronization...');
        $force = $this->option('force');
        $showDiff = $this->option('show-diff');
        $batchSize = (int) $this->option('batch-size');

        if ($showDiff) {
            $this->warn('SHOW DIFF MODE: No changes will be made.');
        }

        // Get all users with associated employees
        $query = User::with('employee')
                     ->whereHas('employee')
                     ->whereNotNull('employee_id');

        $totalUsers = $query->count();
        $this->info("Found {$totalUsers} users with associated employees.");

        if ($totalUsers === 0) {
            $this->info('No users to synchronize.');
            return 0;
        }

        $processed = 0;
        $updated = 0;
        $skipped = 0;

        // Create a progress bar
        $progressBar = $this->output->createProgressBar($totalUsers);
        $progressBar->start();

        $query->chunk($batchSize, function ($users) use (&$processed, &$updated, &$skipped, $force, $showDiff, $progressBar) {
            foreach ($users as $user) {
                $processed++;

                try {
                    if (!$user->employee || !$user->employee->exists) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }

                    $currentName = trim($user->name);
                    $correctName = trim($user->employee->full_name);

                    // Skip if names are identical and not forcing
                    if ($currentName === $correctName && !$force) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }

                    // Show diff if requested
                    if ($showDiff || !$this->option('no-interaction')) {
                        if ($currentName !== $correctName) {
                            $this->line("\nUser ID: {$user->id} | Email: {$user->email}");
                            $this->line("  Current: '{$currentName}'");
                            $this->line("  Correct: '{$correctName}'");

                            if ($showDiff) {
                                $progressBar->advance();
                                continue;
                            }
                        }
                    }

                    // Update user name if not in show-diff mode
                    if (!$showDiff) {
                        $user->update(['name' => $correctName]);
                        $updated++;

                        if ($this->option('verbose')) {
                            $this->line("\n✓ Updated User ID: {$user->id} | Email: {$user->email}");
                            $this->line("  '{$currentName}' → '{$correctName}'");
                        }
                    }

                } catch (\Exception $e) {
                    $this->error("\nError processing User ID {$user->id}: " . $e->getMessage());
                    $skipped++;
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->line("\n");

        // Summary
        $this->info('User name synchronization completed!');
        $this->info("Total users processed: {$processed}");

        if ($showDiff) {
            $this->info("Total users that would be updated: " . ($processed - $skipped));
        } else {
            $this->info("Total users updated: {$updated}");
            $this->info("Total users skipped: {$skipped}");
        }

        // Report any orphaned users
        $orphanedCount = User::where('employee_id', '!=', null)
                           ->whereDoesntHave('employee')
                           ->count();

        if ($orphanedCount > 0) {
            $this->warn("\nFound {$orphanedCount} users with invalid employee_id references.");
            $this->warn('These users were not processed. Consider running:');
            $this->warn('  php artisan users:cleanup-orphaned');
        }

        return 0;
    }
}