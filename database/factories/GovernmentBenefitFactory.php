<?php

namespace Database\Factories;

use App\Models\GovernmentBenefit;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GovernmentBenefit>
 */
class GovernmentBenefitFactory extends Factory
{
    protected $model = GovernmentBenefit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $benefitType = $this->faker->randomElement(['GSIS', 'PhilHealth', 'Pag-IBIG', 'SSS']);
        
        return [
            'employee_id' => Employee::factory(),
            'benefit_type' => $benefitType,
            'member_number' => $this->generateMemberNumber($benefitType),
            'enrollment_date' => $this->faker->dateTimeBetween('-5 years', '-1 month'),
            'enrollment_status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'coverage_type' => $this->faker->randomElement(['basic', 'premium', 'dependent', 'family']),
            'coverage_amount' => $this->faker->randomFloat(2, 50000, 500000),
            'employee_contribution_rate' => $this->getEmployeeRate($benefitType),
            'employer_contribution_rate' => $this->getEmployerRate($benefitType),
            'monthly_contribution_cap' => $this->getContributionCap($benefitType),
            'primary_beneficiaries' => $this->generateBeneficiaries(2),
            'secondary_beneficiaries' => $this->generateBeneficiaries(1),
            'has_active_loan' => $this->faker->boolean(30), // 30% chance of having a loan
            'loan_balance' => function (array $attributes) {
                return $attributes['has_active_loan'] ? $this->faker->randomFloat(2, 10000, 500000) : 0;
            },
            'monthly_loan_payment' => function (array $attributes) {
                return $attributes['has_active_loan'] ? $this->faker->randomFloat(2, 500, 5000) : 0;
            },
            'loan_start_date' => function (array $attributes) {
                return $attributes['has_active_loan'] ? $this->faker->dateTimeBetween('-3 years', '-1 month') : null;
            },
            'loan_maturity_date' => function (array $attributes) {
                return $attributes['has_active_loan'] ? $this->faker->dateTimeBetween('now', '+3 years') : null;
            },
            'loan_interest_rate' => function (array $attributes) {
                return $attributes['has_active_loan'] ? $this->faker->randomFloat(4, 6.0, 12.0) : null;
            },
            'active_claims_count' => $this->faker->numberBetween(0, 3),
            'total_claims_amount' => $this->faker->randomFloat(2, 0, 100000),
            'last_claim_date' => $this->faker->optional(0.3)->dateTimeBetween('-1 year', 'now'),
            'processing_status' => $this->faker->randomElement(['up_to_date', 'pending_update', 'requires_verification']),
            'additional_details' => $this->generateAdditionalDetails($benefitType),
            'remarks' => $this->faker->optional()->sentence(),
            'last_verified_date' => $this->faker->optional(0.8)->dateTimeBetween('-6 months', 'now'),
            'verified_by' => $this->faker->optional(0.8)->randomElement(User::pluck('id')->toArray() ?: [1]),
            'created_by' => $this->faker->optional(0.9)->randomElement(User::pluck('id')->toArray() ?: [1]),
            'updated_by' => $this->faker->optional(0.7)->randomElement(User::pluck('id')->toArray() ?: [1]),
        ];
    }

    /**
     * Generate member number based on benefit type
     */
    private function generateMemberNumber(string $benefitType): string
    {
        switch ($benefitType) {
            case 'GSIS':
                return 'GSIS-' . $this->faker->numerify('##########');
            case 'PhilHealth':
                return 'PH-' . $this->faker->numerify('############');
            case 'Pag-IBIG':
                return 'HDMF-' . $this->faker->numerify('############');
            case 'SSS':
                return 'SSS-' . $this->faker->numerify('##########');
            default:
                return $this->faker->numerify('############');
        }
    }

    /**
     * Get employee contribution rate based on benefit type
     */
    private function getEmployeeRate(string $benefitType): float
    {
        switch ($benefitType) {
            case 'GSIS':
                return 9.00;
            case 'PhilHealth':
                return 2.75;
            case 'Pag-IBIG':
                return 2.00;
            case 'SSS':
                return 4.50;
            default:
                return 0.00;
        }
    }

    /**
     * Get employer contribution rate based on benefit type
     */
    private function getEmployerRate(string $benefitType): float
    {
        switch ($benefitType) {
            case 'GSIS':
                return 12.00;
            case 'PhilHealth':
                return 2.75;
            case 'Pag-IBIG':
                return 2.00;
            case 'SSS':
                return 8.50;
            default:
                return 0.00;
        }
    }

    /**
     * Get contribution cap based on benefit type
     */
    private function getContributionCap(string $benefitType): ?float
    {
        switch ($benefitType) {
            case 'PhilHealth':
                return 2200.00;
            case 'Pag-IBIG':
                return 100.00;
            case 'SSS':
                return 1125.00;
            case 'GSIS':
            default:
                return null; // No cap for GSIS
        }
    }

    /**
     * Generate beneficiaries array
     */
    private function generateBeneficiaries(int $count): array
    {
        $beneficiaries = [];
        
        for ($i = 0; $i < $count; $i++) {
            $beneficiaries[] = [
                'name' => $this->faker->name(),
                'relationship' => $this->faker->randomElement(['spouse', 'child', 'parent', 'sibling']),
                'birth_date' => $this->faker->date(),
                'percentage' => $count === 1 ? 100 : $this->faker->numberBetween(25, 75),
                'contact_number' => $this->faker->phoneNumber(),
                'address' => $this->faker->address(),
            ];
        }
        
        return $beneficiaries;
    }

    /**
     * Generate additional details based on benefit type
     */
    private function generateAdditionalDetails(string $benefitType): array
    {
        switch ($benefitType) {
            case 'GSIS':
                return [
                    'life_insurance_coverage' => $this->faker->randomFloat(2, 100000, 1000000),
                    'retirement_plan' => 'Regular',
                    'ecard_number' => 'EC-' . $this->faker->numerify('##########'),
                ];
                
            case 'PhilHealth':
                return [
                    'member_category' => $this->faker->randomElement(['direct_contributor', 'dependent']),
                    'philhealth_id' => 'PHN-' . $this->faker->numerify('############'),
                    'mdr_number' => 'MDR-' . $this->faker->numerify('########'),
                ];
                
            case 'Pag-IBIG':
                return [
                    'loyalty_card_number' => 'LC-' . $this->faker->numerify('##########'),
                    'membership_type' => 'Regular',
                    'virtual_account' => 'VA-' . $this->faker->numerify('##########'),
                ];
                
            case 'SSS':
                return [
                    'ss_number' => 'SS-' . $this->faker->numerify('##########'),
                    'employment_type' => $this->faker->randomElement(['kasambahay', 'ofw', 'voluntary']),
                    'umid_number' => 'UMID-' . $this->faker->numerify('##########'),
                ];
                
            default:
                return [];
        }
    }

    /**
     * State for active enrollment
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_status' => 'active',
            'processing_status' => 'up_to_date',
        ]);
    }

    /**
     * State for inactive enrollment
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_status' => 'inactive',
            'processing_status' => 'suspended',
        ]);
    }

    /**
     * State for with active loan
     */
    public function withLoan(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_active_loan' => true,
            'loan_balance' => $this->faker->randomFloat(2, 50000, 300000),
            'monthly_loan_payment' => $this->faker->randomFloat(2, 2000, 8000),
            'loan_start_date' => $this->faker->dateTimeBetween('-2 years', '-3 months'),
            'loan_maturity_date' => $this->faker->dateTimeBetween('+6 months', '+3 years'),
            'loan_interest_rate' => $this->faker->randomFloat(4, 8.0, 12.0),
        ]);
    }

    /**
     * State for without loan
     */
    public function withoutLoan(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_active_loan' => false,
            'loan_balance' => 0,
            'monthly_loan_payment' => 0,
            'loan_start_date' => null,
            'loan_maturity_date' => null,
            'loan_interest_rate' => null,
        ]);
    }

    /**
     * State for specific benefit type
     */
    public function benefitType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'benefit_type' => $type,
            'member_number' => $this->generateMemberNumber($type),
            'employee_contribution_rate' => $this->getEmployeeRate($type),
            'employer_contribution_rate' => $this->getEmployerRate($type),
            'monthly_contribution_cap' => $this->getContributionCap($type),
            'additional_details' => $this->generateAdditionalDetails($type),
        ]);
    }

    /**
     * State for requiring verification
     */
    public function requiresVerification(): static
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'requires_verification',
            'last_verified_date' => $this->faker->dateTimeBetween('-1 year', '-6 months'),
        ]);
    }
}