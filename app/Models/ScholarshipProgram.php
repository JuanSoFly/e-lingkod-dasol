<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class ScholarshipProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_name',
        'program_code',
        'funding_agency',
        'implementing_agency',
        'scholarship_type',
        'degree_level',
        'field_of_study',
        'preferred_courses',
        'restricted_courses',
        'duration_years',
        'duration_months',
        'duration_weeks',
        'program_start',
        'program_end',
        'application_deadline',
        'selection_date',
        'total_budget',
        'per_scholar_budget',
        'covers_tuition',
        'covers_living_allowance',
        'covers_transportation',
        'covers_books_materials',
        'covers_research_expenses',
        'covers_conference_fees',
        'monthly_allowance',
        'book_allowance',
        'thesis_allowance',
        'travel_allowance',
        'eligibility_criteria',
        'min_years_service',
        'max_years_service',
        'min_age',
        'max_age',
        'min_performance_rating',
        'position_requirements',
        'educational_requirements',
        'health_requirements',
        'selection_criteria',
        'application_requirements',
        'requires_entrance_exam',
        'requires_interview',
        'requires_medical_exam',
        'requires_psychological_exam',
        'evaluation_process',
        'available_slots',
        'reserved_slots',
        'service_obligation_years',
        'bond_amount',
        'bond_conditions',
        'service_agreement_terms',
        'allows_early_termination',
        'early_termination_penalty',
        'exemption_conditions',
        'status',
        'program_manager',
        'contact_person',
        'contact_email',
        'contact_phone',
        'application_process',
        'application_form_url',
        'legal_basis',
        'implementing_rules',
        'daps_approved',
        'csc_approved',
        'dbm_approved',
        'approval_documents',
        'requires_progress_reports',
        'reporting_frequency',
        'requires_final_report',
        'requires_thesis_submission',
        'monitoring_requirements',
        'target_completion_rate',
        'actual_completion_rate',
        'total_scholars_graduated',
        'total_scholars_active',
        'success_indicators',
        'is_international',
        'participating_countries',
        'partner_institutions',
        'requires_visa',
        'requires_language_proficiency',
        'language_requirements',
        'created_by',
        'approved_by',
        'approved_at',
        'additional_data',
    ];

    protected $casts = [
        'program_start' => 'date',
        'program_end' => 'date',
        'application_deadline' => 'date',
        'selection_date' => 'date',
        'approved_at' => 'datetime',
        'total_budget' => 'decimal:2',
        'per_scholar_budget' => 'decimal:2',
        'monthly_allowance' => 'decimal:2',
        'book_allowance' => 'decimal:2',
        'thesis_allowance' => 'decimal:2',
        'travel_allowance' => 'decimal:2',
        'min_performance_rating' => 'decimal:2',
        'bond_amount' => 'decimal:2',
        'early_termination_penalty' => 'decimal:2',
        'target_completion_rate' => 'decimal:2',
        'actual_completion_rate' => 'decimal:2',
        'covers_tuition' => 'boolean',
        'covers_living_allowance' => 'boolean',
        'covers_transportation' => 'boolean',
        'covers_books_materials' => 'boolean',
        'covers_research_expenses' => 'boolean',
        'covers_conference_fees' => 'boolean',
        'requires_entrance_exam' => 'boolean',
        'requires_interview' => 'boolean',
        'requires_medical_exam' => 'boolean',
        'requires_psychological_exam' => 'boolean',
        'allows_early_termination' => 'boolean',
        'daps_approved' => 'boolean',
        'csc_approved' => 'boolean',
        'dbm_approved' => 'boolean',
        'requires_progress_reports' => 'boolean',
        'requires_final_report' => 'boolean',
        'requires_thesis_submission' => 'boolean',
        'is_international' => 'boolean',
        'requires_visa' => 'boolean',
        'requires_language_proficiency' => 'boolean',
        'additional_data' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function employeeScholarships(): HasMany
    {
        return $this->hasMany(EmployeeScholarship::class);
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

    public function scopeByFundingAgency(Builder $query, string $agency): Builder
    {
        return $query->where('funding_agency', $agency);
    }

    public function scopeByScholarshipType(Builder $query, string $type): Builder
    {
        return $query->where('scholarship_type', $type);
    }

    public function scopeByDegreeLevel(Builder $query, string $level): Builder
    {
        return $query->where('degree_level', $level);
    }

    public function scopeByFieldOfStudy(Builder $query, string $field): Builder
    {
        return $query->where('field_of_study', 'like', "%{$field}%");
    }

    public function scopeInternational(Builder $query): Builder
    {
        return $query->where('is_international', true);
    }

    public function scopeLocal(Builder $query): Builder
    {
        return $query->where('is_international', false);
    }

    public function scopeOpenForApplication(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->where('status', 'Active')
            ->where(function ($q) use ($today) {
                $q->whereNull('application_deadline')
                  ->orWhere('application_deadline', '>=', $today);
            });
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('program_start', '>', now()->toDateString())
            ->orderBy('program_start');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->where('program_start', '<=', $today)
            ->where('program_end', '>=', $today);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('program_end', '<', now()->toDateString());
    }

    public function scopeWithAvailableSlots(Builder $query): Builder
    {
        return $query->whereRaw('available_slots > (
            SELECT COUNT(*) FROM employee_scholarships 
            WHERE scholarship_program_id = scholarship_programs.id 
            AND approval_status IN ("Approved", "Conditionally Approved")
        )');
    }

    /**
     * Business Logic Methods
     */

    /**
     * Check if application is currently open
     */
    public function isApplicationOpen(): bool
    {
        if ($this->status !== 'Active') {
            return false;
        }

        if ($this->application_deadline && $this->application_deadline < now()->toDateString()) {
            return false;
        }

        return true;
    }

    /**
     * Check if program is currently in progress
     */
    public function isInProgress(): bool
    {
        $today = now()->toDateString();
        return $this->program_start <= $today && $this->program_end >= $today;
    }

    /**
     * Check if program is completed
     */
    public function isCompleted(): bool
    {
        return $this->program_end < now()->toDateString();
    }

    /**
     * Check if program is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->program_start > now()->toDateString();
    }

    /**
     * Get application capacity information
     */
    public function getApplicationCapacity(): array
    {
        $appliedCount = $this->employeeScholarships()->count();
        $approvedCount = $this->employeeScholarships()
            ->whereIn('approval_status', ['Approved', 'Conditionally Approved'])
            ->count();

        return [
            'available_slots' => $this->available_slots,
            'reserved_slots' => $this->reserved_slots,
            'total_applications' => $appliedCount,
            'approved_applications' => $approvedCount,
            'remaining_slots' => $this->available_slots ? $this->available_slots - $approvedCount : null,
            'is_full' => $this->available_slots ? $approvedCount >= $this->available_slots : false,
            'utilization_rate' => $this->available_slots ? round(($approvedCount / $this->available_slots) * 100, 2) : 0,
        ];
    }

    /**
     * Get scholarship statistics
     */
    public function getScholarshipStatistics(): array
    {
        $scholarships = $this->employeeScholarships();

        return [
            'total_applications' => $scholarships->count(),
            'approved' => $scholarships->where('approval_status', 'Approved')->count(),
            'conditionally_approved' => $scholarships->where('approval_status', 'Conditionally Approved')->count(),
            'rejected' => $scholarships->where('approval_status', 'Rejected')->count(),
            'under_review' => $scholarships->where('approval_status', 'Under Review')->count(),
            'active_scholars' => $scholarships->whereIn('academic_status', ['Enrolled', 'In Progress'])->count(),
            'graduated' => $scholarships->where('academic_status', 'Graduated')->count(),
            'completion_rate' => $this->getCompletionRate(),
            'dropout_rate' => $this->getDropoutRate(),
            'average_gpa' => $this->getAverageGPA(),
        ];
    }

    /**
     * Calculate completion rate
     */
    public function getCompletionRate(): float
    {
        $totalScholars = $this->employeeScholarships()
            ->whereIn('approval_status', ['Approved', 'Conditionally Approved'])
            ->count();

        if ($totalScholars === 0) {
            return 0;
        }

        $graduated = $this->employeeScholarships()
            ->where('academic_status', 'Graduated')
            ->count();

        return round(($graduated / $totalScholars) * 100, 2);
    }

    /**
     * Calculate dropout rate
     */
    public function getDropoutRate(): float
    {
        $totalScholars = $this->employeeScholarships()
            ->whereIn('approval_status', ['Approved', 'Conditionally Approved'])
            ->count();

        if ($totalScholars === 0) {
            return 0;
        }

        $dropped = $this->employeeScholarships()
            ->whereIn('academic_status', ['Dropped', 'Terminated'])
            ->count();

        return round(($dropped / $totalScholars) * 100, 2);
    }

    /**
     * Calculate average GPA
     */
    public function getAverageGPA(): ?float
    {
        $average = $this->employeeScholarships()
            ->whereNotNull('gpa')
            ->avg('gpa');

        return $average ? round($average, 2) : null;
    }

    /**
     * Check if employee can apply for this scholarship
     */
    public function canEmployeeApply(Employee $employee): array
    {
        $errors = [];
        $warnings = [];

        // Check if application is open
        if (!$this->isApplicationOpen()) {
            $errors[] = 'Application period is not currently open for this scholarship program.';
        }

        // Check capacity
        $capacity = $this->getApplicationCapacity();
        if ($capacity['is_full']) {
            $errors[] = 'This scholarship program has reached maximum capacity.';
        }

        // Check if employee already has an application
        $existingApplication = $this->employeeScholarships()
            ->where('employee_id', $employee->id)
            ->whereNotIn('approval_status', ['Rejected', 'Withdrawn'])
            ->exists();

        if ($existingApplication) {
            $errors[] = 'Employee already has an active application for this scholarship.';
        }

        // Check age requirements
        if ($this->min_age || $this->max_age) {
            $employeeAge = $employee->birth_date ? $employee->birth_date->age : null;
            
            if ($this->min_age && $employeeAge < $this->min_age) {
                $errors[] = "Employee age ({$employeeAge}) is below minimum requirement ({$this->min_age}).";
            }
            
            if ($this->max_age && $employeeAge > $this->max_age) {
                $errors[] = "Employee age ({$employeeAge}) exceeds maximum limit ({$this->max_age}).";
            }
        }

        // Check years of service
        $yearsOfService = $employee->date_hired ? $employee->date_hired->diffInYears(now()) : 0;
        
        if ($this->min_years_service && $yearsOfService < $this->min_years_service) {
            $errors[] = "Years of service ({$yearsOfService}) below minimum requirement ({$this->min_years_service}).";
        }
        
        if ($this->max_years_service && $yearsOfService > $this->max_years_service) {
            $errors[] = "Years of service ({$yearsOfService}) exceeds maximum limit ({$this->max_years_service}).";
        }

        // Check performance rating
        if ($this->min_performance_rating && $employee->latest_performance_rating < $this->min_performance_rating) {
            $errors[] = "Performance rating ({$employee->latest_performance_rating}) below minimum requirement ({$this->min_performance_rating}).";
        }

        // Check position requirements
        if ($this->position_requirements) {
            $positionRequirements = explode(',', strtolower($this->position_requirements));
            $employeePosition = strtolower($employee->position);
            
            $positionMatch = collect($positionRequirements)->contains(function ($requirement) use ($employeePosition) {
                return str_contains($employeePosition, trim($requirement));
            });
            
            if (!$positionMatch) {
                $warnings[] = 'Employee position may not meet scholarship requirements. Please verify eligibility.';
            }
        }

        return [
            'can_apply' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get financial coverage summary
     */
    public function getFinancialCoverage(): array
    {
        return [
            'tuition_covered' => $this->covers_tuition,
            'living_allowance' => $this->covers_living_allowance ? $this->monthly_allowance : 0,
            'transportation' => $this->covers_transportation,
            'books_materials' => $this->covers_books_materials ? $this->book_allowance : 0,
            'research_expenses' => $this->covers_research_expenses,
            'conference_fees' => $this->covers_conference_fees,
            'thesis_allowance' => $this->thesis_allowance,
            'travel_allowance' => $this->travel_allowance,
            'total_monthly_benefits' => $this->calculateMonthlyBenefits(),
            'estimated_total_value' => $this->calculateEstimatedValue(),
        ];
    }

    /**
     * Calculate monthly benefits
     */
    private function calculateMonthlyBenefits(): float
    {
        $monthly = 0;
        
        if ($this->covers_living_allowance) {
            $monthly += $this->monthly_allowance ?? 0;
        }
        
        if ($this->covers_books_materials) {
            $monthly += ($this->book_allowance ?? 0) / 12; // Assume annual book allowance
        }
        
        return $monthly;
    }

    /**
     * Calculate estimated total scholarship value
     */
    private function calculateEstimatedValue(): float
    {
        if ($this->per_scholar_budget) {
            return $this->per_scholar_budget;
        }

        $totalValue = 0;
        $durationMonths = $this->getDurationInMonths();

        // Monthly allowances
        $totalValue += $this->calculateMonthlyBenefits() * $durationMonths;

        // One-time allowances
        $totalValue += $this->book_allowance ?? 0;
        $totalValue += $this->thesis_allowance ?? 0;
        $totalValue += $this->travel_allowance ?? 0;

        return $totalValue;
    }

    /**
     * Get program duration in months
     */
    private function getDurationInMonths(): int
    {
        if ($this->duration_months) {
            return $this->duration_months;
        }

        if ($this->duration_years) {
            return $this->duration_years * 12;
        }

        if ($this->duration_weeks) {
            return (int) ceil($this->duration_weeks / 4);
        }

        // Default estimation based on degree level
        return match ($this->degree_level) {
            'Certificate' => 3,
            'Diploma' => 6,
            'Associate Degree' => 24,
            'Bachelor Degree' => 48,
            'Master Degree' => 24,
            'Doctoral Degree' => 60,
            'Post-Doctoral' => 12,
            default => 12,
        };
    }

    /**
     * Get program requirements summary
     */
    public function getRequirementsSummary(): array
    {
        return [
            'eligibility_criteria' => $this->eligibility_criteria,
            'application_requirements' => $this->application_requirements,
            'selection_process' => [
                'entrance_exam' => $this->requires_entrance_exam,
                'interview' => $this->requires_interview,
                'medical_exam' => $this->requires_medical_exam,
                'psychological_exam' => $this->requires_psychological_exam,
            ],
            'service_obligation' => [
                'years' => $this->service_obligation_years,
                'bond_amount' => $this->bond_amount,
                'early_termination_allowed' => $this->allows_early_termination,
                'penalty' => $this->early_termination_penalty,
            ],
            'reporting_requirements' => [
                'progress_reports' => $this->requires_progress_reports,
                'frequency' => $this->reporting_frequency,
                'final_report' => $this->requires_final_report,
                'thesis_submission' => $this->requires_thesis_submission,
            ],
        ];
    }

    /**
     * Get scholarship timeline
     */
    public function getScholarshipTimeline(): array
    {
        $timeline = [];

        if ($this->application_deadline) {
            $timeline[] = [
                'date' => $this->application_deadline,
                'event' => 'Application Deadline',
                'status' => 'Deadline',
                'description' => 'Last date for application submission',
            ];
        }

        if ($this->selection_date) {
            $timeline[] = [
                'date' => $this->selection_date,
                'event' => 'Selection Results',
                'status' => 'Selection',
                'description' => 'Announcement of selected scholars',
            ];
        }

        if ($this->program_start) {
            $timeline[] = [
                'date' => $this->program_start,
                'event' => 'Program Start',
                'status' => 'Active',
                'description' => 'Scholarship program begins',
            ];
        }

        if ($this->program_end) {
            $timeline[] = [
                'date' => $this->program_end,
                'event' => 'Program End',
                'status' => 'Completion',
                'description' => 'Expected program completion',
            ];
        }

        // Sort by date
        usort($timeline, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        return $timeline;
    }

    /**
     * Get success metrics
     */
    public function getSuccessMetrics(): array
    {
        return [
            'target_completion_rate' => $this->target_completion_rate,
            'actual_completion_rate' => $this->actual_completion_rate ?? $this->getCompletionRate(),
            'total_scholars_graduated' => $this->total_scholars_graduated,
            'total_scholars_active' => $this->total_scholars_active,
            'success_indicators' => $this->success_indicators,
            'performance_vs_target' => $this->actual_completion_rate && $this->target_completion_rate 
                ? round(($this->actual_completion_rate / $this->target_completion_rate) * 100, 2) 
                : null,
        ];
    }

    /**
     * Check if program has government approval
     */
    public function hasGovernmentApproval(): array
    {
        return [
            'daps_approved' => $this->daps_approved,
            'csc_approved' => $this->csc_approved,
            'dbm_approved' => $this->dbm_approved,
            'all_approved' => $this->daps_approved && $this->csc_approved && $this->dbm_approved,
            'approval_documents' => $this->approval_documents,
            'legal_basis' => $this->legal_basis,
        ];
    }

    /**
     * Get program status with context
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
            } elseif ($this->isApplicationOpen()) {
                $context = 'Open for Applications';
            } else {
                $context = 'Applications Closed';
            }
        }

        return [
            'status' => $status,
            'context' => $context,
            'is_active' => $status === 'Active',
            'application_open' => $this->isApplicationOpen(),
        ];
    }
}