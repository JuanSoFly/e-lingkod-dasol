<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\GovernmentBenefit;
use Carbon\Carbon;

class GovernmentComplianceService
{
    /**
     * Calculate monthly contribution based on salary and benefit type
     */
    public function calculateMonthlyContribution(string $benefitType, float $basicSalary, float $additionalCompensation = 0, ?float $customEmployeeRate = null, ?float $customEmployerRate = null, ?float $customCap = null): array
    {
        $rates = $this->getCurrentContributionRates($benefitType);
        
        // Use custom rates if provided, otherwise use default
        $employeeRate = $customEmployeeRate ?? $rates['employee_rate'];
        $employerRate = $customEmployerRate ?? $rates['employer_rate'];
        $monthlyCap = $customCap ?? ($rates['monthly_cap'] ?? null);

        $totalCompensation = $basicSalary + $additionalCompensation;
        $contributionBase = $this->getContributionBase($benefitType, $totalCompensation);
        
        $employeeContribution = $contributionBase * ($employeeRate / 100);
        $employerContribution = $contributionBase * ($employerRate / 100);
        
        // Apply monthly cap if set
        if ($monthlyCap) {
            // For some agencies cap applies to the base, for others to the contribution.
            // Based on original logic:
            // if ($this->monthly_contribution_cap) {
            //     $employeeContribution = min($employeeContribution, $this->monthly_contribution_cap);
            //     $employerContribution = min($employerContribution, $this->monthly_contribution_cap);
            // }
            // This implementation assumes the cap applies to the contribution amount as per original code.
            $employeeContribution = min($employeeContribution, $monthlyCap);
            $employerContribution = min($employerContribution, $monthlyCap);
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
    public function getContributionBase(string $benefitType, float $totalCompensation): float
    {
        switch ($benefitType) {
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
     * 2024 PhilHealth premium contribution cap
     */
    public function getPhilHealthSalaryCap(): float
    {
        return 80000; // Monthly salary cap
    }

    /**
     * Get Pag-IBIG salary cap for contribution calculation
     * 2024 Pag-IBIG contribution cap
     */
    public function getPagIbigSalaryCap(): float
    {
        return 5000; // Monthly salary cap for 2% rate
    }

    /**
     * Get SSS salary cap for contribution calculation
     * 2024 SSS contribution cap
     */
    public function getSssSalaryCap(): float
    {
        return 25000; // Monthly salary cap
    }

    /**
     * Check if employee is eligible for specific benefit
     */
    public function isEligibleForBenefit(Employee $employee, string $benefitType): bool
    {
        $status = strtolower($employee->employment_status);

        switch ($benefitType) {
            case 'GSIS':
                return in_array($status, [
                    'permanent',
                    'regular',
                    'temporary',
                    'contractual',
                    'casual'
                ]);
            case 'PhilHealth':
                // PhilHealth has universal coverage - all employees eligible
                return true;
            case 'Pag-IBIG':
                // All employees with compensation are eligible
                return $status !== 'terminated';
            case 'SSS':
                // For government agencies, SSS typically for contractual/casual employees
                return in_array($status, [
                    'contractual',
                    'casual',
                    'temporary',
                    'job order', // Added common government status for SSS
                    'jo'
                ]);
            default:
                return false;
        }
    }

    /**
     * Get current contribution rates for the benefit type
     */
    public function getCurrentContributionRates(string $benefitType): array
    {
        switch ($benefitType) {
            case 'GSIS':
                return [
                    'employee_rate' => 9.00, // 9% employee share
                    'employer_rate' => 12.00, // 12% employer share
                    'monthly_cap' => null, // No cap for GSIS
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
                    'monthly_cap' => null,
                ];
        }
    }

    /**
     * Get compliance status
     */
    public function getComplianceStatus(GovernmentBenefit $benefit): string
    {
        $overdueContributions = $benefit->benefitContributions()
            ->where('payment_status', 'overdue')
            ->count();
            
        if ($overdueContributions > 0) {
            return 'non_compliant';
        }
        
        if ($benefit->processing_status === 'requires_verification') {
            return 'requires_attention';
        }
        
        return 'compliant';
    }
}
