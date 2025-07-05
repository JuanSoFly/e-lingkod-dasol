<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveCredit;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveCalculationService
{
    /**
     * Calculate comprehensive leave balance for an employee
     */
    public function calculateLeaveBalance(Employee $employee, int $year = null): array
    {
        $year = $year ?? now()->year;
        $balances = [];

        $applicablePolicies = $employee->getApplicableLeavePolicies();

        foreach ($applicablePolicies as $policy) {
            $leaveTypeId = $policy->leave_type_id;
            
            $balance = [
                'leave_type_id' => $leaveTypeId,
                'leave_type_name' => $policy->leaveType->name,
                'policy_name' => $policy->name,
                'entitled_days' => $this->calculateAnnualEntitlement($employee, $policy, $year),
                'used_days' => $this->calculateUsedDays($employee, $leaveTypeId, $year),
                'pending_days' => $this->calculatePendingDays($employee, $leaveTypeId, $year),
                'remaining_days' => 0, // Will be calculated below
                'carryover_days' => $this->calculateCarryoverDays($employee, $policy, $year),
                'accrued_days' => $this->calculateAccruedDays($employee, $policy, $year),
                'projected_year_end' => 0, // Will be calculated below
                'monthly_accrual_rate' => $policy->monthly_accrual_rate ?? 0,
                'can_carryover' => $policy->allow_carryover,
                'max_carryover' => $policy->max_carryover_days ?? 0,
                'policy' => $policy
            ];

            // Calculate remaining days
            $balance['remaining_days'] = $balance['entitled_days'] + $balance['carryover_days'] - $balance['used_days'] - $balance['pending_days'];
            
            // Calculate projected year-end balance
            $balance['projected_year_end'] = $this->calculateProjectedYearEndBalance($employee, $policy, $year, $balance);

            $balances[] = $balance;
        }

        return $balances;
    }

    /**
     * Calculate annual entitlement with pro-rating for first year
     */
    public function calculateAnnualEntitlement(Employee $employee, LeavePolicy $policy, int $year): float
    {
        $baseEntitlement = $policy->max_days_per_year ?? 0;

        // Check if this is the employee's first year and pro-rating is allowed
        if ($policy->allow_prorated_first_year && $employee->date_hired) {
            $hireYear = $employee->date_hired->year;
            
            if ($hireYear == $year) {
                // Pro-rate based on months worked
                $monthsWorked = 12 - $employee->date_hired->month + 1;
                $baseEntitlement = ($baseEntitlement / 12) * $monthsWorked;
            }
        }

        return round($baseEntitlement, 2);
    }

    /**
     * Calculate used days for a specific leave type and year
     */
    public function calculateUsedDays(Employee $employee, int $leaveTypeId, int $year): float
    {
        return LeaveApplication::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days_requested') ?? 0;
    }

    /**
     * Calculate pending days for a specific leave type and year
     */
    public function calculatePendingDays(Employee $employee, int $leaveTypeId, int $year): float
    {
        return LeaveApplication::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'pending')
            ->whereYear('start_date', $year)
            ->sum('days_requested') ?? 0;
    }

    /**
     * Calculate carryover days from previous year
     */
    public function calculateCarryoverDays(Employee $employee, LeavePolicy $policy, int $year): float
    {
        if (!$policy->allow_carryover || $year <= ($employee->date_hired?->year ?? $year)) {
            return 0;
        }

        $previousYear = $year - 1;
        $previousYearBalance = $this->calculateRemainingDaysForYear($employee, $policy, $previousYear);
        
        $maxCarryover = $policy->max_carryover_days ?? 0;
        $carriedOver = min($previousYearBalance, $maxCarryover);

        // Check if carryover has expired
        if ($policy->carryover_expiry_date) {
            $expiryDate = Carbon::create($year, $policy->carryover_expiry_date->month, $policy->carryover_expiry_date->day);
            if (now()->greaterThan($expiryDate)) {
                return 0;
            }
        }

        return max(0, $carriedOver);
    }

    /**
     * Calculate accrued days based on accrual method
     */
    public function calculateAccruedDays(Employee $employee, LeavePolicy $policy, int $year): float
    {
        if ($policy->accrual_method !== 'monthly' || !$policy->monthly_accrual_rate) {
            return 0;
        }

        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Only calculate accrual for current year
        if ($year != $currentYear) {
            return 0;
        }

        // Calculate months since start of year or hire date, whichever is later
        $startDate = Carbon::create($year, 1, 1);
        if ($employee->date_hired && $employee->date_hired->year == $year) {
            $startDate = $employee->date_hired;
        }

        $monthsWorked = $startDate->diffInMonths(now()) + 1;
        $accruedDays = $monthsWorked * $policy->monthly_accrual_rate;

        return round($accruedDays, 2);
    }

    /**
     * Calculate remaining days for a specific year
     */
    protected function calculateRemainingDaysForYear(Employee $employee, LeavePolicy $policy, int $year): float
    {
        $entitled = $this->calculateAnnualEntitlement($employee, $policy, $year);
        $used = $this->calculateUsedDays($employee, $policy->leave_type_id, $year);
        
        return max(0, $entitled - $used);
    }

    /**
     * Calculate projected year-end balance
     */
    protected function calculateProjectedYearEndBalance(Employee $employee, LeavePolicy $policy, int $year, array $currentBalance): float
    {
        $currentRemaining = $currentBalance['remaining_days'];
        
        // If using monthly accrual, project future accruals
        if ($policy->accrual_method === 'monthly' && $policy->monthly_accrual_rate && $year == now()->year) {
            $monthsRemaining = 12 - now()->month;
            $futureAccruals = $monthsRemaining * $policy->monthly_accrual_rate;
            return $currentRemaining + $futureAccruals;
        }

        return $currentRemaining;
    }

    /**
     * Validate leave application with comprehensive checks
     */
    public function validateLeaveApplication(Employee $employee, array $applicationData): array
    {
        $errors = [];
        $leaveTypeId = $applicationData['leave_type_id'];
        $daysRequested = $applicationData['days_requested'];
        $startDate = Carbon::parse($applicationData['start_date']);
        $endDate = Carbon::parse($applicationData['end_date']);

        // Get applicable policy
        $policy = $employee->getApplicableLeavePolicies()
            ->where('leave_type_id', $leaveTypeId)
            ->first();

        if (!$policy) {
            $errors[] = 'This leave type is not available for your employment status or position.';
            return ['valid' => false, 'errors' => $errors];
        }

        // Validate tenure requirement
        if ($policy->minimum_tenure_months && $employee->date_hired) {
            $tenureMonths = $employee->date_hired->diffInMonths(now());
            if ($tenureMonths < $policy->minimum_tenure_months) {
                $errors[] = "You need at least {$policy->minimum_tenure_months} months of service to apply for this leave type.";
            }
        }

        // Validate minimum days
        if ($policy->min_days_per_application && $daysRequested < $policy->min_days_per_application) {
            $errors[] = "Minimum {$policy->min_days_per_application} days required for this leave type.";
        }

        // Validate maximum consecutive days
        if ($policy->max_consecutive_days && $daysRequested > $policy->max_consecutive_days) {
            $errors[] = "Maximum {$policy->max_consecutive_days} consecutive days allowed for this leave type.";
        }

        // Validate advance notice
        if ($policy->min_advance_notice_days) {
            $daysDiff = now()->diffInDays($startDate);
            if ($daysDiff < $policy->min_advance_notice_days) {
                $errors[] = "Minimum {$policy->min_advance_notice_days} days advance notice required.";
            }
        }

        // Validate maximum advance notice
        if ($policy->max_advance_notice_days) {
            $daysDiff = now()->diffInDays($startDate);
            if ($daysDiff > $policy->max_advance_notice_days) {
                $errors[] = "Cannot apply more than {$policy->max_advance_notice_days} days in advance.";
            }
        }

        // Validate blocked dates
        if ($policy->blocked_dates) {
            foreach ($policy->blocked_dates as $blockedDate) {
                $blocked = Carbon::parse($blockedDate);
                if ($startDate->lte($blocked) && $endDate->gte($blocked)) {
                    $errors[] = "Leave cannot be taken on {$blocked->format('M d, Y')} as it's a blocked date.";
                }
            }
        }

        // Validate leave balance
        $balance = $this->calculateLeaveBalance($employee, $startDate->year);
        $leaveBalance = collect($balance)->firstWhere('leave_type_id', $leaveTypeId);
        
        if ($leaveBalance) {
            $availableBalance = $leaveBalance['remaining_days'];
            
            if (!$policy->allow_negative_balance && $daysRequested > $availableBalance) {
                $errors[] = "Insufficient leave balance. Available: {$availableBalance} days, Requested: {$daysRequested} days.";
            }
        }

        // Validate medical certificate requirement
        if ($policy->requires_medical_certificate && 
            $policy->medical_cert_required_days && 
            $daysRequested >= $policy->medical_cert_required_days) {
            
            if (empty($applicationData['supporting_documents']) || 
                !in_array('medical_certificate', $applicationData['supporting_documents'] ?? [])) {
                $errors[] = "Medical certificate required for {$policy->medical_cert_required_days}+ days of this leave type.";
            }
        }

        // Validate required documents
        if ($policy->required_documents) {
            $providedDocs = $applicationData['supporting_documents'] ?? [];
            $missingDocs = array_diff($policy->required_documents, $providedDocs);
            
            if (!empty($missingDocs)) {
                $errors[] = "Missing required documents: " . implode(', ', $missingDocs);
            }
        }

        // Validate monthly limit
        if ($policy->max_days_per_month) {
            $monthlyUsed = LeaveApplication::where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveTypeId)
                ->whereIn('status', ['approved', 'pending'])
                ->whereYear('start_date', $startDate->year)
                ->whereMonth('start_date', $startDate->month)
                ->sum('days_requested') ?? 0;
                
            if (($monthlyUsed + $daysRequested) > $policy->max_days_per_month) {
                $errors[] = "Monthly limit of {$policy->max_days_per_month} days exceeded for this leave type.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'policy' => $policy,
            'balance' => $leaveBalance
        ];
    }

    /**
     * Get leave usage statistics for an employee
     */
    public function getLeaveUsageStatistics(Employee $employee, int $year = null): array
    {
        $year = $year ?? now()->year;
        $balances = $this->calculateLeaveBalance($employee, $year);
        
        $stats = [
            'total_entitled' => 0,
            'total_used' => 0,
            'total_remaining' => 0,
            'total_pending' => 0,
            'utilization_rate' => 0,
            'by_leave_type' => [],
            'monthly_usage' => $this->getMonthlyUsage($employee, $year),
            'trends' => $this->getUsageTrends($employee, $year)
        ];

        foreach ($balances as $balance) {
            $stats['total_entitled'] += $balance['entitled_days'];
            $stats['total_used'] += $balance['used_days'];
            $stats['total_remaining'] += $balance['remaining_days'];
            $stats['total_pending'] += $balance['pending_days'];
            
            $stats['by_leave_type'][] = [
                'leave_type' => $balance['leave_type_name'],
                'entitled' => $balance['entitled_days'],
                'used' => $balance['used_days'],
                'remaining' => $balance['remaining_days'],
                'utilization_rate' => $balance['entitled_days'] > 0 ? 
                    round(($balance['used_days'] / $balance['entitled_days']) * 100, 2) : 0
            ];
        }

        $stats['utilization_rate'] = $stats['total_entitled'] > 0 ? 
            round(($stats['total_used'] / $stats['total_entitled']) * 100, 2) : 0;

        return $stats;
    }

    /**
     * Get monthly usage breakdown
     */
    protected function getMonthlyUsage(Employee $employee, int $year): array
    {
        $monthly = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $usage = LeaveApplication::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $year)
                ->whereMonth('start_date', $month)
                ->sum('days_requested') ?? 0;
                
            $monthly[] = [
                'month' => $month,
                'month_name' => Carbon::create($year, $month, 1)->format('M'),
                'days_used' => $usage
            ];
        }
        
        return $monthly;
    }

    /**
     * Get usage trends and patterns
     */
    protected function getUsageTrends(Employee $employee, int $year): array
    {
        $currentYearUsage = $this->calculateUsedDays($employee, null, $year);
        $previousYearUsage = $this->calculateUsedDays($employee, null, $year - 1);
        
        $trend = 'stable';
        $changePercent = 0;
        
        if ($previousYearUsage > 0) {
            $changePercent = (($currentYearUsage - $previousYearUsage) / $previousYearUsage) * 100;
            
            if ($changePercent > 10) {
                $trend = 'increasing';
            } elseif ($changePercent < -10) {
                $trend = 'decreasing';
            }
        }

        return [
            'current_year_usage' => $currentYearUsage,
            'previous_year_usage' => $previousYearUsage,
            'trend' => $trend,
            'change_percent' => round($changePercent, 2),
            'average_days_per_application' => $this->getAverageDaysPerApplication($employee, $year),
            'most_used_leave_type' => $this->getMostUsedLeaveType($employee, $year)
        ];
    }

    /**
     * Get average days per application
     */
    protected function getAverageDaysPerApplication(Employee $employee, int $year): float
    {
        return LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->avg('days_requested') ?? 0;
    }

    /**
     * Get most used leave type
     */
    protected function getMostUsedLeaveType(Employee $employee, int $year): ?string
    {
        $mostUsed = LeaveApplication::join('leave_types', 'leave_applications.leave_type_id', '=', 'leave_types.id')
            ->where('leave_applications.employee_id', $employee->id)
            ->where('leave_applications.status', 'approved')
            ->whereYear('leave_applications.start_date', $year)
            ->select('leave_types.name', DB::raw('SUM(leave_applications.days_requested) as total_days'))
            ->groupBy('leave_types.id', 'leave_types.name')
            ->orderBy('total_days', 'desc')
            ->first();

        return $mostUsed?->name;
    }

    /**
     * Calculate all leave types usage for null leave type ID
     */
    protected function calculateUsedDaysAllTypes(Employee $employee, int $year): float
    {
        return LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days_requested') ?? 0;
    }
}