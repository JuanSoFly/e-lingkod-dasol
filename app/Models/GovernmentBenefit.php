<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class GovernmentBenefit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'benefit_type',
        'member_number',
        'enrollment_date',
        'enrollment_status',
        'coverage_type',
        'coverage_amount',
        'employee_contribution_rate',
        'employer_contribution_rate',
        'monthly_contribution_cap',
        'primary_beneficiaries',
        'secondary_beneficiaries',
        'has_active_loan',
        'loan_balance',
        'monthly_loan_payment',
        'loan_start_date',
        'loan_maturity_date',
        'loan_interest_rate',
        'active_claims_count',
        'total_claims_amount',
        'last_claim_date',
        'processing_status',
        'additional_details',
        'remarks',
        'last_verified_date',
        'verified_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'coverage_amount' => 'decimal:2',
        'employee_contribution_rate' => 'decimal:4',
        'employer_contribution_rate' => 'decimal:4',
        'monthly_contribution_cap' => 'decimal:2',
        'primary_beneficiaries' => 'array',
        'secondary_beneficiaries' => 'array',
        'has_active_loan' => 'boolean',
        'loan_balance' => 'decimal:2',
        'monthly_loan_payment' => 'decimal:2',
        'loan_start_date' => 'date',
        'loan_maturity_date' => 'date',
        'loan_interest_rate' => 'decimal:4',
        'active_claims_count' => 'integer',
        'total_claims_amount' => 'decimal:2',
        'last_claim_date' => 'date',
        'additional_details' => 'array',
        'last_verified_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function benefitContributions(): HasMany
    {
        return $this->hasMany(BenefitContribution::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('enrollment_status', 'active');
    }

    public function scopeByBenefitType(Builder $query, string $benefitType): Builder
    {
        return $query->where('benefit_type', $benefitType);
    }

    public function scopeWithActiveLoans(Builder $query): Builder
    {
        return $query->where('has_active_loan', true)
                    ->where('loan_balance', '>', 0);
    }

    public function scopeRequiringVerification(Builder $query): Builder
    {
        return $query->where('processing_status', 'requires_verification')
                    ->orWhere('last_verified_date', '<', now()->subMonths(6));
    }

    public function scopeOverdueVerification(Builder $query): Builder
    {
        return $query->whereNull('last_verified_date')
                    ->orWhere('last_verified_date', '<', now()->subYear());
    }

    // Helper Methods

    /**
     * Calculate monthly contribution based on salary and benefit type
     */
    public function calculateMonthlyContribution(float $basicSalary, float $additionalCompensation = 0): array
    {
        $totalCompensation = $basicSalary + $additionalCompensation;
        $contributionBase = $this->getContributionBase($totalCompensation);
        
        $employeeContribution = $contributionBase * ($this->employee_contribution_rate / 100);
        $employerContribution = $contributionBase * ($this->employer_contribution_rate / 100);
        
        // Apply monthly cap if set
        if ($this->monthly_contribution_cap) {
            $employeeContribution = min($employeeContribution, $this->monthly_contribution_cap);
            $employerContribution = min($employerContribution, $this->monthly_contribution_cap);
        }
        
        return [
            'contribution_base' => $contributionBase,
            'employee_contribution' => round($employeeContribution, 2),
            'employer_contribution' => round($employerContribution, 2),
            'total_contribution' => round($employeeContribution + $employerContribution, 2),
        ];
    }

    /**
     * Get contribution base amount based on benefit type and salary
     */
    private function getContributionBase(float $totalCompensation): float
    {
        // Apply benefit-specific salary caps and rules
        switch ($this->benefit_type) {
            case 'GSIS':
                // GSIS typically has no salary cap for contributions
                return $totalCompensation;
                
            case 'PhilHealth':
                // PhilHealth has premium contribution brackets
                return min($totalCompensation, $this->getPhilHealthSalaryCap());
                
            case 'Pag-IBIG':
                // Pag-IBIG has specific salary caps
                return min($totalCompensation, $this->getPagIbigSalaryCap());
                
            case 'SSS':
                // SSS has contribution brackets
                return min($totalCompensation, $this->getSssSalaryCap());
                
            default:
                return $totalCompensation;
        }
    }

    /**
     * Get PhilHealth salary cap for contribution calculation
     */
    private function getPhilHealthSalaryCap(): float
    {
        // 2024 PhilHealth premium contribution cap
        return 80000; // Monthly salary cap
    }

    /**
     * Get Pag-IBIG salary cap for contribution calculation
     */
    private function getPagIbigSalaryCap(): float
    {
        // 2024 Pag-IBIG contribution cap
        return 5000; // Monthly salary cap for 2% rate
    }

    /**
     * Get SSS salary cap for contribution calculation
     */
    private function getSssSalaryCap(): float
    {
        // 2024 SSS contribution cap
        return 25000; // Monthly salary cap
    }

    /**
     * Check if employee is eligible for specific benefit
     */
    public function isEligibleForBenefit(): bool
    {
        if (!$this->employee) {
            return false;
        }

        switch ($this->benefit_type) {
            case 'GSIS':
                return $this->isEligibleForGsis();
            case 'PhilHealth':
                return $this->isEligibleForPhilHealth();
            case 'Pag-IBIG':
                return $this->isEligibleForPagIbig();
            case 'SSS':
                return $this->isEligibleForSss();
            default:
                return false;
        }
    }

    /**
     * Check GSIS eligibility (government employees)
     */
    private function isEligibleForGsis(): bool
    {
        return in_array($this->employee->employment_status, [
            'permanent',
            'temporary',
            'contractual',
            'casual'
        ]);
    }

    /**
     * Check PhilHealth eligibility (universal coverage)
     */
    private function isEligibleForPhilHealth(): bool
    {
        // PhilHealth has universal coverage - all employees eligible
        return true;
    }

    /**
     * Check Pag-IBIG eligibility
     */
    private function isEligibleForPagIbig(): bool
    {
        // All employees with compensation are eligible
        return $this->employee->employment_status !== 'terminated';
    }

    /**
     * Check SSS eligibility (private sector or contractual)
     */
    private function isEligibleForSss(): bool
    {
        // For government agencies, SSS typically for contractual/casual employees
        return in_array($this->employee->employment_status, [
            'contractual',
            'casual',
            'temporary'
        ]);
    }

    /**
     * Get loan payment schedule
     */
    public function getLoanPaymentSchedule(): array
    {
        if (!$this->has_active_loan || !$this->loan_start_date) {
            return [];
        }

        $schedule = [];
        $currentDate = $this->loan_start_date->copy();
        $remainingBalance = $this->loan_balance;
        $monthlyPayment = $this->monthly_loan_payment;
        
        while ($remainingBalance > 0 && $currentDate->lte($this->loan_maturity_date)) {
            $interestPayment = $remainingBalance * ($this->loan_interest_rate / 100 / 12);
            $principalPayment = min($monthlyPayment - $interestPayment, $remainingBalance);
            
            $schedule[] = [
                'payment_date' => $currentDate->copy(),
                'principal_amount' => round($principalPayment, 2),
                'interest_amount' => round($interestPayment, 2),
                'total_payment' => round($principalPayment + $interestPayment, 2),
                'remaining_balance' => round($remainingBalance - $principalPayment, 2),
            ];
            
            $remainingBalance -= $principalPayment;
            $currentDate->addMonth();
        }
        
        return $schedule;
    }

    /**
     * Update contribution rates based on current government regulations
     */
    public function updateContributionRates(): void
    {
        $rates = $this->getCurrentContributionRates();
        
        $this->update([
            'employee_contribution_rate' => $rates['employee_rate'],
            'employer_contribution_rate' => $rates['employer_rate'],
            'monthly_contribution_cap' => $rates['monthly_cap'] ?? null,
        ]);
    }

    /**
     * Get current contribution rates for the benefit type
     */
    private function getCurrentContributionRates(): array
    {
        // Return current rates based on benefit type
        // These should be updated annually or as regulations change
        
        switch ($this->benefit_type) {
            case 'GSIS':
                return [
                    'employee_rate' => 9.00, // 9% employee share
                    'employer_rate' => 12.00, // 12% employer share
                ];
                
            case 'PhilHealth':
                return [
                    'employee_rate' => 2.75, // 2.75% employee share
                    'employer_rate' => 2.75, // 2.75% employer share
                    'monthly_cap' => 2200.00, // Maximum monthly premium
                ];
                
            case 'Pag-IBIG':
                return [
                    'employee_rate' => 2.00, // 2% employee share
                    'employer_rate' => 2.00, // 2% employer share
                    'monthly_cap' => 100.00, // Maximum monthly contribution
                ];
                
            case 'SSS':
                return [
                    'employee_rate' => 4.5, // 4.5% employee share
                    'employer_rate' => 8.5, // 8.5% employer share (includes EC)
                    'monthly_cap' => 1125.00, // Maximum monthly contribution
                ];
                
            default:
                return [
                    'employee_rate' => 0,
                    'employer_rate' => 0,
                ];
        }
    }

    /**
     * Get benefit summary for dashboard
     */
    public function getBenefitSummary(): array
    {
        return [
            'benefit_type' => $this->benefit_type,
            'member_number' => $this->member_number,
            'enrollment_status' => $this->enrollment_status,
            'has_active_loan' => $this->has_active_loan,
            'loan_balance' => $this->loan_balance,
            'monthly_loan_payment' => $this->monthly_loan_payment,
            'active_claims' => $this->active_claims_count,
            'last_contribution' => $this->benefitContributions()->latest('payroll_date')->first()?->payroll_date,
            'total_contributions_ytd' => $this->getTotalContributionsYTD(),
            'compliance_status' => $this->getComplianceStatus(),
        ];
    }

    /**
     * Get total contributions for the current year
     */
    public function getTotalContributionsYTD(): float
    {
        return $this->benefitContributions()
            ->where('contribution_year', now()->year)
            ->sum('total_contribution_amount');
    }

    /**
     * Get compliance status
     */
    public function getComplianceStatus(): string
    {
        $overdueContributions = $this->benefitContributions()
            ->where('payment_status', 'overdue')
            ->count();
            
        if ($overdueContributions > 0) {
            return 'non_compliant';
        }
        
        if ($this->processing_status === 'requires_verification') {
            return 'requires_attention';
        }
        
        return 'compliant';
    }
}