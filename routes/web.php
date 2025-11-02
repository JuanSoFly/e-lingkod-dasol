<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveCardController;
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
use App\Http\Controllers\ApprovalWorkflowController;
use App\Http\Controllers\OPCRController;
use App\Http\Controllers\API\OPCRController as ApiOPCRController;
use App\Http\Controllers\MFOController;
use App\Http\Controllers\SuccessIndicatorController;
use App\Http\Controllers\OPCRWorkflowController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\OfficeAssignmentController;
use App\Http\Controllers\OPCRExportController;
use App\Http\Controllers\OPCRAnalyticsController;
use App\Http\Controllers\RatingScaleController;
use App\Http\Controllers\AuditTrailController;
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

    // Employee Export Routes (must be defined before resource route to avoid conflicts)
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export')->middleware('can:employee.view');
    Route::get('employees/export/filtered', [EmployeeController::class, 'exportFiltered'])->name('employees.export.filtered')->middleware('can:employee.view');

    // Employee Archive Routes (must be defined before resource route to avoid conflicts)
    Route::prefix('employees/archive')->name('employees.archive.')->middleware(['auth', 'verified'])->group(function () {
        Route::get('/', [ArchiveController::class, 'index'])->name('index')->middleware('can:employee.view');
        Route::get('export', [ArchiveController::class, 'export'])->name('export')->middleware('can:employee.view');
        Route::get('stats', [ArchiveController::class, 'stats'])->name('stats')->middleware('can:employee.view');
        Route::post('bulk-restore', [ArchiveController::class, 'bulkRestore'])->name('bulkRestore')->middleware('can:employee.delete');
        Route::get('{employee}', [ArchiveController::class, 'show'])->name('show')->middleware('can:employee.view');
        Route::post('{employee}/restore', [ArchiveController::class, 'restore'])->name('restore')->middleware('can:employee.delete');
        Route::delete('{employee}/force-delete', [ArchiveController::class, 'forceDelete'])->name('forceDelete')->middleware('can:employee.delete');
    });

    // Employee Management Routes
    Route::resource('employees', EmployeeController::class)->middleware('can:employee.view');

    // API Routes for employee management
    Route::prefix('api/employees')->name('api.employees.')->group(function () {
        Route::get('/next-number', [App\Http\Controllers\Api\EmployeeNumberController::class, 'getNextNumber'])
            ->name('next-number')
            ->middleware('can:employee.create');
    });

    Route::prefix('api/offices')->name('api.offices.')->group(function () {
        Route::get('{office}/mfos', [ApiOPCRController::class, 'officeMfos'])
            ->name('mfos')
            ->middleware('permission:opcr.view');
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

    // Approval Workflow routes
    Route::prefix('approvals')->name('approvals.')->middleware(['auth', 'can:leave.approve'])->group(function () {
        Route::get('/pending', [ApprovalWorkflowController::class, 'pendingApprovals'])->name('pending');
        Route::get('/{leave_application}/workflow', [ApprovalWorkflowController::class, 'workflowStatus'])->name('workflow-status');
        Route::post('/{leave_application}/approve', [ApprovalWorkflowController::class, 'approve'])->name('approve');
        Route::post('/{leave_application}/reject', [ApprovalWorkflowController::class, 'reject'])->name('reject');
    });

    // HR Management routes for escalation and override
    Route::prefix('approvals')->name('approvals.')->middleware(['auth', 'can:leave.manage'])->group(function () {
        Route::post('/{leave_application}/escalate', [ApprovalWorkflowController::class, 'escalate'])->name('escalate');
        Route::post('/{leave_application}/override', [ApprovalWorkflowController::class, 'overrideApproval'])->name('override');
    });

    // Leave Card Routes
    Route::prefix('leave-cards')->name('leave-cards.')->group(function () {
        Route::get('/', [LeaveCardController::class, 'index'])->name('index');
        Route::get('/{employee}', [LeaveCardController::class, 'show'])->name('show')->middleware('can:employee.view');
        Route::post('/{employee}/initialize', [LeaveCardController::class, 'initializeBalances'])
            ->name('initialize')->middleware('can:employee.manage');
        Route::post('/manual-entry', [LeaveCardController::class, 'createManualEntry'])
            ->name('manual-entry')->middleware('can:employee.manage');
        Route::get('/api/data', [LeaveCardController::class, 'getLeaveCardData'])->name('api.data');
        Route::get('/api/all', [LeaveCardController::class, 'getAllLeaveCards'])
            ->name('api.all')->middleware('can:employee.manage');
        Route::get('/print/{employeeId?}', [LeaveCardController::class, 'printLeaveCard'])->name('print');
    });

    // Legacy leave card routes (for backward compatibility)
    Route::get('/leave-card', [LeaveCardController::class, 'index'])->name('leave-card.show')->middleware('can:leave.view');
    Route::get('/leave-card/{employeeId}', [LeaveCardController::class, 'show'])->name('leave-card.employee')->middleware('can:employee.view');
    Route::get('/leave-card/print/{employeeId?}', [LeaveCardController::class, 'printLeaveCard'])->name('leave-card.print')->middleware('can:leave.view');
    Route::get('/leave-card/print/{employeeId?}/view', [LeaveCardController::class, 'showPrintableLeaveCard'])->name('leave-card.print-view')->middleware('can:leave.view');

    // Leave Card View Route
    Route::get('/leave-card-view/{employeeId?}', [LeaveApplicationController::class, 'showLeaveCard'])->name('leave-card.view')->middleware('can:leave.view');

    // Performance Management Routes
    Route::resource('performance-periods', PerformancePeriodController::class)->except(['show']);
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
        // Dashboard - Enhanced with API endpoints
        Route::get('/dashboard', [App\Http\Controllers\EmployeeSelfServiceController::class, 'dashboard'])->name('dashboard');
        Route::get('/leave-dashboard', [App\Http\Controllers\Employee\DashboardController::class, 'index'])->name('dashboard.leave');
        Route::get('/dashboard/analytics', [App\Http\Controllers\Employee\DashboardController::class, 'getBalanceAnalytics'])->name('dashboard.analytics');

        // Calendar and Announcements
        Route::get('/calendar-data', [App\Http\Controllers\Employee\DashboardController::class, 'getCalendarData'])->name('calendar.data');
        Route::get('/announcements', [App\Http\Controllers\Employee\DashboardController::class, 'getAnnouncements'])->name('announcements');

        // Leave History and Analytics
        Route::get('/leave-history', [App\Http\Controllers\Employee\DashboardController::class, 'getLeaveHistory'])->name('leave-history');
        Route::get('/leave-analytics', [App\Http\Controllers\Employee\DashboardController::class, 'getAnalytics'])->name('leave-analytics');
        Route::get('/leave-history/export', [App\Http\Controllers\Employee\DashboardController::class, 'exportLeaveHistory'])->name('leave-history.export');
        Route::get('/leave-card/print', [App\Http\Controllers\Employee\DashboardController::class, 'getPrintableLeaveCard'])->name('leave-card.print');

        // Profile Management
        Route::get('/profile', [App\Http\Controllers\Employee\ProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile', [App\Http\Controllers\Employee\ProfileController::class, 'update'])->name('profile.update');
        Route::get('/profile/notifications', [App\Http\Controllers\Employee\ProfileController::class, 'getNotificationPreferences'])->name('profile.notifications');
        Route::put('/profile/notifications', [App\Http\Controllers\Employee\ProfileController::class, 'updateNotificationPreferences'])->name('profile.notifications.update');
        Route::put('/profile/change-password', [App\Http\Controllers\Employee\ProfileController::class, 'changePassword'])->name('profile.change-password');

        // Document Management
        Route::get('/documents', [App\Http\Controllers\Employee\DocumentController::class, 'index'])->name('documents.index');
        Route::post('/documents', [App\Http\Controllers\Employee\DocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}/download', [App\Http\Controllers\Employee\DocumentController::class, 'download'])->name('documents.download');
        Route::get('/documents/{document}/download-file', [App\Http\Controllers\Employee\DocumentController::class, 'downloadFile'])->name('documents.download-file');
        Route::put('/documents/{document}', [App\Http\Controllers\Employee\DocumentController::class, 'update'])->name('documents.update');
        Route::delete('/documents/{document}', [App\Http\Controllers\Employee\DocumentController::class, 'destroy'])->name('documents.destroy');

        // Legacy Dashboard Route (kept for compatibility)
        Route::get('/dashboard-legacy', [EmployeeSelfServiceController::class, 'dashboard'])->name('dashboard.legacy');

        // Leave Applications - Enhanced employee-specific system
        Route::get('/leave-applications', [App\Http\Controllers\Employee\LeaveApplicationController::class, 'index'])->name('leave-applications.index');
        Route::get('/leave-applications/data', [App\Http\Controllers\Employee\LeaveApplicationController::class, 'getData'])->name('leave-applications.data');
        Route::get('/leave-applications/create', [App\Http\Controllers\Employee\LeaveApplicationController::class, 'create'])->name('leave-applications.create');
        Route::post('/leave-applications', [App\Http\Controllers\Employee\LeaveApplicationController::class, 'store'])->name('leave-applications.store')->middleware(\App\Http\Middleware\RateLimitLeaveApplications::class);
        Route::post('/leave-applications/calculate-days', [App\Http\Controllers\Employee\LeaveApplicationController::class, 'calculateDays'])->name('leave-applications.calculate-days');
        Route::post('/leave-applications/draft', [App\Http\Controllers\Employee\LeaveApplicationController::class, 'saveDraft'])->name('leave-applications.draft')->middleware(\App\Http\Middleware\RateLimitLeaveApplications::class);
        Route::delete('/leave-applications/{leave_application}/withdraw', [App\Http\Controllers\Employee\LeaveApplicationController::class, 'withdraw'])->name('leave-applications.withdraw');

        // Original Employee Portal Routes
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

        // Leave Card System
        Route::get('/leave-card', [LeaveCardController::class, 'index'])->name('leave-card');

        // Personal Data Update System
        Route::get('/personal-data-update', [EmployeeSelfServiceController::class, 'personalDataUpdate'])->name('personal-data-update');
        Route::get('/personal-data-update/new', [EmployeeSelfServiceController::class, 'newPersonalDataUpdate'])->name('personal-data-update.new');
        Route::post('/change-requests', [EmployeeSelfServiceController::class, 'storeChangeRequest'])->name('change-requests.store');
    });

    // OPCR System Routes
    Route::prefix('opcr')->name('opcr.')->middleware(['auth', 'can:opcr.view'])->group(function () {
        // OPCR Dashboard
        Route::get('/dashboard', [OPCRController::class, 'dashboard'])->name('dashboard');

        // OPCR Workflows
        Route::prefix('workflows')->name('workflows.')->group(function () {
            Route::get('/', [OPCRController::class, 'index'])->name('index')->middleware('can:opcr.view');
            Route::get('/create', [OPCRController::class, 'create'])->name('create')->middleware('can:opcr.create');
            Route::post('/', [OPCRController::class, 'store'])->name('store')->middleware('can:opcr.create');
            Route::get('/{workflow}', [OPCRController::class, 'show'])->name('show')->middleware('can:opcr.view');
            Route::get('/{workflow}/edit', [OPCRController::class, 'edit'])->name('edit')->middleware(['can:opcr.edit']);
            Route::patch('/{workflow}', [OPCRController::class, 'update'])->name('update')->middleware(['can:opcr.edit']);
            Route::post('/{workflow}/submit', [OPCRController::class, 'submit'])->name('submit')->middleware(['can:opcr.submit']);
            Route::delete('/{workflow}', [OPCRController::class, 'destroy'])->name('destroy')->middleware(['can:opcr.delete']);

            // Workflow Evaluation and Approval
            Route::get('/{workflow}/evaluate', [OPCRController::class, 'evaluate'])->name('evaluate')->middleware(['can:opcr.assess']);
            Route::post('/{workflow}/evaluate', [OPCRController::class, 'submitEvaluation'])->name('submit.evaluation')->middleware(['can:opcr.assess']);
            Route::get('/{workflow}/review', [OPCRController::class, 'review'])->name('review')->middleware(['can:opcr.view']);
            Route::post('/{workflow}/approve', [OPCRController::class, 'finalApprove'])->name('approve')->middleware(['can:opcr.approve']);
            Route::post('/{workflow}/reject', [OPCRController::class, 'reject'])->name('reject')->middleware(['can:opcr.approve']);
            Route::post('/{workflow}/return', [OPCRController::class, 'returnForRevision'])->name('return')->middleware(['can:opcr.return']);
        });

        // MFO Management
        Route::prefix('mfos')->name('mfos.')->middleware('can:opcr.settings')->group(function () {
            Route::get('/', [MFOController::class, 'index'])->name('index');
            Route::get('/create', [MFOController::class, 'create'])->name('create')->middleware('can:opcr.create');
            Route::post('/', [MFOController::class, 'store'])->name('store')->middleware('can:opcr.create');
            Route::get('/{mfo}', [MFOController::class, 'show'])->name('show');
            Route::get('/{mfo}/edit', [MFOController::class, 'edit'])->name('edit')->middleware('can:opcr.edit');
            Route::patch('/{mfo}', [MFOController::class, 'update'])->name('update')->middleware('can:opcr.edit');
            Route::delete('/{mfo}', [MFOController::class, 'destroy'])->name('destroy')->middleware('can:opcr.delete');
        });

        // Success Indicators
        Route::prefix('success-indicators')->name('success-indicators.')->middleware('can:opcr.settings')->group(function () {
            Route::get('/', [SuccessIndicatorController::class, 'index'])->name('index');
            Route::get('/create', [SuccessIndicatorController::class, 'create'])->name('create')->middleware('can:opcr.create');
            Route::post('/', [SuccessIndicatorController::class, 'store'])->name('store')->middleware('can:opcr.create');
            Route::get('/{indicator}', [SuccessIndicatorController::class, 'show'])->name('show');
            Route::get('/{indicator}/edit', [SuccessIndicatorController::class, 'edit'])->name('edit')->middleware('can:opcr.edit');
            Route::patch('/{indicator}', [SuccessIndicatorController::class, 'update'])->name('update')->middleware('can:opcr.edit');
            Route::delete('/{indicator}', [SuccessIndicatorController::class, 'destroy'])->name('destroy')->middleware('can:opcr.delete');
        });

        // Office Management
        Route::prefix('offices')->name('offices.')->middleware('can:opcr.settings')->group(function () {
            Route::get('/', [OfficeController::class, 'opcrIndex'])->name('index');
            Route::get('/create', [OfficeController::class, 'opcrCreate'])->name('create')->middleware('can:opcr.create');
            Route::post('/', [OfficeController::class, 'store'])->name('store')->middleware('can:opcr.create');
            Route::get('/{office}', [OfficeController::class, 'opcrShow'])->name('show');
            Route::get('/{office}/edit', [OfficeController::class, 'opcrEdit'])->name('edit')->middleware('can:opcr.edit');
            Route::patch('/{office}', [OfficeController::class, 'update'])->name('update')->middleware('can:opcr.edit');
            Route::delete('/{office}', [OfficeController::class, 'destroy'])->name('destroy')->middleware('can:opcr.delete');

            // Office Assignments
            Route::prefix('{office}/assignments')->name('assignments.')->group(function () {
                Route::get('/', [OfficeAssignmentController::class, 'opcrIndex'])->name('index');
                Route::get('/create', [OfficeAssignmentController::class, 'opcrCreate'])->name('create')->middleware('can:opcr.create');
                Route::post('/', [OfficeAssignmentController::class, 'opcrStore'])->name('store')->middleware('can:opcr.create');
                Route::get('/{assignment}/edit', [OfficeAssignmentController::class, 'opcrEdit'])->name('edit')->middleware('can:opcr.edit');
                Route::patch('/{assignment}', [OfficeAssignmentController::class, 'opcrUpdate'])->name('update')->middleware('can:opcr.edit');
                Route::delete('/{assignment}', [OfficeAssignmentController::class, 'destroy'])->name('destroy')->middleware('can:opcr.delete');
            });
        });

        // Analytics and Reports
        Route::prefix('analytics')->name('analytics.')->middleware('can:opcr.analytics')->group(function () {
            Route::get('/', [OPCRAnalyticsController::class, 'index'])->name('index');
            Route::get('/performance', [OPCRAnalyticsController::class, 'performance'])->name('performance');
            Route::get('/workflow', [OPCRAnalyticsController::class, 'workflow'])->name('workflow');
            Route::get('/compliance', [OPCRAnalyticsController::class, 'compliance'])->name('compliance');
            Route::get('/export', [OPCRAnalyticsController::class, 'exportAnalytics'])->name('export');
        });

  
        // Export System
        Route::prefix('export')->name('export.')->middleware('can:opcr.export')->group(function () {
            Route::get('/', [OPCRExportController::class, 'index'])->name('index');
            Route::post('/workflow/{workflow}', [OPCRExportController::class, 'exportWorkflow'])->name('workflow')->middleware('opcr.state:can_view');
            Route::post('/summary', [OPCRExportController::class, 'exportSummary'])->name('summary');
            Route::post('/batch', [OPCRExportController::class, 'exportBatch'])->name('batch');
            Route::get('/pdf/{workflow}', [OPCRExportController::class, 'exportPDF'])->name('pdf')->middleware('opcr.state:can_view');
        });

        // Historical Archive
        Route::prefix('archive')->name('archive.')->middleware('can:opcr.admin')->group(function () {
            Route::get('/', [OPCRController::class, 'archive'])->name('index');
            Route::get('/export', [OPCRController::class, 'exportArchive'])->name('export');
        });
    });

    // Office Assignment Administration Routes
    Route::prefix('admin/office-assignments')->name('admin.office-assignments.')->middleware('verified')->group(function () {
        Route::get('/', [OfficeAssignmentController::class, 'index'])->name('index');
        Route::get('/create', [OfficeAssignmentController::class, 'create'])->name('create');
        Route::post('/', [OfficeAssignmentController::class, 'store'])->name('store');
        Route::get('/bulk/create', [OfficeAssignmentController::class, 'bulkCreate'])->name('bulk-create');
        Route::post('/bulk', [OfficeAssignmentController::class, 'bulkStore'])->name('bulk-store');
        Route::get('/{officeAssignment}', [OfficeAssignmentController::class, 'show'])->name('show');
        Route::get('/{officeAssignment}/edit', [OfficeAssignmentController::class, 'edit'])->name('edit');
        Route::patch('/{officeAssignment}', [OfficeAssignmentController::class, 'update'])->name('update');
        Route::post('/{officeAssignment}/toggle-status', [OfficeAssignmentController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{officeAssignment}', [OfficeAssignmentController::class, 'destroy'])->name('destroy');
    });

    // Admin Routes for Rating Scale Configuration
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:opcr.manage'])->group(function () {
        Route::prefix('rating-scales')->name('rating-scales.')->group(function () {
            Route::get('/', [RatingScaleController::class, 'index'])->name('index');
            Route::get('/create', [RatingScaleController::class, 'create'])->name('create');
            Route::post('/', [RatingScaleController::class, 'store'])->name('store');
            Route::get('/{ratingScale}', [RatingScaleController::class, 'show'])->name('show');
            Route::get('/{ratingScale}/edit', [RatingScaleController::class, 'edit'])->name('edit');
            Route::patch('/{ratingScale}', [RatingScaleController::class, 'update'])->name('update');
            Route::post('/{ratingScale}/set-default', [RatingScaleController::class, 'setDefault'])->name('set-default');
            Route::delete('/{ratingScale}', [RatingScaleController::class, 'destroy'])->name('destroy');
        });

        // Performance Period Management
        Route::prefix('performance-periods')->name('performance-periods.')->group(function () {
            Route::get('/', [PerformancePeriodController::class, 'index'])->name('index');
            Route::get('/create', [PerformancePeriodController::class, 'create'])->name('create');
            Route::post('/', [PerformancePeriodController::class, 'store'])->name('store');
            Route::get('/{period}', [PerformancePeriodController::class, 'show'])->name('show');
            Route::get('/{period}/edit', [PerformancePeriodController::class, 'edit'])->name('edit');
            Route::patch('/{period}', [PerformancePeriodController::class, 'update'])->name('update');
            Route::post('/{period}/activate', [PerformancePeriodController::class, 'activate'])->name('activate');
            Route::post('/{period}/close', [PerformancePeriodController::class, 'close'])->name('close');
            Route::delete('/{period}', [PerformancePeriodController::class, 'destroy'])->name('destroy');
        });

    });

    // Audit Trail Management (moved outside OPCR admin group)
    Route::prefix('admin/audit-trail')->name('admin.audit-trail.')->middleware(['auth', 'verified'])->group(function () {
        Route::get('/', [AuditTrailController::class, 'index'])->name('index');
        Route::get('/{activity}', [AuditTrailController::class, 'show'])->name('show');
        Route::post('/export', [AuditTrailController::class, 'export'])->name('export');
        Route::post('/cleanup', [AuditTrailController::class, 'cleanup'])->name('cleanup');
    });


});

require __DIR__ . '/auth.php';
