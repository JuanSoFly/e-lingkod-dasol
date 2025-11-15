<?php

namespace App\Services;

use App\Models\OPCRWorkflow;
use App\Models\MajorFinalOutput;
use App\Models\SuccessIndicator;
use App\Models\Office;
use App\Models\OfficeAssignment;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\User;
use App\Exports\AuditTrailExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class AuditTrailService
{
    /**
     * Generic log method for audit trails
     */
    public function log(string $action, $subjectId, string $description, array $data = []): Activity
    {
        return activity()
            ->causedBy(Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'audit_trail',
                'action' => $action,
                'subject_id' => $subjectId,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log($description);
    }

    /**
     * Log OPCR workflow activity
     */
    public function logOPCRActivity(string $action, OPCRWorkflow $workflow, array $data = []): Activity
    {
        $description = $data['action_description'] ?? "OPCR Workflow: {$action}";
        unset($data['action_description']); // Remove from properties to avoid duplication

        return activity()
            ->performedOn($workflow)
            ->causedBy(Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'opcr_workflow',
                'action' => $action,
                'workflow_id' => $workflow->id,
                'office_id' => $workflow->office_id,
                'period_id' => $workflow->period_id,
                'workflow_state' => $workflow->workflow_state,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log($description);
    }

    /**
     * Log MFO activity
     */
    public function logMFOActivity(string $action, MajorFinalOutput $mfo, array $data = []): Activity
    {
        return activity()
            ->performedOn($mfo)
            ->causedBy(Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'mfo',
                'action' => $action,
                'mfo_id' => $mfo->id,
                'office_id' => $mfo->office_id,
                'mfo_code' => $mfo->code,
                'mfo_title' => $mfo->title,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log("MFO: {$action}");
    }

    /**
     * Log Success Indicator activity
     */
    public function logSuccessIndicatorActivity(string $action, SuccessIndicator $successIndicator, array $data = []): Activity
    {
        return activity()
            ->performedOn($successIndicator)
            ->causedBy(Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'success_indicator',
                'action' => $action,
                'success_indicator_id' => $successIndicator->id,
                'mfo_id' => $successIndicator->mfo_id,
                'si_code' => $successIndicator->code,
                'si_title' => $successIndicator->title,
                'ratings_before' => [
                    'quantity' => $successIndicator->rating_quantity,
                    'efficiency' => $successIndicator->rating_efficiency,
                    'timeliness' => $successIndicator->rating_timeliness,
                    'average' => $successIndicator->average_rating,
                ],
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log("Success Indicator: {$action}");
    }

    /**
     * Log Office activity
     */
    public function logOfficeActivity(string $action, Office $office, array $data = []): Activity
    {
        return activity()
            ->performedOn($office)
            ->causedBy(Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'office',
                'action' => $action,
                'office_id' => $office->id,
                'office_code' => $office->code,
                'office_name' => $office->name,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log("Office: {$action}");
    }

    /**
     * Log office assignment activity
     */
    public function logOfficeAssignment(string $action, OfficeAssignment $assignment, Office $office, Employee $employee, array $data = []): Activity
    {
        return activity()
            ->performedOn($assignment)
            ->causedBy(Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'office_assignment',
                'action' => $action,
                'assignment_id' => $assignment->id,
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'user_id' => $assignment->user_id,
                'office_id' => $office->id,
                'office_name' => $office->name,
                'office_code' => $office->code,
                'role' => $assignment->role,
                'assigned_date' => $assignment->assigned_date,
                'ended_date' => $assignment->ended_date,
                'is_active' => $assignment->is_active,
                'assigned_by' => $assignment->assigned_by,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log("Office Assignment: {$action}");
    }

    /**
     * Log bulk activity (for operations affecting multiple records)
     */
    public function logBulkActivity(string $action, array $data = []): Activity
    {
        return activity()
            ->causedBy(Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'bulk_operation',
                'action' => $action,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log("Bulk Operation: {$action}");
    }

    /**
     * Log rating changes
     */
    public function logRatingChange(SuccessIndicator $successIndicator, array $oldRatings, array $newRatings): Activity
    {
        return activity()
            ->performedOn($successIndicator)
            ->causedBy(Auth::user())
            ->withProperties([
                'action_type' => 'rating_change',
                'action' => 'ratings_updated',
                'success_indicator_id' => $successIndicator->id,
                'si_code' => $successIndicator->code,
                'old_ratings' => $oldRatings,
                'new_ratings' => $newRatings,
                'rating_differences' => [
                    'quantity_change' => ($newRatings['rating_quantity'] ?? null) - ($oldRatings['rating_quantity'] ?? null),
                    'efficiency_change' => ($newRatings['rating_efficiency'] ?? null) - ($oldRatings['rating_efficiency'] ?? null),
                    'timeliness_change' => ($newRatings['rating_timeliness'] ?? null) - ($oldRatings['rating_timeliness'] ?? null),
                    'average_change' => ($newRatings['average_rating'] ?? null) - ($oldRatings['average_rating'] ?? null),
                ],
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Success Indicator: Ratings Updated");
    }

    /**
     * Log state transition
     */
    public function logStateTransition(OPCRWorkflow $workflow, string $fromState, string $toState, array $data = []): Activity
    {
        return activity()
            ->performedOn($workflow)
            ->causedBy(Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'state_transition',
                'action' => 'state_changed',
                'workflow_id' => $workflow->id,
                'from_state' => $fromState,
                'to_state' => $toState,
                'transition_reason' => $data['reason'] ?? null,
                'transition_data' => $data,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log("OPCR Workflow: State changed from {$fromState} to {$toState}");
    }

    /**
     * Log file attachment/download
     */
    public function logFileActivity(string $action, string $fileType, $model, array $data = []): Activity
    {
        return activity()
            ->performedOn($model)
            ->causedBy(Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'file_activity',
                'action' => $action,
                'file_type' => $fileType,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log("File: {$action} - {$fileType}");
    }

    /**
     * Log login/logout activity for OPCR system
     */
    public function logAuthenticationActivity(string $action, User $user, array $data = []): Activity
    {
        return activity()
            ->causedBy($user)
            ->withProperties(array_merge($data, [
                'action_type' => 'authentication',
                'action' => $action,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'opcr_system' => true,
            ]))
            ->log("Authentication: {$action}");
    }

    /**
     * Log permission/role changes
     */
    public function logRoleActivity(string $action, User $user, string $role, int $officeId, array $data = []): Activity
    {
        return activity()
            ->causedBy(Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'role_change',
                'action' => $action,
                'target_user_id' => $user->id,
                'target_user_email' => $user->email,
                'role' => $role,
                'office_id' => $officeId,
                'performed_by' => Auth::id(),
                'performed_by_email' => Auth::user()->email,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log("Role: {$action} - {$role} for {$user->email}");
    }

    /**
     * Log employee activity (generic employee audit logging)
     */
    public function logEmployeeActivity(string $action, Employee $employee, array $data = []): Activity
    {
        return activity()
            ->performedOn($employee)
            ->causedBy(Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'employee_activity',
                'action' => $action,
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'employee_name' => $employee->full_name,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log("Employee: {$action} - {$employee->full_name}");
    }

    /**
     * Log employee edit with before/after values
     */
    public function logEmployeeEdit(Employee $employee, array $oldValues, array $newValues): Activity
    {
        $changedFields = array_keys(array_diff_assoc($newValues, $oldValues));

        return activity()
            ->performedOn($employee)
            ->causedBy(Auth::user())
            ->withProperties([
                'action_type' => 'employee_edit',
                'action' => 'profile_updated',
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'employee_name' => $employee->full_name,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'changed_fields' => $changedFields,
                'fields_count' => count($changedFields),
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Employee Profile Updated: {$employee->full_name} (changed: " . implode(', ', $changedFields) . ")");
    }

    /**
     * Log employee archive operation
     */
    public function logEmployeeArchive(Employee $employee, ?User $archivedBy = null, array $data = []): Activity
    {
        return activity()
            ->performedOn($employee)
            ->causedBy($archivedBy ?? Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'employee_archive',
                'action' => 'archived',
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'employee_name' => $employee->full_name,
                'archived_by' => ($archivedBy ?? Auth::user())->id,
                'archived_by_email' => ($archivedBy ?? Auth::user())->email,
                'archive_reason' => $data['reason'] ?? null,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log("Employee Archived: {$employee->full_name}" . ($data['reason'] ?? null ? " (reason: {$data['reason']})" : ""));
    }

    /**
     * Log employee restore operation
     */
    public function logEmployeeRestore(Employee $employee, ?User $restoredBy = null, array $data = []): Activity
    {
        return activity()
            ->performedOn($employee)
            ->causedBy($restoredBy ?? Auth::user())
            ->withProperties(array_merge($data, [
                'action_type' => 'employee_restore',
                'action' => 'restored',
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'employee_name' => $employee->full_name,
                'restored_by' => ($restoredBy ?? Auth::user())->id,
                'restored_by_email' => ($restoredBy ?? Auth::user())->email,
                'restore_reason' => $data['reason'] ?? null,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log("Employee Restored: {$employee->full_name} from archive");
    }

    /**
     * Log PDS panel update
     */
    public function logPDSUpdate(string $panel, Employee $employee, array $changes): Activity
    {
        $changedFields = array_keys($changes);

        return activity()
            ->performedOn($employee)
            ->causedBy(Auth::user())
            ->withProperties([
                'action_type' => 'pds_update',
                'action' => 'panel_updated',
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'employee_name' => $employee->full_name,
                'pds_panel' => $panel,
                'changes' => $changes,
                'changed_fields' => $changedFields,
                'fields_count' => count($changedFields),
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("PDS Panel {$panel} Updated: {$employee->full_name} (changed: " . implode(', ', $changedFields) . ")");
    }

    /**
     * Log education record update
     */
    public function logEducationUpdate(EmployeeEducation $education, array $oldValues, array $newValues): Activity
    {
        $changedFields = array_keys(array_diff_assoc($newValues, $oldValues));

        return activity()
            ->performedOn($education)
            ->causedBy(Auth::user())
            ->withProperties([
                'action_type' => 'education_update',
                'action' => 'education_updated',
                'education_id' => $education->id,
                'employee_id' => $education->employee_id,
                'employee_name' => $education->employee ? $education->employee->full_name : 'Unknown Employee',
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'changed_fields' => $changedFields,
                'fields_count' => count($changedFields),
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Education Record Updated: " . ($education->employee ? $education->employee->full_name : 'Unknown Employee') . " (changed: " . implode(', ', $changedFields) . ")");
    }

    /**
     * Get OPCR audit trail
     */
    public function getOPCRAuditTrail(OPCRWorkflow $workflow, ?int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Activity::where('subject_type', OPCRWorkflow::class)
            ->where('subject_id', $workflow->id)
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get MFO audit trail
     */
    public function getMFOAuditTrail(MajorFinalOutput $mfo, ?int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Activity::where('subject_type', MajorFinalOutput::class)
            ->where('subject_id', $mfo->id)
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get Success Indicator audit trail
     */
    public function getSuccessIndicatorAuditTrail(SuccessIndicator $successIndicator, ?int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Activity::where('subject_type', SuccessIndicator::class)
            ->where('subject_id', $successIndicator->id)
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get user activity history
     */
    public function getUserActivityHistory(User $user, ?\DateTime $startDate = null, ?\DateTime $endDate = null, ?int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Activity::where('causer_id', $user->id)
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get audit statistics for dashboard view
     */
    public function getDashboardAuditStatistics(array $filters = []): array
    {
        $query = Activity::with(['causer']);

        // Apply filters if provided
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['office_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereJsonContains('properties->office_id', $filters['office_id'])
                  ->orWhereHas('causer', function ($userQuery) use ($filters) {
                      $userQuery->whereHas('officeAssignments', function ($officeQuery) use ($filters) {
                          $officeQuery->where('office_id', $filters['office_id']);
                      });
                  });
            });
        }

        // Total activities
        $totalActivities = $query->count();

        // Today's activities
        $todayActivities = $query->whereDate('created_at', today())->count();

        // This week's activities
        $thisWeekActivities = $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();

        // This month's activities
        $thisMonthActivities = $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Unique users
        $uniqueUsers = $query->whereNotNull('causer_id')
            ->distinct('causer_id')
            ->count();

        // Top action types (for the chart section)
        $topActionTypes = Activity::selectRaw("JSON_EXTRACT(properties, '$.action_type') as action_type, COUNT(*) as count")
            ->whereRaw("JSON_EXTRACT(properties, '$.action_type') IS NOT NULL")
            ->groupBy('action_type')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $item->action_type = json_decode($item->action_type);
                return $item;
            });

        return [
            'total_activities' => $totalActivities,
            'today_activities' => $todayActivities,
            'this_week_activities' => $thisWeekActivities,
            'this_month_activities' => $thisMonthActivities,
            'unique_users' => $uniqueUsers,
            'top_action_types' => $topActionTypes
        ];
    }

    /**
     * Get OPCR system audit statistics
     */
    public function getAuditStatistics(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = Activity::where(function ($q) {
            $q->where('subject_type', OPCRWorkflow::class)
              ->orWhere('subject_type', MajorFinalOutput::class)
              ->orWhere('subject_type', SuccessIndicator::class)
              ->orWhere('subject_type', Office::class)
              ->orWhere('properties->opcr_system', true);
        });

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalActivities = $query->count();

        $activitiesByType = $query->get()
            ->groupBy('properties.action_type')
            ->map(fn($group) => $group->count())
            ->toArray();

        $activitiesByUser = $query->with('causer')
            ->get()
            ->where('causer')
            ->groupBy('causer.email')
            ->map(fn($group) => $group->count())
            ->take(10)
            ->toArray();

        $activitiesByDate = $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->take(30)
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return [
            'total_activities' => $totalActivities,
            'activities_by_type' => $activitiesByType,
            'top_users' => $activitiesByUser,
            'daily_activities' => $activitiesByDate,
            'period' => [
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate?->toDateString(),
            ],
        ];
    }

    /**
     * Export audit trail to CSV
     */
    public function exportAuditTrail(array $filters = []): string
    {
        $query = Activity::with(['causer', 'subject']);

        // Apply filters
        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (!empty($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (!empty($filters['action_type'])) {
            $query->where('properties->action_type', $filters['action_type']);
        }

        $activities = $query->orderBy('created_at', 'desc')->get();

        $filename = 'audit_trail_' . now()->format('Y_m_d_H_i_s') . '.csv';
        $path = storage_path('app/exports/' . $filename);

        // Ensure directory exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $handle = fopen($path, 'w');

        // CSV headers
        fputcsv($handle, [
            'Timestamp',
            'User',
            'Action',
            'Subject Type',
            'Subject ID',
            'Description',
            'IP Address',
            'User Agent',
        ]);

        foreach ($activities as $activity) {
            fputcsv($handle, [
                $activity->created_at->toDateTimeString(),
                $activity->causer?->email ?? 'System',
                $activity->properties['action'] ?? $activity->description,
                $activity->subject_type,
                $activity->subject_id,
                $activity->description,
                $activity->properties['user_ip'] ?? 'N/A',
                $activity->properties['user_agent'] ?? 'N/A',
            ]);
        }

        fclose($handle);

        return $path;
    }

    /**
     * Clean up old audit logs
     */
    public function cleanupOldLogs(int $daysToKeep = 365): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        $deletedCount = Activity::where('created_at', '<', $cutoffDate)
            ->where(function ($query) {
                $query->where('subject_type', OPCRWorkflow::class)
                      ->orWhere('subject_type', MajorFinalOutput::class)
                      ->orWhere('subject_type', SuccessIndicator::class)
                      ->orWhere('properties->opcr_system', true);
            })
            ->delete();

        // Log the cleanup activity
        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'action_type' => 'system_maintenance',
                'action' => 'audit_cleanup',
                'cutoff_date' => $cutoffDate->toDateString(),
                'deleted_count' => $deletedCount,
                'performed_by' => Auth::id(),
            ])
            ->log("Audit: Cleaned up {$deletedCount} old audit logs");

        return $deletedCount;
    }

    /**
     * Search audit trail
     */
    public function searchAuditTrail(string $searchTerm, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Activity::where(function ($q) use ($searchTerm) {
            $q->where('description', 'like', "%{$searchTerm}%")
              ->orWhere('properties->action', 'like', "%{$searchTerm}%")
              ->orWhere('properties->action_type', 'like', "%{$searchTerm}%");
        })
        ->with(['causer', 'subject']);

        // Apply additional filters
        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (!empty($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get();
    }

    /**
     * Get security audit events
     */
    public function getSecurityAuditEvents(?\DateTime $startDate = null, ?\DateTime $endDate = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Activity::whereIn('properties->action_type', [
            'authentication',
            'role_change',
            'state_transition',
            'file_activity',
        ])
        ->with(['causer', 'subject']);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get compliance report
     */
    public function getComplianceReport(\DateTime $startDate, \DateTime $endDate): array
    {
        $workflows = OPCRWorkflow::whereBetween('created_at', [$startDate, $endDate])
            ->with(['office'])
            ->get();

        $totalWorkflows = $workflows->count();
        $completedWorkflows = $workflows->where('workflow_state', 'final_approval')->count();
        $completionRate = $totalWorkflows > 0 ? ($completedWorkflows / $totalWorkflows) * 100 : 0;

        $averageCompletionTime = $workflows->where('workflow_state', 'final_approval')
            ->map(function ($workflow) {
                return $workflow->created_at->diffInDays($workflow->approved_at);
            })
            ->avg();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'total_workflows' => $totalWorkflows,
            'completed_workflows' => $completedWorkflows,
            'completion_rate' => round($completionRate, 2),
            'average_completion_days' => round($averageCompletionTime ?? 0, 2),
            'workflows_by_office' => $workflows->groupBy('office.name')
                ->map(fn($group) => [
                    'total' => $group->count(),
                    'completed' => $group->where('workflow_state', 'final_approval')->count(),
                    'completion_rate' => $group->count() > 0
                        ? round(($group->where('workflow_state', 'final_approval')->count() / $group->count()) * 100, 2)
                        : 0,
                ])
                ->toArray(),
        ];
    }

    /**
     * Get filtered audit logs with pagination
     */
    public function getFilteredAuditLogs(array $filters, bool $paginate = false)
    {
        $query = Activity::with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        if (!empty($filters['user_id'])) {
            $query->where('causer_id', $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('description', 'like', '%' . $filters['action'] . '%');
        }

        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', 'like', '%' . $filters['subject_type'] . '%');
        }

        if (!empty($filters['action_type'])) {
            $query->where('properties->action_type', $filters['action_type']);
        }

        if (!empty($filters['office_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereJsonContains('properties->office_id', $filters['office_id'])
                  ->orWhereHas('causer', function ($userQuery) use ($filters) {
                      $userQuery->whereHas('officeAssignments', function ($officeQuery) use ($filters) {
                          $officeQuery->where('office_id', $filters['office_id']);
                      });
                  });
            });
        }

        $results = $paginate ? $query->paginate($filters['per_page'] ?? 25) : $query->get();

        // Normalize action_type for legacy entries
        $collection = $paginate ? $results->getCollection() : $results;
        $collection->transform(function ($activity) {
            if (!isset($activity->properties['action_type'])) {
                // Set default action_type based on existing properties or description
                $properties = $activity->properties;
                $properties['action_type'] = $this->inferActionType($activity);
                $activity->properties = $properties;
            }
            return $activity;
        });

        if ($paginate) {
            $results->setCollection($collection);
        } else {
            $results = $collection;
        }

        return $results;
    }

    /**
     * Infer action type from activity properties and description
     */
    private function inferActionType(Activity $activity): string
    {
        // Check if it's an OPCR-related activity
        if (str_contains($activity->description, 'OPCR') ||
            str_contains($activity->subject_type, 'OPCR')) {
            return 'opcr_workflow';
        }

        // Check if it's an MFO activity
        if (str_contains($activity->description, 'MFO') ||
            str_contains($activity->subject_type, 'MajorFinalOutput')) {
            return 'mfo';
        }

        // Check if it's a Success Indicator activity
        if (str_contains($activity->description, 'Success Indicator') ||
            str_contains($activity->subject_type, 'SuccessIndicator')) {
            return 'success_indicator';
        }

        // Check if it's an Office activity
        if (str_contains($activity->description, 'Office') ||
            str_contains($activity->subject_type, 'Office')) {
            return 'office';
        }

        // Check if it's a User activity
        if (str_contains($activity->description, 'User') ||
            str_contains($activity->subject_type, 'User') ||
            str_contains($activity->description, 'Employee') ||
            str_contains($activity->subject_type, 'Employee')) {
            return 'user';
        }

        // Check for authentication activities
        if (str_contains($activity->description, 'login') ||
            str_contains($activity->description, 'logout') ||
            str_contains($activity->description, 'authentication')) {
            return 'authentication';
        }

        // Check for file activities
        if (str_contains($activity->description, 'file') ||
            str_contains($activity->description, 'upload') ||
            str_contains($activity->description, 'download') ||
            str_contains($activity->description, 'export')) {
            return 'file_activity';
        }

        // Default to system if no specific type can be inferred
        return 'system';
    }

    /**
     * Get list of offices for filtering
     */
    public function getOfficeList()
    {
        return Office::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Get list of users for filtering
     */
    public function getUserList()
    {
        return User::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Get available actions for filtering
     */
    public function getAvailableActions()
    {
        return Activity::distinct()
            ->select('description')
            ->whereNotNull('description')
            ->orderBy('description')
            ->pluck('description')
            ->toArray();
    }

    /**
     * Get detailed audit log information
     */
    public function getAuditLogDetails(string $id)
{
    $auditLog = Activity::with(['causer', 'subject'])
        ->where('id', $id)
        ->first();

    // Normalize action_type for legacy entries
    if ($auditLog && !isset($auditLog->properties['action_type'])) {
        $properties = $auditLog->properties;
        $properties['action_type'] = $this->inferActionType($auditLog);
        $auditLog->properties = $properties;
    }

    return $auditLog;
}

    /**
     * Get related audit logs based on subject or user
     */
    public function getRelatedAuditLogs(Activity $auditLog)
    {
        return Activity::with(['causer', 'subject'])
            ->where(function ($query) use ($auditLog) {
                $query->where('subject_type', $auditLog->subject_type)
                      ->where('subject_id', $auditLog->subject_id)
                      ->where('id', '!=', $auditLog->id);
            })
            ->orWhere(function ($query) use ($auditLog) {
                if ($auditLog->causer_id) {
                    $query->where('causer_id', $auditLog->causer_id)
                          ->where('id', '!=', $auditLog->id)
                          ->where('created_at', '>=', $auditLog->created_at->subDay())
                          ->where('created_at', '<=', $auditLog->created_at->addDay());
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Export audit logs to various formats
     */
    public function exportAuditLogs(array $options): array
    {
        $format = $this->normalizeFormat($options['format'] ?? 'csv');

        $filters = [
            'date_from' => $options['date_from'] ?? null,
            'date_to' => $options['date_to'] ?? null,
            'user_id' => $options['user_id'] ?? null,
            'action' => $options['action'] ?? null,
            'subject_type' => $options['subject_type'] ?? null,
            'office_id' => $options['office_id'] ?? null,
            'action_type' => $options['action_type'] ?? null,
            'per_page' => 10000,
        ];

        $includeOldValues = (bool) ($options['include_old_values'] ?? false);
        $includeNewValues = (bool) ($options['include_new_values'] ?? false);

        /** @var Collection $auditLogs */
        $auditLogs = $this->getFilteredAuditLogs($filters, false);

        $filename = 'audit-trail-' . now()->format('Y-m-d-H-i-s');

        return match ($format) {
            'xlsx' => $this->exportToExcel($auditLogs, $filename, $includeOldValues, $includeNewValues, $filters),
            'pdf' => $this->exportToPDF($auditLogs, $filename, $includeOldValues, $includeNewValues, $filters),
            default => $this->exportToCSV($auditLogs, $filename, $includeOldValues, $includeNewValues, $filters),
        };
    }

    private function normalizeFormat(string $format): string
    {
        return match (strtolower($format)) {
            'xlsx', 'excel', 'xls' => 'xlsx',
            'pdf' => 'pdf',
            default => 'csv',
        };
    }

    /**
     * Export to CSV format
     */
    private function exportToCSV(Collection $auditLogs, string $filename, bool $includeOldValues, bool $includeNewValues, array $filters): array
    {
        $directory = $this->ensureExportDirectory();
        $filePath = $directory . DIRECTORY_SEPARATOR . "{$filename}.csv";

        $handle = fopen($filePath, 'w');
        // UTF-8 BOM for Excel compatibility with Filipino characters
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($this->buildCsvMetadataRows($auditLogs, $filters) as $row) {
            fputcsv($handle, $row);
        }

        $headers = [
            'Timestamp',
            'User',
            'Action',
            'Action Category',
            'Subject Type',
            'Subject ID',
            'Description',
            'IP Address',
            'User Agent',
        ];

        if ($includeOldValues) {
            $headers[] = 'Old Values';
        }

        if ($includeNewValues) {
            $headers[] = 'New Values';
        }

        fputcsv($handle, $headers);

        foreach ($auditLogs as $log) {
            $row = [
                optional($log->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                optional($log->causer)->name ?? 'System',
                $log->properties['action'] ?? $log->description,
                Str::headline($log->properties['action_type'] ?? 'System'),
                class_basename($log->subject_type) ?: '-',
                $log->subject_id ?? '-',
                $log->description,
                $log->properties['user_ip'] ?? 'N/A',
                $log->properties['user_agent'] ?? 'N/A',
            ];

            if ($includeOldValues) {
                $row[] = $this->formatValuesList($log->properties['old_values'] ?? []);
            }

            if ($includeNewValues) {
                $row[] = $this->formatValuesList($log->properties['new_values'] ?? []);
            }

            fputcsv($handle, $row);
        }

        fclose($handle);

        return [
            'format' => 'csv',
            'path' => $filePath,
            'filename' => "{$filename}.csv",
            'download_name' => "{$filename}.csv",
            'mime' => 'text/csv',
            'headers' => ['Content-Type' => 'text/csv; charset=UTF-8'],
        ];
    }

    /**
     * Export to Excel format
     */
    private function exportToExcel(Collection $auditLogs, string $filename, bool $includeOldValues, bool $includeNewValues, array $filters): array
    {
        Storage::disk('exports')->makeDirectory('audit-trail');
        $relativePath = "audit-trail/{$filename}.xlsx";

        Excel::store(
            new AuditTrailExport($auditLogs, $includeOldValues, $includeNewValues, $this->cleanFilterContext($filters)),
            $relativePath,
            'exports'
        );

        return [
            'format' => 'xlsx',
            'path' => Storage::disk('exports')->path($relativePath),
            'filename' => "{$filename}.xlsx",
            'download_name' => "{$filename}.xlsx",
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'headers' => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ];
    }

    /**
     * Export to PDF format
     */
    private function exportToPDF(Collection $auditLogs, string $filename, bool $includeOldValues, bool $includeNewValues, array $filters): array
    {
        $directory = $this->ensureExportDirectory();
        $filePath = $directory . DIRECTORY_SEPARATOR . "{$filename}.pdf";

        $pdf = Pdf::loadView('admin.audit-trail.export-pdf', [
            'logs' => $auditLogs,
            'includeOldValues' => $includeOldValues,
            'includeNewValues' => $includeNewValues,
            'generatedAt' => now(),
            'filters' => array_filter($filters),
        ])->setPaper('a4', 'landscape');

        file_put_contents($filePath, $pdf->output());

        return [
            'format' => 'pdf',
            'path' => $filePath,
            'filename' => "{$filename}.pdf",
            'download_name' => "{$filename}.pdf",
            'mime' => 'application/pdf',
            'headers' => ['Content-Type' => 'application/pdf'],
        ];
    }

    private function ensureExportDirectory(): string
    {
        $directory = storage_path('app/exports/audit-trail');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory;
    }

    private function buildCsvMetadataRows(Collection $auditLogs, array $filters): array
    {
        $rows = [
            ['E-Lingkod Dasol HRIS - Audit Trail Export'],
            ['Generated At', now()->timezone(config('app.timezone'))->format('F d, Y g:i A')],
            ['Total Records', $auditLogs->count()],
        ];

        $summary = $this->summarizeFilters($filters);

        if (!empty($summary)) {
            $rows[] = ['Filters Applied', ''];
            foreach ($summary as $label => $value) {
                $rows[] = [$label, $value];
            }
        }

        $rows[] = []; // spacer before headings

        return $rows;
    }

    private function summarizeFilters(array $filters): array
    {
        $filters = $this->cleanFilterContext($filters);
        $summary = [];

        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $from = $filters['date_from'] ? date('M d, Y', strtotime($filters['date_from'])) : 'Start';
            $to = $filters['date_to'] ? date('M d, Y', strtotime($filters['date_to'])) : 'Today';
            $summary['Date Range'] = "{$from} - {$to}";
        }

        if (!empty($filters['action_type'])) {
            $summary['Action Category'] = Str::headline($filters['action_type']);
        }

        if (!empty($filters['action'])) {
            $summary['Action Contains'] = $filters['action'];
        }

        if (!empty($filters['user_id'])) {
            $user = User::find($filters['user_id']);
            $summary['User'] = $user ? $user->name . " (#{$user->id})" : 'User ID ' . $filters['user_id'];
        }

        if (!empty($filters['office_id'])) {
            $office = Office::find($filters['office_id']);
            $summary['Office'] = $office?->name ?? 'Office ID ' . $filters['office_id'];
        }

        if (!empty($filters['subject_type'])) {
            $summary['Subject Type'] = class_basename($filters['subject_type']);
        }

        return $summary;
    }

    private function cleanFilterContext(array $filters): array
    {
        return collect($filters)
            ->except(['per_page'])
            ->filter(fn ($value) => filled($value))
            ->all();
    }

    private function formatValuesList($values, string $separator = '; '): string
    {
        if (empty($values)) {
            return '';
        }

        $flat = $this->flattenValueArray((array) $values);

        return collect($flat)
            ->map(fn ($value, $key) => sprintf('%s: %s', Str::headline($key), $this->stringifyValue($value)))
            ->implode($separator);
    }

    private function flattenValueArray(array $values, string $prefix = ''): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            $fullKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result += $this->flattenValueArray($value, $fullKey);
            } else {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }

    private function stringifyValue($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }
}
