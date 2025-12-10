<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeavePolicy;
use Carbon\Carbon;

class ProRationCalculator
{
    /**
     * Calculate pro-rated leave entitlement for first year employees
     */
    public function calculateFirstYearEntitlement(
        Employee $employee,
        LeavePolicy $policy,
        int $year
    ): float {
        // If pro-rating is not allowed, return full entitlement
        if (!$policy->allow_prorated_first_year) {
            return $policy->max_days_per_year;
        }
        
        // If employee was not hired this year, return full entitlement
        if (!$employee->date_hired || $employee->date_hired->year !== $year) {
            return $policy->max_days_per_year;
        }
        
        $hireDate = $employee->date_hired;
        $yearEnd = Carbon::create($year, 12, 31);
        
        // Calculate months remaining including hire month
        $monthsRemaining = $hireDate->diffInMonths($yearEnd) + 1;
        
        // Calculate pro-rated entitlement
        $proRatedEntitlement = ($policy->max_days_per_year / 12) * $monthsRemaining;
        
        return round($proRatedEntitlement, 2);
    }
    
    /**
     * Calculate pro-rated entitlement for partial year periods
     */
    public function calculatePartialYearEntitlement(
        Employee $employee,
        LeavePolicy $policy,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        // Calculate total months in the period
        $totalMonths = $startDate->diffInMonths($endDate) + 1;
        
        // Calculate pro-rated entitlement
        $proRatedEntitlement = ($policy->max_days_per_year / 12) * $totalMonths;
        
        return round($proRatedEntitlement, 2);
    }
    
    /**
     * Calculate monthly pro-rated entitlement
     */
    public function calculateMonthlyProRation(
        LeavePolicy $policy,
        int $monthsWorked
    ): float {
        return round(($policy->max_days_per_year / 12) * $monthsWorked, 2);
    }
    
    /**
     * Get pro-rated calculation details for audit purposes
     */
    public function getProRationDetails(
        Employee $employee,
        LeavePolicy $policy,
        int $year
    ): array {
        if (!$policy->allow_prorated_first_year) {
            return [
                'pro_rated' => false,
                'reason' => 'Policy does not allow pro-rating',
                'full_entitlement' => $policy->max_days_per_year,
                'calculated_entitlement' => $policy->max_days_per_year
            ];
        }
        
        if (!$employee->date_hired || $employee->date_hired->year !== $year) {
            return [
                'pro_rated' => false,
                'reason' => 'Employee not hired this year',
                'full_entitlement' => $policy->max_days_per_year,
                'calculated_entitlement' => $policy->max_days_per_year
            ];
        }
        
        $hireDate = $employee->date_hired;
        $yearEnd = Carbon::create($year, 12, 31);
        $monthsRemaining = $hireDate->diffInMonths($yearEnd) + 1;
        $proRatedEntitlement = ($policy->max_days_per_year / 12) * $monthsRemaining;
        
        return [
            'pro_rated' => true,
            'hire_date' => $hireDate->format('Y-m-d'),
            'months_remaining' => $monthsRemaining,
            'full_entitlement' => $policy->max_days_per_year,
            'calculated_entitlement' => round($proRatedEntitlement, 2),
            'calculation' => "({$policy->max_days_per_year} / 12) * {$monthsRemaining}",
            'hire_month' => $hireDate->format('F'),
            'year_end_month' => $yearEnd->format('F')
        ];
    }
    
    /**
     * Calculate pro-rated entitlement for mid-year policy changes
     */
    public function calculatePolicyChangeProRation(
        Employee $employee,
        LeavePolicy $oldPolicy,
        LeavePolicy $newPolicy,
        Carbon $changeDate
    ): array {
        $year = $changeDate->year;
        $yearStart = Carbon::create($year, 1, 1);
        $yearEnd = Carbon::create($year, 12, 31);
        
        // Calculate days before policy change
        $monthsBeforeChange = $yearStart->diffInMonths($changeDate);
        $oldPolicyDays = $this->calculateMonthlyProRation($oldPolicy, $monthsBeforeChange);
        
        // Calculate days after policy change
        $monthsAfterChange = $changeDate->diffInMonths($yearEnd);
        $newPolicyDays = $this->calculateMonthlyProRation($newPolicy, $monthsAfterChange);
        
        return [
            'total_entitlement' => round($oldPolicyDays + $newPolicyDays, 2),
            'old_policy_contribution' => round($oldPolicyDays, 2),
            'new_policy_contribution' => round($newPolicyDays, 2),
            'months_before_change' => $monthsBeforeChange,
            'months_after_change' => $monthsAfterChange,
            'change_date' => $changeDate->format('Y-m-d')
        ];
    }
}