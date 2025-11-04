<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class CheckMigrationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrations:status
                            {--run : Run migrations if they are pending}
                            {--force : Force migration execution}
                            {--check-only : Only check status, do not run migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check migration status and optionally run migrations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking migration status...');

        // Check database connectivity first
        if (!$this->checkDatabaseConnection()) {
            $this->error('Database is not accessible!');
            return 1;
        }

        // Check migration status
        $status = $this->getMigrationStatus();

        if ($status['status'] === 'completed') {
            $this->info('✅ All migrations have been completed successfully!');
            $this->line("Migrations run: {$status['migration_count']}");
            $this->line("Completed at: {$status['completed_at']}");
            return 0;
        }

        if ($status['status'] === 'running') {
            $this->warn('⏳ Migrations are currently running...');
            $this->line("Started at: {$status['start_time']}");
            $this->line("Duration: {$status['duration_seconds']} seconds");
            return 0;
        }

        if ($status['status'] === 'pending') {
            $this->warn('⚠️  Migrations are pending and need to be run.');

            if ($this->option('check-only')) {
                $this->line('Use --run option to run migrations');
                return 0;
            }

            if ($this->option('run') || $this->confirm('Do you want to run migrations now?')) {
                return $this->runMigrations();
            }

            return 1;
        }

        if ($status['status'] === 'unknown') {
            $this->error('❌ Unable to determine migration status.');
            $this->line('Error: ' . $status['error']);

            if ($this->option('run') || $this->confirm('Do you want to try running migrations anyway?')) {
                return $this->runMigrations();
            }

            return 1;
        }

        return 0;
    }

    /**
     * Check database connection
     *
     * @return bool
     */
    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get migration status
     *
     * @return array
     */
    private function getMigrationStatus(): array
    {
        $migrationFlagFile = storage_path('app/migrations_complete.flag');
        $migrationLockFile = storage_path('app/migration.lock');

        // Check if migrations are marked complete
        if (file_exists($migrationFlagFile)) {
            try {
                $migrationData = json_decode(file_get_contents($migrationFlagFile), true);
                return [
                    'status' => 'completed',
                    'migration_count' => $migrationData['migration_count'] ?? 'Unknown',
                    'completed_at' => $migrationData['completed_at'] ?? 'Unknown'
                ];
            } catch (\Exception $e) {
                // Flag file exists but is corrupted, continue with other checks
            }
        }

        // Check if migrations are currently running
        if (file_exists($migrationLockFile)) {
            $lockTime = filemtime($migrationLockFile);
            return [
                'status' => 'running',
                'start_time' => date('Y-m-d H:i:s', $lockTime),
                'duration_seconds' => time() - $lockTime
            ];
        }

        // Check database directly
        try {
            if (Schema::hasTable('migrations')) {
                $migrationCount = DB::table('migrations')->count();

                if ($migrationCount > 0) {
                    return [
                        'status' => 'completed',
                        'migration_count' => $migrationCount,
                        'completed_at' => 'Unknown',
                        'note' => 'Detected from migrations table'
                    ];
                } else {
                    return [
                        'status' => 'pending',
                        'note' => 'Migrations table exists but is empty'
                    ];
                }
            } else {
                return [
                    'status' => 'pending',
                    'note' => 'Migrations table does not exist'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Run migrations
     *
     * @return int
     */
    private function runMigrations(): int
    {
        $this->info('Running migrations...');

        try {
            // Create migration lock file
            $lockFile = storage_path('app/migration.lock');
            File::put($lockFile, (string) time());

            // Check if migrations table exists
            if (!Schema::hasTable('migrations')) {
                $this->line('Creating migrations table...');
                $exitCode = $this->call('migrate:install');
                if ($exitCode !== 0) {
                    throw new \Exception('Failed to create migrations table');
                }
            }

            // Run migrations
            $exitCode = $this->call('migrate', [
                '--force' => $this->option('force') || app()->environment('production')
            ]);

            if ($exitCode !== 0) {
                throw new \Exception('Migration command failed with exit code: ' . $exitCode);
            }

            // Get migration count
            $migrationCount = DB::table('migrations')->count();

            // Create completion flag
            $completionData = [
                'completed_at' => now()->toISOString(),
                'migration_count' => $migrationCount
            ];
            File::put(storage_path('app/migrations_complete.flag'), json_encode($completionData));

            // Remove lock file
            File::delete($lockFile);

            $this->info('✅ Migrations completed successfully!');
            $this->line("Total migrations run: {$migrationCount}");

            // Ask if user wants to run production seeder
            if ($this->shouldRunProductionSeeder()) {
                if ($this->confirm('Production seeder can be run. Do you want to run it?')) {
                    $this->runProductionSeeder();
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());

            // Clean up lock file on error
            if (file_exists($lockFile)) {
                File::delete($lockFile);
            }

            return 1;
        }
    }

    /**
     * Check if production seeder should run
     *
     * @return bool
     */
    private function shouldRunProductionSeeder(): bool
    {
        if (!Schema::hasTable('users')) {
            return false;
        }

        return DB::table('users')->count() === 0;
    }

    /**
     * Run production seeder
     *
     * @return void
     */
    private function runProductionSeeder(): void
    {
        $this->info('Running production seeder...');

        $exitCode = $this->call('db:seed', [
            '--class' => 'ProductionDatabaseSeeder',
            '--force' => true
        ]);

        if ($exitCode !== 0) {
            $this->warn('Production seeder failed');
        } else {
            $this->info('✅ Production seeder completed successfully');
        }
    }
}