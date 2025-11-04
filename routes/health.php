<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

// Helper function to measure execution time
if (!function_exists('measureExecutionTime')) {
    function measureExecutionTime($callback)
    {
        $start = microtime(true);
        $callback();
        $end = microtime(true);
        return round(($end - $start) * 1000, 2); // Return time in milliseconds
    }
}

Route::get('/health', function () {
    $health = [
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'application' => 'E-Lingkod Dasol HRIS',
        'version' => app()->version(),
        'environment' => app()->environment(),
        'checks' => []
    ];

    $statusCode = 200;

    try {
        // Database connection check
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = [
                'status' => 'connected',
                'response_time' => measureExecutionTime(function () {
                    DB::select('SELECT 1');
                })
            ];
        } catch (\Exception $e) {
            $health['checks']['database'] = [
                'status' => 'error',
                'error' => app()->environment('production') ? 'Database connection failed' : $e->getMessage()
            ];
            $health['status'] = 'unhealthy';
            $statusCode = 503;
        }

        // Cache connection check
        try {
            $cacheTime = measureExecutionTime(function () {
                Cache::put('health_check', 'ok', 60);
                Cache::get('health_check');
            });
            $health['checks']['cache'] = [
                'status' => 'connected',
                'response_time' => $cacheTime
            ];
        } catch (\Exception $e) {
            $health['checks']['cache'] = [
                'status' => 'error',
                'error' => app()->environment('production') ? 'Cache connection failed' : $e->getMessage()
            ];
            $health['status'] = 'degraded';
            if ($statusCode === 200) $statusCode = 503;
        }

        // Storage system check
        try {
            $storageTime = measureExecutionTime(function () {
                \Storage::put('health_check.txt', 'ok');
                \Storage::exists('health_check.txt');
                \Storage::delete('health_check.txt');
            });
            $health['checks']['storage'] = [
                'status' => 'connected',
                'response_time' => $storageTime
            ];
        } catch (\Exception $e) {
            $health['checks']['storage'] = [
                'status' => 'error',
                'error' => app()->environment('production') ? 'Storage system error' : $e->getMessage()
            ];
            $health['status'] = 'degraded';
            if ($statusCode === 200) $statusCode = 503;
        }

        // Migration status check
        try {
            $migrationFlagFile = storage_path('app/migrations_complete.flag');
            $migrationLockFile = storage_path('app/migration.lock');

            if (file_exists($migrationFlagFile)) {
                $migrationData = json_decode(file_get_contents($migrationFlagFile), true);
                $health['checks']['migrations'] = [
                    'status' => 'completed',
                    'completed_at' => $migrationData['completed_at'] ?? 'Unknown',
                    'migration_count' => $migrationData['migration_count'] ?? 'Unknown'
                ];
            } elseif (file_exists($migrationLockFile)) {
                $lockTime = filemtime($migrationLockFile);
                $health['checks']['migrations'] = [
                    'status' => 'running',
                    'lock_time' => date('Y-m-d H:i:s', $lockTime),
                    'duration_seconds' => time() - $lockTime
                ];
                if ($health['status'] === 'healthy') {
                    $health['status'] = 'degraded';
                    if ($statusCode === 200) $statusCode = 503;
                }
            } else {
                // Check if migrations table exists and has migrations
                try {
                    if (Schema::hasTable('migrations')) {
                        $migrationCount = DB::table('migrations')->count();
                        $health['checks']['migrations'] = [
                            'status' => 'completed',
                            'migration_count' => $migrationCount,
                            'note' => 'Detected from existing migrations table'
                        ];
                    } else {
                        $health['checks']['migrations'] = [
                            'status' => 'pending',
                            'note' => 'Migrations not yet run'
                        ];
                        if ($health['status'] === 'healthy') {
                            $health['status'] = 'degraded';
                            if ($statusCode === 200) $statusCode = 503;
                        }
                    }
                } catch (\Exception $e) {
                    $health['checks']['migrations'] = [
                        'status' => 'unknown',
                        'error' => 'Cannot check migration status: ' . $e->getMessage()
                    ];
                    if ($health['status'] === 'healthy') {
                        $health['status'] = 'degraded';
                        if ($statusCode === 200) $statusCode = 503;
                    }
                }
            }
        } catch (\Exception $e) {
            $health['checks']['migrations'] = [
                'status' => 'error',
                'error' => 'Migration check failed: ' . $e->getMessage()
            ];
            if ($health['status'] === 'healthy') {
                $health['status'] = 'degraded';
                if ($statusCode === 200) $statusCode = 503;
            }
        }

        // Application metrics
        $health['metrics'] = [
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            'uptime' => config('app.uptime', 'Unknown')
        ];

    } catch (\Exception $e) {
        $health['status'] = 'unhealthy';
        $health['error'] = app()->environment('production') ? 'Service unavailable' : $e->getMessage();
        $statusCode = 503;
    }

    return response()->json($health, $statusCode);
})->name('health');