<?php

use App\Http\Controllers\DocumentSearchController;
use App\Http\Controllers\CSCReportController;
use App\Http\Controllers\CSCReportManagementController;
use App\Http\Controllers\API\OPCRController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Document Search API Routes
Route::middleware(['auth:sanctum'])->prefix('documents')->name('api.documents.')->group(function () {
    
    // Main search endpoint
    Route::post('/search', [DocumentSearchController::class, 'search'])->name('search');
    
    // Search suggestions for autocomplete
    Route::get('/suggestions', [DocumentSearchController::class, 'suggestions'])->name('suggestions');
    
    // Get available filter options
    Route::get('/filter-options', [DocumentSearchController::class, 'filterOptions'])->name('filter-options');
    
    // Document content extraction and indexing
    Route::post('/{document}/index', [DocumentSearchController::class, 'indexDocument'])->name('index');
    Route::post('/bulk-index', [DocumentSearchController::class, 'bulkIndex'])->name('bulk-index');
    Route::get('/{document}/content', [DocumentSearchController::class, 'getDocumentContent'])->name('content');
    
    // Cache management
    Route::post('/clear-cache', [DocumentSearchController::class, 'clearCache'])->name('clear-cache');
    
    // Analytics and statistics
    Route::get('/analytics', [DocumentSearchController::class, 'analytics'])->name('analytics');
});

// CSC Reporting API Routes
Route::middleware(['auth:sanctum', 'can:reports.generate'])->prefix('csc-reports')->name('api.csc-reports.')->group(function () {
    
    // Monthly Reports
    Route::post('/monthly/accession', [CSCReportController::class, 'generateMonthlyAccession'])->name('monthly.accession');
    Route::post('/monthly/separation', [CSCReportController::class, 'generateMonthlySeparation'])->name('monthly.separation');
    Route::post('/monthly/dibar', [CSCReportController::class, 'generateMonthlyDibar'])->name('monthly.dibar');
    Route::post('/monthly/harassment', [CSCReportController::class, 'generateMonthlyHarassment'])->name('monthly.harassment');
    
    // Annual Reports
    Route::post('/annual/ighr', [CSCReportController::class, 'generateAnnualIghr'])->name('annual.ighr');
    
    // Export Functions
    Route::post('/export/pdf', [CSCReportController::class, 'exportToPdf'])->name('export.pdf');
    Route::post('/export/excel', [CSCReportController::class, 'exportToExcel'])->name('export.excel');
    
    // Report Management
    Route::get('/list', [CSCReportManagementController::class, 'index'])->name('list');
    Route::get('/{report}', [CSCReportManagementController::class, 'show'])->name('show');
    Route::post('/{report}/submit', [CSCReportManagementController::class, 'submit'])->name('submit');
    Route::post('/{report}/approve', [CSCReportManagementController::class, 'approve'])->name('approve');
    Route::post('/{report}/reject', [CSCReportManagementController::class, 'reject'])->name('reject');
    Route::delete('/{report}', [CSCReportManagementController::class, 'destroy'])->name('destroy');
    
    // Cache management for reports
    Route::post('/clear-cache', [CSCReportController::class, 'clearCache'])->name('clear-cache');
});

// OPCR Mobile API Routes
Route::prefix('v1')->group(function () {

    // Authentication endpoints
    Route::prefix('auth')->group(function () {
        Route::post('login', [OPCRController::class, 'login']);
        Route::post('refresh', [OPCRController::class, 'refresh'])->middleware('auth:sanctum');
    });

    // OPCR endpoints (require authentication)
    Route::middleware('auth:sanctum')->group(function () {

        // OPCR Workflows
        Route::prefix('opcr')->group(function () {
            Route::get('workflows', [OPCRController::class, 'workflows']);
            Route::post('workflows', [OPCRController::class, 'workflowStore']);
            Route::get('workflows/{workflow}', [OPCRController::class, 'workflowShow']);
            Route::get('workflows/{workflow}/history', [OPCRController::class, 'workflowHistory']);
            Route::post('workflows/{workflow}/ratings', [OPCRController::class, 'submitRatings']);
        });

        // Performance Periods
        Route::get('periods', [OPCRController::class, 'periods']);

        // Offices
        Route::get('offices', [OPCRController::class, 'offices']);
        Route::get('offices/{office}/mfos', [OPCRController::class, 'officeMfos']);

        // Configuration
        Route::get('config', [OPCRController::class, 'configuration']);

        // Data Synchronization
        Route::post('sync', [OPCRController::class, 'sync']);
    });
});

// Health check endpoint
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
        'maintenance' => config('app.maintenance_mode', false)
    ]);
});

// API documentation endpoint
Route::get('docs', function () {
    return response()->json([
        'title' => 'E-Lingkod Dasol HRIS API',
        'version' => '1.0.0',
        'description' => 'RESTful API for OPCR mobile and external integrations',
        'base_url' => url('/api/v1'),
        'endpoints' => [
            'Authentication' => [
                'POST /api/v1/auth/login' => 'Authenticate user and get API token',
                'POST /api/v1/auth/refresh' => 'Refresh existing API token (requires auth)'
            ],
            'OPCR Workflows' => [
                'GET /api/v1/opcr/workflows' => 'List user\'s OPCR workflows',
                'POST /api/v1/opcr/workflows' => 'Create new OPCR workflow',
                'GET /api/v1/opcr/workflows/{id}' => 'Get specific workflow details',
                'GET /api/v1/opcr/workflows/{id}/history' => 'Get workflow action history',
                'POST /api/v1/opcr/workflows/{id}/ratings' => 'Submit QET ratings for workflow'
            ],
            'Reference Data' => [
                'GET /api/v1/periods' => 'List available performance periods',
                'GET /api/v1/offices' => 'List accessible offices',
                'GET /api/v1/config' => 'Get app configuration and settings'
            ],
            'Utilities' => [
                'POST /api/v1/sync' => 'Synchronize offline data',
                'GET /api/health' => 'API health check'
            ]
        ],
        'authentication' => [
            'type' => 'Bearer Token',
            'header' => 'Authorization: Bearer {token}',
            'login_endpoint' => '/api/v1/auth/login'
        ],
        'rate_limits' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
            'burst_limit' => 100
        ]
    ]);
});
