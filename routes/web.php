<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeavePolicyController;
use App\Http\Controllers\PerformancePeriodController;
use App\Http\Controllers\PerformanceRatingController;
use App\Http\Controllers\PerformanceTargetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GovernmentBenefitController;
use App\Http\Controllers\BenefitContributionController;
use App\Http\Controllers\EmployeeSelfServiceController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\PDSController;
use App\Http\Controllers\PDSExportController;
use App\Http\Controllers\EducationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});



Route::get('/dashboard', function () {
    $user = Auth::user();

    // Route Employee users directly to Employee Self-Service Portal
    if ($user->hasRole('Employee') && !$user->hasAnyRole(['HR Admin', 'Super Admin', 'Department Head'])) {
        return app(EmployeeSelfServiceController::class)->dashboard();
    }

    // Route other roles to main dashboard
    return app(DashboardController::class)->index();
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Employee Management Routes
    Route::resource('employees', EmployeeController::class)->middleware('can:employee.view');

    // Employee Export Routes (clean approach - no conflicts)
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export')->middleware('can:employee.view');
    Route::get('employees/export/filtered', [EmployeeController::class, 'exportFiltered'])->name('employees.export.filtered')->middleware('can:employee.view');

    // API Routes for employee management
    Route::prefix('api/employees')->name('api.employees.')->group(function () {
        Route::get('/next-number', [App\Http\Controllers\Api\EmployeeNumberController::class, 'getNextNumber'])
            ->name('next-number')
            ->middleware('can:employee.create');
    });

    // Education Routes (nested under employees)
    Route::prefix('employees/{employee}/education')
        ->name('employees.education.')
        ->middleware('can:employee.view')
        ->group(function () {
            Route::get('/', [EducationController::class, 'index'])->name('index');
            Route::get('/create', [EducationController::class, 'create'])->name('create');
            Route::post('/', [EducationController::class, 'store'])->name('store');
            Route::get('/{education}/edit', [EducationController::class, 'edit'])->name('edit');
            Route::patch('/{education}', [EducationController::class, 'update'])->name('update');
            Route::delete('/{education}', [EducationController::class, 'destroy'])->name('destroy');
            Route::get('/{education}/download', [EducationController::class, 'downloadAttachment'])->name('download');
        });

    // PDS (Personal Data Sheet) Routes
    Route::prefix('pds')->name('pds.')->middleware(['auth', 'verified'])->group(function () {
        Route::get('{employee}', [PDSController::class, 'dashboard'])->name('dashboard');
        Route::get('{employee}/personal-information', [PDSController::class, 'personalInformation'])->name('personal-information');
        Route::patch('{employee}/personal-information', [PDSController::class, 'updatePersonalInformation'])->name('update-personal-information');
        Route::get('{employee}/family-background', [PDSController::class, 'familyBackground'])->name('family-background');
        Route::post('{employee}/family-background', [PDSController::class, 'updateFamilyBackground'])->name('update-family-background');
        Route::get('{employee}/eligibility', [PDSController::class, 'eligibility'])->name('eligibility');
        Route::post('{employee}/eligibility', [PDSController::class, 'storeEligibility'])->name('store-eligibility');
        Route::delete('{employee}/eligibility/{eligibility}', [PDSController::class, 'destroyEligibility'])->name('destroy-eligibility');
        Route::get('{employee}/voluntary-work', [PDSController::class, 'voluntaryWork'])->name('voluntary-work');
        Route::post('{employee}/voluntary-work', [PDSController::class, 'storeVoluntaryWork'])->name('store-voluntary-work');
        Route::delete('{employee}/voluntary-work/{voluntaryWork}', [PDSController::class, 'destroyVoluntaryWork'])->name('destroy-voluntary-work');
        Route::get('{employee}/other-information', [PDSController::class, 'otherInformation'])->name('other-information');
        Route::post('{employee}/other-information', [PDSController::class, 'storeOtherInformation'])->name('store-other-information');
        Route::delete('{employee}/other-information/{otherInformation}', [PDSController::class, 'destroyOtherInformation'])->name('destroy-other-information');
        Route::get('{employee}/references', [PDSController::class, 'references'])->name('references');
        Route::post('{employee}/references', [PDSController::class, 'storeReference'])->name('store-reference');
        Route::delete('{employee}/references', [PDSController::class, 'replaceReferences'])->name('replace-references');
        Route::delete('{employee}/references/{reference}', [PDSController::class, 'destroyReference'])->name('destroy-reference');
        Route::get('{employee}/questionnaire', [PDSController::class, 'questionnaire'])->name('questionnaire');
        Route::post('{employee}/questionnaire', [PDSController::class, 'updateQuestionnaire'])->name('update-questionnaire');
        Route::get('{employee}/photo', [PDSController::class, 'photo'])->name('photo');
        Route::post('{employee}/photo', [PDSController::class, 'uploadPhoto'])->name('upload-photo');
        Route::post('{employee}/thumbmark', [PDSController::class, 'uploadThumbmark'])->name('upload-thumbmark');
        // Panel 5: Work Experience
        Route::get('{employee}/work-experience', [PDSController::class, 'workExperience'])->name('work-experience');
        Route::post('{employee}/work-experience', [PDSController::class, 'storeWorkExperience'])->name('store-work-experience');
        Route::delete('{employee}/work-experience/{workExperience}', [PDSController::class, 'destroyWorkExperience'])->name('destroy-work-experience');
        // Panel 7: Learning & Development
        Route::get('{employee}/learning-development', [PDSController::class, 'learningDevelopment'])->name('learning-development');
        Route::post('{employee}/learning-development', [PDSController::class, 'storeLearningDevelopment'])->name('store-learning-development');
        Route::delete('{employee}/learning-development/{training}', [PDSController::class, 'destroyLearningDevelopment'])->name('destroy-learning-development');

        // PDS Export Routes
        Route::get('export', [PDSExportController::class, 'index'])->name('export.index')->middleware('can:viewExportInterface,App\Models\Employee');
        Route::get('export/single/{employee}', [PDSExportController::class, 'exportSingle'])->name('export.single')->middleware('can:export,employee');
        Route::post('export/batch', [PDSExportController::class, 'exportBatch'])->name('export.batch')->middleware('can:batchExport,App\Models\Employee');
        Route::get('export/status/{jobId}', [PDSExportController::class, 'getExportStatus'])->name('export.status')->middleware('can:viewExportStatus,App\Models\Employee');
        Route::get('export/download/{filename}', [PDSExportController::class, 'downloadExport'])->name('export.download')->middleware('can:downloadExport,App\Models\Employee');
        Route::get('export/history', [PDSExportController::class, 'getExportHistory'])->name('export.history')->middleware('can:viewExportHistory,App\Models\Employee');

        // Super Admin System Audit Routes (New)
        Route::prefix('export/admin')->name('export.admin.')->middleware('can:manageConcurrentExports,App\Models\Employee')->group(function () {
            Route::get('concurrent-exports', [PDSExportController::class, 'getConcurrentExports'])->name('concurrent-exports');
            Route::post('retry-export/{jobId}', [PDSExportController::class, 'retryExport'])->name('retry-export');
            Route::delete('cancel-export/{jobId}', [PDSExportController::class, 'cancelExport'])->name('cancel-export');
            Route::get('performance-metrics', [PDSExportController::class, 'getPerformanceMetrics'])->name('performance-metrics')->middleware('can:viewPerformanceMetrics,App\Models\Employee');
        });

      });

  
    // Employee Document Routes (New)
    Route::post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
    Route::get('documents/{document}', [EmployeeDocumentController::class, 'show'])->name('documents.show');
    Route::delete('documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');


    // Leave Management Routes
    Route::resource('leave-types', LeaveTypeController::class)->except(['show'])->middleware('can:user.manage');
    Route::resource('leave-applications', LeaveApplicationController::class);
    Route::patch('/leave-applications/{leave_application}/approve', [LeaveApplicationController::class, 'approve'])->name('leave-applications.approve')->middleware('can:leave.approve');
    Route::patch('/leave-applications/{leave_application}/reject', [LeaveApplicationController::class, 'reject'])->name('leave-applications.reject')->middleware('can:leave.approve');

    // Performance Management (SPMS/IPCR) Routes
    Route::resource('performance-periods', PerformancePeriodController::class)->except(['show'])->middleware('can:user.manage');
    Route::resource('performance-targets', PerformanceTargetController::class);

    // Performance Rating Routes
    Route::post('performance-ratings/self-rate/{target}', [PerformanceRatingController::class, 'storeSelfRating'])->name('performance-ratings.self-rate');
    Route::post('performance-ratings/supervisor-rate/{target}', [PerformanceRatingController::class, 'storeSupervisorRating'])->name('performance-ratings.supervisor-rate');

  
    
    // Leave Policy Management Routes (New)
    Route::resource('leave-policies', LeavePolicyController::class)
        ->middleware('can:user.manage');

    // Government Benefits Management Routes (New)
    Route::prefix('benefits')->name('benefits.')->middleware('can:reports.view')->group(function () {
        Route::get('/', [GovernmentBenefitController::class, 'index'])->name('index');
        Route::get('/create', [GovernmentBenefitController::class, 'create'])->name('create');
        Route::post('/', [GovernmentBenefitController::class, 'store'])->name('store');
        Route::get('/{benefit}', [GovernmentBenefitController::class, 'show'])->name('show');
        Route::get('/{benefit}/edit', [GovernmentBenefitController::class, 'edit'])->name('edit');
        Route::put('/{benefit}', [GovernmentBenefitController::class, 'update'])->name('update');
        Route::delete('/{benefit}', [GovernmentBenefitController::class, 'destroy'])->name('destroy');

        // Contribution management
        Route::get('/{benefit}/contributions', [BenefitContributionController::class, 'index'])->name('contributions.index');
        Route::post('/{benefit}/contributions', [BenefitContributionController::class, 'store'])->name('contributions.store');
        Route::get('/contributions/{contribution}/edit', [BenefitContributionController::class, 'edit'])->name('contributions.edit');
        Route::put('/contributions/{contribution}', [BenefitContributionController::class, 'update'])->name('contributions.update');

        // Reports
        Route::get('/reports/contributions', [BenefitContributionController::class, 'report'])->name('reports.contributions');
        Route::get('/reports/compliance', [GovernmentBenefitController::class, 'complianceReport'])->name('reports.compliance');
    });

    // Employee Self-Service Portal Routes (New)
    Route::prefix('employee-portal')->name('employee-portal.')->group(function () {
        Route::get('/dashboard', [EmployeeSelfServiceController::class, 'dashboard'])->name('dashboard');
        Route::get('/service-record', [EmployeeSelfServiceController::class, 'serviceRecord'])->name('service-record');
        Route::get('/benefits-summary', [EmployeeSelfServiceController::class, 'benefitsSummary'])->name('benefits-summary');
        Route::get('/my-201-file', [EmployeeSelfServiceController::class, 'my201File'])->name('my-201-file');

        // PDS Export for Employee Self-Service
        Route::get('/export-pds', [PDSExportController::class, 'exportSelfPDS'])->name('export-pds');

        // Document Request System
        Route::get('/document-requests', [EmployeeSelfServiceController::class, 'documentRequests'])->name('document-requests');
        Route::get('/document-requests/new', [EmployeeSelfServiceController::class, 'newDocumentRequest'])->name('document-requests.new');
        Route::post('/document-requests', [EmployeeSelfServiceController::class, 'storeDocumentRequest'])->name('document-requests.store');
        Route::get('/document-requests/{documentRequest}/download', [EmployeeSelfServiceController::class, 'downloadDocumentRequest'])->name('document-requests.download');

        // Personal Data Update System
        Route::get('/personal-data-update', [EmployeeSelfServiceController::class, 'personalDataUpdate'])->name('personal-data-update');
        Route::get('/personal-data-update/new', [EmployeeSelfServiceController::class, 'newPersonalDataUpdate'])->name('personal-data-update.new');
        Route::post('/change-requests', [EmployeeSelfServiceController::class, 'storeChangeRequest'])->name('change-requests.store');
    });

    
});

require __DIR__ . '/auth.php';
