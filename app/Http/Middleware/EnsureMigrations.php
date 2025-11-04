<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class EnsureMigrations
{
    /**
     * Migration lock file path
     */
    private const MIGRATION_LOCK_FILE = storage_path('app/migration.lock');

    /**
     * Migration completion flag file path
     */
    private const MIGRATION_COMPLETE_FLAG = storage_path('app/migrations_complete.flag');

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip migration check for non-web routes or during testing
        if ($request->is('api/*') || $request->is('health') || app()->environment('testing')) {
            return $next($request);
        }

        // Check if migrations are already completed
        if ($this->areMigrationsComplete()) {
            return $next($request);
        }

        // Try to acquire migration lock
        if ($this->acquireMigrationLock()) {
            try {
                $this->runMigrations();
                $this->markMigrationsComplete();
                $this->releaseMigrationLock();
            } catch (\Exception $e) {
                $this->releaseMigrationLock();
                Log::error('Migration failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                // In production, show a maintenance page
                if (app()->environment('production')) {
                    return response()->view('errors.maintenance', [
                        'message' => 'Database migration in progress. Please wait a moment and refresh.'
                    ], 503);
                }

                // In development, show the error
                throw $e;
            }
        } else {
            // Another process is running migrations, wait briefly
            usleep(500000); // 0.5 seconds

            // Check again if migrations completed while waiting
            if ($this->areMigrationsComplete()) {
                return $next($request);
            }

            // Still running, show maintenance page
            return response()->view('errors.maintenance', [
                'message' => 'Database migration in progress. Please wait a moment and refresh.'
            ], 503);
        }

        return $next($request);
    }

    /**
     * Check if migrations are already completed
     *
     * @return bool
     */
    private function areMigrationsComplete(): bool
    {
        return File::exists(self::MIGRATION_COMPLETE_FLAG);
    }

    /**
     * Acquire migration lock
     *
     * @return bool
     */
    private function acquireMigrationLock(): bool
    {
        if (File::exists(self::MIGRATION_LOCK_FILE)) {
            $lockTime = File::lastModified(self::MIGRATION_LOCK_FILE);

            // If lock is older than 5 minutes, consider it stale
            if (time() - $lockTime > 300) {
                $this->releaseMigrationLock();
            } else {
                return false;
            }
        }

        return File::put(self::MIGRATION_LOCK_FILE, (string) time()) !== false;
    }

    /**
     * Release migration lock
     *
     * @return void
     */
    private function releaseMigrationLock(): void
    {
        if (File::exists(self::MIGRATION_LOCK_FILE)) {
            File::delete(self::MIGRATION_LOCK_FILE);
        }
    }

    /**
     * Run migrations
     *
     * @return void
     */
    private function runMigrations(): void
    {
        // First, check if database is accessible
        if (!$this->isDatabaseAccessible()) {
            throw new \Exception('Database is not accessible');
        }

        // Check if migrations table exists
        if (!Schema::hasTable('migrations')) {
            // Create migrations table
            \Artisan::call('migrate:install', ['--force' => true]);

            Log::info('Migrations table created');
        }

        // Run migrations
        $exitCode = \Artisan::call('migrate', ['--force' => true]);

        if ($exitCode !== 0) {
            throw new \Exception('Migration command failed with exit code: ' . $exitCode);
        }

        Log::info('Migrations completed successfully', [
            'output' => \Artisan::output()
        ]);

        // Run production seeder if this is a fresh deployment
        if ($this->shouldRunProductionSeeder()) {
            $this->runProductionSeeder();
        }
    }

    /**
     * Mark migrations as complete
     *
     * @return void
     */
    private function markMigrationsComplete(): void
    {
        File::put(self::MIGRATION_COMPLETE_FLAG, json_encode([
            'completed_at' => now()->toISOString(),
            'migration_count' => DB::table('migrations')->count()
        ]));
    }

    /**
     * Check if database is accessible
     *
     * @return bool
     */
    private function isDatabaseAccessible(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::warning('Database not accessible', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check if production seeder should run
     *
     * @return bool
     */
    private function shouldRunProductionSeeder(): bool
    {
        // Run production seeder if users table is empty
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
        $exitCode = \Artisan::call('db:seed', [
            '--class' => 'ProductionDatabaseSeeder',
            '--force' => true
        ]);

        if ($exitCode !== 0) {
            Log::warning('Production seeder failed', [
                'exit_code' => $exitCode,
                'output' => \Artisan::output()
            ]);
        } else {
            Log::info('Production seeder completed successfully', [
                'output' => \Artisan::output()
            ]);
        }
    }
}