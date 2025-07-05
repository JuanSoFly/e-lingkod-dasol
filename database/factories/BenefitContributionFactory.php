<?php

namespace Database\Factories;

use App\Models\BenefitContribution;
use App\Models\Employee;
use App\Models\GovernmentBenefit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BenefitContribution>
 */
class BenefitContributionFactory extends Factory
{
    protected $model = BenefitContribution::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = $this->faker->numberBetween(2023, 2025);
        $month = $this->faker->numberBetween(1, 12);
        $payrollDate = Carbon::create($year, $month, $this->faker->numberBetween(15, 30));
        $dueDate = $payrollDate->copy()->addDays(15);
        
        $basicSalary = $this->faker->randomFloat(2, 15000, 80000);
        $additionalCompensation = $this->faker->randomFloat(2, 0, 20000);
        $totalCompensation = $basicSalary + $additionalCompensation;
        $contributionBase = min($totalCompensation, $this->faker->randomFloat(2, $totalCompensation * 0.8, $totalCompensation));
        
        $employeeRate = $this->faker->randomFloat(4, 2.0, 9.0);
        $employerRate = $this->faker->randomFloat(4, 2.0, 12.0);
        
        $employeeContribution = $contributionBase * ($employeeRate / 100);
        $employerContribution = $contributionBase * ($employerRate / 100);
        $totalContribution = $employeeContribution + $employerContribution;
        
        $paymentStatus = $this->faker->randomElement(['pending', 'paid', 'overdue', 'partial']);
        $daysOverdue = $paymentStatus === 'overdue' ? $this->faker->numberBetween(1, 180) : 0;
        
        return [
            'employee_id' => Employee::factory(),
            'government_benefit_id' => GovernmentBenefit::factory(),
            'contribution_year' => $year,
            'contribution_month' => $month,
            'payroll_date' => $payrollDate,
            'due_date' => $dueDate,
            'basic_salary' => $basicSalary,
            'additional_compensation' => $additionalCompensation,
            'total_compensation' => $totalCompensation,
            'contribution_base' => $contributionBase,
            'employee_contribution_rate' => $employeeRate,
            'employer_contribution_rate' => $employerRate,
            'employee_contribution_amount' => round($employeeContribution, 2),
            'employer_contribution_amount' => round($employerContribution, 2),
            'total_contribution_amount' => round($totalContribution, 2),
            'loan_payment_amount' => $this->faker->randomFloat(2, 0, 5000),
            'interest_amount' => $this->faker->randomFloat(2, 0, 100),
            'penalty_amount' => $this->faker->randomFloat(2, 0, 200),
            'payment_status' => $paymentStatus,
            'payment_date' => $paymentStatus === 'paid' ? $this->faker->dateTimeBetween($payrollDate, $dueDate->copy()->addDays(30)) : null,
            'payment_reference' => $paymentStatus === 'paid' ? 'REF-' . $this->faker->numerify('##########') : null,
            'payment_method' => $paymentStatus === 'paid' ? $this->faker->randomElement(['payroll_deduction', 'bank_transfer', 'check']) : null,
            'days_overdue' => $daysOverdue,
            'late_penalty_rate' => $daysOverdue > 0 ? $this->faker->randomFloat(4, 1.0, 3.0) : 0,
            'late_penalty_amount' => $daysOverdue > 0 ? $this->faker->randomFloat(2, 50, 500) : 0,
            'interest_on_penalty' => $daysOverdue > 30 ? $this->faker->randomFloat(2, 10, 100) : 0,
            'is_adjustment' => $this->faker->boolean(10), // 10% chance of being an adjustment
            'adjustment_reason' => function (array $attributes) {
                return $attributes['is_adjustment'] ? $this->faker->randomElement([
                    'Rate correction',
                    'Salary adjustment',
                    'Previous period correction',
                    'Manual override',
                ]) : null;
            },
            'adjustment_amount' => function (array $attributes) {
                return $attributes['is_adjustment'] ? $this->faker->randomFloat(2, -1000, 1000) : 0;
            },
            'remittance_status' => $this->faker->randomElement(['not_remitted', 'remitted', 'partial_remitted']),
            'remittance_date' => function (array $attributes) {
                return $attributes['remittance_status'] === 'remitted' ? 
                    $this->faker->dateTimeBetween($attributes['due_date'], $attributes['due_date']->copy()->addDays(45)) : null;
            },
            'remittance_reference' => function (array $attributes) {
                return $attributes['remittance_status'] === 'remitted' ? 
                    'REM-' . $this->faker->numerify('##########') : null;
            },
            'remittance_amount' => function (array $attributes) {
                return $attributes['remittance_status'] === 'remitted' ? 
                    $attributes['total_contribution_amount'] : null;
            },
            'rate_changes' => $this->faker->optional(0.1)->randomElements([
                ['field' => 'employee_rate', 'old_value' => 2.5, 'new_value' => 2.75, 'effective_date' => '2024-01-01'],
            ]),
            'premium_adjustments' => $this->faker->optional(0.05)->randomElements([
                ['type' => 'salary_cap_adjustment', 'amount' => 5000, 'reason' => 'Annual adjustment'],
            ]),
            'is_compliant' => $this->faker->boolean(85), // 85% compliance rate
            'compliance_issues' => function (array $attributes) {
                return !$attributes['is_compliant'] ? [
                    $this->faker->randomElement([
                        'Late payment',
                        'Incorrect calculation',
                        'Missing documentation',
                        'Rate mismatch',
                    ])
                ] : null;
            },
            'remarks' => $this->faker->optional(0.2)->sentence(),
            'last_verified_date' => $this->faker->optional(0.7)->dateTimeBetween('-3 months', 'now'),
            'verified_by' => $this->faker->optional(0.7)->randomElement(User::pluck('id')->toArray() ?: [1]),
            'payroll_batch_id' => 'BATCH-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . $this->faker->numerify('###'),
            'included_in_payroll' => $this->faker->boolean(90), // 90% included in payroll
            'payroll_details' => [
                'payroll_period' => $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT),
                'payroll_type' => $this->faker->randomElement(['regular', 'supplemental', 'bonus']),
                'processed_by' => $this->faker->name(),
                'processing_date' => $payrollDate->format('Y-m-d'),
            ],
            'created_by' => $this->faker->optional(0.9)->randomElement(User::pluck('id')->toArray() ?: [1]),
            'updated_by' => $this->faker->optional(0.7)->randomElement(User::pluck('id')->toArray() ?: [1]),
        ];
    }

    /**
     * State for current year contributions
     */
    public function currentYear(): static
    {
        return $this->state(fn (array $attributes) => [
            'contribution_year' => now()->year,
            'contribution_month' => $this->faker->numberBetween(1, now()->month),
        ]);
    }

    /**
     * State for current month contribution
     */
    public function currentMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'contribution_year' => now()->year,
            'contribution_month' => now()->month,
            'payroll_date' => now()->startOfMonth()->addDays($this->faker->numberBetween(15, 30)),
        ]);
    }

    /**
     * State for paid contributions
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'payment_date' => $this->faker->dateTimeBetween($attributes['payroll_date'], $attributes['due_date']->copy()->addDays(15)),
            'payment_reference' => 'PAY-' . $this->faker->numerify('##########'),
            'payment_method' => $this->faker->randomElement(['payroll_deduction', 'bank_transfer']),
            'days_overdue' => 0,
            'late_penalty_amount' => 0,
            'interest_on_penalty' => 0,
            'is_compliant' => true,
            'compliance_issues' => null,
            'remittance_status' => 'remitted',
            'remittance_date' => $this->faker->dateTimeBetween($attributes['due_date'], $attributes['due_date']->copy()->addDays(30)),
            'remittance_reference' => 'REM-' . $this->faker->numerify('##########'),
            'remittance_amount' => $attributes['total_contribution_amount'],
        ]);
    }

    /**
     * State for overdue contributions
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $daysOverdue = $this->faker->numberBetween(15, 120);
            $penaltyAmount = $attributes['total_contribution_amount'] * 0.02 * ceil($daysOverdue / 30);
            
            return [
                'payment_status' => 'overdue',
                'payment_date' => null,
                'payment_reference' => null,
                'payment_method' => null,
                'days_overdue' => $daysOverdue,
                'late_penalty_rate' => 2.0,
                'late_penalty_amount' => round($penaltyAmount, 2),
                'interest_on_penalty' => $daysOverdue > 30 ? round($penaltyAmount * 0.01, 2) : 0,
                'is_compliant' => false,
                'compliance_issues' => ['Payment overdue'],
                'remittance_status' => 'not_remitted',
                'remittance_date' => null,
                'remittance_reference' => null,
                'remittance_amount' => null,
            ];
        });
    }

    /**
     * State for pending contributions
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'pending',
            'payment_date' => null,
            'payment_reference' => null,
            'payment_method' => null,
            'days_overdue' => 0,
            'late_penalty_amount' => 0,
            'interest_on_penalty' => 0,
            'is_compliant' => true,
            'remittance_status' => 'not_remitted',
            'remittance_date' => null,
            'remittance_reference' => null,
            'remittance_amount' => null,
        ]);
    }

    /**
     * State for adjustment records
     */
    public function adjustment(): static
    {
        return $this->state(function (array $attributes) {
            $adjustmentAmount = $this->faker->randomFloat(2, -2000, 2000);
            
            return [
                'is_adjustment' => true,
                'adjustment_reason' => $this->faker->randomElement([
                    'Salary retroactive adjustment',
                    'Rate correction from previous period',
                    'Manual override due to system error',
                    'Policy change adjustment',
                ]),
                'adjustment_amount' => $adjustmentAmount,
                'total_contribution_amount' => abs($adjustmentAmount),
                'employee_contribution_amount' => abs($adjustmentAmount) * 0.4,
                'employer_contribution_amount' => abs($adjustmentAmount) * 0.6,
            ];
        });
    }

    /**
     * State for specific benefit type
     */
    public function forBenefitType(string $benefitType): static
    {
        return $this->state(function (array $attributes) use ($benefitType) {
            $rates = $this->getBenefitRates($benefitType);
            $contributionBase = $attributes['contribution_base'];
            
            $employeeContribution = $contributionBase * ($rates['employee_rate'] / 100);
            $employerContribution = $contributionBase * ($rates['employer_rate'] / 100);
            
            // Apply caps if applicable
            if (isset($rates['cap'])) {
                $employeeContribution = min($employeeContribution, $rates['cap']);
                $employerContribution = min($employerContribution, $rates['cap']);
            }
            
            return [
                'employee_contribution_rate' => $rates['employee_rate'],
                'employer_contribution_rate' => $rates['employer_rate'],
                'employee_contribution_amount' => round($employeeContribution, 2),
                'employer_contribution_amount' => round($employerContribution, 2),
                'total_contribution_amount' => round($employeeContribution + $employerContribution, 2),
            ];
        });
    }

    /**
     * Get benefit rates for calculation
     */
    private function getBenefitRates(string $benefitType): array
    {
        switch ($benefitType) {
            case 'GSIS':
                return ['employee_rate' => 9.00, 'employer_rate' => 12.00];
            case 'PhilHealth':
                return ['employee_rate' => 2.75, 'employer_rate' => 2.75, 'cap' => 1100.00];
            case 'Pag-IBIG':
                return ['employee_rate' => 2.00, 'employer_rate' => 2.00, 'cap' => 50.00];
            case 'SSS':
                return ['employee_rate' => 4.50, 'employer_rate' => 8.50, 'cap' => 562.50];
            default:
                return ['employee_rate' => 0, 'employer_rate' => 0];
        }
    }

    /**
     * State for specific period
     */
    public function forPeriod(int $year, int $month): static
    {
        return $this->state(function (array $attributes) use ($year, $month) {
            $payrollDate = Carbon::create($year, $month, $this->faker->numberBetween(15, 30));
            
            return [
                'contribution_year' => $year,
                'contribution_month' => $month,
                'payroll_date' => $payrollDate,
                'due_date' => $payrollDate->copy()->addDays(15),
                'payroll_batch_id' => 'BATCH-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . $this->faker->numerify('###'),
            ];
        });
    }
}