<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
                'response_time' => measure(function () {
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
            $cacheTime = measure(function () {
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
            $storageTime = measure(function () {
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

// Helper function to measure execution time
if (!function_exists('measure')) {
    function measure($callback)
    {
        $start = microtime(true);
        $callback();
        $end = microtime(true);
        return round(($end - $start) * 1000, 2); // Return time in milliseconds
    }
}