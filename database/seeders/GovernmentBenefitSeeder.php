<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\GovernmentBenefit;
use App\Models\BenefitContribution;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GovernmentBenefitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all existing employees
        $employees = Employee::all();
        
        if ($employees->isEmpty()) {
            $this->command->info('No employees found. Please run EmployeeSeeder first.');
            return;
        }

        // Get existing users for audit fields
        $userIds = User::pluck('id')->toArray();
        $createdById = $userIds[0] ?? 1;

        $this->command->info('Creating government benefits for ' . $employees->count() . ' employees...');

        DB::transaction(function () use ($employees, $userIds, $createdById) {
            foreach ($employees as $employee) {
                $this->createBenefitsForEmployee($employee, $userIds, $createdById);
            }
        });

        $this->command->info('Government benefits and contributions created successfully!');
    }

    /**
     * Create government benefits for a single employee
     */
    private function createBenefitsForEmployee(Employee $employee, array $userIds, int $createdById): void
    {
        $requiredBenefits = $this->getRequiredBenefitsForEmployee($employee);
        
        foreach ($requiredBenefits as $benefitType) {
            // Create the government benefit record
            $benefit = GovernmentBenefit::create([
                'employee_id' => $employee->id,
                'benefit_type' => $benefitType,
                'member_number' => $this->generateMemberNumber($benefitType, $employee),
                'enrollment_date' => $this->getEnrollmentDate($employee),
                'enrollment_status' => 'active',
                'coverage_type' => $this->getCoverageType($benefitType),
                'coverage_amount' => $this->getCoverageAmount($benefitType, $employee),
                'employee_contribution_rate' => $this->getEmployeeRate($benefitType),
                'employer_contribution_rate' => $this->getEmployerRate($benefitType),
                'monthly_contribution_cap' => $this->getContributionCap($benefitType),
                'primary_beneficiaries' => $this->generateBeneficiaries($employee, true),
                'secondary_beneficiaries' => $this->generateBeneficiaries($employee, false),
                'has_active_loan' => $this->shouldHaveLoan($benefitType),
                'loan_balance' => $this->shouldHaveLoan($benefitType) ? rand(50000, 300000) : 0,
                'monthly_loan_payment' => $this->shouldHaveLoan($benefitType) ? rand(2000, 8000) : 0,
                'loan_start_date' => $this->shouldHaveLoan($benefitType) ? now()->subMonths(rand(6, 24)) : null,
                'loan_maturity_date' => $this->shouldHaveLoan($benefitType) ? now()->addMonths(rand(12, 36)) : null,
                'loan_interest_rate' => $this->shouldHaveLoan($benefitType) ? rand(80, 120) / 10 : null,
                'active_claims_count' => 0,
                'total_claims_amount' => 0,
                'processing_status' => 'up_to_date',
                'additional_details' => $this->getAdditionalDetails($benefitType),
                'last_verified_date' => now()->subDays(rand(0, 90)),
                'verified_by' => $userIds[array_rand($userIds)] ?? $createdById,
                'created_by' => $createdById,
            ]);

            // Create contribution history for the past 12 months
            $this->createContributionHistory($benefit, $employee);
        }
    }

    /**
     * Get required benefits based on employee employment status
     */
    private function getRequiredBenefitsForEmployee(Employee $employee): array
    {
        $benefits = ['PhilHealth']; // Universal coverage

        switch ($employee->employment_status) {
            case 'permanent':
            case 'temporary':
                $benefits[] = 'GSIS';
                $benefits[] = 'Pag-IBIG';
                break;
                
            case 'contractual':
            case 'casual':
                // For contractual/casual, might use SSS instead of GSIS
                $benefits[] = rand(0, 1) ? 'GSIS' : 'SSS';
                $benefits[] = 'Pag-IBIG';
                break;
        }

        return $benefits;
    }

    /**
     * Generate member number based on benefit type and employee
     */
    private function generateMemberNumber(string $benefitType, Employee $employee): string
    {
        $empNum = str_pad($employee->id, 6, '0', STR_PAD_LEFT);
        
        switch ($benefitType) {
            case 'GSIS':
                return 'GSIS' . $empNum . rand(1000, 9999);
            case 'PhilHealth':
                return str_replace('-', '', $employee->gsis_number ?: ('PH' . $empNum . rand(100000, 999999)));
            case 'Pag-IBIG':
                return str_replace('-', '', $employee->pagibig_number ?: ('HDMF' . $empNum . rand(100000, 999999)));
            case 'SSS':
                return str_replace('-', '', $employee->sss_number ?: ('SSS' . $empNum . rand(100000, 999999)));
            default:
                return $benefitType . $empNum . rand(100000, 999999);
        }
    }

    /**
     * Get enrollment date (typically date hired or a bit after)
     */
    private function getEnrollmentDate(Employee $employee): Carbon
    {
        $dateHired = Carbon::parse($employee->date_hired);
        return $dateHired->addDays(rand(0, 30)); // Enrolled within 30 days of hiring
    }

    /**
     * Get coverage type based on benefit
     */
    private function getCoverageType(string $benefitType): string
    {
        switch ($benefitType) {
            case 'PhilHealth':
                return ['basic', 'family'].array_rand(['basic', 'family']);
            default:
                return 'basic';
        }
    }

    /**
     * Get coverage amount based on benefit type and employee
     */
    private function getCoverageAmount(string $benefitType, Employee $employee): ?float
    {
        $monthlySalary = ($employee->salary_grade * 5000) + ($employee->step_increment * 500); // Rough calculation
        
        switch ($benefitType) {
            case 'GSIS':
                return $monthlySalary * 24; // 2 years salary coverage
            case 'PhilHealth':
                return 400000; // Standard PhilHealth coverage
            case 'SSS':
                return $monthlySalary * 36; // 3 years salary coverage
            default:
                return null;
        }
    }

    /**
     * Get employee contribution rate
     */
    private function getEmployeeRate(string $benefitType): float
    {
        return match ($benefitType) {
            'GSIS' => 9.00,
            'PhilHealth' => 2.75,
            'Pag-IBIG' => 2.00,
            'SSS' => 4.50,
            default => 0.00,
        };
    }

    /**
     * Get employer contribution rate
     */
    private function getEmployerRate(string $benefitType): float
    {
        return match ($benefitType) {
            'GSIS' => 12.00,
            'PhilHealth' => 2.75,
            'Pag-IBIG' => 2.00,
            'SSS' => 8.50,
            default => 0.00,
        };
    }

    /**
     * Get monthly contribution cap
     */
    private function getContributionCap(string $benefitType): ?float
    {
        return match ($benefitType) {
            'PhilHealth' => 2200.00,
            'Pag-IBIG' => 100.00,
            'SSS' => 1125.00,
            default => null, // No cap for GSIS
        };
    }

    /**
     * Generate beneficiaries for the employee
     */
    private function generateBeneficiaries(Employee $employee, bool $isPrimary): array
    {
        $beneficiaries = [];
        
        if ($isPrimary) {
            // Add spouse if married
            if (in_array($employee->civil_status, ['married', 'widowed'])) {
                $beneficiaries[] = [
                    'name' => $employee->spouse_name ?: 'Spouse Name',
                    'relationship' => 'spouse',
                    'birth_date' => now()->subYears(rand(25, 60))->format('Y-m-d'),
                    'percentage' => 50,
                    'contact_number' => $employee->contact_number,
                    'address' => $employee->address,
                ];
            }
            
            // Add children
            $childrenCount = rand(0, 3);
            for ($i = 0; $i < $childrenCount; $i++) {
                $beneficiaries[] = [
                    'name' => 'Child ' . ($i + 1),
                    'relationship' => 'child',
                    'birth_date' => now()->subYears(rand(1, 25))->format('Y-m-d'),
                    'percentage' => count($beneficiaries) > 0 ? 25 : 100,
                    'contact_number' => $employee->contact_number,
                    'address' => $employee->address,
                ];
            }
        } else {
            // Secondary beneficiaries (parents, siblings)
            if (rand(0, 1)) {
                $beneficiaries[] = [
                    'name' => 'Parent Name',
                    'relationship' => 'parent',
                    'birth_date' => now()->subYears(rand(50, 80))->format('Y-m-d'),
                    'percentage' => 100,
                    'contact_number' => $employee->emergency_contact_number ?: $employee->contact_number,
                    'address' => $employee->emergency_contact_address ?: $employee->address,
                ];
            }
        }
        
        return $beneficiaries;
    }

    /**
     * Check if employee should have a loan
     */
    private function shouldHaveLoan(string $benefitType): bool
    {
        // Only GSIS and Pag-IBIG typically offer loans
        if (!in_array($benefitType, ['GSIS', 'Pag-IBIG'])) {
            return false;
        }
        
        // 30% chance of having an active loan
        return rand(1, 100) <= 30;
    }

    /**
     * Get additional details based on benefit type
     */
    private function getAdditionalDetails(string $benefitType): array
    {
        switch ($benefitType) {
            case 'GSIS':
                return [
                    'life_insurance_coverage' => rand(100000, 1000000),
                    'retirement_plan' => 'Regular',
                    'ecard_number' => 'EC-' . rand(1000000000, 9999999999),
                ];
                
            case 'PhilHealth':
                return [
                    'member_category' => 'direct_contributor',
                    'philhealth_id' => 'PHN-' . rand(100000000000, 999999999999),
                    'mdr_number' => 'MDR-' . rand(10000000, 99999999),
                ];
                
            case 'Pag-IBIG':
                return [
                    'loyalty_card_number' => 'LC-' . rand(1000000000, 9999999999),
                    'membership_type' => 'Regular',
                    'virtual_account' => 'VA-' . rand(1000000000, 9999999999),
                ];
                
            case 'SSS':
                return [
                    'ss_number' => 'SS-' . rand(1000000000, 9999999999),
                    'employment_type' => 'regular',
                    'umid_number' => 'UMID-' . rand(1000000000, 9999999999),
                ];
                
            default:
                return [];
        }
    }

    /**
     * Create contribution history for the past 12 months
     */
    private function createContributionHistory(GovernmentBenefit $benefit, Employee $employee): void
    {
        $monthlySalary = ($employee->salary_grade * 5000) + ($employee->step_increment * 500);
        $additionalComp = $monthlySalary * 0.2; // 20% additional compensation
        
        // Create contributions for the past 12 months
        for ($i = 11; $i >= 0; $i--) {
            $contributionDate = now()->subMonths($i);
            $year = $contributionDate->year;
            $month = $contributionDate->month;
            
            // Skip future months
            if ($contributionDate->isFuture()) {
                continue;
            }
            
            $payrollDate = $contributionDate->copy()->day(25);
            $dueDate = $payrollDate->copy()->addDays(15);
            
            $totalCompensation = $monthlySalary + $additionalComp;
            $contributionBase = $this->getContributionBase($totalCompensation, $benefit->benefit_type);
            
            $employeeContribution = $contributionBase * ($benefit->employee_contribution_rate / 100);
            $employerContribution = $contributionBase * ($benefit->employer_contribution_rate / 100);
            
            // Apply caps if applicable
            if ($benefit->monthly_contribution_cap) {
                $employeeContribution = min($employeeContribution, $benefit->monthly_contribution_cap);
                $employerContribution = min($employerContribution, $benefit->monthly_contribution_cap);
            }
            
            $totalContribution = $employeeContribution + $employerContribution;
            
            // Determine payment status
            $paymentStatus = $this->getPaymentStatus($dueDate);
            $daysOverdue = $paymentStatus === 'overdue' ? now()->diffInDays($dueDate) : 0;
            
            BenefitContribution::create([
                'employee_id' => $employee->id,
                'government_benefit_id' => $benefit->id,
                'contribution_year' => $year,
                'contribution_month' => $month,
                'payroll_date' => $payrollDate,
                'due_date' => $dueDate,
                'basic_salary' => $monthlySalary,
                'additional_compensation' => $additionalComp,
                'total_compensation' => $totalCompensation,
                'contribution_base' => $contributionBase,
                'employee_contribution_rate' => $benefit->employee_contribution_rate,
                'employer_contribution_rate' => $benefit->employer_contribution_rate,
                'employee_contribution_amount' => round($employeeContribution, 2),
                'employer_contribution_amount' => round($employerContribution, 2),
                'total_contribution_amount' => round($totalContribution, 2),
                'loan_payment_amount' => $benefit->monthly_loan_payment,
                'payment_status' => $paymentStatus,
                'payment_date' => $paymentStatus === 'paid' ? $payrollDate->copy()->addDays(rand(1, 10)) : null,
                'payment_reference' => $paymentStatus === 'paid' ? 'PAY-' . rand(1000000000, 9999999999) : null,
                'payment_method' => $paymentStatus === 'paid' ? 'payroll_deduction' : null,
                'days_overdue' => $daysOverdue,
                'late_penalty_amount' => $daysOverdue > 0 ? round($totalContribution * 0.02 * ceil($daysOverdue / 30), 2) : 0,
                'remittance_status' => $paymentStatus === 'paid' ? 'remitted' : 'not_remitted',
                'remittance_date' => $paymentStatus === 'paid' ? $dueDate->copy()->addDays(rand(5, 30)) : null,
                'remittance_reference' => $paymentStatus === 'paid' ? 'REM-' . rand(1000000000, 9999999999) : null,
                'remittance_amount' => $paymentStatus === 'paid' ? round($totalContribution, 2) : null,
                'is_compliant' => $paymentStatus !== 'overdue',
                'compliance_issues' => $paymentStatus === 'overdue' ? ['Payment overdue'] : null,
                'payroll_batch_id' => 'BATCH-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-001',
                'included_in_payroll' => true,
                'payroll_details' => [
                    'payroll_period' => $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT),
                    'payroll_type' => 'regular',
                    'processed_by' => 'System',
                    'processing_date' => $payrollDate->format('Y-m-d'),
                ],
                'created_by' => $benefit->created_by,
            ]);
        }
    }

    /**
     * Get contribution base considering benefit-specific caps
     */
    private function getContributionBase(float $totalCompensation, string $benefitType): float
    {
        switch ($benefitType) {
            case 'PhilHealth':
                return min($totalCompensation, 80000); // PhilHealth salary cap
            case 'Pag-IBIG':
                return min($totalCompensation, 5000); // Pag-IBIG salary cap for 2% rate
            case 'SSS':
                return min($totalCompensation, 25000); // SSS salary cap
            case 'GSIS':
            default:
                return $totalCompensation; // No cap for GSIS
        }
    }

    /**
     * Determine payment status based on due date
     */
    private function getPaymentStatus(Carbon $dueDate): string
    {
        if ($dueDate->isFuture()) {
            return 'pending';
        }
        
        $daysSinceDue = now()->diffInDays($dueDate);
        
        if ($daysSinceDue > 30 && rand(1, 100) <= 10) { // 10% chance of being overdue after 30 days
            return 'overdue';
        }
        
        return 'paid';
    }
}