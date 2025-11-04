<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Models\Employee;
use App\Models\LeaveApprovalDelegate;

class LeaveWorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_workflow_id',
        'step_order',
        'step_type',
        'step_name',
        'approvers',
        'required_all',
        'escalation_hours',
        'escalation_to',
    ];

    protected $casts = [
        'approvers' => 'array',
        'escalation_to' => 'array',
        'required_all' => 'boolean',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(LeaveWorkflow::class, 'leave_workflow_id');
    }

    public function applicationSteps(): HasMany
    {
        return $this->hasMany(LeaveApplicationWorkflowStep::class);
    }

    /**
     * Get current approvers for this step
     */
    public function getCurrentApprovers(LeaveApplication $application): array
    {
        $approvers = $this->approvers;
        $currentApprovers = [];

        foreach ($approvers as $approver) {
            switch ($this->step_type) {
                case 'individual':
                    // Direct user ID
                    if (isset($approver['user_id'])) {
                        $user = User::find($approver['user_id']);
                        if ($user && $this->isUserAvailable($user)) {
                            $currentApprovers[] = $user;
                        }
                    }
                    break;

                case 'role_based':
                    // Role-based approver
                    if (isset($approver['role'])) {
                        $users = $this->getUsersByRole($approver['role'], $application);
                        $currentApprovers = array_merge($currentApprovers, $users);
                    }
                    break;

                case 'position_based':
                    // Position-based approver
                    if (isset($approver['position'])) {
                        $user = $this->getUserByPosition($approver['position'], $application);
                        if ($user && $this->isUserAvailable($user)) {
                            $currentApprovers[] = $user;
                        }
                    }
                    break;
            }
        }

        return array_unique($currentApprovers);
    }

    /**
     * Check if user is available for approval
     */
    private function isUserAvailable(User $user): bool
    {
        // Check if user has active delegation
        $delegate = LeaveApprovalDelegate::where('delegator_id', $user->id)
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        return !$delegate;
    }

    /**
     * Get users by role for application context
     */
    private function getUsersByRole(string $role, LeaveApplication $application): array
    {
        switch ($role) {
            case 'department_head':
                return $this->getDepartmentHead($application->employee);
            case 'hr_admin':
                return $this->getHRAdmins();
            case 'direct_supervisor':
                return $this->getDirectSupervisor($application->employee);
            default:
                return [];
        }
    }

    /**
     * Get department head for employee
     */
    private function getDepartmentHead(Employee $employee): array
    {
        // First try: Use OfficeAssignment to find department head
        $headAssignment = \App\Models\OfficeAssignment::with('employee.user')
            ->where('office_id', $employee->office_id)
            ->where('role', 'Department Head')
            ->first();

        if ($headAssignment && $headAssignment->employee && $headAssignment->employee->user) {
            return [$headAssignment->employee->user];
        }

        // Second try: Find department head using the is_department_head flag and office matching
        $head = User::whereHas('employee', function ($query) use ($employee) {
            $query->where('office_id', $employee->office_id)
                ->where('is_department_head', true);
        })->first();

        // Third fallback: try department-based matching if no head found
        if (!$head && $employee->department) {
            $head = User::whereHas('employee', function ($query) use ($employee) {
                $query->where('department', $employee->department)
                    ->where('is_department_head', true);
            })->first();
        }

        // Last fallback: try position-based matching
        if (!$head) {
            $head = User::whereHas('employee', function ($query) use ($employee) {
                $query->where(function ($q) {
                        $q->where('position', 'like', '%head%')
                            ->orWhere('position', 'like', '%chief%')
                            ->orWhere('position', 'like', '%manager%')
                            ->orWhere('position', 'like', '%supervisor%');
                    });
            })->first();
        }

        return $head ? [$head] : [];
    }

    /**
     * Get HR administrators
     */
    private function getHRAdmins(): array
    {
        return User::role('hr_admin')->get()->all();
    }

    /**
     * Get direct supervisor for employee
     */
    private function getDirectSupervisor(Employee $employee): array
    {
        // Implementation depends on your organizational structure
        $supervisor = $employee->supervisor;
        return $supervisor ? [$supervisor->user] : [];
    }

    /**
     * Get user by position for application context
     */
    private function getUserByPosition(string $position, LeaveApplication $application): ?User
    {
        switch ($position) {
            case 'department_head':
                $heads = $this->getDepartmentHead($application->employee);
                return $heads ? $heads[0] : null;
            case 'hr_manager':
                $hrAdmins = $this->getHRAdmins();
                return $hrAdmins ? $hrAdmins[0] : null;
            case 'executive':
                // Find executive level users
                return User::whereHas('employee', function ($query) {
                    $query->where('position', 'like', '%executive%')
                        ->orWhere('position', 'like', '%manager%')
                        ->orWhere('position', 'like', '%director%');
                })->first();
            default:
                return null;
        }
    }
}