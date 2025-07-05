<?php

use App\Http\Controllers\DocumentSearchController;
use App\Http\Controllers\CSCReportController;
use App\Http\Controllers\CSCReportManagementController;
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