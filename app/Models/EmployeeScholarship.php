<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class EmployeeScholarship extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'scholarship_program_id',
        'application_date',
        'application_reference',
        'approval_status',
        'application_notes',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'agency_approver',
        'agency_approval_date',
        'approval_conditions',
        'approval_document_ref',
        'start_date',
        'expected_completion',
        'actual_completion',
        'institution_name',
        'institution_country',
        'course_title',
        'degree_program',
        'major_field',
        'minor_field',
        'academic_status',
        'gpa',
        'current_year_level',
        'units_completed',
        'total_units_required',
        'completion_percentage',
        'thesis_title',
        'thesis_abstract',
        'thesis_advisor',
        'thesis_status',
        'thesis_defense_date',
        'research_contributions',
        'total_scholarship_amount',
        'amount_received',
        'amount_remaining',
        'monthly_stipend',
        'tuition_covered',
        'other_allowances',
        'financial_breakdown',
        'service_obligation_years',
        'bond_amount',
        'service_start_date',
        'service_end_date',
        'compliance_status',
        'service_years_completed',
        'service_years_remaining',
        'bond_balance',
        'academic_achievements',
        'awards_recognition',
        'publications',
        'conferences_attended',
        'skills_developed',
        'knowledge_application',
        'last_report_submitted',
        'next_report_due',
        'reports_submitted',
        'reports_overdue',
        'latest_report_summary',
        'reporting_compliance',
        'hr_officer',
        'hr_notes',
        'scholarship_coordinator',
        'coordinator_notes',
        'record_status',
        'visa_status',
        'visa_expiry',
        'passport_number',
        'passport_expiry',
        'travel_documents',
        'forex_allowance',
        'embassy_contact',
        'graduation_date',
        'final_grade',
        'honors_received',
        'graduation_requirements_met',
        'diploma_status',
        'post_study_career_plan',
        'contribution_to_organization',
        'required_documents',
        'submitted_documents',
        'missing_documents',
        'document_notes',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'emergency_contact_email',
        'support_services_needed',
        'special_accommodations',
    ];

    protected $casts = [
        'application_date' => 'date',
        'approved_at' => 'datetime',
        'agency_approval_date' => 'datetime',
        'start_date' => 'date',
        'expected_completion' => 'date',
        'actual_completion' => 'date',
        'thesis_defense_date' => 'date',
        'service_start_date' => 'date',
        'service_end_date' => 'date',
        'last_report_submitted' => 'date',
        'next_report_due' => 'date',
        'visa_expiry' => 'date',
        'passport_expiry' => 'date',
        'graduation_date' => 'date',
        'gpa' => 'decimal:2',
        'completion_percentage' => 'decimal:2',
        'total_scholarship_amount' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'amount_remaining' => 'decimal:2',
        'monthly_stipend' => 'decimal:2',
        'tuition_covered' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'bond_amount' => 'decimal:2',
        'bond_balance' => 'decimal:2',
        'forex_allowance' => 'decimal:2',
        'required_documents' => 'array',
        'submitted_documents' => 'array',
        'missing_documents' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scholarshipProgram(): BelongsTo
    {
        return $this->belongsTo(ScholarshipProgram::class);
    }

    public function documentLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'source');
    }

    /**
     * Scopes
     */
    public function scopeByEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByScholarshipProgram(Builder $query, int $programId): Builder
    {
        return $query->where('scholarship_program_id', $programId);
    }

    public function scopeByApprovalStatus(Builder $query, string $status): Builder
    {
        return $query->where('approval_status', $status);
    }

    public function scopeByAcademicStatus(Builder $query, string $status): Builder
    {
        return $query->where('academic_status', $status);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->whereIn('approval_status', ['Approved', 'Conditionally Approved']);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('academic_status', ['Enrolled', 'In Progress']);
    }

    public function scopeGraduated(Builder $query): Builder
    {
        return $query->where('academic_status', 'Graduated');
    }

    public function scopeDropped(Builder $query): Builder
    {
        return $query->whereIn('academic_status', ['Dropped', 'Terminated']);
    }

    public function scopeInternational(Builder $query): Builder
    {
        return $query->where('institution_country', '!=', 'Philippines');
    }

    public function scopeLocal(Builder $query): Builder
    {
        return $query->where('institution_country', 'Philippines');
    }

    public function scopeOverdueReports(Builder $query): Builder
    {
        return $query->where('next_report_due', '<', now()->toDateString())
            ->where('reporting_compliance', '!=', 'Not Required');
    }

    public function scopeServiceCompliant(Builder $query): Builder
    {
        return $query->where('compliance_status', 'In Compliance');
    }

    public function scopeServiceNonCompliant(Builder $query): Builder
    {
        return $query->where('compliance_status', 'Non-Compliant');
    }

    public function scopeExpiringSoon(Builder $query, string $field = 'visa_expiry', int $days = 30): Builder
    {
        $cutoffDate = now()->addDays($days)->toDateString();
        return $query->whereNotNull($field)
            ->where($field, '<=', $cutoffDate)
            ->where($field, '>=', now()->toDateString());
    }

    /**
     * Business Logic Methods
     */

    /**
     * Check if scholarship is currently active
     */
    public function isActive(): bool
    {
        return in_array($this->academic_status, ['Enrolled', 'In Progress']);
    }

    /**
     * Check if scholar has graduated
     */
    public function hasGraduated(): bool
    {
        return $this->academic_status === 'Graduated';
    }

    /**
     * Check if scholarship was terminated
     */
    public function isTerminated(): bool
    {
        return in_array($this->academic_status, ['Dropped', 'Terminated']);
    }

    /**
     * Check if application is approved
     */
    public function isApproved(): bool
    {
        return in_array($this->approval_status, ['Approved', 'Conditionally Approved']);
    }

    /**
     * Check if reports are overdue
     */
    public function hasOverdueReports(): bool
    {
        return $this->next_report_due && 
               $this->next_report_due < now() && 
               $this->reporting_compliance !== 'Not Required';
    }

    /**
     * Check if service obligation is compliant
     */
    public function isServiceCompliant(): bool
    {
        return $this->compliance_status === 'In Compliance';
    }

    /**
     * Get academic progress summary
     */
    public function getAcademicProgress(): array
    {
        $progressPercentage = 0;

        if ($this->total_units_required && $this->units_completed) {
            $progressPercentage = ($this->units_completed / $this->total_units_required) * 100;
        } elseif ($this->completion_percentage) {
            $progressPercentage = $this->completion_percentage;
        }

        return [
            'academic_status' => $this->academic_status,
            'current_year_level' => $this->current_year_level,
            'gpa' => $this->gpa,
            'units_completed' => $this->units_completed,
            'total_units_required' => $this->total_units_required,
            'progress_percentage' => round($progressPercentage, 2),
            'thesis_status' => $this->thesis_status,
            'expected_completion' => $this->expected_completion,
            'is_on_track' => $this->isOnTrack(),
        ];
    }

    /**
     * Check if scholar is on track for completion
     */
    public function isOnTrack(): bool
    {
        if (!$this->expected_completion || !$this->start_date) {
            return true; // Cannot determine without dates
        }

        $totalDuration = $this->start_date->diffInMonths($this->expected_completion);
        $elapsed = $this->start_date->diffInMonths(now());
        $expectedProgress = $elapsed / $totalDuration * 100;

        $actualProgress = $this->completion_percentage ?? 0;
        if ($this->total_units_required && $this->units_completed) {
            $actualProgress = ($this->units_completed / $this->total_units_required) * 100;
        }

        // Consider on track if within 10% of expected progress
        return $actualProgress >= ($expectedProgress - 10);
    }

    /**
     * Get financial summary
     */
    public function getFinancialSummary(): array
    {
        $disbursementRate = $this->total_scholarship_amount > 0 
            ? ($this->amount_received / $this->total_scholarship_amount) * 100 
            : 0;

        return [
            'total_scholarship_amount' => $this->total_scholarship_amount,
            'amount_received' => $this->amount_received,
            'amount_remaining' => $this->amount_remaining,
            'disbursement_rate' => round($disbursementRate, 2),
            'monthly_stipend' => $this->monthly_stipend,
            'tuition_covered' => $this->tuition_covered,
            'other_allowances' => $this->other_allowances,
            'financial_breakdown' => $this->financial_breakdown,
            'estimated_monthly_expenses' => $this->estimateMonthlyExpenses(),
        ];
    }

    /**
     * Estimate monthly expenses
     */
    private function estimateMonthlyExpenses(): float
    {
        $monthly = 0;
        
        if ($this->monthly_stipend) {
            $monthly += $this->monthly_stipend;
        }
        
        if ($this->other_allowances) {
            $monthly += $this->other_allowances;
        }
        
        // Add estimated monthly tuition if covered
        if ($this->tuition_covered && $this->total_scholarship_amount) {
            $durationMonths = $this->start_date && $this->expected_completion 
                ? $this->start_date->diffInMonths($this->expected_completion)
                : 12; // Default to 1 year
                
            $monthly += $this->tuition_covered / $durationMonths;
        }
        
        return $monthly;
    }

    /**
     * Get service obligation summary
     */
    public function getServiceObligationSummary(): array
    {
        $remainingYears = 0;
        $remainingAmount = $this->bond_balance ?? $this->bond_amount ?? 0;

        if ($this->service_obligation_years && $this->service_years_completed) {
            $remainingYears = max(0, $this->service_obligation_years - $this->service_years_completed);
        }

        return [
            'service_obligation_years' => $this->service_obligation_years,
            'service_years_completed' => $this->service_years_completed,
            'service_years_remaining' => $remainingYears,
            'compliance_status' => $this->compliance_status,
            'bond_amount' => $this->bond_amount,
            'bond_balance' => $remainingAmount,
            'service_start_date' => $this->service_start_date,
            'service_end_date' => $this->service_end_date,
            'completion_percentage' => $this->service_obligation_years > 0 
                ? round(($this->service_years_completed / $this->service_obligation_years) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Get reporting compliance status
     */
    public function getReportingStatus(): array
    {
        $status = 'Compliant';
        $issues = [];

        if ($this->hasOverdueReports()) {
            $status = 'Overdue';
            $daysOverdue = now()->diffInDays($this->next_report_due);
            $issues[] = "Report overdue by {$daysOverdue} days";
        }

        if ($this->reports_overdue > 0) {
            $status = 'Delayed';
            $issues[] = "{$this->reports_overdue} reports previously overdue";
        }

        return [
            'status' => $status,
            'compliance' => $this->reporting_compliance,
            'last_report_submitted' => $this->last_report_submitted,
            'next_report_due' => $this->next_report_due,
            'reports_submitted' => $this->reports_submitted,
            'reports_overdue' => $this->reports_overdue,
            'issues' => $issues,
            'days_until_next_report' => $this->next_report_due 
                ? now()->diffInDays($this->next_report_due, false) 
                : null,
        ];
    }

    /**
     * Get document compliance status
     */
    public function getDocumentCompliance(): array
    {
        $requiredDocs = $this->required_documents ?? [];
        $submittedDocs = $this->submitted_documents ?? [];
        $missingDocs = $this->missing_documents ?? [];

        $totalRequired = count($requiredDocs);
        $totalSubmitted = count($submittedDocs);
        $totalMissing = count($missingDocs);

        $complianceRate = $totalRequired > 0 ? ($totalSubmitted / $totalRequired) * 100 : 100;

        return [
            'total_required' => $totalRequired,
            'total_submitted' => $totalSubmitted,
            'total_missing' => $totalMissing,
            'compliance_rate' => round($complianceRate, 2),
            'is_compliant' => $totalMissing === 0,
            'required_documents' => $requiredDocs,
            'submitted_documents' => $submittedDocs,
            'missing_documents' => $missingDocs,
        ];
    }

    /**
     * Get international study specifics (if applicable)
     */
    public function getInternationalStudyInfo(): ?array
    {
        if ($this->institution_country === 'Philippines') {
            return null;
        }

        return [
            'institution_country' => $this->institution_country,
            'visa_status' => $this->visa_status,
            'visa_expiry' => $this->visa_expiry,
            'visa_expires_soon' => $this->visa_expiry && $this->visa_expiry <= now()->addDays(30),
            'passport_number' => $this->passport_number,
            'passport_expiry' => $this->passport_expiry,
            'passport_expires_soon' => $this->passport_expiry && $this->passport_expiry <= now()->addDays(60),
            'forex_allowance' => $this->forex_allowance,
            'embassy_contact' => $this->embassy_contact,
            'travel_documents' => $this->travel_documents,
        ];
    }

    /**
     * Calculate scholarship ROI (Return on Investment)
     */
    public function calculateROI(): array
    {
        $investment = $this->total_scholarship_amount;
        $estimatedBenefits = 0;

        if ($this->hasGraduated()) {
            // Estimate benefits based on degree level and performance
            $degreeLevel = $this->scholarshipProgram->degree_level ?? 'Bachelor Degree';
            $baseValue = match ($degreeLevel) {
                'Certificate' => 50000,
                'Diploma' => 100000,
                'Associate Degree' => 200000,
                'Bachelor Degree' => 500000,
                'Master Degree' => 1000000,
                'Doctoral Degree' => 2000000,
                'Post-Doctoral' => 1500000,
                default => 500000,
            };

            // Adjust based on GPA
            if ($this->gpa) {
                $gpaMultiplier = $this->gpa / 4.0; // Assuming 4.0 scale
                $estimatedBenefits = $baseValue * $gpaMultiplier;
            } else {
                $estimatedBenefits = $baseValue * 0.8; // Assume average performance
            }

            // Add value for publications, awards, etc.
            if ($this->publications) {
                $estimatedBenefits += 100000; // Arbitrary value for research contributions
            }
        }

        $roi = $investment > 0 ? (($estimatedBenefits - $investment) / $investment) * 100 : 0;

        return [
            'total_investment' => $investment,
            'estimated_benefits' => $estimatedBenefits,
            'roi_percentage' => round($roi, 2),
            'is_profitable' => $roi > 0,
            'payback_period_years' => $estimatedBenefits > 0 ? $investment / ($estimatedBenefits / 10) : null, // Assume 10-year benefit period
        ];
    }

    /**
     * Get scholarship timeline
     */
    public function getScholarshipTimeline(): array
    {
        $timeline = [];

        // Application
        $timeline[] = [
            'date' => $this->application_date,
            'event' => 'Application Submitted',
            'status' => $this->approval_status,
            'description' => 'Scholarship application submitted',
        ];

        // Approval
        if ($this->approved_at) {
            $timeline[] = [
                'date' => $this->approved_at->toDateString(),
                'event' => 'Application Approved',
                'status' => 'Approved',
                'description' => "Approved by {$this->approved_by}",
            ];
        }

        // Study start
        if ($this->start_date) {
            $timeline[] = [
                'date' => $this->start_date,
                'event' => 'Study Program Started',
                'status' => 'Active',
                'description' => "Started {$this->degree_program} at {$this->institution_name}",
            ];
        }

        // Thesis defense
        if ($this->thesis_defense_date) {
            $timeline[] = [
                'date' => $this->thesis_defense_date,
                'event' => 'Thesis Defense',
                'status' => 'Academic',
                'description' => "Defended thesis: {$this->thesis_title}",
            ];
        }

        // Graduation
        if ($this->graduation_date) {
            $timeline[] = [
                'date' => $this->graduation_date,
                'event' => 'Graduation',
                'status' => 'Completed',
                'description' => "Graduated with {$this->final_grade}",
            ];
        }

        // Service obligation start
        if ($this->service_start_date) {
            $timeline[] = [
                'date' => $this->service_start_date,
                'event' => 'Service Obligation Started',
                'status' => 'Service',
                'description' => "Started {$this->service_obligation_years}-year service obligation",
            ];
        }

        // Sort by date
        usort($timeline, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        return $timeline;
    }

    /**
     * Update academic progress
     */
    public function updateAcademicProgress(array $progressData): bool
    {
        if (isset($progressData['units_completed'])) {
            $this->units_completed = $progressData['units_completed'];
            
            if ($this->total_units_required) {
                $this->completion_percentage = ($this->units_completed / $this->total_units_required) * 100;
            }
        }

        if (isset($progressData['gpa'])) {
            $this->gpa = $progressData['gpa'];
        }

        if (isset($progressData['current_year_level'])) {
            $this->current_year_level = $progressData['current_year_level'];
        }

        if (isset($progressData['thesis_status'])) {
            $this->thesis_status = $progressData['thesis_status'];
        }

        // Auto-update academic status based on progress
        if ($this->completion_percentage >= 100) {
            $this->academic_status = 'Completed';
        } elseif ($this->completion_percentage > 0) {
            $this->academic_status = 'In Progress';
        }

        return $this->save();
    }

    /**
     * Update service obligation compliance
     */
    public function updateServiceCompliance(): bool
    {
        if (!$this->service_start_date || !$this->service_obligation_years) {
            return true;
        }

        $monthsServed = $this->service_start_date->diffInMonths(now());
        $this->service_years_completed = floor($monthsServed / 12);
        $this->service_years_remaining = max(0, $this->service_obligation_years - $this->service_years_completed);

        // Update compliance status
        if ($this->service_years_completed >= $this->service_obligation_years) {
            $this->compliance_status = 'In Compliance';
            $this->bond_balance = 0;
        } else {
            $remainingRatio = $this->service_years_remaining / $this->service_obligation_years;
            $this->bond_balance = ($this->bond_amount ?? 0) * $remainingRatio;
        }

        return $this->save();
    }
}