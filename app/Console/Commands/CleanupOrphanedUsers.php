<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CleanupOrphanedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:cleanup-orphaned
                           {--force : Actually perform the cleanup (default is dry-run)}
                           {--set-null : Set employee_id to null instead of deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up users with invalid employee_id references';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for orphaned users...');

        // Find users with employee_id that doesn't exist
        $orphanedUsers = User::where('employee_id', '!=', null)
                           ->whereDoesntHave('employee')
                           ->get();

        $count = $orphanedUsers->count();

        if ($count === 0) {
            $this->info('No orphaned users found. All users have valid employee references.');
            return 0;
        }

        $this->warn("Found {$count} users with invalid employee_id references:");

        // Display orphaned users
        $orphanedUsers->each(function ($user) {
            $this->line("  User ID: {$user->id} | Email: {$user->email} | Invalid employee_id: {$user->employee_id}");
        });

        $force = $this->option('force');
        $setNull = $this->option('set-null');

        if (!$force) {
            $this->warn("\nThis is a DRY RUN. No changes were made.");
            $this->info("To actually clean up these users, run with --force");
            $this->info("Options:");
            $this->info("  --force --set-null  : Set employee_id to null (recommended)");
            $this->info("  --force             : Delete orphaned users (use with caution)");
            return 0;
        }

        if ($setNull) {
            $this->info("\nSetting employee_id to null for orphaned users...");

            $orphanedUsers->each(function ($user) {
                $user->update(['employee_id' => null]);
                $this->line("✓ User ID {$user->id}: employee_id set to null");
            });

            $this->info("\nCleanup completed! {$count} users updated.");
        } else {
            $this->warn("\nWARNING: This will DELETE {$count} users permanently.");

            if (!$this->confirm('Are you sure you want to delete these orphaned users?')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }

            $orphanedUsers->each(function ($user) {
                $this->line("✗ User ID {$user->id} ({$user->email}) deleted");
                $user->delete();
            });

            $this->info("\nCleanup completed! {$count} orphaned users deleted.");
        }

        return 0;
    }
}