<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeWorkExperience;
use App\Models\EmployeeEducation;
use App\Models\EmployeeFamilyBackground;
use App\Models\EmployeeChildren;
use App\Models\EmployeeCivilServiceEligibility;
use App\Models\EmployeeVoluntaryWork;
use App\Models\EmployeeOtherInformation;
use App\Models\EmployeeReference;
use App\Models\EmployeeQuestionnaire;
use App\Models\EmployeeDocument;
use App\Models\LeaveApplication;
use App\Models\LeaveCredit;
use App\Models\LeaveCard;
use App\Models\LeaveCardEntry;
use App\Models\LeaveApplicationWorkflowStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\Activity;

class ArchiveService
{
    private AuditTrailService $auditTrailService;
    private OfficeAssignmentService $officeAssignmentService;

    public function __construct(
        AuditTrailService $auditTrailService,
        OfficeAssignmentService $officeAssignmentService
    ) {
        $this->auditTrailService = $auditTrailService;
        $this->officeAssignmentService = $officeAssignmentService;
    }

    /**
     * Archive an employee and their associated user account
     */
    public function archiveEmployee(Employee $employee, ?User $archivedBy = null): Employee
    {
        return DB::transaction(function () use ($employee, $archivedBy) {
            try {
                // Handle pending leave applications before archival
                $pendingApplicationsResult = $this->handlePendingApplicationsDuringArchival($employee, $archivedBy);

                // Archive the employee record
                $employee->update([
                    'archived_at' => now(),
                    'archived_by' => $archivedBy?->id,
                    'updated_at' => now(),
                ]);

                // Turn off any lingering office assignments before soft delete
                $assignmentCleanup = $this->officeAssignmentService->deactivateAssignmentsForEmployee(
                    $employee,
                    'employee archive'
                );

                // Soft delete the employee
                $employee->delete();

                // Soft delete associated user account if exists
                if ($employee->user) {
                    $employee->user->delete();
                }

                // Log the archive operation
                $this->auditTrailService->logEmployeeArchive($employee, $archivedBy, [
                    'reason' => 'Employee archive operation',
                    'pending_applications_handled' => $pendingApplicationsResult,
                    'office_assignments_deactivated' => $assignmentCleanup,
                ]);

                Log::info('Employee archived successfully', [
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'archived_by' => $archivedBy?->id,
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'pending_applications' => $pendingApplicationsResult,
                    'office_assignments_deactivated' => $assignmentCleanup,
                ]);

                return $employee;

            } catch (\Exception $e) {
                Log::error('Failed to archive employee', [
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'archived_by' => $archivedBy?->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Restore an archived employee and their complete data
     */
    public function restoreEmployee(Employee $employee, ?User $restoredBy = null): Employee
    {
        // Ensure we have a linked user before running validations
        $this->ensureUserLinkage($employee);

        // Pre-restoration validation
        $validation = $this->validateUserAccount($employee);
        if (!$validation['can_restore']) {
            throw new \Exception('Cannot restore employee: ' . implode(', ', $validation['issues']));
        }

        // Get restoration details for logging
        $restorationDetails = $this->getRestorationDetails($employee);

        return DB::transaction(function () use ($employee, $restoredBy, $validation, $restorationDetails) {
            try {
                // Ensure we are working with the latest linked user (including trashed)
                $this->ensureUserLinkage($employee);

                // Restore the employee record
                $employee->restore();

                // Clear archive fields
                $employee->update([
                    'archived_at' => null,
                    'archived_by' => null,
                    'updated_at' => now(),
                ]);

                // Restore all PDS-related data
                $pdsRestorationResult = $this->restoreAllPDSData($employee, $restoredBy);

                // Restore all leave-related data
                $leaveRestorationResult = $this->restoreAllLeaveData($employee, $restoredBy);

                $userRestorationResult = $this->restoreUserAccount($employee, $restoredBy);

                // Log the restore operation with enhanced details
                $this->auditTrailService->logEmployeeRestore($employee, $restoredBy, [
                    'reason' => 'Employee restore operation',
                    'user_account_restored' => $userRestorationResult['restored'],
                    'restoration_warnings' => $validation['warnings'],
                    'restoration_risks' => $restorationDetails['restoration_risks'],
                    'pds_data_restored' => $pdsRestorationResult,
                    'leave_data_restored' => $leaveRestorationResult,
                ]);

                Log::info('Employee restored successfully', [
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'restored_by' => $restoredBy?->id,
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'user_restored' => $userRestorationResult['restored'],
                    'user_id' => $userRestorationResult['user_id'],
                    'validation_warnings' => $validation['warnings'],
                    'pds_restoration' => $pdsRestorationResult,
                    'leave_restoration' => $leaveRestorationResult,
                ]);

                return $employee;

            } catch (\Exception $e) {
                Log::error('Failed to restore employee', [
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'restored_by' => $restoredBy?->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'validation_issues' => $validation['issues'] ?? [],
                ]);

                throw $e;
            }
        });
    }

    /**
     * Restore all PDS-related data for an employee
     */
    private function restoreAllPDSData(Employee $employee, ?User $restoredBy): array
    {
        $restorationResult = [
            'total_models' => 0,
            'restored_models' => [],
            'failed_models' => [],
            'total_records_restored' => 0,
        ];

        // PDS models to restore
        $pdsModels = [
            'workExperiences' => EmployeeWorkExperience::class,
            'education' => EmployeeEducation::class,
            'familyBackground' => EmployeeFamilyBackground::class,
            'children' => EmployeeChildren::class,
            'civilServiceEligibilities' => EmployeeCivilServiceEligibility::class,
            'voluntaryWork' => EmployeeVoluntaryWork::class,
            'otherInformation' => EmployeeOtherInformation::class,
            'references' => EmployeeReference::class,
            'questionnaire' => EmployeeQuestionnaire::class,
            'documents' => EmployeeDocument::class,
        ];

        foreach ($pdsModels as $relation => $modelClass) {
            try {
                $restorationResult['total_models']++;

                // Restore soft-deleted records for this relation
                $restoredCount = $modelClass::where('employee_id', $employee->id)
                    ->onlyTrashed()
                    ->restore();

                if ($restoredCount > 0) {
                    $restorationResult['restored_models'][] = [
                        'relation' => $relation,
                        'model' => $modelClass,
                        'count' => $restoredCount,
                    ];
                    $restorationResult['total_records_restored'] += $restoredCount;

                    Log::info("PDS data restored for {$relation}", [
                        'employee_id' => $employee->id,
                        'relation' => $relation,
                        'count' => $restoredCount,
                        'restored_by' => $restoredBy?->id,
                    ]);
                }

            } catch (\Exception $e) {
                $restorationResult['failed_models'][] = [
                    'relation' => $relation,
                    'model' => $modelClass,
                    'error' => $e->getMessage(),
                ];

                Log::error("Failed to restore PDS data for {$relation}", [
                    'employee_id' => $employee->id,
                    'relation' => $relation,
                    'error' => $e->getMessage(),
                    'restored_by' => $restoredBy?->id,
                ]);
            }
        }

        // Validate PDS data integrity after restoration
        $this->validatePDSDataIntegrity($employee);

        return $restorationResult;
    }

    /**
     * Restore all leave-related data for an employee
     */
    private function restoreAllLeaveData(Employee $employee, ?User $restoredBy): array
    {
        $restorationResult = [
            'total_models' => 0,
            'restored_models' => [],
            'failed_models' => [],
            'total_records_restored' => 0,
            'pendingApplicationsHandled' => 0,
        ];

        // Leave models to restore
        $leaveModels = [
            'leaveApplications' => LeaveApplication::class,
            'leaveCredits' => LeaveCredit::class,
            'leaveCards' => LeaveCard::class,
            'leaveCardEntries' => LeaveCardEntry::class,
            'leaveApplicationWorkflowSteps' => LeaveApplicationWorkflowStep::class,
        ];

        foreach ($leaveModels as $relation => $modelClass) {
            try {
                $restorationResult['total_models']++;

                // Restore soft-deleted records for this relation
                $restoredCount = $modelClass::where('employee_id', $employee->id)
                    ->onlyTrashed()
                    ->restore();

                if ($restoredCount > 0) {
                    $restorationResult['restored_models'][] = [
                        'relation' => $relation,
                        'model' => $modelClass,
                        'count' => $restoredCount,
                    ];
                    $restorationResult['total_records_restored'] += $restoredCount;

                    Log::info("Leave data restored for {$relation}", [
                        'employee_id' => $employee->id,
                        'relation' => $relation,
                        'count' => $restoredCount,
                        'restored_by' => $restoredBy?->id,
                    ]);
                }

            } catch (\Exception $e) {
                $restorationResult['failed_models'][] = [
                    'relation' => $relation,
                    'model' => $modelClass,
                    'error' => $e->getMessage(),
                ];

                Log::error("Failed to restore leave data for {$relation}", [
                    'employee_id' => $employee->id,
                    'relation' => $relation,
                    'error' => $e->getMessage(),
                    'restored_by' => $restoredBy?->id,
                ]);
            }
        }

        // Handle pending leave applications after restoration
        $pendingHandled = $this->handlePendingLeaveApplications($employee, $restoredBy);
        $restorationResult['pendingApplicationsHandled'] = $pendingHandled;

        return $restorationResult;
    }

    /**
     * Validate PDS data integrity after restoration
     */
    private function validatePDSDataIntegrity(Employee $employee): void
    {
        $issues = [];

        // Check for critical PDS data completeness
        $workExperienceCount = $employee->workExperiences()->count();
        if ($workExperienceCount === 0) {
            $issues[] = 'No work experience records found';
        }

        $educationCount = $employee->education()->count();
        if ($educationCount === 0) {
            $issues[] = 'No education records found';
        }

        // Check family background completeness
        if (!$employee->familyBackground()->exists()) {
            $issues[] = 'Family background record missing';
        }

        // Log any integrity issues
        if (!empty($issues)) {
            Log::warning('PDS data integrity issues detected after restoration', [
                'employee_id' => $employee->id,
                'issues' => $issues,
            ]);
        }
    }

    /**
     * Handle pending leave applications after employee restoration
     */
    private function handlePendingLeaveApplications(Employee $employee, ?User $restoredBy): int
    {
        $handledCount = 0;

        try {
            // Find pending leave applications
            $pendingApplications = $employee->leaveApplications()
                ->whereIn('status', ['pending', 'under_review'])
                ->get();

            foreach ($pendingApplications as $application) {
                // Update status to reflect that employee was archived
                $application->update([
                    'status' => 'cancelled',
                    'remarks' => ($application->remarks ?? '') . "\n[CANCELLED: Employee was archived and has been restored]",
                ]);

                // Cancel workflow steps
                $application->workflowSteps()
                    ->whereIn('status', ['pending', 'escalated'])
                    ->update([
                        'status' => 'cancelled',
                        'remarks' => 'Cancelled due to employee archival',
                    ]);

                $handledCount++;

                Log::info('Pending leave application cancelled after employee restoration', [
                    'employee_id' => $employee->id,
                    'application_id' => $application->id,
                    'leave_type' => $application->leaveType->name ?? 'Unknown',
                    'days_requested' => $application->days_requested,
                    'restored_by' => $restoredBy?->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle pending leave applications after restoration', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'restored_by' => $restoredBy?->id,
            ]);
        }

        return $handledCount;
    }

    /**
     * Restore user account with enhanced error handling
     */
    private function restoreUserAccount(Employee $employee, ?User $restoredBy): array
    {
        $result = [
            'restored' => false,
            'user_id' => null,
            'message' => '',
        ];

        $this->ensureUserLinkage($employee);

        if (!$employee->user) {
            $result['message'] = 'No user account associated with employee';
            Log::warning('No user account found for restored employee', [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'restored_by' => $restoredBy?->id,
            ]);
            return $result;
        }

        $user = $employee->user;

        // Check if user is already active
        if (!$user->trashed()) {
            $result['user_id'] = $user->id;
            $result['message'] = 'User account already active';
            Log::warning('User account already active during employee restore', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'employee_id' => $employee->id,
                'restored_by' => $restoredBy?->id,
            ]);
            return $result;
        }

        // Check for email conflicts before restoration
        $emailConflict = User::where('email', $user->email)
            ->where('id', '!=', $user->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($emailConflict) {
            $result['message'] = 'Email conflict detected - user account not restored';
            Log::error('Email conflict prevented user account restoration', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'employee_id' => $employee->id,
                'restored_by' => $restoredBy?->id,
            ]);
            throw new \Exception('Cannot restore user account: email address conflicts with existing active user');
        }

        try {
            // Restore the user account
            $user->restore();

            $result['restored'] = true;
            $result['user_id'] = $user->id;
            $result['message'] = 'User account restored successfully';

            Log::info('User account restored successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'employee_id' => $employee->id,
                'restored_by' => $restoredBy?->id,
                'employee_name' => $employee->first_name . ' ' . $employee->last_name,
            ]);

        } catch (\Exception $e) {
            $result['message'] = 'Failed to restore user account: ' . $e->getMessage();
            Log::error('Failed to restore user account', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'employee_id' => $employee->id,
                'restored_by' => $restoredBy?->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $result;
    }

    /**
     * Handle pending leave applications during employee archival
     */
    private function handlePendingApplicationsDuringArchival(Employee $employee, ?User $archivedBy): array
    {
        $result = [
            'total_pending' => 0,
            'cancelled' => 0,
            'rejected' => 0,
            'errors' => [],
        ];

        try {
            // Find pending leave applications
            $pendingApplications = $employee->leaveApplications()
                ->whereIn('status', ['pending', 'under_review', 'escalated'])
                ->get();

            $result['total_pending'] = $pendingApplications->count();

            foreach ($pendingApplications as $application) {
                try {
                    // Determine appropriate action based on application status and age
                    $daysSinceApplication = $application->applied_date
                        ? now()->diffInDays($application->applied_date)
                        : 0;

                    // Auto-reject very old applications or auto-cancel recent ones
                    if ($daysSinceApplication > 30 || $application->status === 'escalated') {
                        $action = 'rejected';
                        $reason = 'Auto-rejected: Employee has been archived';
                    } else {
                        $action = 'cancelled';
                        $reason = 'Auto-cancelled: Employee has been archived';
                    }

                    // Update application status
                    $application->update([
                        'status' => $action,
                        'remarks' => ($application->remarks ?? '') . "\n[{$reason}]",
                    ]);

                    // Cancel workflow steps
                    $application->workflowSteps()
                        ->whereIn('status', ['pending', 'escalated'])
                        ->update([
                            'status' => 'cancelled',
                            'remarks' => 'Cancelled due to employee archival',
                        ]);

                    $result[$action]++;

                    Log::info("Pending leave application {$action} during archival", [
                        'employee_id' => $employee->id,
                        'application_id' => $application->id,
                        'leave_type' => $application->leaveType->name ?? 'Unknown',
                        'days_requested' => $application->days_requested,
                        'original_status' => $application->getOriginal('status'),
                        'new_status' => $action,
                        'archived_by' => $archivedBy?->id,
                    ]);

                } catch (\Exception $e) {
                    $result['errors'][] = [
                        'application_id' => $application->id,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to handle pending leave application during archival', [
                        'employee_id' => $employee->id,
                        'application_id' => $application->id,
                        'error' => $e->getMessage(),
                        'archived_by' => $archivedBy?->id,
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle pending leave applications during archival', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'archived_by' => $archivedBy?->id,
            ]);

            $result['errors'][] = [
                'general_error' => $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * Permanently delete an archived employee (only for Super Admin)
     */
    public function permanentDelete(Employee $employee, ?User $deletedBy = null): bool
    {
        return DB::transaction(function () use ($employee, $deletedBy) {
            try {
                // Ensure employee is archived before permanent deletion
                if (!$employee->trashed()) {
                    throw new \Exception('Cannot permanently delete an active employee. Archive the employee first.');
                }

                // Perform safety checks and data impact assessment
                $safetyCheck = $this->performPermanentDeleteSafetyCheck($employee);

                if (!$safetyCheck['can_delete']) {
                    throw new \Exception('Cannot permanently delete employee: ' . implode(', ', $safetyCheck['blocking_issues']));
                }

                // Get employee details for logging before deletion
                $employeeDetails = [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                ];

                // Log final warning before permanent deletion
                Log::critical('PERMANENT DELETION IMMINENT - This action cannot be undone', [
                    'employee_details' => $employeeDetails,
                    'deleted_by' => $deletedBy?->id,
                    'data_impact_summary' => $safetyCheck['impact_summary'],
                    'warnings' => $safetyCheck['warnings'],
                ]);

                // Force delete the employee (will cascade delete related records)
                $employee->forceDelete();

                // Log the permanent deletion
                $this->auditTrailService->logEmployeeActivity('permanently_deleted', $employee, [
                    'employee_details' => $employeeDetails,
                    'deleted_by' => $deletedBy?->id,
                    'action_type' => 'permanent_delete',
                    'reason' => 'Permanent deletion of archived employee',
                    'data_impact_summary' => $safetyCheck['impact_summary'],
                    'warnings' => $safetyCheck['warnings'],
                ]);

                Log::warning('Employee permanently deleted', [
                    'employee_details' => $employeeDetails,
                    'deleted_by' => $deletedBy?->id,
                    'data_impact_summary' => $safetyCheck['impact_summary'],
                ]);

                return true;

            } catch (\Exception $e) {
                Log::error('Failed to permanently delete employee', [
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'deleted_by' => $deletedBy?->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Perform safety checks before permanent deletion
     */
    private function performPermanentDeleteSafetyCheck(Employee $employee): array
    {
        $safetyCheck = [
            'can_delete' => true,
            'blocking_issues' => [],
            'warnings' => [],
            'impact_summary' => [],
        ];

        // Check data impact summary
        $impactSummary = [];

        // Count PDS records that will be permanently deleted
        $pdsCounts = [
            'work_experiences' => $employee->workExperiences()->withTrashed()->count(),
            'education_records' => $employee->education()->withTrashed()->count(),
            'family_background' => $employee->familyBackground()->withTrashed()->count(),
            'children' => $employee->children()->withTrashed()->count(),
            'civil_service_eligibilities' => $employee->civilServiceEligibilities()->withTrashed()->count(),
            'voluntary_work' => $employee->voluntaryWork()->withTrashed()->count(),
            'other_information' => $employee->otherInformation()->withTrashed()->count(),
            'references' => $employee->references()->withTrashed()->count(),
            'questionnaire' => $employee->questionnaire()->withTrashed()->count(),
            'documents' => $employee->documents()->withTrashed()->count(),
        ];

        $totalPDSRecords = array_sum($pdsCounts);
        if ($totalPDSRecords > 0) {
            $impactSummary['pds_records'] = $pdsCounts;
            $impactSummary['total_pds_records'] = $totalPDSRecords;
            $safetyCheck['warnings'][] = "PERMANENTLY DELETING {$totalPDSRecords} PDS records including work experience, education, and family data";
        }

        // Count leave records that will be permanently deleted
        $leaveCounts = [
            'leave_applications' => $employee->leaveApplications()->withTrashed()->count(),
            'leave_credits' => $employee->leaveCredits()->withTrashed()->count(),
            'leave_cards' => $employee->leaveCards()->withTrashed()->count(),
            'leave_card_entries' => $employee->leaveCardEntries()->withTrashed()->count(),
        ];

        $totalLeaveRecords = array_sum($leaveCounts);
        if ($totalLeaveRecords > 0) {
            $impactSummary['leave_records'] = $leaveCounts;
            $impactSummary['total_leave_records'] = $totalLeaveRecords;
            $safetyCheck['warnings'][] = "PERMANENTLY DELETING {$totalLeaveRecords} leave records including applications, credits, and history";
        }

        // Check for compliance requirements
        $archivedDate = $employee->archived_at;
        if ($archivedDate) {
            $daysArchived = now()->diffInDays($archivedDate);
            if ($daysArchived < 365) {
                $safetyCheck['warnings'][] = "Employee archived only {$daysArchived} days ago. Consider maintaining records for at least 1 year for compliance";
            }
        }

        // Check for recent activity that might indicate the employee shouldn't be deleted
        $recentActivity = $this->checkRecentEmployeeActivity($employee);
        if (!empty($recentActivity)) {
            $safetyCheck['blocking_issues'][] = "Recent activity detected: " . implode(', ', $recentActivity);
            $safetyCheck['can_delete'] = false;
        }

        // Check for active legal or compliance holds
        if ($this->hasComplianceHold($employee)) {
            $safetyCheck['blocking_issues'][] = "Employee has compliance holds that prevent permanent deletion";
            $safetyCheck['can_delete'] = false;
        }

        $safetyCheck['impact_summary'] = $impactSummary;

        return $safetyCheck;
    }

    /**
     * Check for recent employee activity that might prevent deletion
     */
    private function checkRecentEmployeeActivity(Employee $employee): array
    {
        $activities = [];

        // Check for recent leave applications
        $recentLeaveApps = $employee->leaveApplications()
            ->withTrashed()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        if ($recentLeaveApps > 0) {
            $activities[] = "{$recentLeaveApps} recent leave application(s)";
        }

        // Check for recent document uploads
        $recentDocuments = $employee->documents()
            ->withTrashed()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        if ($recentDocuments > 0) {
            $activities[] = "{$recentDocuments} recent document upload(s)";
        }

        // Check for recent performance evaluations
        $recentEvaluations = $employee->performanceEvaluations()
            ->withTrashed()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        if ($recentEvaluations > 0) {
            $activities[] = "{$recentEvaluations} recent performance evaluation(s)";
        }

        return $activities;
    }

    /**
     * Check if employee has compliance holds
     */
    private function hasComplianceHold(Employee $employee): bool
    {
        // Check for active legal cases
        $activeLegalCases = $employee->legalCases()
            ->where('status', 'active')
            ->count();

        if ($activeLegalCases > 0) {
            return true;
        }

        // Check for ongoing investigations
        $activeInvestigations = $employee->investigations()
            ->where('status', 'ongoing')
            ->count();

        if ($activeInvestigations > 0) {
            return true;
        }

        // Check for pending disciplinary actions
        $pendingDisciplinary = $employee->disciplinaryActions()
            ->where('status', 'pending')
            ->count();

        if ($pendingDisciplinary > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get archived employees with filtering and pagination
     */
    public function getArchivedEmployees(array $filters = [], int $perPage = 15)
    {
        $query = Employee::onlyTrashed()
            ->with(['user', 'archivedBy'])
            ->orderBy('archived_at', 'desc');

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (!empty($filters['position'])) {
            $query->where('position', $filters['position']);
        }

        if (!empty($filters['archived_by'])) {
            $query->where('archived_by', $filters['archived_by']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('archived_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('archived_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get archive statistics
     */
    public function getArchiveStats(): array
    {
        $totalArchived = Employee::onlyTrashed()->count();
        $archivedThisMonth = Employee::onlyTrashed()
            ->whereMonth('archived_at', now()->month)
            ->whereYear('archived_at', now()->year)
            ->count();

        $archivedByUsers = Employee::onlyTrashed()
            ->with('archivedBy')
            ->get()
            ->groupBy('archivedBy.name')
            ->map(fn($group) => $group->count())
            ->toArray();

        $recentArchives = Employee::onlyTrashed()
            ->with('archivedBy')
            ->orderBy('archived_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_archived' => $totalArchived,
            'archived_this_month' => $archivedThisMonth,
            'archived_by_users' => $archivedByUsers,
            'recent_archives' => $recentArchives,
        ];
    }

    /**
     * Check if employee can be archived
     */
    public function canArchive(Employee $employee): bool
    {
        // Cannot archive already archived employee
        if ($employee->trashed()) {
            return false;
        }

        // Add any additional business rules here
        // For example: check if employee has pending tasks, etc.

        return true;
    }

    /**
     * Check if employee can be restored
     */
    public function canRestore(Employee $employee): bool
    {
        // Can only restore archived employees
        if (!$employee->trashed()) {
            return false;
        }

        // Check for email conflicts with existing active users
        if ($employee->user) {
            $emailConflict = User::where('email', $employee->user->email)
                ->where('id', '!=', $employee->user->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($emailConflict) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ensure employee has a linked user record (including soft-deleted users)
     */
    private function ensureUserLinkage(Employee $employee): void
    {
        if (!$employee->relationLoaded('user') || !$employee->user) {
            $employee->load(['user' => function ($query) {
                $query->withTrashed();
            }]);
        }

        if ($employee->user && $employee->user->employee_id === $employee->id) {
            return;
        }

        if (!$employee->email) {
            return;
        }

        $user = User::withTrashed()
            ->where('email', $employee->email)
            ->first();

        if ($user) {
            app(IdentityLinker::class)->link($user, $employee);
            $employee->setRelation('user', $user);
        }
    }

    /**
     * Validate user account before restoration
     */
    public function validateUserAccount(Employee $employee): array
    {
        $issues = [];
        $warnings = [];

        $this->ensureUserLinkage($employee);

        // Load user relationship including soft-deleted records
        $employee->load(['user' => function($query) {
            $query->withTrashed();
        }]);

        if (!$employee->user) {
            $warnings[] = 'No user account associated with this employee';
            return ['issues' => $issues, 'warnings' => $warnings, 'can_restore' => true];
        }

        $user = $employee->user;

        // Check for email conflicts
        if ($user->email) {
            $emailConflict = User::where('email', $user->email)
                ->where('id', '!=', $user->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($emailConflict) {
                $issues[] = 'Email address conflicts with an existing active user account';
            }
        }

        // Check user status
        if (!$user->trashed()) {
            $warnings[] = 'User account is already active';
        }

        // Check if user has required fields
        if (empty($user->email)) {
            $issues[] = 'User account is missing email address';
        }

        if (empty($user->password)) {
            $warnings[] = 'User account may need password reset';
        }

        return [
            'issues' => $issues,
            'warnings' => $warnings,
            'can_restore' => empty($issues),
            'user_status' => $user->trashed() ? 'soft_deleted' : 'active'
        ];
    }

    /**
     * Get restoration details for reporting
     */
    public function getRestorationDetails(Employee $employee): array
    {
        $this->ensureUserLinkage($employee);

        $employee->load(['user' => function($query) {
            $query->withTrashed();
        }]);

        $details = [
            'employee_id' => $employee->id,
            'employee_name' => $employee->first_name . ' ' . $employee->last_name,
            'employee_number' => $employee->employee_number,
            'archived_at' => $employee->archived_at,
            'user_account' => null,
            'restoration_risks' => [],
        ];

        if ($employee->user) {
            $user = $employee->user;
            $details['user_account'] = [
                'id' => $user->id,
                'email' => $user->email,
                'status' => $user->trashed() ? 'soft_deleted' : 'active',
                'last_login' => $user->last_login_at,
                'email_verified' => !is_null($user->email_verified_at),
            ];

            // Check for restoration risks
            if ($user->trashed()) {
                $details['restoration_risks'][] = 'User account has been soft-deleted and will be restored';
            }

            // Check for email conflicts
            $emailConflict = User::where('email', $user->email)
                ->where('id', '!=', $user->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($emailConflict) {
                $details['restoration_risks'][] = 'Email address conflicts with existing user';
            }
        } else {
            $details['restoration_risks'][] = 'No user account found - employee will not have login access';
        }

        return $details;
    }
}
