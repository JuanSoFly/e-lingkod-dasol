<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class TrainingProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_name',
        'program_code',
        'provider',
        'provider_organization',
        'training_type',
        'category',
        'delivery_method',
        'duration_hours',
        'credit_hours',
        'duration_days',
        'description',
        'learning_objectives',
        'target_participants',
        'prerequisites',
        'curriculum_outline',
        'cost_per_participant',
        'max_participants',
        'venue',
        'trainer_name',
        'trainer_credentials',
        'status',
        'accreditation_number',
        'accrediting_body',
        'accreditation_date',
        'accreditation_expiry',
        'csc_recognized',
        'dap_accredited',
        'counts_towards_promotion',
        'promotion_points',
        'enrollment_start',
        'enrollment_end',
        'training_start',
        'training_end',
        'has_certification',
        'certificate_template',
        'passing_score',
        'evaluation_criteria',
        'created_by',
        'approved_by',
        'approved_at',
        'additional_data',
    ];

    protected $casts = [
        'accreditation_date' => 'date',
        'accreditation_expiry' => 'date',
        'enrollment_start' => 'date',
        'enrollment_end' => 'date',
        'training_start' => 'date',
        'training_end' => 'date',
        'approved_at' => 'datetime',
        'csc_recognized' => 'boolean',
        'dap_accredited' => 'boolean',
        'counts_towards_promotion' => 'boolean',
        'has_certification' => 'boolean',
        'cost_per_participant' => 'decimal:2',
        'credit_hours' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'additional_data' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function trainingRequirements(): HasMany
    {
        return $this->hasMany(TrainingRequirement::class);
    }

    public function employeeTrainings(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    public function documentLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'source');
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    public function scopeByProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    public function scopeByTrainingType(Builder $query, string $type): Builder
    {
        return $query->where('training_type', $type);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeCscRecognized(Builder $query): Builder
    {
        return $query->where('csc_recognized', true);
    }

    public function scopeDapAccredited(Builder $query): Builder
    {
        return $query->where('dap_accredited', true);
    }

    public function scopeOpenForEnrollment(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->where('status', 'Active')
            ->where(function ($q) use ($today) {
                $q->whereNull('enrollment_start')
                  ->orWhere('enrollment_start', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('enrollment_end')
                  ->orWhere('enrollment_end', '>=', $today);
            });
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('training_start', '>', now()->toDateString())
            ->orderBy('training_start');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->where('training_start', '<=', $today)
            ->where('training_end', '>=', $today);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('training_end', '<', now()->toDateString());
    }

    /**
     * Business Logic Methods
     */
    
    /**
     * Check if enrollment is currently open
     */
    public function isEnrollmentOpen(): bool
    {
        if ($this->status !== 'Active') {
            return false;
        }

        $today = now()->toDateString();
        
        if ($this->enrollment_start && $this->enrollment_start > $today) {
            return false;
        }
        
        if ($this->enrollment_end && $this->enrollment_end < $today) {
            return false;
        }

        return true;
    }

    /**
     * Check if training is currently in progress
     */
    public function isInProgress(): bool
    {
        $today = now()->toDateString();
        return $this->training_start <= $today && $this->training_end >= $today;
    }

    /**
     * Check if training is completed
     */
    public function isCompleted(): bool
    {
        return $this->training_end < now()->toDateString();
    }

    /**
     * Check if training is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->training_start > now()->toDateString();
    }

    /**
     * Get enrollment capacity information
     */
    public function getEnrollmentCapacity(): array
    {
        $enrolledCount = $this->employeeTrainings()
            ->whereIn('enrollment_status', ['Enrolled', 'Approved'])
            ->count();

        return [
            'max_participants' => $this->max_participants,
            'enrolled_count' => $enrolledCount,
            'available_slots' => $this->max_participants ? $this->max_participants - $enrolledCount : null,
            'is_full' => $this->max_participants ? $enrolledCount >= $this->max_participants : false,
            'utilization_rate' => $this->max_participants ? round(($enrolledCount / $this->max_participants) * 100, 2) : 0,
        ];
    }

    /**
     * Get training statistics
     */
    public function getTrainingStatistics(): array
    {
        $trainings = $this->employeeTrainings();

        return [
            'total_enrolled' => $trainings->count(),
            'completed' => $trainings->where('completion_status', 'Completed')->count(),
            'in_progress' => $trainings->where('completion_status', 'In Progress')->count(),
            'failed' => $trainings->where('completion_status', 'Failed')->count(),
            'withdrawn' => $trainings->where('completion_status', 'Withdrawn')->count(),
            'completion_rate' => $this->getCompletionRate(),
            'average_grade' => $this->getAverageGrade(),
            'passing_rate' => $this->getPassingRate(),
        ];
    }

    /**
     * Calculate completion rate
     */
    public function getCompletionRate(): float
    {
        $total = $this->employeeTrainings()->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $this->employeeTrainings()
            ->where('completion_status', 'Completed')
            ->count();

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Calculate average grade
     */
    public function getAverageGrade(): ?float
    {
        $average = $this->employeeTrainings()
            ->whereNotNull('final_score')
            ->avg('final_score');

        return $average ? round($average, 2) : null;
    }

    /**
     * Calculate passing rate
     */
    public function getPassingRate(): float
    {
        $total = $this->employeeTrainings()
            ->whereNotNull('passing_status')
            ->count();

        if ($total === 0) {
            return 0;
        }

        $passed = $this->employeeTrainings()
            ->where('passing_status', 'Passed')
            ->count();

        return round(($passed / $total) * 100, 2);
    }

    /**
     * Check if employee can enroll in this training
     */
    public function canEmployeeEnroll(Employee $employee): array
    {
        $errors = [];

        // Check if enrollment is open
        if (!$this->isEnrollmentOpen()) {
            $errors[] = 'Enrollment is not currently open for this training program.';
        }

        // Check capacity
        $capacity = $this->getEnrollmentCapacity();
        if ($capacity['is_full']) {
            $errors[] = 'This training program has reached maximum capacity.';
        }

        // Check if employee is already enrolled
        $existingEnrollment = $this->employeeTrainings()
            ->where('employee_id', $employee->id)
            ->whereIn('enrollment_status', ['Enrolled', 'Approved', 'Waitlisted'])
            ->exists();

        if ($existingEnrollment) {
            $errors[] = 'Employee is already enrolled or has a pending enrollment for this training.';
        }

        // Check prerequisites (if specified)
        if ($this->prerequisites) {
            // This would need to be implemented based on specific prerequisite rules
            // For now, we'll just note that prerequisites exist
            $errors[] = 'Please verify that all prerequisites are met before enrolling.';
        }

        return [
            'can_enroll' => empty($errors),
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * Get employees who have mandatory requirements for this training
     */
    public function getEmployeesWithMandatoryRequirement()
    {
        $requirements = $this->trainingRequirements()
            ->where('requirement_type', 'Mandatory')
            ->where('status', 'Active')
            ->get();

        $employees = collect();

        foreach ($requirements as $requirement) {
            $positionEmployees = Employee::where('position', $requirement->position_title)
                ->when($requirement->department, function ($query, $department) {
                    return $query->where('department', $department);
                })
                ->get();

            $employees = $employees->merge($positionEmployees);
        }

        return $employees->unique('id');
    }

    /**
     * Check if accreditation is still valid
     */
    public function isAccreditationValid(): bool
    {
        if (!$this->accreditation_expiry) {
            return true; // No expiry date means it doesn't expire
        }

        return $this->accreditation_expiry >= now()->toDateString();
    }

    /**
     * Get training duration in different formats
     */
    public function getFormattedDuration(): array
    {
        return [
            'hours' => $this->duration_hours,
            'days' => $this->duration_days,
            'credit_hours' => $this->credit_hours,
            'readable' => $this->getReadableDuration(),
        ];
    }

    /**
     * Get human-readable duration
     */
    private function getReadableDuration(): string
    {
        if ($this->duration_days) {
            $dayText = $this->duration_days === 1 ? 'day' : 'days';
            return "{$this->duration_days} {$dayText}";
        }

        if ($this->duration_hours) {
            $hourText = $this->duration_hours === 1 ? 'hour' : 'hours';
            return "{$this->duration_hours} {$hourText}";
        }

        return 'Duration not specified';
    }

    /**
     * Get training program status with context
     */
    public function getStatusWithContext(): array
    {
        $status = $this->status;
        $context = '';

        if ($status === 'Active') {
            if ($this->isCompleted()) {
                $context = 'Completed';
            } elseif ($this->isInProgress()) {
                $context = 'Currently Running';
            } elseif ($this->isUpcoming()) {
                $context = 'Upcoming';
            } elseif ($this->isEnrollmentOpen()) {
                $context = 'Open for Enrollment';
            } else {
                $context = 'Enrollment Closed';
            }
        }

        return [
            'status' => $status,
            'context' => $context,
            'is_active' => $status === 'Active',
            'enrollment_open' => $this->isEnrollmentOpen(),
        ];
    }
}