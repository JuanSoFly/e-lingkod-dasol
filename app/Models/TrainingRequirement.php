<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TrainingRequirement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'position_title',
        'department',
        'salary_grade_level',
        'training_program_id',
        'requirement_type',
        'priority_level',
        'frequency',
        'deadline_months',
        'grace_period_days',
        'effective_date',
        'expiry_date',
        'exemption_criteria',
        'alternative_compliance',
        'affects_promotion',
        'affects_evaluation',
        'compliance_penalty',
        'career_level',
        'min_years_experience',
        'max_years_experience',
        'min_passing_score',
        'min_attendance_percentage',
        'requires_certification',
        'requires_pre_assessment',
        'requires_post_assessment',
        'csc_mandated',
        'dap_required',
        'legal_basis',
        'compliance_notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'additional_requirements',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'approved_at' => 'datetime',
        'affects_promotion' => 'boolean',
        'affects_evaluation' => 'boolean',
        'requires_certification' => 'boolean',
        'requires_pre_assessment' => 'boolean',
        'requires_post_assessment' => 'boolean',
        'csc_mandated' => 'boolean',
        'dap_required' => 'boolean',
        'compliance_penalty' => 'decimal:2',
        'min_passing_score' => 'decimal:2',
        'additional_requirements' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function employeeTrainings(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    public function scopeEffective(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->where(function ($q) use ($today) {
            $q->whereNull('effective_date')
              ->orWhere('effective_date', '<=', $today);
        })->where(function ($q) use ($today) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>=', $today);
        });
    }

    public function scopeByPosition(Builder $query, string $position): Builder
    {
        return $query->where('position_title', $position);
    }

    public function scopeByDepartment(Builder $query, string $department): Builder
    {
        return $query->where('department', $department);
    }

    public function scopeByRequirementType(Builder $query, string $type): Builder
    {
        return $query->where('requirement_type', $type);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority_level', $priority);
    }

    public function scopeMandatory(Builder $query): Builder
    {
        return $query->where('requirement_type', 'Mandatory');
    }

    public function scopeCscMandated(Builder $query): Builder
    {
        return $query->where('csc_mandated', true);
    }

    public function scopeAffectsPromotion(Builder $query): Builder
    {
        return $query->where('affects_promotion', true);
    }

    public function scopeByCareerLevel(Builder $query, string $level): Builder
    {
        return $query->where('career_level', $level);
    }

    /**
     * Business Logic Methods
     */

    /**
     * Check if requirement applies to a specific employee
     */
    public function appliesTo(Employee $employee): bool
    {
        // Check if requirement is active and effective
        if ($this->status !== 'Active' || !$this->isEffective()) {
            return false;
        }

        // Check position match
        if ($this->position_title !== $employee->position) {
            return false;
        }

        // Check department if specified
        if ($this->department && $this->department !== $employee->department) {
            return false;
        }

        // Check salary grade if specified
        if ($this->salary_grade_level && $this->salary_grade_level !== $employee->salary_grade) {
            return false;
        }

        // Check years of experience if specified
        $yearsOfService = $this->calculateYearsOfService($employee);
        if ($this->min_years_experience && $yearsOfService < $this->min_years_experience) {
            return false;
        }

        if ($this->max_years_experience && $yearsOfService > $this->max_years_experience) {
            return false;
        }

        // Check career level if specified and not 'All Levels'
        if ($this->career_level !== 'All Levels') {
            $employeeCareerLevel = $this->determineCareerLevel($employee);
            if ($employeeCareerLevel !== $this->career_level) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if requirement is currently effective
     */
    public function isEffective(): bool
    {
        $today = now()->toDateString();

        if ($this->effective_date && $this->effective_date > $today) {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date < $today) {
            return false;
        }

        return true;
    }

    /**
     * Calculate compliance deadline for an employee
     */
    public function getComplianceDeadline(Employee $employee): ?Carbon
    {
        if (!$this->deadline_months) {
            return null;
        }

        // Use appointment date or hire date as base
        $baseDate = $employee->appointment_date ?? $employee->date_hired;
        
        if (!$baseDate) {
            return null;
        }

        return $baseDate->addMonths($this->deadline_months);
    }

    /**
     * Check if employee is compliant with this requirement
     */
    public function isEmployeeCompliant(Employee $employee): array
    {
        if (!$this->appliesTo($employee)) {
            return [
                'is_compliant' => true,
                'reason' => 'Requirement does not apply to this employee',
                'status' => 'Not Applicable',
            ];
        }

        // Check if employee has completed the required training
        $completedTraining = $this->employeeTrainings()
            ->where('employee_id', $employee->id)
            ->where('completion_status', 'Completed')
            ->orderBy('end_date', 'desc')
            ->first();

        if (!$completedTraining) {
            return [
                'is_compliant' => false,
                'reason' => 'Required training not completed',
                'status' => 'Non-Compliant',
                'deadline' => $this->getComplianceDeadline($employee),
            ];
        }

        // Check if training completion meets the requirements
        $meetsCriteria = $this->trainingMeetsCriteria($completedTraining);
        
        if (!$meetsCriteria['meets_criteria']) {
            return [
                'is_compliant' => false,
                'reason' => $meetsCriteria['reason'],
                'status' => 'Partially Compliant',
                'completed_training' => $completedTraining,
            ];
        }

        // Check if training is still valid based on frequency
        $isStillValid = $this->isTrainingStillValid($completedTraining);
        
        if (!$isStillValid['is_valid']) {
            return [
                'is_compliant' => false,
                'reason' => $isStillValid['reason'],
                'status' => 'Expired',
                'completed_training' => $completedTraining,
                'renewal_due' => $isStillValid['renewal_due'],
            ];
        }

        return [
            'is_compliant' => true,
            'reason' => 'All requirements met',
            'status' => 'Compliant',
            'completed_training' => $completedTraining,
        ];
    }

    /**
     * Check if completed training meets the requirement criteria
     */
    private function trainingMeetsCriteria(EmployeeTraining $training): array
    {
        // Check minimum passing score
        if ($this->min_passing_score && $training->final_score < $this->min_passing_score) {
            return [
                'meets_criteria' => false,
                'reason' => "Training score ({$training->final_score}) below minimum required ({$this->min_passing_score})",
            ];
        }

        // Check minimum attendance percentage
        if ($training->attendance_percentage < $this->min_attendance_percentage) {
            return [
                'meets_criteria' => false,
                'reason' => "Attendance ({$training->attendance_percentage}%) below minimum required ({$this->min_attendance_percentage}%)",
            ];
        }

        // Check certification requirement
        if ($this->requires_certification && !$training->certificate_number) {
            return [
                'meets_criteria' => false,
                'reason' => 'Certification required but not obtained',
            ];
        }

        // Check passing status
        if ($training->passing_status === 'Failed') {
            return [
                'meets_criteria' => false,
                'reason' => 'Training was not successfully passed',
            ];
        }

        return [
            'meets_criteria' => true,
            'reason' => 'All criteria met',
        ];
    }

    /**
     * Check if training is still valid based on frequency requirements
     */
    private function isTrainingStillValid(EmployeeTraining $training): array
    {
        if ($this->frequency === 'One-time') {
            return [
                'is_valid' => true,
                'reason' => 'One-time requirement fulfilled',
            ];
        }

        $completionDate = $training->end_date;
        $today = now();

        $validityPeriod = match ($this->frequency) {
            'Annual' => 12,
            'Biennial' => 24,
            'Every 3 Years' => 36,
            'Every 5 Years' => 60,
            default => null,
        };

        if (!$validityPeriod) {
            return [
                'is_valid' => true,
                'reason' => 'No specific renewal requirement',
            ];
        }

        $expiryDate = $completionDate->addMonths($validityPeriod);
        $renewalDue = $expiryDate->subDays($this->grace_period_days ?? 30);

        if ($today->greaterThan($expiryDate)) {
            return [
                'is_valid' => false,
                'reason' => 'Training has expired and needs renewal',
                'renewal_due' => $renewalDue,
            ];
        }

        if ($today->greaterThan($renewalDue)) {
            return [
                'is_valid' => true,
                'reason' => 'Training valid but renewal due soon',
                'renewal_due' => $renewalDue,
                'warning' => 'Renewal needed soon',
            ];
        }

        return [
            'is_valid' => true,
            'reason' => 'Training is current and valid',
            'renewal_due' => $renewalDue,
        ];
    }

    /**
     * Get all employees who need to comply with this requirement
     */
    public function getApplicableEmployees()
    {
        $query = Employee::query();

        // Filter by position
        $query->where('position', $this->position_title);

        // Filter by department if specified
        if ($this->department) {
            $query->where('department', $this->department);
        }

        // Filter by salary grade if specified
        if ($this->salary_grade_level) {
            $query->where('salary_grade', $this->salary_grade_level);
        }

        $employees = $query->get();

        // Apply additional filters that require calculation
        return $employees->filter(function ($employee) {
            return $this->appliesTo($employee);
        });
    }

    /**
     * Get compliance summary for this requirement
     */
    public function getComplianceSummary(): array
    {
        $applicableEmployees = $this->getApplicableEmployees();
        $totalApplicable = $applicableEmployees->count();

        if ($totalApplicable === 0) {
            return [
                'total_applicable' => 0,
                'compliant' => 0,
                'non_compliant' => 0,
                'partially_compliant' => 0,
                'expired' => 0,
                'compliance_rate' => 0,
            ];
        }

        $complianceData = $applicableEmployees->map(function ($employee) {
            return $this->isEmployeeCompliant($employee);
        });

        $compliant = $complianceData->where('status', 'Compliant')->count();
        $nonCompliant = $complianceData->where('status', 'Non-Compliant')->count();
        $partiallyCompliant = $complianceData->where('status', 'Partially Compliant')->count();
        $expired = $complianceData->where('status', 'Expired')->count();

        return [
            'total_applicable' => $totalApplicable,
            'compliant' => $compliant,
            'non_compliant' => $nonCompliant,
            'partially_compliant' => $partiallyCompliant,
            'expired' => $expired,
            'compliance_rate' => round(($compliant / $totalApplicable) * 100, 2),
        ];
    }

    /**
     * Calculate years of service for an employee
     */
    private function calculateYearsOfService(Employee $employee): int
    {
        $serviceStartDate = $employee->date_hired;
        if (!$serviceStartDate) {
            return 0;
        }

        return $serviceStartDate->diffInYears(now());
    }

    /**
     * Determine career level based on employee data
     */
    private function determineCareerLevel(Employee $employee): string
    {
        $yearsOfService = $this->calculateYearsOfService($employee);
        $position = strtolower($employee->position);

        // Simple logic to determine career level - can be made more sophisticated
        if (str_contains($position, 'manager') || str_contains($position, 'director')) {
            return 'Managerial';
        }

        if (str_contains($position, 'supervisor') || str_contains($position, 'lead')) {
            return 'Supervisory';
        }

        if ($yearsOfService >= 10) {
            return 'Senior';
        }

        if ($yearsOfService >= 3) {
            return 'Junior';
        }

        return 'Entry Level';
    }

    /**
     * Get next compliance deadline for applicable employees
     */
    public function getUpcomingDeadlines(): array
    {
        $applicableEmployees = $this->getApplicableEmployees();
        $deadlines = [];

        foreach ($applicableEmployees as $employee) {
            $compliance = $this->isEmployeeCompliant($employee);
            
            if (!$compliance['is_compliant']) {
                $deadline = $this->getComplianceDeadline($employee);
                if ($deadline) {
                    $deadlines[] = [
                        'employee' => $employee,
                        'deadline' => $deadline,
                        'status' => $compliance['status'],
                        'days_until_deadline' => now()->diffInDays($deadline, false),
                    ];
                }
            }
        }

        // Sort by deadline
        usort($deadlines, function ($a, $b) {
            return $a['deadline'] <=> $b['deadline'];
        });

        return $deadlines;
    }
}