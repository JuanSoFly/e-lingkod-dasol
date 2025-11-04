<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Route::get('/health', function () {
    try {
        // Check database connection
        DB::connection()->getPdo();

        // Check cache connection
        Cache::put('health_check', 'ok', 60);
        Cache::get('health_check');

        // Return health status
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'application' => 'E-Lingkod Dasol HRIS',
            'version' => app()->version(),
            'environment' => app()->environment(),
            'database' => 'connected',
            'cache' => 'connected'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'timestamp' => now()->toISOString(),
            'application' => 'E-Lingkod Dasol HRIS',
            'error' => app()->environment('production') ? 'Service unavailable' : $e->getMessage()
        ], 503);
    }
})->name('health');