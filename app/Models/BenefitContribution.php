<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class BenefitContribution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'government_benefit_id',
        'contribution_year',
        'contribution_month',
        'payroll_date',
        'due_date',
        'basic_salary',
        'additional_compensation',
        'total_compensation',
        'contribution_base',
        'employee_contribution_rate',
        'employer_contribution_rate',
        'employee_contribution_amount',
        'employer_contribution_amount',
        'total_contribution_amount',
        'loan_payment_amount',
        'interest_amount',
        'penalty_amount',
        'payment_status',
        'payment_date',
        'payment_reference',
        'payment_method',
        'days_overdue',
        'late_penalty_rate',
        'late_penalty_amount',
        'interest_on_penalty',
        'is_adjustment',
        'adjustment_reason',
        'adjustment_amount',
        'adjusted_from_id',
        'remittance_status',
        'remittance_date',
        'remittance_reference',
        'remittance_amount',
        'rate_changes',
        'premium_adjustments',
        'is_compliant',
        'compliance_issues',
        'remarks',
        'last_verified_date',
        'verified_by',
        'payroll_batch_id',
        'included_in_payroll',
        'payroll_details',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'contribution_year' => 'integer',
        'contribution_month' => 'integer',
        'payroll_date' => 'date',
        'due_date' => 'date',
        'basic_salary' => 'decimal:2',
        'additional_compensation' => 'decimal:2',
        'total_compensation' => 'decimal:2',
        'contribution_base' => 'decimal:2',
        'employee_contribution_rate' => 'decimal:4',
        'employer_contribution_rate' => 'decimal:4',
        'employee_contribution_amount' => 'decimal:2',
        'employer_contribution_amount' => 'decimal:2',
        'total_contribution_amount' => 'decimal:2',
        'loan_payment_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'payment_date' => 'date',
        'days_overdue' => 'integer',
        'late_penalty_rate' => 'decimal:4',
        'late_penalty_amount' => 'decimal:2',
        'interest_on_penalty' => 'decimal:2',
        'is_adjustment' => 'boolean',
        'adjustment_amount' => 'decimal:2',
        'remittance_date' => 'date',
        'remittance_amount' => 'decimal:2',
        'rate_changes' => 'array',
        'premium_adjustments' => 'array',
        'is_compliant' => 'boolean',
        'compliance_issues' => 'array',
        'last_verified_date' => 'date',
        'included_in_payroll' => 'boolean',
        'payroll_details' => 'array',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function governmentBenefit(): BelongsTo
    {
        return $this->belongsTo(GovernmentBenefit::class);
    }

    public function adjustedFrom(): BelongsTo
    {
        return $this->belongsTo(BenefitContribution::class, 'adjusted_from_id');
    }

    public function adjustments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BenefitContribution::class, 'adjusted_from_id');
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
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('contribution_year', $year);
    }

    public function scopeForMonth(Builder $query, int $month): Builder
    {
        return $query->where('contribution_month', $month);
    }

    public function scopeForPeriod(Builder $query, int $year, int $month): Builder
    {
        return $query->where('contribution_year', $year)
                    ->where('contribution_month', $month);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('payment_status', 'overdue')
                    ->orWhere('days_overdue', '>', 0);
    }

    public function scopeNotRemitted(Builder $query): Builder
    {
        return $query->where('remittance_status', 'not_remitted');
    }

    public function scopeNonCompliant(Builder $query): Builder
    {
        return $query->where('is_compliant', false);
    }

    public function scopeByBenefitType(Builder $query, string $benefitType): Builder
    {
        return $query->whereHas('governmentBenefit', function ($q) use ($benefitType) {
            $q->where('benefit_type', $benefitType);
        });
    }

    public function scopeByPayrollBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('payroll_batch_id', $batchId);
    }

    public function scopeIncludedInPayroll(Builder $query): Builder
    {
        return $query->where('included_in_payroll', true);
    }

    public function scopeAdjustments(Builder $query): Builder
    {
        return $query->where('is_adjustment', true);
    }

    // Helper Methods

    /**
     * Calculate and update overdue penalties
     */
    public function calculateOverduePenalties(): void
    {
        if ($this->payment_status === 'paid' || !$this->due_date) {
            return;
        }

        $this->days_overdue = max(0, now()->diffInDays($this->due_date, false));

        if ($this->days_overdue > 0) {
            $this->payment_status = 'overdue';

            // Calculate penalty based on benefit type
            $penaltyCalculation = $this->calculateLatePenalty();
            $this->late_penalty_amount = $penaltyCalculation['penalty_amount'];
            $this->interest_on_penalty = $penaltyCalculation['interest_amount'];

            $this->save();
        }
    }

    /**
     * Calculate late penalty based on benefit type and days overdue
     */
    private function calculateLatePenalty(): array
    {
        $benefitType = $this->governmentBenefit->benefit_type;
        $daysOverdue = $this->days_overdue;
        $totalContribution = $this->total_contribution_amount;

        $penaltyAmount = 0;
        $interestAmount = 0;

        switch ($benefitType) {
            case 'GSIS':
                // GSIS penalty: 2% per month on unpaid contributions
                $monthsOverdue = ceil($daysOverdue / 30);
                $penaltyAmount = $totalContribution * 0.02 * $monthsOverdue;
                break;

            case 'PhilHealth':
                // PhilHealth penalty: 2.5% per month
                $monthsOverdue = ceil($daysOverdue / 30);
                $penaltyAmount = $totalContribution * 0.025 * $monthsOverdue;
                break;

            case 'Pag-IBIG':
                // Pag-IBIG penalty: varies, typically 1% per month
                $monthsOverdue = ceil($daysOverdue / 30);
                $penaltyAmount = $totalContribution * 0.01 * $monthsOverdue;
                break;

            case 'SSS':
                // SSS penalty: 3% per month on employer share
                $monthsOverdue = ceil($daysOverdue / 30);
                $penaltyAmount = $this->employer_contribution_amount * 0.03 * $monthsOverdue;
                break;
        }

        // Calculate interest on penalty if applicable
        if ($daysOverdue > 30) {
            $interestAmount = $penaltyAmount * 0.01 * ceil($daysOverdue / 30);
        }

        return [
            'penalty_amount' => round($penaltyAmount, 2),
            'interest_amount' => round($interestAmount, 2),
        ];
    }

    /**
     * Mark contribution as paid
     */
    public function markAsPaid(string $paymentReference = null, string $paymentMethod = null): void
    {
        $this->update([
            'payment_status' => 'paid',
            'payment_date' => now(),
            'payment_reference' => $paymentReference,
            'payment_method' => $paymentMethod,
            'days_overdue' => 0,
            'is_compliant' => true,
        ]);
    }

    /**
     * Create adjustment record
     */
    public function createAdjustment(float $adjustmentAmount, string $reason): BenefitContribution
    {
        return static::create([
            'employee_id' => $this->employee_id,
            'government_benefit_id' => $this->government_benefit_id,
            'contribution_year' => $this->contribution_year,
            'contribution_month' => $this->contribution_month,
            'payroll_date' => $this->payroll_date,
            'due_date' => $this->due_date,
            'basic_salary' => $this->basic_salary,
            'additional_compensation' => $this->additional_compensation,
            'total_compensation' => $this->total_compensation,
            'contribution_base' => $this->contribution_base,
            'employee_contribution_rate' => $this->employee_contribution_rate,
            'employer_contribution_rate' => $this->employer_contribution_rate,
            'employee_contribution_amount' => 0,
            'employer_contribution_amount' => 0,
            'total_contribution_amount' => $adjustmentAmount,
            'is_adjustment' => true,
            'adjustment_reason' => $reason,
            'adjustment_amount' => $adjustmentAmount,
            'adjusted_from_id' => $this->id,
            'payment_status' => 'pending',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Validate contribution data
     */
    public function validateContribution(): array
    {
        $errors = [];

        // Check if contribution matches calculation
        $expectedContribution = $this->contribution_base *
            (($this->employee_contribution_rate + $this->employer_contribution_rate) / 100);

        if (abs($this->total_contribution_amount - $expectedContribution) > 0.01) {
            $errors[] = 'Contribution amount does not match calculation';
        }

        // Check if employee and employer rates are valid
        if ($this->employee_contribution_rate < 0 || $this->employer_contribution_rate < 0) {
            $errors[] = 'Contribution rates cannot be negative';
        }

        // Check if contribution base is valid
        if ($this->contribution_base > $this->total_compensation) {
            $errors[] = 'Contribution base cannot exceed total compensation';
        }

        // Check if loan payment is valid
        if ($this->loan_payment_amount > 0 && !$this->governmentBenefit->has_active_loan) {
            $errors[] = 'Loan payment recorded but no active loan exists';
        }

        // Update compliance status
        $this->is_compliant = empty($errors);
        $this->compliance_issues = $errors;

        return $errors;
    }

    /**
     * Get contribution summary
     */
    public function getContributionSummary(): array
    {
        return [
            'period' => $this->contribution_year . '-' . str_pad($this->contribution_month, 2, '0', STR_PAD_LEFT),
            'benefit_type' => $this->governmentBenefit->benefit_type,
            'employee_name' => $this->employee->first_name . ' ' . $this->employee->last_name,
            'basic_salary' => $this->basic_salary,
            'total_compensation' => $this->total_compensation,
            'contribution_base' => $this->contribution_base,
            'employee_contribution' => $this->employee_contribution_amount,
            'employer_contribution' => $this->employer_contribution_amount,
            'total_contribution' => $this->total_contribution_amount,
            'loan_payment' => $this->loan_payment_amount,
            'penalty_amount' => $this->late_penalty_amount,
            'payment_status' => $this->payment_status,
            'remittance_status' => $this->remittance_status,
            'days_overdue' => $this->days_overdue,
            'is_compliant' => $this->is_compliant,
        ];
    }

    /**
     * Get remittance details for government submission
     */
    public function getRemittanceDetails(): array
    {
        return [
            'employee_id' => $this->employee->employee_number,
            'member_number' => $this->governmentBenefit->member_number,
            'contribution_period' => $this->contribution_year . '-' . str_pad($this->contribution_month, 2, '0', STR_PAD_LEFT),
            'salary_base' => $this->contribution_base,
            'employee_share' => $this->employee_contribution_amount,
            'employer_share' => $this->employer_contribution_amount,
            'total_contribution' => $this->total_contribution_amount,
            'loan_payment' => $this->loan_payment_amount,
            'penalty' => $this->late_penalty_amount,
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            'payment_reference' => $this->payment_reference,
        ];
    }

    /**
     * Generate contribution certificate
     */
    public function generateContributionCertificate(): array
    {
        return [
            'certificate_number' => 'CERT-' . $this->id . '-' . now()->format('Ymd'),
            'employee_details' => [
                'name' => $this->employee->first_name . ' ' . $this->employee->last_name,
                'employee_number' => $this->employee->employee_number,
                'position' => $this->employee->position,
                'department' => $this->employee->department,
            ],
            'benefit_details' => [
                'type' => $this->governmentBenefit->benefit_type,
                'member_number' => $this->governmentBenefit->member_number,
                'enrollment_date' => $this->governmentBenefit->enrollment_date?->format('Y-m-d'),
            ],
            'contribution_details' => [
                'period' => $this->contribution_year . '-' . str_pad($this->contribution_month, 2, '0', STR_PAD_LEFT),
                'salary_base' => $this->contribution_base,
                'employee_contribution' => $this->employee_contribution_amount,
                'employer_contribution' => $this->employer_contribution_amount,
                'total_contribution' => $this->total_contribution_amount,
                'payment_status' => $this->payment_status,
                'payment_date' => $this->payment_date?->format('Y-m-d'),
            ],
            'generated_date' => now()->format('Y-m-d H:i:s'),
            'generated_by' => auth()->user()?->name ?? 'System',
        ];
    }

    /**
     * Static method to calculate monthly contributions for all employees
     */
    public static function calculateMonthlyContributionsForEmployee(Employee $employee, int $year, int $month, float $basicSalary, float $additionalCompensation = 0): array
    {
        $contributions = [];
        $complianceService = app(\App\Services\GovernmentComplianceService::class);

        $governmentBenefits = $employee->governmentBenefits()->active()->get();

        foreach ($governmentBenefits as $benefit) {
            $calculation = $complianceService->calculateMonthlyContribution(
                $benefit->benefit_type,
                $basicSalary,
                $additionalCompensation,
                $benefit->employee_contribution_rate,
                $benefit->employer_contribution_rate,
                $benefit->monthly_contribution_cap
            );

            $contributions[] = [
                'government_benefit_id' => $benefit->id,
                'benefit_type' => $benefit->benefit_type,
                'contribution_base' => $calculation['contribution_base'],
                'employee_contribution' => $calculation['employee_contribution'],
                'employer_contribution' => $calculation['employer_contribution'],
                'total_contribution' => $calculation['total_contribution'],
                'loan_payment' => $benefit->monthly_loan_payment ?? 0,
            ];
        }

        return $contributions;
    }
}
