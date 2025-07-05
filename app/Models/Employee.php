<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'birth_date',
        'gender',
        'civil_status',
        'address',
        'contact_number',
        'email',
        'position',
        'department',
        'employment_status',
        'date_hired',
        'salary_grade',
        'step_increment',
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
    ];

    protected $casts = [
        'birth_date' => 'date',
        'date_hired' => 'date',
        'appointment_date' => 'date',
        'separation_date' => 'date',
        'csc_eligibility_date' => 'date',
        'latest_performance_date' => 'date',
        'last_promotion_date' => 'date',
        'last_attendance_date' => 'date',
        'awol_start_date' => 'date',
        'latest_performance_rating' => 'decimal:2',
        'training_hours_ytd' => 'integer',
        'consecutive_absent_days' => 'integer',
        'is_awol' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
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

    public function trainings()
    {
        return $this->belongsToMany(Training::class, 'training_participants')
                    ->withPivot('completion_status', 'completion_date', 'satisfaction_rating', 'feedback')
                    ->withTimestamps();
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
        
        if (in_array($this->employment_status, ['permanent', 'temporary', 'contractual', 'casual'])) {
            if ($this->employment_status === 'permanent' || $this->employment_status === 'temporary') {
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
        
        return $this->birth_date->diffInYears(now());
    }

    /**
     * Get years of service (tenure)
     */
    public function getTenureYearsAttribute()
    {
        if (!$this->date_hired) {
            return null;
        }
        
        return $this->date_hired->diffInYears(now());
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
            ->wherePivot('completion_status', 'completed')
            ->whereYear('start_date', now()->year)
            ->sum('duration_hours');
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
            ->sum('cost') ?? 0;
        
        return $annualSalary + $benefitsCost + $trainingCost;
    }

    /**
     * Get department headcount for analytics
     */
    public static function getDepartmentHeadcount()
    {
        return self::where('employment_status', 'active')
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
        return $query->where('employment_status', 'active');
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
        return $query->whereRaw('YEAR(CURDATE()) - YEAR(birth_date) BETWEEN ? AND ?', [$minAge, $maxAge]);
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
            return $query->where('employment_status', 'active');
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
        return $query->forUser(auth()->user());
    }
}