<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeavePolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HolidayAwareLeaveCalculator
{
    public function __construct(private HolidayService $holidayService) {}
    
    /**
     * Calculate leave days with holiday awareness
     */
    public function calculateLeaveDays(
        Carbon $start,
        Carbon $end,
        Employee $employee,
        LeavePolicy $policy
    ): float {
        // Use HolidayService for business day calculation
        $businessDays = $this->holidayService->businessDaysBetween($start, $end, $employee);
        
        // Apply leave policy rules
        $days = $businessDays;
        
        // Check if policy has minimum day requirements
        if ($policy->min_days_per_application && $days < $policy->min_days_per_application) {
            Log::warning('Leave days below minimum policy requirement', [
                'employee_id' => $employee->id,
                'policy_id' => $policy->id,
                'calculated_days' => $days,
                'minimum_required' => $policy->min_days_per_application
            ]);
        }
        
        // Check maximum consecutive days
        if ($policy->max_consecutive_days && $days > $policy->max_consecutive_days) {
            Log::warning('Leave days exceed maximum consecutive days', [
                'employee_id' => $employee->id,
                'policy_id' => $policy->id,
                'calculated_days' => $days,
                'maximum_allowed' => $policy->max_consecutive_days
            ]);
        }
        
        return $days;
    }
    
    /**
     * Calculate leave days for application validation
     */
    public function calculateDaysForValidation(
        Carbon $start,
        Carbon $end,
        Employee $employee,
        LeavePolicy $policy,
        bool $includeWeekends = false
    ): array {
        $result = [
            'total_days' => 0,
            'business_days' => 0,
            'holidays' => [],
            'weekend_days' => 0,
            'policy_violations' => []
        ];
        
        // Calculate total days
        $result['total_days'] = $start->diffInDays($end) + 1;
        
        // Calculate business days using HolidayService
        $result['business_days'] = $this->holidayService->businessDaysBetween($start, $end, $employee);
        
        // Get holiday details
        $holidaySummaries = $this->holidayService->getHolidaySummaries($start, $end, $employee);
        $result['holidays'] = $holidaySummaries;
        
        // Calculate weekend days
        $current = $start->copy();
        $result['weekend_days'] = 0;
        
        while ($current->lte($end)) {
            if ($current->isWeekend()) {
                $result['weekend_days']++;
            }
            $current->addDay();
        }
        
        // Apply policy validation
        $result['policy_violations'] = $this->validateAgainstPolicy($start, $end, $result['business_days'], $policy);
        
        return $result;
    }
    
    /**
     * Validate leave period against policy rules
     */
    protected function validateAgainstPolicy(
        Carbon $start,
        Carbon $end,
        float $days,
        LeavePolicy $policy
    ): array {
        $violations = [];
        
        // Check minimum days
        if ($policy->min_days_per_application && $days < $policy->min_days_per_application) {
            $violations[] = "Minimum {$policy->min_days_per_application} day(s) required.";
        }
        
        // Check maximum consecutive days
        if ($policy->max_consecutive_days && $days > $policy->max_consecutive_days) {
            $violations[] = "Maximum {$policy->max_consecutive_days} consecutive day(s) allowed.";
        }
        
        // Check advance notice
        $noticeGiven = now()->diffInDays($start);
        if ($policy->min_advance_notice_days && $noticeGiven < $policy->min_advance_notice_days) {
            $violations[] = "Minimum {$policy->min_advance_notice_days} day(s) advance notice required.";
        }
        
        // Check blocked dates
        if ($policy->blocked_dates) {
            $current = $start->copy();
            while ($current->lte($end)) {
                foreach ($policy->blocked_dates as $blockedDate) {
                    $blocked = Carbon::parse($blockedDate);
                    if ($current->toDateString() === $blocked->toDateString()) {
                        $violations[] = "Leave cannot be taken on {$blocked->format('M d, Y')} (blocked date).";
                        break;
                    }
                }
                $current->addDay();
            }
        }
        
        return $violations;
    }
    
    /**
     * Calculate pro-rated leave entitlement with holiday awareness
     */
    public function calculateProRatedEntitlement(
        Employee $employee,
        LeavePolicy $policy,
        int $year
    ): float {
        if (!$policy->allow_prorated_first_year) {
            return $policy->max_days_per_year;
        }
        
        if (!$employee->date_hired || $employee->date_hired->year !== $year) {
            return $policy->max_days_per_year;
        }
        
        $hireDate = $employee->date_hired;
        $yearEnd = Carbon::create($year, 12, 31);
        
        // Calculate months remaining including hire month
        $monthsRemaining = $hireDate->diffInMonths($yearEnd) + 1;
        
        // Calculate business months (excluding holidays)
        $businessMonths = 0;
        $currentMonth = $hireDate->copy();
        
        while ($currentMonth->lte($yearEnd)) {
            $monthStart = $currentMonth->copy()->startOfMonth();
            $monthEnd = $currentMonth->copy()->endOfMonth();
            
            $businessDaysInMonth = $this->holidayService->businessDaysBetween($monthStart, $monthEnd, $employee);
            $totalDaysInMonth = $monthStart->diffInDays($monthEnd) + 1;
            
            // Consider month as business month if it has reasonable working days
            if ($businessDaysInMonth >= ($totalDaysInMonth * 0.7)) {
                $businessMonths++;
            }
            
            $currentMonth->addMonth();
        }
        
        // Use the more conservative estimate (actual months vs business months)
        $effectiveMonths = min($monthsRemaining, $businessMonths);
        
        return round(($policy->max_days_per_year / 12) * $effectiveMonths, 2);
    }
}