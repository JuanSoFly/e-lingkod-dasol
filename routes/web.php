<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\DocumentSearchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeavePolicyController;
use App\Http\Controllers\PerformancePeriodController;
use App\Http\Controllers\PerformanceRatingController;
use App\Http\Controllers\PerformanceTargetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CSCReportController;
use App\Http\Controllers\CSCReportManagementController;
use App\Http\Controllers\GovernmentBenefitController;
use App\Http\Controllers\BenefitContributionController;
use App\Http\Controllers\EmployeeSelfServiceController;
use App\Http\Controllers\HRAnalyticsController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\PDSController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\DocumentApprovalController;
use App\Http\Controllers\ApprovalActionController;
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
        Route::delete('{employee}/references/{reference}', [PDSController::class, 'destroyReference'])->name('destroy-reference');
        Route::get('{employee}/questionnaire', [PDSController::class, 'questionnaire'])->name('questionnaire');
        Route::patch('{employee}/questionnaire', [PDSController::class, 'updateQuestionnaire'])->name('update-questionnaire');
        // Panel 5: Work Experience
        Route::get('{employee}/work-experience', [PDSController::class, 'workExperience'])->name('work-experience');
        Route::post('{employee}/work-experience', [PDSController::class, 'storeWorkExperience'])->name('store-work-experience');
        Route::delete('{employee}/work-experience/{workExperience}', [PDSController::class, 'destroyWorkExperience'])->name('destroy-work-experience');
        // Panel 7: Learning & Development
        Route::get('{employee}/learning-development', [PDSController::class, 'learningDevelopment'])->name('learning-development');
        Route::post('{employee}/learning-development', [PDSController::class, 'storeLearningDevelopment'])->name('store-learning-development');
        Route::delete('{employee}/learning-development/{training}', [PDSController::class, 'destroyLearningDevelopment'])->name('destroy-learning-development');
        Route::get('{employee}/pdf', [PDSController::class, 'generatePDF'])->name('generate-pdf');
    });

    // Document Search Routes (New) - Must come before parameterized routes
    Route::get('documents/search', function () {
        return view('documents.search');
    })->name('documents.search')->middleware('can:employee.view');

    Route::get('documents/analytics', function () {
        return view('documents.analytics');
    })->name('documents.analytics')->middleware('can:reports.view');

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

    // Reporting Routes
    Route::prefix('reports')->name('reports.')->middleware('can:reports.view')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/employees/excel', [ReportController::class, 'exportEmployeesExcel'])->name('employees.excel');
        Route::get('/employees/pdf', [ReportController::class, 'exportEmployeesPdf'])->name('employees.pdf');
        // Leave Reports (New)
        Route::get('/leave-balances/excel', [ReportController::class, 'exportLeaveBalancesExcel'])->name('leave-balances.excel');

        // Performance Reports (New)
        Route::get('/performance-summary/excel', [ReportController::class, 'exportPerformanceSummaryExcel'])->name('performance-summary.excel');
    });

    // CSC Reporting Routes (New - Mr. Bryan's Requirements)
    Route::prefix('csc-reports')->name('csc-reports.')->middleware('can:reports.generate')->group(function () {
        Route::get('/', [CSCReportManagementController::class, 'index'])->name('index');
        Route::get('/generate', [CSCReportManagementController::class, 'create'])->name('create');
        Route::post('/generate', [CSCReportManagementController::class, 'store'])->name('store');
        Route::get('/{report}', [CSCReportManagementController::class, 'show'])->name('show');
        Route::get('/{report}/edit', [CSCReportManagementController::class, 'edit'])->name('edit');
        Route::put('/{report}', [CSCReportManagementController::class, 'update'])->name('update');
        Route::delete('/{report}', [CSCReportManagementController::class, 'destroy'])->name('destroy');

        // Workflow actions
        Route::post('/{report}/submit', [CSCReportManagementController::class, 'submit'])->name('submit');
        Route::post('/{report}/approve', [CSCReportManagementController::class, 'approve'])->name('approve');
        Route::post('/{report}/reject', [CSCReportManagementController::class, 'reject'])->name('reject');
        Route::post('/{report}/acknowledge', [CSCReportManagementController::class, 'acknowledge'])->name('acknowledge');

        // File downloads
        Route::get('/{report}/download/pdf', [CSCReportManagementController::class, 'downloadPdf'])->name('download.pdf');
        Route::get('/{report}/download/excel', [CSCReportManagementController::class, 'downloadExcel'])->name('download.excel');
    });

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

    // HR Analytics Routes (New)
    Route::prefix('hr-analytics')->name('hr-analytics.')->middleware('can:reports.view')->group(function () {
        // Main dashboard
        Route::get('/', [HRAnalyticsController::class, 'index'])->name('dashboard');

        // Individual analytics pages
        Route::get('/workforce', [HRAnalyticsController::class, 'workforce'])->name('workforce');
        Route::get('/turnover', [HRAnalyticsController::class, 'turnover'])->name('turnover');
        Route::get('/performance', [HRAnalyticsController::class, 'performance'])->name('performance');
        Route::get('/training', [HRAnalyticsController::class, 'training'])->name('training');
        Route::get('/compliance', [HRAnalyticsController::class, 'compliance'])->name('compliance');
        Route::get('/workforce-planning', [HRAnalyticsController::class, 'workforcePlanning'])->name('workforce-planning');
        Route::get('/costs', [HRAnalyticsController::class, 'costs'])->name('costs');
        Route::get('/predictive', [HRAnalyticsController::class, 'predictive'])->name('predictive');

        // API endpoints for data
        Route::get('/api/summary', [HRAnalyticsController::class, 'getSummary'])->name('summary');
        Route::get('/api/workforce', [HRAnalyticsController::class, 'getWorkforceAnalytics'])->name('api.workforce');
        Route::get('/api/turnover', [HRAnalyticsController::class, 'getTurnoverAnalytics'])->name('api.turnover');
        Route::get('/api/performance', [HRAnalyticsController::class, 'getPerformanceAnalytics'])->name('api.performance');
        Route::get('/api/training', [HRAnalyticsController::class, 'getTrainingAnalytics'])->name('api.training');
        Route::get('/api/compliance', [HRAnalyticsController::class, 'getComplianceAnalytics'])->name('api.compliance');
        Route::get('/api/workforce-planning', [HRAnalyticsController::class, 'getWorkforcePlanningAnalytics'])->name('api.workforce-planning');
        Route::get('/api/costs', [HRAnalyticsController::class, 'getCostAnalytics'])->name('api.costs');
        Route::get('/api/predictive', [HRAnalyticsController::class, 'getPredictiveAnalytics'])->name('api.predictive');
        Route::get('/api/insights', [HRAnalyticsController::class, 'getInsights'])->name('insights');
        Route::get('/api/trends', [HRAnalyticsController::class, 'getTrends'])->name('trends');
        Route::get('/api/department/{department}', [HRAnalyticsController::class, 'getDepartmentAnalytics'])->name('department');

        // Export and utility endpoints
        Route::post('/export', [HRAnalyticsController::class, 'export'])->name('export');
        Route::post('/clear-cache', [HRAnalyticsController::class, 'clearCache'])->name('clear-cache');
    });

    // Document Approval Routes
    Route::prefix('document-approvals')->name('document-approvals.')->group(function () {
        Route::get('/', [DocumentApprovalController::class, 'index'])->name('index');
        Route::get('/create', [DocumentApprovalController::class, 'create'])->name('create');
        Route::post('/', [DocumentApprovalController::class, 'store'])->name('store');
        Route::get('/dashboard', [DocumentApprovalController::class, 'dashboard'])->name('dashboard');
        Route::get('/my-requests', [DocumentApprovalController::class, 'myRequests'])->name('my-requests');
        Route::get('/pending-approvals', [DocumentApprovalController::class, 'pendingApprovals'])->name('pending-approvals');

        Route::get('/{documentApprovalRequest}', [DocumentApprovalController::class, 'show'])->name('show');
        Route::get('/{documentApprovalRequest}/edit', [DocumentApprovalController::class, 'edit'])->name('edit');
        Route::put('/{documentApprovalRequest}', [DocumentApprovalController::class, 'update'])->name('update');
        Route::delete('/{documentApprovalRequest}', [DocumentApprovalController::class, 'destroy'])->name('destroy');
        Route::post('/{documentApprovalRequest}/submit', [DocumentApprovalController::class, 'submit'])->name('submit');
        Route::patch('/{documentApprovalRequest}/withdraw', [DocumentApprovalController::class, 'withdraw'])->name('withdraw');
        Route::get('/{documentApprovalRequest}/download/{attachment}', [DocumentApprovalController::class, 'download'])->name('download');

        // Approval Actions
        Route::post('/{documentApprovalRequest}/approve', [ApprovalActionController::class, 'approve'])->name('approve');
        Route::post('/{documentApprovalRequest}/reject', [ApprovalActionController::class, 'reject'])->name('reject');
        Route::post('/{documentApprovalRequest}/request-changes', [ApprovalActionController::class, 'requestChanges'])->name('request-changes');
        Route::post('/{documentApprovalRequest}/add-comment', [ApprovalActionController::class, 'addComment'])->name('add-comment');
        Route::post('/{documentApprovalRequest}/assign-approver', [ApprovalActionController::class, 'assignApprover'])->name('assign-approver');
        Route::post('/{documentApprovalRequest}/reassign', [ApprovalActionController::class, 'reassign'])->name('reassign');
        Route::post('/{documentApprovalRequest}/escalate', [ApprovalActionController::class, 'escalate'])->name('escalate');

        // Bulk Actions
        Route::post('/bulk/approve', [ApprovalActionController::class, 'bulkApprove'])->name('bulk.approve');
        Route::post('/bulk/reject', [ApprovalActionController::class, 'bulkReject'])->name('bulk.reject');
    });
});

require __DIR__ . '/auth.php';
