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
     * Get benefit summary for dashboard
     */
    public function getBenefitSummary(): array
    {
        // Ideally this should also be moved to a View Service or Presenter, but keeping it here for now as it's view-related.
        // We will need to inject the service if we want to use getComplianceStatus here, or we can just keep the logic minimal.
        // For now, I'll instantiate the service or use the App container to get it effectively.
        // However, making Models depend on Services is bad practice.
        // I will simplify this method to only return data it has, and let the Controller/Service assemble the full summary.
        
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
            // 'compliance_status' => ... // Removed, should be calculated by service
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
}