<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MetricsCalculationService
{
    /**
     * Calculate employee turnover rate for the current year
     */
    public function calculateTurnoverRate(): float
    {
        $startOfYear = now()->startOfYear();
        $averageEmployees = $this->getAverageEmployeeCount($startOfYear, now());
        
        if ($averageEmployees == 0) {
            return 0.0;
        }

        $separations = Employee::onlyTrashed()
            ->whereBetween('deleted_at', [$startOfYear, now()])
            ->count();

        return ($separations / $averageEmployees) * 100;
    }

    /**
     * Calculate average employee tenure in years
     */
    public function calculateAverageTenure(): float
    {
        $employees = Employee::whereNotNull('date_hired')->get();
        
        if ($employees->isEmpty()) {
            return 0.0;
        }

        $totalTenureMonths = $employees->sum(function ($employee) {
            return now()->diffInMonths($employee->date_hired);
        });

        return round(($totalTenureMonths / $employees->count()) / 12, 2);
    }

    /**
     * Calculate leave utilization rate by department
     */
    public function calculateLeaveUtilizationByDepartment(): array
    {
        $departments = Employee::distinct()->pluck('department')->filter();
        $utilizationRates = [];

        foreach ($departments as $department) {
            $employees = Employee::where('department', $department)->get();
            $totalPossibleLeaveDays = $employees->count() * 15; // Assuming 15 days per employee
            
            $usedLeaveDays = LeaveApplication::whereHas('employee', function ($query) use ($department) {
                $query->where('department', $department);
            })
            ->where('status', 'approved')
            ->whereYear('applied_date', now()->year)
            ->sum('days_requested');

            $utilizationRates[$department] = $totalPossibleLeaveDays > 0 
                ? round(($usedLeaveDays / $totalPossibleLeaveDays) * 100, 2)
                : 0;
        }

        return $utilizationRates;
    }

    /**
     * Calculate performance metrics for employees with performance data
     */
    public function calculatePerformanceMetrics(): array
    {
        // This would be expanded when performance management is fully implemented
        return [
            'employees_with_reviews' => 0, // Placeholder
            'average_performance_score' => 0.0, // Placeholder
            'pending_reviews' => 0, // Placeholder
        ];
    }

    /**
     * Calculate headcount trends by month for the current year
     */
    public function calculateHeadcountTrends(): array
    {
        $trends = [];
        $currentYear = now()->year;

        for ($month = 1; $month <= 12; $month++) {
            $endOfMonth = Carbon::create($currentYear, $month)->endOfMonth();
            
            // Count employees hired before or during this month, not yet terminated
            $headcount = Employee::where('date_hired', '<=', $endOfMonth)
                ->where(function ($query) use ($endOfMonth) {
                    $query->whereNull('deleted_at')
                        ->orWhere('deleted_at', '>', $endOfMonth);
                })
                ->count();

            $trends[] = $headcount;
        }

        return $trends;
    }

    /**
     * Calculate leave approval rates
     */
    public function calculateLeaveApprovalRates(): array
    {
        $currentYear = now()->year;
        
        $totalApplications = LeaveApplication::whereYear('applied_date', $currentYear)->count();
        
        if ($totalApplications == 0) {
            return [
                'approval_rate' => 0,
                'rejection_rate' => 0,
                'pending_rate' => 0,
            ];
        }

        $approved = LeaveApplication::where('status', 'approved')
            ->whereYear('applied_date', $currentYear)
            ->count();
            
        $rejected = LeaveApplication::where('status', 'rejected')
            ->whereYear('applied_date', $currentYear)
            ->count();
            
        $pending = LeaveApplication::where('status', 'pending')
            ->whereYear('applied_date', $currentYear)
            ->count();

        return [
            'approval_rate' => round(($approved / $totalApplications) * 100, 2),
            'rejection_rate' => round(($rejected / $totalApplications) * 100, 2),
            'pending_rate' => round(($pending / $totalApplications) * 100, 2),
        ];
    }

    /**
     * Calculate gender distribution metrics
     */
    public function calculateGenderDistribution(): array
    {
        $distribution = Employee::select('gender', DB::raw('count(*) as count'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->get();

        return [
            'genders' => $distribution->pluck('gender')->toArray(),
            'counts' => $distribution->pluck('count')->toArray(),
        ];
    }

    /**
     * Calculate age distribution metrics
     */
    public function calculateAgeDistribution(): array
    {
        $employees = Employee::whereNotNull('birth_date')->get();
        
        $ageGroups = [
            '20-29' => 0,
            '30-39' => 0,
            '40-49' => 0,
            '50-59' => 0,
            '60+' => 0,
        ];

        foreach ($employees as $employee) {
            $age = now()->diffInYears($employee->birth_date);
            
            if ($age < 30) {
                $ageGroups['20-29']++;
            } elseif ($age < 40) {
                $ageGroups['30-39']++;
            } elseif ($age < 50) {
                $ageGroups['40-49']++;
            } elseif ($age < 60) {
                $ageGroups['50-59']++;
            } else {
                $ageGroups['60+']++;
            }
        }

        return [
            'age_groups' => array_keys($ageGroups),
            'counts' => array_values($ageGroups),
        ];
    }

    /**
     * Get average employee count for a period (for turnover calculation)
     */
    private function getAverageEmployeeCount(Carbon $startDate, Carbon $endDate): float
    {
        $startCount = Employee::where('date_hired', '<=', $startDate)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>', $startDate);
            })
            ->count();

        $endCount = Employee::where('date_hired', '<=', $endDate)
            ->where(function ($query) use ($endDate) {
                $query->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>', $endDate);
            })
            ->count();

        return ($startCount + $endCount) / 2;
    }
}