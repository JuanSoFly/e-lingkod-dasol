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

class LeavePolicyService
{
    /**
     * Get all applicable leave policies for an employee
     */
    public function getApplicablePolicies(Employee $employee): Collection
    {
        return LeavePolicy::active()
            ->effective()
            ->forEmploymentStatus($employee->employment_status)
            ->forPosition($employee->position)
            ->forGender($employee->gender)
            ->with('leaveType')
            ->get()
            ->filter(function ($policy) use ($employee) {
                return $policy->appliesTo($employee);
            });
    }

    /**
     * Calculate leave entitlements for an employee for a given year
     */
    public function calculateLeaveEntitlements(Employee $employee, int $year = null): array
    {
        $year = $year ?? now()->year;
        $policies = $this->getApplicablePolicies($employee);
        $entitlements = [];

        foreach ($policies as $policy) {
            $entitlements[] = [
                'leave_type_id' => $policy->leave_type_id,
                'leave_type_name' => $policy->leaveType->name,
                'policy_name' => $policy->name,
                'entitled_days' => $policy->calculateAnnualEntitlement($employee, $year),
                'accrual_method' => $policy->accrual_method,
                'monthly_accrual' => $policy->calculateMonthlyAccrual($employee),
                'policy' => $policy
            ];
        }

        return $entitlements;
    }

    /**
     * Validate a leave application against policies
     */
    public function validateLeaveApplication(Employee $employee, array $applicationData): array
    {
        $leaveTypeId = $applicationData['leave_type_id'];
        $policy = $this->getApplicablePolicies($employee)
            ->where('leave_type_id', $leaveTypeId)
            ->first();

        if (!$policy) {
            return [
                'valid' => false,
                'errors' => ['This leave type is not available for your employment status or position.'],
                'policy' => null
            ];
        }

        $errors = $policy->validateApplication($employee, $applicationData);

        // Additional validations
        $additionalErrors = $this->validateAgainstLeaveBalance($employee, $applicationData, $policy);
        $errors = array_merge($errors, $additionalErrors);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'policy' => $policy
        ];
    }

    /**
     * Validate against current leave balance
     */
    protected function validateAgainstLeaveBalance(Employee $employee, array $applicationData, LeavePolicy $policy): array
    {
        $errors = [];
        $year = Carbon::parse($applicationData['start_date'])->year;
        $daysRequested = $applicationData['days_requested'];
        
        // Get current leave credit
        $leaveCredit = LeaveCredit::where('employee_id', $employee->id)
            ->where('leave_type_id', $policy->leave_type_id)
            ->where('year', $year)
            ->first();

        if (!$leaveCredit) {
            // Create leave credit record if it doesn't exist
            $entitlement = $policy->calculateAnnualEntitlement($employee, $year);
            $leaveCredit = $this->createLeaveCredit($employee, $policy, $year, $entitlement);
        }

        $availableBalance = $leaveCredit->remaining_credits;

        // Check if sufficient balance
        if (!$policy->allow_negative_balance && $daysRequested > $availableBalance) {
            $errors[] = "Insufficient leave balance. Available: {$availableBalance} days, Requested: {$daysRequested} days.";
        }

        // Check monthly limits if applicable
        if ($policy->max_days_per_month) {
            $monthlyUsage = $this->getMonthlyUsage($employee, $policy->leave_type_id, 
                Carbon::parse($applicationData['start_date']));
            
            if (($monthlyUsage + $daysRequested) > $policy->max_days_per_month) {
                $errors[] = "Monthly limit exceeded. Maximum {$policy->max_days_per_month} days per month.";
            }
        }

        return $errors;
    }

    /**
     * Get monthly usage for a specific leave type
     */
    protected function getMonthlyUsage(Employee $employee, int $leaveTypeId, Carbon $date): float
    {
        return LeaveApplication::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->whereYear('start_date', $date->year)
            ->whereMonth('start_date', $date->month)
            ->sum('days_requested');
    }

    /**
     * Create leave credit record for employee
     */
    public function createLeaveCredit(Employee $employee, LeavePolicy $policy, int $year, float $entitlement): LeaveCredit
    {
        return LeaveCredit::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $policy->leave_type_id,
            'year' => $year,
            'earned_credits' => $entitlement,
            'used_credits' => 0,
            'remaining_credits' => $entitlement
        ]);
    }

    /**
     * Update leave credits after application approval/rejection
     */
    public function updateLeaveCredits(LeaveApplication $application): void
    {
        $year = $application->start_date->year;
        
        $leaveCredit = LeaveCredit::where('employee_id', $application->employee_id)
            ->where('leave_type_id', $application->leave_type_id)
            ->where('year', $year)
            ->first();

        if (!$leaveCredit) {
            // Create if doesn't exist
            $policy = $this->getApplicablePolicies($application->employee)
                ->where('leave_type_id', $application->leave_type_id)
                ->first();
                
            if ($policy) {
                $entitlement = $policy->calculateAnnualEntitlement($application->employee, $year);
                $leaveCredit = $this->createLeaveCredit($application->employee, $policy, $year, $entitlement);
            }
        }

        if ($leaveCredit) {
            if ($application->status === 'approved') {
                $leaveCredit->used_credits += $application->days_requested;
                $leaveCredit->remaining_credits = $leaveCredit->earned_credits - $leaveCredit->used_credits;
            } elseif ($application->status === 'rejected' && $leaveCredit->used_credits >= $application->days_requested) {
                // Restore credits if previously approved application is rejected
                $leaveCredit->used_credits -= $application->days_requested;
                $leaveCredit->remaining_credits = $leaveCredit->earned_credits - $leaveCredit->used_credits;
            }
            
            $leaveCredit->save();
        }
    }

    /**
     * Process annual leave credit generation
     */
    public function generateAnnualLeaveCredits(int $year = null): void
    {
        $year = $year ?? now()->year;
        
        Employee::with(['leaveCredits' => function ($query) use ($year) {
            $query->where('year', $year);
        }])->chunk(100, function ($employees) use ($year) {
            foreach ($employees as $employee) {
                $this->generateEmployeeLeaveCredits($employee, $year);
            }
        });
    }

    /**
     * Generate leave credits for a specific employee
     */
    public function generateEmployeeLeaveCredits(Employee $employee, int $year = null): void
    {
        $year = $year ?? now()->year;
        $policies = $this->getApplicablePolicies($employee);

        foreach ($policies as $policy) {
            // Check if credit already exists
            $existingCredit = $employee->leaveCredits()
                ->where('leave_type_id', $policy->leave_type_id)
                ->where('year', $year)
                ->first();

            if (!$existingCredit) {
                $entitlement = $policy->calculateAnnualEntitlement($employee, $year);
                
                if ($entitlement > 0) {
                    $this->createLeaveCredit($employee, $policy, $year, $entitlement);
                    Log::info("Generated leave credit for {$employee->employee_number}: {$entitlement} days of {$policy->leaveType->name}");
                }
            }
        }
    }

    /**
     * Process leave carryovers from previous year
     */
    public function processLeaveCarryovers(int $fromYear = null, int $toYear = null): void
    {
        $fromYear = $fromYear ?? (now()->year - 1);
        $toYear = $toYear ?? now()->year;

        $policies = LeavePolicy::active()
            ->where('allow_carryover', true)
            ->get();

        foreach ($policies as $policy) {
            $this->processCarryoverForPolicy($policy, $fromYear, $toYear);
        }
    }

    /**
     * Process carryover for a specific policy
     */
    protected function processCarryoverForPolicy(LeavePolicy $policy, int $fromYear, int $toYear): void
    {
        $previousYearCredits = LeaveCredit::where('leave_type_id', $policy->leave_type_id)
            ->where('year', $fromYear)
            ->where('remaining_credits', '>', 0)
            ->get();

        foreach ($previousYearCredits as $previousCredit) {
            $carryoverDays = min($previousCredit->remaining_credits, $policy->max_carryover_days ?? $previousCredit->remaining_credits);
            
            if ($carryoverDays > 0) {
                // Get or create current year credit
                $currentCredit = LeaveCredit::firstOrCreate([
                    'employee_id' => $previousCredit->employee_id,
                    'leave_type_id' => $policy->leave_type_id,
                    'year' => $toYear
                ], [
                    'earned_credits' => 0,
                    'used_credits' => 0,
                    'remaining_credits' => 0
                ]);

                // Add carryover to current year
                $currentCredit->earned_credits += $carryoverDays;
                $currentCredit->remaining_credits += $carryoverDays;
                $currentCredit->save();

                Log::info("Carried over {$carryoverDays} days for employee {$previousCredit->employee_id} from {$fromYear} to {$toYear}");
            }
        }
    }

    /**
     * Get leave balance summary for an employee
     */
    public function getLeaveBalanceSummary(Employee $employee, int $year = null): array
    {
        $year = $year ?? now()->year;
        $summary = [];

        $leaveCredits = $employee->leaveCredits()
            ->where('year', $year)
            ->with('leaveType')
            ->get();

        foreach ($leaveCredits as $credit) {
            $policy = $this->getApplicablePolicies($employee)
                ->where('leave_type_id', $credit->leave_type_id)
                ->first();

            $summary[] = [
                'leave_type' => $credit->leaveType->name,
                'earned' => $credit->earned_credits,
                'used' => $credit->used_credits,
                'remaining' => $credit->remaining_credits,
                'can_carryover' => $policy ? $policy->allow_carryover : false,
                'max_carryover' => $policy ? $policy->max_carryover_days : null,
                'monthly_accrual' => $policy ? $policy->calculateMonthlyAccrual($employee) : 0
            ];
        }

        return $summary;
    }

    /**
     * Get comprehensive leave policy report for an employee
     */
    public function getEmployeeLeavePolicyReport(Employee $employee): array
    {
        $policies = $this->getApplicablePolicies($employee);
        $entitlements = $this->calculateLeaveEntitlements($employee);
        $balances = $this->getLeaveBalanceSummary($employee);

        return [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->first_name . ' ' . $employee->last_name,
                'employment_status' => $employee->employment_status,
                'position' => $employee->position,
                'department' => $employee->department,
                'date_hired' => $employee->date_hired
            ],
            'applicable_policies' => $policies->map->getSummary(),
            'entitlements' => $entitlements,
            'current_balances' => $balances
        ];
    }
}