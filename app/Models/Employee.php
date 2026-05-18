<?php

namespace App\Models;

use App\Models\OPCRWorkflow;
use App\Models\OfficeAssignment;
use App\Models\User;
use App\Support\DatabaseExpression;
use App\Models\WorkCalendar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    public const ACTIVE_EMPLOYMENT_STATUSES = [
        'active',
        'regular',
        'permanent',
        'temporary',
        'contractual',
        'casual',
        'probationary',
    ];

    protected $fillable = [
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'name_extension',
        'birth_date',
        'gender',
        'civil_status',
        'civil_status_other_details',
        'address',
        'contact_number',
        'email',
        'position',
        'department',
        'employment_status',
        'date_hired',
        'salary_grade',
        'step_increment',
        'basic_salary',
        // CSC Reporting fields
        'appointment_type',
        'appointment_date',
        'separation_type',
        'separation_date',
        'separation_reason',
        'csc_eligibility',
        'csc_eligibility_date',
        'place_of_birth',
        'citizenship',
        'dual_citizenship_type',
        'dual_citizenship_country',
        'religion',
        'height',
        'weight',
        'blood_type',
        'tin_number',
        'sss_number',
        'pagibig_number',
        'philhealth_number',
        'gsis_number',
        'latest_performance_rating',
        'latest_performance_date',
        'training_hours_ytd',
        'last_promotion_date',
        'previous_position',
        'last_attendance_date',
        'consecutive_absent_days',
        'is_awol',
        'awol_start_date',
        'spouse_name',
        'spouse_occupation',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_number',
        'emergency_contact_address',
        // PDS Address fields
        'res_house_block_lot_no',
        'res_street',
        'res_subdivision_village',
        'res_barangay',
        'res_city_municipality',
        'res_province',
        'res_zip_code',
        'perm_house_block_lot_no',
        'perm_street',
        'perm_subdivision_village',
        'perm_barangay',
        'perm_city_municipality',
        'perm_province',
        'perm_zip_code',
        'telephone_no',
        'mobile_no',
        'agency_employee_no',
        // CSC Form No. 212 - Page 4 Government ID fields
        'gov_id_type',
        'gov_id_number',
        'gov_id_date_issued',
        'gov_id_place_issued',
        // Office relationships
        'office_id',
        'office_code',
        'work_calendar_id',
        'is_department_head',
        // Archive fields
        'archived_at',
        'archived_by',
    ];

    protected $casts = [
        'birth_date' => 'datetime',
        'date_hired' => 'datetime',
        'appointment_date' => 'date',
        'separation_date' => 'date',
        'csc_eligibility_date' => 'date',
        'latest_performance_date' => 'date',
        'last_promotion_date' => 'date',
        'last_attendance_date' => 'date',
        'awol_start_date' => 'date',
        'gov_id_date_issued' => 'date',
        'latest_performance_rating' => 'decimal:2',
        'basic_salary' => 'decimal:2',
        'training_hours_ytd' => 'integer',
        'consecutive_absent_days' => 'integer',
        'is_awol' => 'boolean',
        'deleted_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'employee_id', 'id');
    }

    /**
     * Apply shared employee filters to the query builder.
     */
    public function scopeApplyFilters(Builder $query, array $filters = []): Builder
    {
        $search = $filters['search'] ?? null;

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $likeTerm = "%{$search}%";
                $q->where('first_name', 'LIKE', $likeTerm)
                    ->orWhere('last_name', 'LIKE', $likeTerm)
                    ->orWhere('email', 'LIKE', $likeTerm)
                    ->orWhere('employee_number', 'LIKE', $likeTerm)
                    ->orWhere('position', 'LIKE', $likeTerm)
                    ->orWhere('department', 'LIKE', $likeTerm)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$likeTerm]);
            });
        }

        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (!empty($filters['position'])) {
            $query->where('position', $filters['position']);
        }

        if (!empty($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }

        if (!empty($filters['office_id'])) {
            $query->where('office_id', $filters['office_id']);
        }

        if (array_key_exists('is_department_head', $filters) && $filters['is_department_head'] !== null && $filters['is_department_head'] !== '') {
            $query->where('is_department_head', filter_var($filters['is_department_head'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }

    /**
     * Get the user who archived this employee
     */
    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Get employees archived by this user
     */
    public function archivedEmployees(): HasMany
    {
        return $this->hasMany(Employee::class, 'archived_by');
    }

    /**
     * Get the office for this employee
     */
    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function workCalendar(): BelongsTo
    {
        return $this->belongsTo(WorkCalendar::class);
    }

    /**
     * Individual Performance Commitment and Review records
     */
    public function ipcrs(): HasMany
    {
        return $this->hasMany(Ipcr::class);
    }

    /**
     * OPCR Workflow Relationships
     */
    public function opcrWorkflows()
    {
        // Get OPCR workflows where this employee is involved in any role
        $userId = $this->user_id;
        return OPCRWorkflow::where(function ($query) use ($userId) {
            $query->where('submitted_by', $userId)
                  ->orWhere('committed_by', $userId)
                  ->orWhere('assessed_by', $userId)
                  ->orWhere('approved_by', $userId);
        });
    }

    public function committedOPCRWorkflows()
    {
        // Get committed OPCR workflows where this employee is the committer
        $userId = $this->user_id;
        return OPCRWorkflow::where('committed_by', $userId)
                              ->where('workflow_state', '!=', 'draft');
    }

    public function officeAssignments()
    {
        return $this->hasManyThrough(
            OfficeAssignment::class,
            User::class,
            'employee_id',
            'user_id',
            'id',
            'id'
        );
    }

    /**
     * Get active office assignments directly for this employee
     */
    public function activeOfficeAssignments()
    {
        return $this->hasMany(OfficeAssignment::class)
            ->where('is_active', true)
            ->distinct();
    }

    /**
     * Check if employee is a department head for any office
     */
    public function isDepartmentHead(): bool
    {
        return $this->officeAssignments()
            ->where('role', 'Department Head')
            ->where('office_assignments.is_active', true)
            ->exists();
    }

    /**
     * Get offices where employee is department head
     */
    public function managedOffices()
    {
        return $this->hasManyThrough(
            Office::class,
            OfficeAssignment::class,
            'user_id',
            'id',
            'id',
            'office_id'
        )->where('role', 'Department Head')
         ->where('office_assignments.is_active', true);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function education(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function workExperiences(): HasMany
    {
        return $this->hasMany(EmployeeWorkExperience::class);
    }

    public function leaveCredits(): HasMany
    {
        return $this->hasMany(LeaveCredit::class);
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function performanceTargets(): HasMany
    {
        return $this->hasMany(PerformanceTarget::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function references(): HasMany
    {
        return $this->hasMany(EmployeeReference::class)->ordered();
    }

    public function activePhoto(): HasOne
    {
        return $this->hasOne(EmployeePhoto::class)->active();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EmployeePhoto::class);
    }

    public function sexualHarassmentCasesAsComplainant(): HasMany
    {
        return $this->hasMany(SexualHarassmentCase::class, 'complainant_id');
    }

    public function sexualHarassmentCasesAsRespondent(): HasMany
    {
        return $this->hasMany(SexualHarassmentCase::class, 'respondent_id');
    }

    public function investigatedHarassmentCases(): HasMany
    {
        return $this->hasMany(SexualHarassmentCase::class, 'investigating_officer_id');
    }

    public function governmentBenefits(): HasMany
    {
        return $this->hasMany(GovernmentBenefit::class);
    }

    public function benefitContributions(): HasMany
    {
        return $this->hasMany(BenefitContribution::class);
    }

    public function civilServiceEligibilities(): HasMany
    {
        return $this->hasMany(CivilServiceEligibility::class);
    }

    public function careerProgressions(): HasMany
    {
        return $this->hasMany(CareerProgression::class);
    }

    public function employeeTrainings(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    public function employeeScholarships(): HasMany
    {
        return $this->hasMany(EmployeeScholarship::class);
    }

    // PDS Relationships
    public function familyBackground(): HasOne
    {
        return $this->hasOne(EmployeeFamilyBackground::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(EmployeeChildren::class);
    }

    public function pdsEligibilities(): HasMany
    {
        return $this->hasMany(EmployeeCivilServiceEligibility::class);
    }

    public function voluntaryWork(): HasMany
    {
        return $this->hasMany(EmployeeVoluntaryWork::class);
    }

    public function otherInformation(): HasMany
    {
        return $this->hasMany(EmployeeOtherInformation::class);
    }

    public function specialSkills(): HasMany
    {
        return $this->hasMany(EmployeeOtherInformation::class)->where('information_type', 'special_skills');
    }

    public function distinctions(): HasMany
    {
        return $this->hasMany(EmployeeOtherInformation::class)->where('information_type', 'distinctions');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(EmployeeOtherInformation::class)->where('information_type', 'memberships');
    }

    public function questionnaire(): HasOne
    {
        return $this->hasOne(EmployeeQuestionnaire::class);
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(EmployeeChangeRequest::class);
    }

    public function performanceEvaluations(): HasMany
    {
        return $this->hasMany(PerformanceEvaluation::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    public function currentSalaryGrade()
    {
        return SalaryGrade::findByGradeAndStep($this->salary_grade, $this->step_increment);
    }

    /**
     * Get applicable leave policies for this employee
     */
    public function getApplicableLeavePolicies()
    {
        return LeavePolicy::active()
            ->effective()
            ->forEmploymentStatus($this->employment_status)
            ->forPosition($this->position)
            ->forGender($this->gender)
            ->with('leaveType')
            ->get()
            ->filter(function ($policy) {
                return $policy->appliesTo($this);
            });
    }

    /**
     * Get leave entitlement for a specific leave type and year
     */
    public function getLeaveEntitlement($leaveTypeId, $year = null)
    {
        $year = $year ?? now()->year;

        $policy = $this->getApplicableLeavePolicies()
            ->where('leave_type_id', $leaveTypeId)
            ->first();

        if (!$policy) {
            return 0;
        }

        return $policy->calculateAnnualEntitlement($this, $year);
    }

    /**
     * Check if employee can apply for specific leave type
     */
    public function canApplyForLeave($leaveTypeId, array $applicationData = [])
    {
        $policy = $this->getApplicableLeavePolicies()
            ->where('leave_type_id', $leaveTypeId)
            ->first();

        if (!$policy) {
            return [
                'can_apply' => false,
                'errors' => ['This leave type is not available for your employment status.']
            ];
        }

        $errors = empty($applicationData) ? [] : $policy->validateApplication($this, $applicationData);

        return [
            'can_apply' => empty($errors),
            'errors' => $errors,
            'policy' => $policy
        ];
    }

    /**
     * Document linking relationships
     */
    public function sourceLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'source');
    }

    public function targetLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'target');
    }

    /**
     * Get all document versions for this employee's documents
     */
    public function getAllDocumentVersions(): \Illuminate\Database\Eloquent\Collection
    {
        return DocumentVersion::whereHas('originalDocument', function ($query) {
            $query->where('employee_id', $this->id);
        })->with(['originalDocument', 'uploader', 'approver'])->get();
    }

    /**
     * Get pending document versions awaiting approval
     */
    public function getPendingDocumentVersions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getAllDocumentVersions()->where('approval_status', 'pending');
    }

    /**
     * Get all linked documents across all record types
     */
    public function getAllLinkedDocuments(): \Illuminate\Database\Eloquent\Collection
    {
        $linkedDocuments = collect();

        // Get documents linked to leave applications (with eager loading)
        $leaveApplications = $this->leaveApplications()->with('supportingDocuments')->get();
        foreach ($leaveApplications as $leave) {
            if (method_exists($leave, 'getSupportingDocuments')) {
                $linkedDocuments = $linkedDocuments->merge($leave->getSupportingDocuments());
            }
        }

        // Get documents linked to performance reviews (with eager loading)
        $performanceReviews = $this->performanceReviews()->with('evidenceDocuments')->get();
        foreach ($performanceReviews as $review) {
            if (method_exists($review, 'getEvidenceDocuments')) {
                $linkedDocuments = $linkedDocuments->merge($review->getEvidenceDocuments());
            }
        }

        // Get documents linked to education records (with eager loading)
        $education = $this->education()->with('credentialDocuments')->get();
        foreach ($education as $edu) {
            if (method_exists($edu, 'getCredentialDocuments')) {
                $linkedDocuments = $linkedDocuments->merge($edu->getCredentialDocuments());
            }
        }

        // Get documents linked to work experiences (with eager loading)
        $workExperiences = $this->workExperiences()->with('proofDocuments')->get();
        foreach ($workExperiences as $work) {
            if (method_exists($work, 'getProofDocuments')) {
                $linkedDocuments = $linkedDocuments->merge($work->getProofDocuments());
            }
        }

        return $linkedDocuments->unique('id');
    }

    /**
     * Get document compliance summary
     */
    public function getDocumentComplianceSummary(): array
    {
        $totalDocuments = $this->documents()->count();
        $linkedDocuments = $this->getAllLinkedDocuments()->count();
        $pendingVersions = $this->getPendingDocumentVersions()->count();
        $outdatedDocuments = $this->documents()->where('updated_at', '<', now()->subMonths(12))->count();

        return [
            'total_documents' => $totalDocuments,
            'linked_documents' => $linkedDocuments,
            'unlinked_documents' => $totalDocuments - $linkedDocuments,
            'pending_versions' => $pendingVersions,
            'outdated_documents' => $outdatedDocuments,
            'compliance_rate' => $totalDocuments > 0 ? round(($linkedDocuments / $totalDocuments) * 100, 2) : 0,
        ];
    }

    /**
     * Get government benefits summary for this employee
     */
    public function getGovernmentBenefitsSummary(): array
    {
        $benefits = $this->governmentBenefits()
            ->with(['benefitContributions' => function($query) {
                $query->orderBy('contribution_period', 'desc')->take(12); // Latest 12 months
            }])
            ->get();
        $summary = [];

        foreach ($benefits as $benefit) {
            if (method_exists($benefit, 'getBenefitSummary')) {
                $summary[$benefit->benefit_type] = $benefit->getBenefitSummary();
            }
        }

        return $summary;
    }

    /**
     * Get total contributions for a specific year
     */
    public function getTotalContributionsForYear(int $year): array
    {
        $contributions = $this->benefitContributions()
            ->forYear($year)
            ->with('governmentBenefit')
            ->get()
            ->groupBy(function($contribution) {
                return $contribution->governmentBenefit->benefit_type;
            });

        $totals = [];
        foreach ($contributions as $benefitType => $benefitContributions) {
            $totals[$benefitType] = [
                'employee_total' => $benefitContributions->sum('employee_contribution_amount'),
                'employer_total' => $benefitContributions->sum('employer_contribution_amount'),
                'grand_total' => $benefitContributions->sum('total_contribution_amount'),
                'loan_payments' => $benefitContributions->sum('loan_payment_amount'),
                'penalties' => $benefitContributions->sum('late_penalty_amount'),
            ];
        }

        return $totals;
    }

    /**
     * Check if employee has all required government benefits enrolled
     */
    public function hasRequiredBenefitsEnrolled(): array
    {
        $requiredBenefits = $this->getRequiredBenefitTypes();
        $enrolledBenefits = $this->governmentBenefits()->active()->pluck('benefit_type')->toArray();

        $missing = array_diff($requiredBenefits, $enrolledBenefits);

        return [
            'all_enrolled' => empty($missing),
            'enrolled_benefits' => $enrolledBenefits,
            'missing_benefits' => $missing,
            'compliance_rate' => count($requiredBenefits) > 0 ? round((count($enrolledBenefits) / count($requiredBenefits)) * 100, 2) : 100,
        ];
    }

    /**
     * Get required benefit types based on employment status
     */
    private function getRequiredBenefitTypes(): array
    {
        $required = ['PhilHealth']; // Universal coverage
        $employmentStatus = self::normalizedEmploymentStatus($this->employment_status);

        if (in_array($employmentStatus, ['permanent', 'temporary', 'regular', 'contractual', 'casual'], true)) {
            if (in_array($employmentStatus, ['permanent', 'temporary', 'regular'], true)) {
                $required[] = 'GSIS';
                $required[] = 'Pag-IBIG';
            } else {
                // Contractual/casual might be SSS instead of GSIS
                $required[] = 'SSS';
                $required[] = 'Pag-IBIG';
            }
        }

        return $required;
    }

    /**
     * Get overdue contributions summary
     */
    public function getOverdueContributionsSummary(): array
    {
        $overdueContributions = $this->benefitContributions()
            ->overdue()
            ->with('governmentBenefit')
            ->get();

        $summary = [
            'total_overdue_count' => $overdueContributions->count(),
            'total_overdue_amount' => $overdueContributions->sum('total_contribution_amount'),
            'total_penalties' => $overdueContributions->sum('late_penalty_amount'),
            'by_benefit_type' => [],
        ];

        foreach ($overdueContributions->groupBy(function($contribution) {
            return $contribution->governmentBenefit->benefit_type;
        }) as $benefitType => $contributions) {
            $summary['by_benefit_type'][$benefitType] = [
                'count' => $contributions->count(),
                'total_amount' => $contributions->sum('total_contribution_amount'),
                'total_penalties' => $contributions->sum('late_penalty_amount'),
                'oldest_overdue_days' => $contributions->max('days_overdue'),
            ];
        }

        return $summary;
    }

    /**
     * Get active loans summary
     */
    public function getActiveLoansSummary(): array
    {
        $loansData = $this->governmentBenefits()
            ->withActiveLoans()
            ->get(['benefit_type', 'loan_balance', 'monthly_loan_payment', 'loan_maturity_date']);

        $summary = [
            'total_active_loans' => $loansData->count(),
            'total_outstanding_balance' => $loansData->sum('loan_balance'),
            'total_monthly_payments' => $loansData->sum('monthly_loan_payment'),
            'loans_by_type' => [],
        ];

        foreach ($loansData as $loan) {
            $summary['loans_by_type'][$loan->benefit_type] = [
                'outstanding_balance' => $loan->loan_balance,
                'monthly_payment' => $loan->monthly_loan_payment,
                'maturity_date' => $loan->loan_maturity_date?->format('Y-m-d'),
            ];
        }

        return $summary;
    }

    /**
     * Creation timestamp attributes and methods
     */

    /**
     * Get formatted creation timestamp in Philippine timezone
     */
    public function getCreatedAtFormattedAttribute(): string
    {
        if (!$this->created_at) {
            return 'Not recorded';
        }

        return $this->created_at->format('m/d/Y h:i A');
    }

    /**
     * Get creation date in mm/dd/yyyy format
     */
    public function getCreationDateAttribute(): string
    {
        if (!$this->created_at) {
            return 'Not recorded';
        }

        return $this->created_at->format('m/d/Y');
    }

    /**
     * Analytics-specific attributes and methods
     */

    /**
     * Get employee age
     */
    public function getAgeAttribute()
    {
        if (!$this->birth_date) {
            return null;
        }

        return Carbon::parse($this->birth_date)->diffInYears(now());
    }

    /**
     * Get years of service (tenure)
     */
    public function getTenureYearsAttribute()
    {
        if (!$this->date_hired) {
            return null;
        }

        return Carbon::parse($this->date_hired)->diffInYears(now());
    }

    /**
     * Get human-readable formatted service duration (e.g., "2 years, 3 months")
     */
    public function getFormattedServiceDurationAttribute(): string
    {
        if (!$this->date_hired) {
            return 'N/A';
        }

        $hired = Carbon::parse($this->date_hired);
        $diff = $hired->diff(now());

        $parts = [];

        if ($diff->y > 0) {
            $parts[] = $diff->y . ' ' . ($diff->y === 1 ? 'year' : 'years');
        }

        if ($diff->m > 0) {
            $parts[] = $diff->m . ' ' . ($diff->m === 1 ? 'month' : 'months');
        }

        if ($diff->y === 0 && $diff->m === 0) {
            $days = max($diff->d, 1);
            $parts[] = $days . ' ' . ($days === 1 ? 'day' : 'days');
        }

        return implode(', ', $parts);
    }

    /**
     * Get current performance rating (latest evaluation)
     */
    public function getCurrentPerformanceRatingAttribute()
    {
        $latestEvaluation = $this->performanceEvaluations()
            ->where('evaluation_status', 'final')
            ->orderBy('evaluation_date', 'desc')
            ->first();

        return $latestEvaluation ? $latestEvaluation->overall_rating : null;
    }

    /**
     * Get performance trend (improvement/decline)
     */
    public function getPerformanceTrendAttribute()
    {
        $evaluations = $this->performanceEvaluations()
            ->where('evaluation_status', 'final')
            ->orderBy('evaluation_date', 'desc')
            ->limit(2)
            ->get();

        if ($evaluations->count() < 2) {
            return 'insufficient_data';
        }

        $latest = $evaluations->first()->overall_rating;
        $previous = $evaluations->last()->overall_rating;

        if ($latest > $previous) {
            return 'improving';
        } elseif ($latest < $previous) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    /**
     * Get total training hours this year
     */
    public function getTrainingHoursThisYearAttribute()
    {
        return $this->trainings()
            ->where('completion_status', 'Completed')
            ->whereYear('start_date', now()->year)
            ->sum('number_of_hours');
    }

    /**
     * Check if employee is high performer
     */
    public function getIsHighPerformerAttribute()
    {
        $rating = $this->current_performance_rating;
        return $rating && $rating >= 4.0;
    }

    /**
     * Check if employee is at risk of turnover
     */
    public function getTurnoverRiskAttribute()
    {
        $riskScore = 0;

        // Tenure risk (U-shaped curve)
        $tenure = $this->tenure_years ?? 0;
        if ($tenure < 1 || $tenure > 20) $riskScore += 30;
        elseif ($tenure < 2 || $tenure > 15) $riskScore += 20;
        elseif ($tenure < 3 || $tenure > 10) $riskScore += 10;

        // Performance risk
        $rating = $this->current_performance_rating;
        if ($rating && $rating < 3) $riskScore += 25;

        // Age risk
        $age = $this->age ?? 0;
        if ($age > 60) $riskScore += 15;
        if ($age < 25) $riskScore += 10;

        if ($riskScore >= 70) return 'high';
        if ($riskScore >= 40) return 'medium';
        return 'low';
    }

    /**
     * Check if eligible for retirement
     */
    public function getIsRetirementEligibleAttribute()
    {
        $age = $this->age ?? 0;
        $tenure = $this->tenure_years ?? 0;

        // Government retirement eligibility: 65 years old OR 30+ years of service
        return $age >= 65 || $tenure >= 30;
    }

    /**
     * Get years until mandatory retirement
     */
    public function getYearsUntilRetirementAttribute()
    {
        $age = $this->age ?? 0;

        if ($age >= 65) {
            return 0; // Already at retirement age
        }

        return 65 - $age;
    }

    /**
     * Check if promotion ready based on performance and tenure
     */
    public function getIsPromotionReadyAttribute()
    {
        $latestEvaluation = $this->performanceEvaluations()
            ->where('evaluation_status', 'final')
            ->orderBy('evaluation_date', 'desc')
            ->first();

        if (!$latestEvaluation) {
            return false;
        }

        return $latestEvaluation->promotion_readiness &&
               $latestEvaluation->overall_rating >= 4.0;
    }

    /**
     * Get compliance score across different areas
     */
    public function getComplianceScoreAttribute()
    {
        $scores = [];

        // Document compliance (out of 4 required documents)
        $requiredDocs = ['pds', 'medical_certificate', 'eligibility_certificate', 'diploma'];
        $submittedDocs = $this->documents()->whereIn('document_type', $requiredDocs)->count();
        $scores['documents'] = ($submittedDocs / count($requiredDocs)) * 100;

        // Performance evaluation compliance
        $currentYearEval = $this->performanceEvaluations()
            ->whereYear('evaluation_date', now()->year)
            ->exists();
        $scores['performance'] = $currentYearEval ? 100 : 0;

        // Training compliance (minimum 40 hours per year)
        $trainingHours = $this->training_hours_this_year ?? 0;
        $scores['training'] = min(($trainingHours / 40) * 100, 100);

        // Benefits compliance
        $benefitsCompliance = $this->hasRequiredBenefitsEnrolled();
        $scores['benefits'] = $benefitsCompliance['compliance_rate'];

        return round(array_sum($scores) / count($scores), 2);
    }

    /**
     * Get employee skills as array
     */
    public function getSkillsArrayAttribute()
    {
        if (!$this->skills) {
            return [];
        }

        return array_map('trim', explode(',', $this->skills));
    }

    /**
     * Calculate cost per employee (annual)
     */
    public function getAnnualCostAttribute()
    {
        $monthlySalary = $this->salary ?? 0;
        $annualSalary = $monthlySalary * 12;

        // Add estimated benefits cost (30% of salary)
        $benefitsCost = $annualSalary * 0.30;

        // Add training costs
        $trainingCost = $this->trainings()
            ->whereYear('start_date', now()->year)
            ->sum('training_cost') ?? 0;

        return $annualSalary + $benefitsCost + $trainingCost;
    }

    /**
     * Get department headcount for analytics
     */
    public static function getDepartmentHeadcount()
    {
        return self::active()
            ->select('department', DB::raw('COUNT(*) as count'))
            ->groupBy('department')
            ->pluck('count', 'department');
    }

    /**
     * Get employment status distribution
     */
    public static function getEmploymentStatusDistribution()
    {
        return self::select('employment_status', DB::raw('COUNT(*) as count'))
            ->groupBy('employment_status')
            ->pluck('count', 'employment_status');
    }

    /**
     * Scope for active employees
     */
    public function scopeActive($query)
    {
        return $query->whereIn(DB::raw('LOWER(employment_status)'), self::ACTIVE_EMPLOYMENT_STATUSES);
    }

    public function isActiveEmployment(): bool
    {
        return in_array(strtolower((string) $this->employment_status), self::ACTIVE_EMPLOYMENT_STATUSES, true);
    }

    public static function normalizedEmploymentStatus(?string $status): ?string
    {
        return $status === null ? null : strtolower(trim($status));
    }

    /**
     * Scope for employees hired in specific year
     */
    public function scopeHiredInYear($query, $year)
    {
        return $query->whereYear('date_hired', $year);
    }

    /**
     * Scope for employees in age range
     */
    public function scopeAgeRange($query, $minAge, $maxAge)
    {
        return $query->whereRaw(DatabaseExpression::ageYears('birth_date') . ' BETWEEN ? AND ?', [$minAge, $maxAge]);
    }

    /**
     * Scope for high performers
     */
    public function scopeHighPerformers($query)
    {
        return $query->whereHas('performanceEvaluations', function($q) {
            $q->where('overall_rating', '>=', 4.0)
              ->where('evaluation_status', 'final')
              ->whereYear('evaluation_date', now()->year);
        });
    }

    /**
     * Scope for promotion ready employees
     */
    public function scopePromotionReady($query)
    {
        return $query->whereHas('performanceEvaluations', function($q) {
            $q->where('promotion_readiness', true)
              ->where('overall_rating', '>=', 4.0)
              ->where('evaluation_status', 'final')
              ->whereYear('evaluation_date', now()->year);
        });
    }

    /**
     * Scope for user-based filtering
     */
    public function scopeForUser($query, User $user)
    {
        if ($user->hasRole('Employee')) {
            return $query->where('id', $user->employee?->id);
        }

        if ($user->hasRole('HR Admin')) {
            return $query->active();
        }

        if ($user->hasRole('Super Admin')) {
            return $query; // No filtering
        }

        return $query->whereRaw('1 = 0'); // Unknown role
    }

    /**
     * Scope for accessible employees
     */
    public function scopeAccessible($query)
    {
        $user = auth()->user();
        if (!$user) {
            return $query->whereRaw('1 = 0'); // No authenticated user
        }

        return $query->forUser($user);
    }

    /**
     * PDS-specific methods
     */

    public function getFullNameAttribute(): string
    {
        $name = trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
        return $this->name_extension ? $name . ' ' . $this->name_extension : $name;
    }

    /**
     * Get avatar initials including name extensions.
     * Returns first and last name initials, prioritizing first and last letters.
     */
    public function getAvatarInitialsAttribute(): string
    {
        $fullName = $this->full_name;

        // Split full name into words
        $words = array_filter(explode(' ', $fullName));

        if (count($words) === 0) {
            return 'E';
        }

        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 2));
        }

        // Get first letter of first word and first letter of last significant word
        $firstWord = $words[0];
        $lastWord = end($words);

        // Skip common name extensions for initials (Jr, Sr, II, III, IV)
        $extensions = ['Jr', 'Sr', 'II', 'III', 'IV', 'V', 'VI'];
        if (in_array($lastWord, $extensions)) {
            // Find the last word that's not an extension
            $tempWords = array_filter($words, function($word) use ($extensions) {
                return !in_array($word, $extensions);
            });
            if (count($tempWords) > 1) {
                $lastWord = end($tempWords);
            } else {
                $lastWord = $firstWord;
            }
        }

        return strtoupper(substr($firstWord, 0, 1) . substr($lastWord, 0, 1));
    }

    public function getResidentialAddressAttribute(): string
    {
        $parts = array_filter([
            $this->res_house_block_lot_no,
            $this->res_street,
            $this->res_subdivision_village,
            $this->res_barangay,
            $this->res_city_municipality,
            $this->res_province,
            $this->res_zip_code,
        ]);

        return implode(', ', $parts);
    }

    public function getPermanentAddressAttribute(): string
    {
        $parts = array_filter([
            $this->perm_house_block_lot_no,
            $this->perm_street,
            $this->perm_subdivision_village,
            $this->perm_barangay,
            $this->perm_city_municipality,
            $this->perm_province,
            $this->perm_zip_code,
        ]);

        return implode(', ', $parts);
    }



    /**
     * Department Head status transition methods
     */

    /**
     * Assign employee as Department Head for their office
     */
    public function assignAsDepartmentHead(?int $officeId = null): array
    {
        return DB::transaction(function () use ($officeId) {
            $results = [
                'success' => false,
                'updated_assignments' => [],
                'updated_employee_status' => false,
                'updated_user_roles' => [],
                'errors' => [],
            ];

            try {
                $targetOfficeId = $officeId ?? $this->office_id;
                if (!$targetOfficeId) {
                    $results['errors'][] = 'Employee must be assigned to an office';
                    return $results;
                }

                // Update employee status
                $this->update(['is_department_head' => true]);
                $results['updated_employee_status'] = true;

                // Create or update office assignment
                $existingAssignment = $this->activeOfficeAssignments()
                    ->where('office_id', $targetOfficeId)
                    ->first();

                if ($existingAssignment) {
                    // Update existing assignment to Department Head
                    $existingAssignment->update([
                        'role' => 'Department Head',
                        'position' => 'Department Head',
                        'is_active' => true,
                        'remarks' => ($existingAssignment->remarks ?? '') . "\n\nAutomatically promoted to Department Head",
                    ]);
                    $results['updated_assignments'][] = $existingAssignment->id;
                } else {
                    // Create new Department Head assignment
                    $newAssignment = $this->officeAssignments()->create([
                        'office_id' => $targetOfficeId,
                        'role' => 'Department Head',
                        'position' => 'Department Head',
                        'is_active' => true,
                        'assigned_date' => now()->toDateString(),
                        'started_date' => now()->toDateString(),
                        'assigned_by' => auth()->id(),
                    ]);
                    $results['updated_assignments'][] = $newAssignment->id;
                }

                // Sync user roles if user exists
                if ($this->user) {
                    if (!$this->user->hasRole('Department Head')) {
                        $this->user->assignRole('Department Head');
                        $results['updated_user_roles'][] = 'assigned_department_head';
                    }

                    // Remove Employee role if they have it
                    if ($this->user->hasRole('Employee')) {
                        $this->user->removeRole('Employee');
                        $results['updated_user_roles'][] = 'removed_employee_role';
                    }
                }

                $results['success'] = true;

                // Log the promotion
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($this)
                    ->withProperties([
                        'action' => 'assigned_as_department_head',
                        'office_id' => $targetOfficeId,
                        'assignments_updated' => $results['updated_assignments'],
                        'user_roles_updated' => $results['updated_user_roles'],
                    ])
                    ->log('Employee assigned as Department Head');

            } catch (\Exception $e) {
                $results['errors'][] = $e->getMessage();
            }

            return $results;
        });
    }

    /**
     * Remove Department Head status from employee
     */
    public function removeAsDepartmentHead(): array
    {
        return DB::transaction(function () {
            $results = [
                'success' => false,
                'deactivated_assignments' => [],
                'updated_employee_status' => false,
                'updated_user_roles' => [],
                'errors' => [],
            ];

            try {
                // Update employee status
                $this->update(['is_department_head' => false]);
                $results['updated_employee_status'] = true;

                // Deactivate Department Head assignments
                $departmentHeadAssignments = $this->activeOfficeAssignments()
                    ->where('role', 'Department Head')
                    ->get();

                foreach ($departmentHeadAssignments as $assignment) {
                    $assignment->update([
                        'is_active' => false,
                        'ended_date' => now()->toDateString(),
                        'remarks' => ($assignment->remarks ?? '') . "\n\nDepartment Head status removed",
                    ]);
                    $results['deactivated_assignments'][] = $assignment->id;
                }

                // Sync user roles if user exists
                if ($this->user) {
                    if ($this->user->hasRole('Department Head')) {
                        $this->user->removeRole('Department Head');
                        $results['updated_user_roles'][] = 'removed_department_head';
                    }

                    // Add Employee role back if they don't have other special roles
                    if (!$this->user->hasAnyRole(['HR Admin', 'Super Admin', 'Assessor', 'Final Approver'])) {
                        $this->user->assignRole('Employee');
                        $results['updated_user_roles'][] = 'assigned_employee_role';
                    }
                }

                $results['success'] = true;

                // Log the removal
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($this)
                    ->withProperties([
                        'action' => 'removed_as_department_head',
                        'deactivated_assignments' => $results['deactivated_assignments'],
                        'user_roles_updated' => $results['updated_user_roles'],
                    ])
                    ->log('Department Head status removed from employee');

            } catch (\Exception $e) {
                $results['errors'][] = $e->getMessage();
            }

            return $results;
        });
    }

    /**
     * Check if employee has consistent Department Head status
     */
    public function hasConsistentDepartmentHeadStatus(): bool
    {
        // Check if employee is marked as Department Head
        $employeeStatus = $this->is_department_head;

        // Check if employee has active Department Head assignment
        $hasDepartmentHeadAssignment = $this->activeOfficeAssignments()
            ->where('role', 'Department Head')
            ->exists();

        return $employeeStatus === $hasDepartmentHeadAssignment;
    }

    /**
     * Get Department Head status consistency details
     */
    public function getDepartmentHeadStatusDetails(): array
    {
        $activeDepartmentHeadAssignments = $this->activeOfficeAssignments()
            ->where('role', 'Department Head')
            ->with('office')
            ->get();

        return [
            'employee_is_marked_department_head' => $this->is_department_head,
            'has_active_department_head_assignments' => $activeDepartmentHeadAssignments->count() > 0,
            'department_head_assignments_count' => $activeDepartmentHeadAssignments->count(),
            'department_head_offices' => $activeDepartmentHeadAssignments->map(function ($assignment) {
                return [
                    'office_id' => $assignment->office_id,
                    'office_name' => $assignment->office->name,
                    'assignment_id' => $assignment->id,
                    'assigned_date' => $assignment->assigned_date,
                ];
            })->toArray(),
            'is_consistent' => $this->hasConsistentDepartmentHeadStatus(),
            'issues' => $this->getDepartmentHeadStatusIssues(),
        ];
    }

    /**
     * Get Department Head status issues
     */
    private function getDepartmentHeadStatusIssues(): array
    {
        $issues = [];

        if ($this->is_department_head) {
            // Employee is marked as Department Head, check for assignments
            $activeDepartmentHeadAssignments = $this->activeOfficeAssignments()
                ->where('role', 'Department Head')
                ->get();

            if ($activeDepartmentHeadAssignments->count() === 0) {
                $issues[] = 'Employee is marked as Department Head but has no active Department Head assignments';
            } elseif ($activeDepartmentHeadAssignments->count() > 1) {
                $issues[] = 'Employee has multiple active Department Head assignments (should only have one)';
            }
        } else {
            // Employee is not marked as Department Head, check if they have assignments
            $activeDepartmentHeadAssignments = $this->activeOfficeAssignments()
                ->where('role', 'Department Head')
                ->get();

            if ($activeDepartmentHeadAssignments->count() > 0) {
                $issues[] = 'Employee has active Department Head assignments but is not marked as Department Head';
            }
        }

        return $issues;
    }

    /**
     * Fix Department Head status consistency
     */
    public function fixDepartmentHeadConsistency(): array
    {
        $results = [
            'issues_found' => [],
            'fixes_applied' => [],
            'success' => true,
        ];

        $details = $this->getDepartmentHeadStatusDetails();
        $results['issues_found'] = $details['issues'];

        try {
            if (!$details['is_consistent']) {
                if ($this->is_department_head && !$details['has_active_department_head_assignments']) {
                    // Employee is marked but no assignments - create assignment
                    $assignmentResult = $this->assignAsDepartmentHead();
                    if ($assignmentResult['success']) {
                        $results['fixes_applied'][] = 'Created Department Head assignment for employee';
                    } else {
                        $results['fixes_applied'][] = 'Failed to create Department Head assignment: ' . implode(', ', $assignmentResult['errors']);
                        $results['success'] = false;
                    }
                } elseif (!$this->is_department_head && $details['has_active_department_head_assignments']) {
                    // Employee has assignments but not marked - remove assignments and update status
                    foreach ($details['department_head_assignments'] as $assignment) {
                        $assignmentModel = $this->officeAssignments()->find($assignment['assignment_id']);
                        if ($assignmentModel) {
                            $assignmentModel->update([
                                'is_active' => false,
                                'ended_date' => now()->toDateString(),
                                'remarks' => ($assignmentModel->remarks ?? '') . "\n\nDeactivated due to status inconsistency fix",
                            ]);
                        }
                    }
                    $results['fixes_applied'][] = 'Deactivated inconsistent Department Head assignments';
                }
            } else {
                $results['fixes_applied'][] = 'No fixes needed - status is already consistent';
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['fixes_applied'][] = 'Error during consistency fix: ' . $e->getMessage();
        }

        return $results;
    }

  }
