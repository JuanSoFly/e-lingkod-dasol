<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'office_id',
        'role',
        'assigned_date',
        'ended_date',
        'is_active',
        'remarks',
        'assigned_by',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'ended_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Assignment roles
     */
    const ROLE_DEPARTMENT_HEAD = 'Department Head';
    const ROLE_ASSESSOR = 'Assessor';
    const ROLE_FINAL_APPROVER = 'Final Approver';
    const ROLE_STAFF = 'Staff';
    const ROLE_SUPERVISOR = 'Supervisor';
    const ROLE_MEMBER = 'Member';

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the office
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get the user who made the assignment
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope to get only active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get assignments by role
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get assignments for specific office
     */
    public function scopeForOffice($query, $officeId)
    {
        return $query->where('office_id', $officeId);
    }

    /**
     * Scope to get assignments for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get current assignments (active and not ended)
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('ended_date')
                          ->orWhere('ended_date', '>=', now());
                    });
    }

    /**
     * Check if assignment is currently active
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->ended_date && $this->ended_date < now()) {
            return false;
        }

        return true;
    }

    /**
     * Check if user has department head role
     */
    public function isDepartmentHead(): bool
    {
        return $this->role === self::ROLE_DEPARTMENT_HEAD && $this->isCurrentlyActive();
    }

    /**
     * Check if user has assessor role
     */
    public function isAssessor(): bool
    {
        return $this->role === self::ROLE_ASSESSOR && $this->isCurrentlyActive();
    }

    /**
     * Check if user has final approver role
     */
    public function isFinalApprover(): bool
    {
        return $this->role === self::ROLE_FINAL_APPROVER && $this->isCurrentlyActive();
    }

    /**
     * Check if user has supervisory role
     */
    public function isSupervisor(): bool
    {
        return in_array($this->role, [
            self::ROLE_DEPARTMENT_HEAD,
            self::ROLE_SUPERVISOR,
        ]) && $this->isCurrentlyActive();
    }

    /**
     * Get role permissions
     */
    public function getRolePermissionsAttribute(): array
    {
        return match ($this->role) {
            self::ROLE_DEPARTMENT_HEAD => [
                'opcr.create',
                'opcr.edit',
                'opcr.commit',
                'opcr.submit',
                'opcr.view_own',
                'opcr.export_own',
            ],
            self::ROLE_ASSESSOR => [
                'opcr.view_assigned',
                'opcr.assess',
                'opcr.return',
                'opcr.export_assigned',
            ],
            self::ROLE_FINAL_APPROVER => [
                'opcr.view_assigned',
                'opcr.approve',
                'opcr.return',
                'opcr.export_assigned',
                'opcr.analytics',
            ],
            self::ROLE_SUPERVISOR => [
                'opcr.view_team',
                'opcr.export_team',
            ],
            self::ROLE_STAFF, self::ROLE_MEMBER => [
                'opcr.view_own',
                'opcr.export_own',
            ],
            default => [],
        };
    }

    /**
     * Get assignment duration in days
     */
    public function getDurationInDaysAttribute(): ?int
    {
        $startDate = $this->assigned_date;
        $endDate = $this->ended_date ?? now();

        if (!$startDate) {
            return null;
        }

        return $startDate->diffInDays($endDate);
    }

    /**
     * Deactivate the assignment
     */
    public function deactivate(?string $reason = null): bool
    {
        return $this->update([
            'is_active' => false,
            'ended_date' => now(),
            'remarks' => $reason ?? $this->remarks,
        ]);
    }

    /**
     * Reactivate the assignment
     */
    public function reactivate(): bool
    {
        return $this->update([
            'is_active' => true,
            'ended_date' => null,
        ]);
    }

    /**
     * Update role
     */
    public function updateRole(string $newRole, ?string $reason = null): bool
    {
        return $this->update([
            'role' => $newRole,
            'remarks' => $reason ?? $this->remarks,
        ]);
    }

    /**
     * Get assignment history
     */
    public function getHistoryAttribute(): array
    {
        return [
            'created_at' => $this->created_at,
            'assigned_date' => $this->assigned_date,
            'ended_date' => $this->ended_date,
            'duration_in_days' => $this->duration_in_days,
            'is_active' => $this->is_active,
            'is_currently_active' => $this->isCurrentlyActive(),
            'role' => $this->role,
            'office' => [
                'id' => $this->office->id,
                'name' => $this->office->name,
                'code' => $this->office->code,
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'assigned_by' => $this->assignedBy ? [
                'id' => $this->assignedBy->id,
                'name' => $this->assignedBy->name,
            ] : null,
        ];
    }

    /**
     * Get all available roles
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_DEPARTMENT_HEAD => 'Department Head',
            self::ROLE_ASSESSOR => 'Assessor (PMT)',
            self::ROLE_FINAL_APPROVER => 'Final Approver (Mayor)',
            self::ROLE_SUPERVISOR => 'Supervisor',
            self::ROLE_STAFF => 'Staff',
            self::ROLE_MEMBER => 'Member',
        ];
    }

    /**
     * Get OPCR-specific roles
     */
    public static function getOPCRRoles(): array
    {
        return [
            self::ROLE_DEPARTMENT_HEAD => 'Department Head',
            self::ROLE_ASSESSOR => 'Assessor (PMT)',
            self::ROLE_FINAL_APPROVER => 'Final Approver (Mayor)',
        ];
    }

    /**
     * Create assignment with validation
     */
    public static function createAssignment(array $data): self
    {
        // Check if user already has active assignment for this office
        $existingAssignment = self::where('user_id', $data['user_id'])
            ->where('office_id', $data['office_id'])
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->first();

        if ($existingAssignment) {
            throw new \InvalidArgumentException('User already has an active assignment for this office');
        }

        return self::create(array_merge($data, [
            'assigned_date' => $data['assigned_date'] ?? now()->toDateString(),
            'is_active' => true,
        ]));
    }

    /**
     * Get validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'employee_id' => 'nullable|exists:employees,id',
            'office_id' => 'required|exists:offices,id',
            'role' => 'required|string|max:50',
            'assigned_date' => 'required|date|before_or_equal:today',
            'ended_date' => 'nullable|date|after:assigned_date',
            'remarks' => 'nullable|string',
        ];
    }

    /**
     * Get custom validation messages
     */
    public static function getValidationMessages(): array
    {
        return [
            'user_id.required' => 'The user field is required.',
            'user_id.exists' => 'The selected user is invalid.',
            'office_id.required' => 'The office field is required.',
            'office_id.exists' => 'The selected office is invalid.',
            'role.required' => 'The role field is required.',
            'assigned_date.required' => 'The assigned date field is required.',
            'assigned_date.before_or_equal' => 'The assigned date must be today or a previous date.',
            'ended_date.after' => 'The ended date must be after the assigned date.',
        ];
    }

    /**
     * Get user's active assignments across all offices
     */
    public static function getUserActiveAssignments($userId): \Illuminate\Database\Eloquent\Collection
    {
        return self::with(['office'])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->get();
    }

    /**
     * Get office's active assignments by role
     */
    public static function getOfficeActiveAssignmentsByRole(int $officeId, string $role): \Illuminate\Database\Eloquent\Collection
    {
        return self::with(['user', 'employee'])
            ->where('office_id', $officeId)
            ->where('role', $role)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->get();
    }

    /**
     * Check if user can perform action on workflow
     */
    public function canPerformWorkflowAction(OPCRWorkflow $workflow, string $action): bool
    {
        // Check if assignment is for the workflow's office
        if ($this->office_id !== $workflow->office_id) {
            return false;
        }

        // Check if assignment is currently active
        if (!$this->isCurrentlyActive()) {
            return false;
        }

        return match ($action) {
            'create', 'edit', 'commit', 'submit' => $this->isDepartmentHead(),
            'assess' => $this->isAssessor(),
            'approve' => $this->isFinalApprover(),
            'view' => true, // All assigned users can view
            default => false,
        };
    }

    /**
     * Get assignment statistics
     */
    public function getStatisticsAttribute(): array
    {
        return [
            'duration_in_days' => $this->duration_in_days,
            'is_currently_active' => $this->isCurrentlyActive(),
            'role_permissions' => $this->role_permissions,
            'can_perform_opcr_actions' => [
                'create' => $this->isDepartmentHead(),
                'edit' => $this->isDepartmentHead(),
                'commit' => $this->isDepartmentHead(),
                'submit' => $this->isDepartmentHead(),
                'assess' => $this->isAssessor(),
                'approve' => $this->isFinalApprover(),
                'view' => true,
            ],
        ];
    }
}