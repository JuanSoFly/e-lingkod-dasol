<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SecurityMonitoringController;

/*
|--------------------------------------------------------------------------
| Security Monitoring Routes
|--------------------------------------------------------------------------
|
| These routes handle the security monitoring dashboard and related
| functionality for the E-Lingkod Dasol HRIS security system.
| All routes require authentication and appropriate role permissions.
|
*/

Route::middleware(['auth', 'role:Super Admin|HR Admin'])->group(function () {

    // Main security monitoring dashboard
    Route::get('/security-monitoring', [SecurityMonitoringController::class, 'index'])
        ->name('security-monitoring.dashboard');

    // API endpoints for real-time data
    Route::prefix('security-monitoring')->name('security-monitoring.')->group(function () {

        // Real-time metrics endpoint
        Route::get('/metrics', [SecurityMonitoringController::class, 'getMetrics'])
            ->name('metrics');

        // Test execution status
        Route::get('/test-status', [SecurityMonitoringController::class, 'getTestStatus'])
            ->name('test-status');

        // Privacy violation alerts
        Route::get('/privacy-alerts', [SecurityMonitoringController::class, 'getPrivacyAlerts'])
            ->name('privacy-alerts');

        // Compliance status
        Route::get('/compliance-status', [SecurityMonitoringController::class, 'getComplianceStatus'])
            ->name('compliance-status');

        // Security health report generation
        Route::get('/health-report', [SecurityMonitoringController::class, 'generateHealthReport'])
            ->name('health-report');
    });
});

/*
|--------------------------------------------------------------------------
| Additional Security Monitoring Features
|--------------------------------------------------------------------------
|
| These routes can be added to extend the monitoring functionality
| with additional features like detailed analytics, export capabilities,
| and administrative controls.
|
*/

// Future expansion routes (commented out for now)
/*
Route::middleware(['auth', 'role:Super Admin'])->group(function () {

    // Advanced analytics
    Route::get('/security-monitoring/analytics', [SecurityMonitoringController::class, 'analytics'])
        ->name('security-monitoring.analytics');

    // Export functionality
    Route::get('/security-monitoring/export/{type}', [SecurityMonitoringController::class, 'export'])
        ->name('security-monitoring.export');

    // Configuration management
    Route::get('/security-monitoring/config', [SecurityMonitoringController::class, 'config'])
        ->name('security-monitoring.config');

    Route::post('/security-monitoring/config', [SecurityMonitoringController::class, 'updateConfig'])
        ->name('security-monitoring.config.update');

    // Alert management
    Route::get('/security-monitoring/alerts', [SecurityMonitoringController::class, 'manageAlerts'])
        ->name('security-monitoring.alerts');

    Route::post('/security-monitoring/alerts/{id}/acknowledge', [SecurityMonitoringController::class, 'acknowledgeAlert'])
        ->name('security-monitoring.alerts.acknowledge');
});
*/
