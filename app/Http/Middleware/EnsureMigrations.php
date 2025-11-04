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
    private function getMigrationLockFile(): string
    {
        return storage_path('app/migration.lock');
    }

    /**
     * Migration completion flag file path
     */
    private function getMigrationCompleteFlag(): string
    {
        return storage_path('app/migrations_complete.flag');
    }

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

        // Quick database connectivity check before attempting migrations
        if (!$this->isDatabaseQuickCheck()) {
            // Database not ready, but don't block - let the application try
            Log::warning('Database not accessible for migration check, continuing...');
            return $next($request);
        }

        // Try to acquire migration lock with timeout
        if ($this->acquireMigrationLock()) {
            try {
                $this->runMigrations();
                $this->markMigrationsComplete();
                $this->releaseMigrationLock();
            } catch (\Exception $e) {
                $this->releaseMigrationLock();
                Log::error('Migration failed', [
                    'error' => $e->getMessage(),
                    'request_path' => $request->path(),
                    'user_agent' => $request->userAgent()
                ]);

                // Don't block the application on migration failures
                // Log the error and continue
                return $next($request);
            }
        } else {
            // Another process is running migrations, wait briefly with timeout
            $waitTime = 0;
            $maxWaitTime = 3; // 3 seconds maximum wait

            while (!$this->areMigrationsComplete() && $waitTime < $maxWaitTime) {
                usleep(500000); // 0.5 seconds
                $waitTime += 0.5;

                // If we've waited too long, continue with the request
                if ($waitTime >= $maxWaitTime) {
                    Log::warning('Migration wait timeout, continuing with request');
                    return $next($request);
                }
            }

            // Check if migrations completed while waiting
            if ($this->areMigrationsComplete()) {
                return $next($request);
            }
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
        return File::exists($this->getMigrationCompleteFlag());
    }

    /**
     * Acquire migration lock
     *
     * @return bool
     */
    private function acquireMigrationLock(): bool
    {
        if (File::exists($this->getMigrationLockFile())) {
            $lockTime = File::lastModified($this->getMigrationLockFile());

            // If lock is older than 5 minutes, consider it stale
            if (time() - $lockTime > 300) {
                $this->releaseMigrationLock();
            } else {
                return false;
            }
        }

        return File::put($this->getMigrationLockFile(), (string) time()) !== false;
    }

    /**
     * Release migration lock
     *
     * @return void
     */
    private function releaseMigrationLock(): void
    {
        if (File::exists($this->getMigrationLockFile())) {
            File::delete($this->getMigrationLockFile());
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
        File::put($this->getMigrationCompleteFlag(), json_encode([
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
     * Quick database connectivity check with timeout
     *
     * @return bool
     */
    private function isDatabaseQuickCheck(): bool
    {
        try {
            // Use a very short timeout for quick connectivity check
            $originalTimeout = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_TIMEOUT);
            DB::connection()->getPdo()->setAttribute(\PDO::ATTR_TIMEOUT, 2);

            $result = DB::connection()->select('SELECT 1');

            // Restore original timeout
            DB::connection()->getPdo()->setAttribute(\PDO::ATTR_TIMEOUT, $originalTimeout);

            return true;
        } catch (\Exception $e) {
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