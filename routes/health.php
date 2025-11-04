<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Helper function to measure execution time
if (!function_exists('measureExecutionTime')) {
    function measureExecutionTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        $end = microtime(true);

        return round(($end - $start) * 1000, 2); // Return time in milliseconds
    }
}

Route::get('/health', function (Request $request) {
    $health = [
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'application' => 'E-Lingkod Dasol HRIS',
        'version' => app()->version(),
        'environment' => app()->environment(),
        'checks' => [],
        'http_status' => 200,
    ];

    $criticalFailures = [];
    $nonCriticalFailures = [];
    $storageDisk = config('health.storage_disk', 'local');

    try {
        // Database connection check (critical)
        try {
            $dbTime = measureExecutionTime(function () {
                DB::connection()->getPdo();
                DB::select('SELECT 1');
            });

            $health['checks']['database'] = [
                'status' => 'connected',
                'response_time' => $dbTime,
            ];
        } catch (\Throwable $e) {
            $health['checks']['database'] = [
                'status' => 'error',
                'error' => app()->environment('production') ? 'Database connection failed' : $e->getMessage(),
            ];
            $health['status'] = 'unhealthy';
            $criticalFailures[] = 'database';
        }

        // Cache connection check (non-critical)
        try {
            $cacheTime = measureExecutionTime(function () {
                Cache::put('health_check', 'ok', 60);
                Cache::get('health_check');
            });

            $health['checks']['cache'] = [
                'status' => 'connected',
                'response_time' => $cacheTime,
            ];
        } catch (\Throwable $e) {
            $health['checks']['cache'] = [
                'status' => 'error',
                'error' => app()->environment('production') ? 'Cache connection failed' : $e->getMessage(),
            ];
            if ($health['status'] === 'healthy') {
                $health['status'] = 'degraded';
            }
            $nonCriticalFailures[] = 'cache';
        }

        // Storage system check (non-critical, forced to local disk by default)
        try {
            $storageTime = measureExecutionTime(function () use ($storageDisk) {
                $storage = Storage::disk($storageDisk);
                $tempFile = sprintf('health_check_%s.txt', Str::uuid());
                $storage->put($tempFile, 'ok');
                $storage->exists($tempFile);
                $storage->delete($tempFile);
            });

            $health['checks']['storage'] = [
                'status' => 'connected',
                'response_time' => $storageTime,
                'disk' => $storageDisk,
            ];
        } catch (\Throwable $e) {
            $health['checks']['storage'] = [
                'status' => 'error',
                'error' => app()->environment('production') ? 'Storage system error' : $e->getMessage(),
                'disk' => $storageDisk,
            ];
            if ($health['status'] === 'healthy') {
                $health['status'] = 'degraded';
            }
            $nonCriticalFailures[] = 'storage';
        }

        // Migration status check (informational)
        try {
            $migrationFlagFile = storage_path('app/migrations_complete.flag');
            $migrationLockFile = storage_path('app/migration.lock');

            if (file_exists($migrationFlagFile)) {
                $migrationData = json_decode(file_get_contents($migrationFlagFile), true);
                $health['checks']['migrations'] = [
                    'status' => 'completed',
                    'completed_at' => $migrationData['completed_at'] ?? 'Unknown',
                    'migration_count' => $migrationData['migration_count'] ?? 'Unknown',
                ];
            } elseif (file_exists($migrationLockFile)) {
                $lockTime = filemtime($migrationLockFile);
                $health['checks']['migrations'] = [
                    'status' => 'running',
                    'lock_time' => date('Y-m-d H:i:s', $lockTime),
                    'duration_seconds' => time() - $lockTime,
                ];
                if ($health['status'] === 'healthy') {
                    $health['status'] = 'degraded';
                }
            } else {
                if (Schema::hasTable('migrations')) {
                    $migrationCount = DB::table('migrations')->count();
                    $health['checks']['migrations'] = [
                        'status' => 'completed',
                        'migration_count' => $migrationCount,
                        'note' => 'Detected from existing migrations table',
                    ];
                } else {
                    $health['checks']['migrations'] = [
                        'status' => 'pending',
                        'note' => 'Migrations not yet run',
                    ];
                    if ($health['status'] === 'healthy') {
                        $health['status'] = 'degraded';
                    }
                }
            }
        } catch (\Throwable $e) {
            $health['checks']['migrations'] = [
                'status' => 'unknown',
                'error' => app()->environment('production') ? 'Migration check failed' : $e->getMessage(),
            ];
            if ($health['status'] === 'healthy') {
                $health['status'] = 'degraded';
            }
        }

        // Application metrics
        $health['metrics'] = [
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            'uptime' => config('app.uptime', 'Unknown'),
        ];
    } catch (\Throwable $e) {
        $health['status'] = 'unhealthy';
        $health['error'] = app()->environment('production') ? 'Service unavailable' : $e->getMessage();
        $criticalFailures[] = 'application';
    }

    $shouldEnforce = $request->boolean('enforceStatus', false);
    $httpStatus = ($shouldEnforce && !empty($criticalFailures)) ? 503 : 200;

    $health['checks']['meta'] = [
        'critical_failures' => $criticalFailures,
        'non_critical_failures' => $nonCriticalFailures,
        'enforce_status' => $shouldEnforce,
        'storage_disk_checked' => $storageDisk,
    ];

    $health['http_status'] = $httpStatus;

    return response()->json($health, $httpStatus);
})->name('health');
