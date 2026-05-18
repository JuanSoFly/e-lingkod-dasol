<?php

namespace App\Services;

use App\Contracts\DashboardServiceInterface;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\OfficeAssignment;
use App\Support\DatabaseExpression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DashboardService implements DashboardServiceInterface
{
    /**
     * Cache duration in minutes
     */
    private const CACHE_DURATION = 5;

    /**
     * Get total number of employees
     */
    public function getTotalEmployees(): int
    {
        return Cache::remember('dashboard.total_employees', self::CACHE_DURATION, function () {
            return Employee::count();
        });
    }

    /**
     * Get number of active employees
     */
    public function getActiveEmployees(): int
    {
        return Cache::remember('dashboard.active_employees', self::CACHE_DURATION, function () {
            return Employee::active()->count();
        });
    }

    /**
     * Get number of pending leave applications
     */
    public function getPendingLeaveApplications(): int
    {
        return Cache::remember('dashboard.pending_leave_applications', self::CACHE_DURATION, function () {
            return LeaveApplication::where('status', 'pending')->count();
        });
    }

    /**
     * Get employees on leave today
     */
    public function getEmployeesOnLeaveToday(): int
    {
        return Cache::remember('dashboard.employees_on_leave_today', self::CACHE_DURATION, function () {
            $today = now()->toDateString();
            return LeaveApplication::where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->count();
        });
    }

    /**
     * Get upcoming birthdays (next 5 employees)
     */
    public function getUpcomingBirthdays(int $limit = 5): Collection
    {
        return Cache::remember("dashboard.upcoming_birthdays_{$limit}", self::CACHE_DURATION, function () use ($limit) {
            $currentMonth = now()->month;
            $currentDay = now()->day;
            $birthdayDayExpression = $this->birthdayDayExpression();

            // Get birthdays for current month from today onwards
            $currentMonthBirthdays = Employee::select('id', 'first_name', 'middle_name', 'last_name', 'birth_date', 'department', 'position')
                ->whereMonth('birth_date', $currentMonth)
                ->whereDay('birth_date', '>=', $currentDay)
                ->orderByRaw("{$birthdayDayExpression} ASC")
                ->take($limit)
                ->get();

            // If we need more birthdays, get from next month
            if ($currentMonthBirthdays->count() < $limit) {
                $nextMonth = $currentMonth == 12 ? 1 : $currentMonth + 1;
                $remaining = $limit - $currentMonthBirthdays->count();
                
                $nextMonthBirthdays = Employee::select('id', 'first_name', 'middle_name', 'last_name', 'birth_date', 'department', 'position')
                    ->whereMonth('birth_date', $nextMonth)
                    ->orderByRaw("{$birthdayDayExpression} ASC")
                    ->take($remaining)
                    ->get();

                return $currentMonthBirthdays->merge($nextMonthBirthdays);
            }

            return $currentMonthBirthdays;
        });
    }

    /**
     * Get leave statistics including approved, pending, and rejected counts
     */
    public function getLeaveStatistics(): array
    {
        return Cache::remember('dashboard.leave_statistics', self::CACHE_DURATION, function () {
            $currentYear = now()->year;
            
            return [
                'approved' => LeaveApplication::where('status', 'approved')
                    ->whereYear('applied_date', $currentYear)
                    ->count(),
                'pending' => LeaveApplication::where('status', 'pending')
                    ->whereYear('applied_date', $currentYear)
                    ->count(),
                'rejected' => LeaveApplication::where('status', 'rejected')
                    ->whereYear('applied_date', $currentYear)
                    ->count(),
                'cancelled' => LeaveApplication::where('status', 'cancelled')
                    ->whereYear('applied_date', $currentYear)
                    ->count(),
                'total' => LeaveApplication::whereYear('applied_date', $currentYear)->count(),
            ];
        });
    }

    /**
     * Get leave applications by month for the current year
     */
    public function getLeaveApplicationsByMonth(): array
    {
        return Cache::remember('dashboard.leave_applications_by_month', self::CACHE_DURATION, function () {
            $currentYear = now()->year;
            $monthExpression = $this->monthExpression('applied_date');
            
            $monthlyData = LeaveApplication::selectRaw("{$monthExpression} as month, COUNT(*) as count")
                ->whereYear('applied_date', $currentYear)
                ->groupByRaw($monthExpression)
                ->orderByRaw($monthExpression)
                ->pluck('count', 'month')
                ->toArray();

            // Fill missing months with 0
            $result = [];
            for ($i = 1; $i <= 12; $i++) {
                $result[] = $monthlyData[$i] ?? 0;
            }

            return $result;
        });
    }

    /**
     * Get department metrics including employee count and distribution
     */
    public function getDepartmentMetrics(): array
    {
        return Cache::remember('dashboard.department_metrics', self::CACHE_DURATION, function () {
            $departmentCounts = Employee::select('department', DB::raw('count(*) as count'))
                ->whereNotNull('department')
                ->groupBy('department')
                ->orderBy('count', 'desc')
                ->get();

            return [
                'departments' => $departmentCounts->pluck('department')->toArray(),
                'counts' => $departmentCounts->pluck('count')->toArray(),
                'total_departments' => $departmentCounts->count(),
            ];
        });
    }

    /**
     * Get employment status distribution
     */
    public function getEmploymentStatusMetrics(): array
    {
        return Cache::remember('dashboard.employment_status_metrics', self::CACHE_DURATION, function () {
            $statusCounts = Employee::select('employment_status', DB::raw('count(*) as count'))
                ->whereNotNull('employment_status')
                ->groupBy('employment_status')
                ->get();

            return [
                'statuses' => $statusCounts->pluck('employment_status')->toArray(),
                'counts' => $statusCounts->pluck('count')->toArray(),
            ];
        });
    }

    /**
     * Get new hires for current month
     */
    public function getNewHiresThisMonth(): int
    {
        return Cache::remember('dashboard.new_hires_this_month', self::CACHE_DURATION, function () {
            return Employee::whereMonth('date_hired', now()->month)
                ->whereYear('date_hired', now()->year)
                ->count();
        });
    }

    /**
     * Get most requested leave types
     */
    public function getMostRequestedLeaveTypes(int $limit = 5): array
    {
        return Cache::remember("dashboard.most_requested_leave_types_{$limit}", self::CACHE_DURATION, function () use ($limit) {
            $leaveTypes = LeaveApplication::join('leave_types', 'leave_applications.leave_type_id', '=', 'leave_types.id')
                ->select('leave_types.name', DB::raw('count(*) as count'))
                ->whereYear('leave_applications.applied_date', now()->year)
                ->groupBy('leave_types.id', 'leave_types.name')
                ->orderBy('count', 'desc')
                ->take($limit)
                ->get();

            return [
                'names' => $leaveTypes->pluck('name')->toArray(),
                'counts' => $leaveTypes->pluck('count')->toArray(),
            ];
        });
    }

    /**
     * Get average days per leave application
     */
    public function getAverageLeaveDays(): float
    {
        return Cache::remember('dashboard.average_leave_days', self::CACHE_DURATION, function () {
            return (float) LeaveApplication::whereYear('applied_date', now()->year)
                ->avg('days_requested') ?? 0;
        });
    }

    /**
     * Get comprehensive dashboard data for role-based views
     */
    public function getDashboardData(?User $user = null): array
    {
        try {
            // If no user provided, return empty data
            if (!$user) {
                return $this->getEmptyDashboardData();
            }

            // Get base data filtered by user role and permissions
            $data = $this->getFilteredDashboardData($user);

            // Add role-specific data
            $data = $this->addRoleSpecificData($data, $user);

            // Add user context information
            $data['user_context'] = [
                'role' => $user->getRoleNames()->first(),
                'department' => $user->employee?->department,
                'employee_id' => $user->employee?->id,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ];

            return $data;
        } catch (\Exception $e) {
            Log::error('Dashboard data retrieval failed', [
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
                'roles' => $user?->getRoleNames(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return empty data structure on error to prevent dashboard failures
            return $this->getEmptyDashboardData();
        }
    }

    /**
     * Get filtered dashboard data based on user role and permissions
     */
    private function getFilteredDashboardData(User $user): array
    {
        $data = [];

        // Determine data access level based on role priority
        // 1. Final Approver - Global Access
        if ($user->hasRole('Final Approver')) {
            return $this->getFinalApproverData($user);
        }
        
        // 2. Department Head - Office Scope
        if ($user->hasRole('Department Head')) {
            return $this->getDepartmentHeadData($user);
        }

        // 3. Supervisor - Office Scope
        if ($user->hasRole('Supervisor')) {
            return $this->getSupervisorData($user);
        }
        
        // 4. Assessor - Limited Scope
        if ($user->hasRole('Assessor')) {
            return $this->getAssessorData($user);
        }

        // 5. HR Admin - Full Access (legacy role support)
        if ($user->hasRole('HR Admin')) {
            return $this->getHRAdminData($user);
        }

        // 6. Super Admin
        if ($user->hasRole('Super Admin')) {
            return $this->getSuperAdminData($user);
        }

        // 7. Regular Employee
        if ($user->hasRole('Employee')) {
            return $this->getEmployeeData($user);
        }

        // Fallback
        return $this->getPermissionBasedData($user);
    }

    /**
     * Get Super Admin dashboard data - full access to all metrics and system data
     */
    private function getSuperAdminData(User $user): array
    {
        return [
            'metrics' => [
                'total_employees' => $this->getTotalEmployees(),
                'active_employees' => $this->getActiveEmployees(),
                'pending_leave_applications' => $this->getPendingLeaveApplications(),
                'employees_on_leave_today' => $this->getEmployeesOnLeaveToday(),
                'new_hires_this_month' => $this->getNewHiresThisMonth(),
                'average_leave_days' => $this->getAverageLeaveDays(),
                // Super Admin specific metrics
                'total_users' => User::count(),
                'inactive_employees' => Employee::whereNotIn(DB::raw('LOWER(employment_status)'), Employee::ACTIVE_EMPLOYMENT_STATUSES)->count(),
                'system_health_score' => 95, // Placeholder for system health monitoring
            ],
            'upcoming_birthdays' => $this->getUpcomingBirthdays(),
            'leave_statistics' => $this->getLeaveStatistics(),
            'leave_applications_by_month' => $this->getLeaveApplicationsByMonth(),
            'department_metrics' => $this->getDepartmentMetrics(),
            'employment_status_metrics' => $this->getEmploymentStatusMetrics(),
            'most_requested_leave_types' => $this->getMostRequestedLeaveTypes(),
            'role_view' => 'super_admin',
        ];
    }

    /**
     * Get HR Admin dashboard data - organization-wide data except system metrics
     */
    private function getHRAdminData(User $user): array
    {
        return [
            'metrics' => [
                'total_employees' => $this->getTotalEmployees(),
                'active_employees' => $this->getActiveEmployees(),
                'pending_leave_applications' => $this->getPendingLeaveApplications(),
                'employees_on_leave_today' => $this->getEmployeesOnLeaveToday(),
                'new_hires_this_month' => $this->getNewHiresThisMonth(),
                'average_leave_days' => $this->getAverageLeaveDays(),
            ],
            'upcoming_birthdays' => $this->getUpcomingBirthdays(),
            'leave_statistics' => $this->getLeaveStatistics(),
            'leave_applications_by_month' => $this->getLeaveApplicationsByMonth(),
            'department_metrics' => $this->getDepartmentMetrics(),
            'employment_status_metrics' => $this->getEmploymentStatusMetrics(),
            'most_requested_leave_types' => $this->getMostRequestedLeaveTypes(),
            'role_view' => 'hr_admin',
        ];
    }

    /**
     * Get Final Approver dashboard data - Global access
     */
    private function getFinalApproverData(User $user): array
    {
        // Final Approver sees everything global, same as HR Admin/Super Admin regarding metrics
        return [
             'metrics' => [
                'total_employees' => $this->getTotalEmployees(),
                'active_employees' => $this->getActiveEmployees(),
                'pending_leave_applications' => $this->getPendingLeaveApplications(),
                'employees_on_leave_today' => $this->getEmployeesOnLeaveToday(),
                'new_hires_this_month' => $this->getNewHiresThisMonth(),
                'average_leave_days' => $this->getAverageLeaveDays(),
            ],
            'upcoming_birthdays' => $this->getUpcomingBirthdays(),
            'leave_statistics' => $this->getLeaveStatistics(),
            'leave_applications_by_month' => $this->getLeaveApplicationsByMonth(),
            'department_metrics' => $this->getDepartmentMetrics(),
            'employment_status_metrics' => $this->getEmploymentStatusMetrics(),
            'most_requested_leave_types' => $this->getMostRequestedLeaveTypes(),
            // Flag to ensure UI shows everything
            'role_view' => 'global',
        ];
    }

    /**
     * Get Assessor dashboard data - Limited access
     */
    private function getAssessorData(User $user): array
    {
        // Assessor:
        // - "Total Employees", "On Leave Today", "Pending Approvals", "Quick Overview", "Upcoming Birthdays": HIDE
        // - "Workforce Distribution": SHOW (All departments)
        
        return [
            'metrics' => [
                'total_employees' => null, // Hidden
                'active_employees' => null,
                'pending_leave_applications' => null,
                'employees_on_leave_today' => null,
                'new_hires_this_month' => null,
                'average_leave_days' => null,
            ],
            'upcoming_birthdays' => collect([]), // Hidden
            'leave_statistics' => $this->getEmptyLeaveStatistics(), // Hidden
            'leave_applications_by_month' => [],
            'department_metrics' => $this->getDepartmentMetrics(), // SHOW GLOBAL
            'employment_status_metrics' => [],
            'most_requested_leave_types' => [],
             // Flag to help UI hide sections
            'role_view' => 'assessor',
        ];

    }

    /**
     * Get Supervisor dashboard data - Office limited
     */
    private function getSupervisorData(User $user): array
    {
        // Get active supervisor assignments
        $officeIds = OfficeAssignment::where('user_id', $user->id)
            ->where('role', 'Supervisor')
            ->where('is_active', true)
            ->pluck('office_id')
            ->toArray();

        if (empty($officeIds)) {
            // Fallback: If no assignment but has role, maybe fallback to employee department? 
            // For strictness, return empty if no assignment found to avoid data leak.
            return $this->getEmptyDashboardData();
        }

        return $this->getScopedDashboardData($officeIds, 'supervisor');
    }

    /**
     * Get Department Head dashboard data - department-scoped data only
     */
    private function getDepartmentHeadData(User $user): array
    {
        // Get active Dept Head assignments
        $officeIds = OfficeAssignment::where('user_id', $user->id)
            ->where('role', 'Department Head')
            ->where('is_active', true)
            ->pluck('office_id')
            ->toArray();
        
        // Fallback to employee department if no office assignment (legacy support)
        if (empty($officeIds) && $user->employee && $user->employee->department) {
             // We need office ID, find office by name? 
             // Or filter by department string in Employee table.
             // For now, let's assume we can filter by department string if office_id is missing, 
             // but to be consistent with new schema, let's try to map it.
             // Helper to get scoped data by department NAME if needed.
             return $this->getScopedDashboardDataByDeptName([$user->employee->department], 'department_head');
        }

        if (empty($officeIds)) {
             return $this->getEmptyDashboardData();
        }

        return $this->getScopedDashboardData($officeIds, 'department_head');
    }

    /**
     * Shared method to get scoped data by Office IDs
     */
    private function getScopedDashboardData(array $officeIds, string $roleView): array
    {
        return [
            'metrics' => [
                'total_employees' => $this->getScopedEmployeeCount($officeIds),
                'active_employees' => $this->getScopedActiveEmployeeCount($officeIds),
                'pending_leave_applications' => $this->getScopedPendingLeaveApplications($officeIds),
                'employees_on_leave_today' => $this->getScopedEmployeesOnLeaveToday($officeIds),
                'new_hires_this_month' => $this->getScopedNewHiresThisMonth($officeIds),
                'average_leave_days' => 0, // Hard to calc scoped avg efficiently without more methods, keeping 0 for now or add method
            ],
            'upcoming_birthdays' => $this->getScopedUpcomingBirthdays($officeIds),
            'leave_statistics' => $this->getScopedLeaveStatistics($officeIds),
            'leave_applications_by_month' => [], // Keep empty for scoped views to avoid clutter
            'department_metrics' => $this->getScopedDepartmentMetrics($officeIds),
            'employment_status_metrics' => [],
            'most_requested_leave_types' => [],
            'role_view' => $roleView,
        ];
    }
    
    /**
     * Helper to return empty statistics array
     */
    private function getEmptyLeaveStatistics(): array
    {
        return [
             'approved' => 0, 'pending' => 0, 'rejected' => 0, 'cancelled' => 0, 'total' => 0
        ];
    }

    // ===================================
    // SCOPED QUERIES
    // ===================================

    private function getScopedEmployeeCount(array $officeIds): int
    {
         return Employee::whereIn('office_id', $officeIds)->count();
    }

    private function getScopedActiveEmployeeCount(array $officeIds): int
    {
         return Employee::whereIn('office_id', $officeIds)->active()->count();
    }

    private function getScopedPendingLeaveApplications(array $officeIds): int
    {
        return LeaveApplication::whereHas('employee', function ($q) use ($officeIds) {
            $q->whereIn('office_id', $officeIds);
        })->where('status', 'pending')->count();
    }

    private function getScopedEmployeesOnLeaveToday(array $officeIds): int
    {
        $today = now()->toDateString();
        return LeaveApplication::whereHas('employee', function ($q) use ($officeIds) {
            $q->whereIn('office_id', $officeIds);
        })
        ->where('status', 'approved')
        ->where('start_date', '<=', $today)
        ->where('end_date', '>=', $today)
        ->count();
    }

    private function getScopedNewHiresThisMonth(array $officeIds): int
    {
        return Employee::whereIn('office_id', $officeIds)
            ->whereMonth('date_hired', now()->month)
            ->whereYear('date_hired', now()->year)
            ->count();
    }

    private function getScopedUpcomingBirthdays(array $officeIds, int $limit = 5): Collection
    {
        $currentMonth = now()->month;
        $currentDay = now()->day;
        $birthdayDayExpression = $this->birthdayDayExpression();
        
        $query = Employee::whereIn('office_id', $officeIds);

        // Get birthdays for current month from today onwards
        $currentMonthBirthdays = (clone $query)
            ->whereMonth('birth_date', $currentMonth)
            ->whereDay('birth_date', '>=', $currentDay)
            ->orderByRaw("{$birthdayDayExpression} ASC")
            ->take($limit)
            ->get();

        if ($currentMonthBirthdays->count() < $limit) {
            $nextMonth = $currentMonth == 12 ? 1 : $currentMonth + 1;
            $remaining = $limit - $currentMonthBirthdays->count();
            
            $nextMonthBirthdays = (clone $query)
                ->whereMonth('birth_date', $nextMonth)
                ->orderByRaw("{$birthdayDayExpression} ASC")
                ->take($remaining)
                ->get();

            return $currentMonthBirthdays->merge($nextMonthBirthdays);
        }

        return $currentMonthBirthdays;
    }

    private function getScopedLeaveStatistics(array $officeIds): array
    {
        $currentYear = now()->year;
        $query = LeaveApplication::whereHas('employee', function ($q) use ($officeIds) {
            $q->whereIn('office_id', $officeIds);
        })->whereYear('applied_date', $currentYear);

        return [
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'total' => $query->count(),
        ];
    }
    
    private function getScopedDepartmentMetrics(array $officeIds): array 
    {
         // For scoped view, essentially show distribution within the offices (or just the offices themselves)
         // Since office_id might map to multiple departments? 
         // Let's just group by department for employees in those offices.
         $departmentCounts = Employee::whereIn('office_id', $officeIds)
            ->select('department', DB::raw('count(*) as count'))
            ->whereNotNull('department')
            ->groupBy('department')
            ->orderBy('count', 'desc')
            ->get();

        return [
            'departments' => $departmentCounts->pluck('department')->toArray(),
            'counts' => $departmentCounts->pluck('count')->toArray(),
            'total_departments' => $departmentCounts->count(),
        ];
    }

    /** 
     * Legacy Support: Get scoped data by Department Name string
     */ 
    private function getScopedDashboardDataByDeptName(array $deptNames, string $roleView): array
    {
         // IMPLEMENT similar to scoped but with whereIn('department', ...)
         // For brevity, using simplified implementation
        return [
            'metrics' => [
                'total_employees' => Employee::whereIn('department', $deptNames)->count(),
                'active_employees' => Employee::whereIn('department', $deptNames)->active()->count(),
                'pending_leave_applications' => LeaveApplication::whereHas('employee', fn($q) => $q->whereIn('department', $deptNames))->where('status', 'pending')->count(),
                'employees_on_leave_today' => 0,
                'new_hires_this_month' => 0,
                'average_leave_days' => 0,
            ],
            'upcoming_birthdays' => collect([]),
            'leave_statistics' => $this->getEmptyLeaveStatistics(),
            'leave_applications_by_month' => [],
            'department_metrics' => [ 'departments' => $deptNames, 'counts' => [], 'total_departments' => count($deptNames)],
            'employment_status_metrics' => [],
            'most_requested_leave_types' => [],
            'role_view' => $roleView,
        ];
    }

    /**
     * Get Employee dashboard data - personal data only
     */
    private function getEmployeeData(User $user): array
    {
        if (!$user->employee) {
            return $this->getEmptyDashboardData();
        }

        $employee = $user->employee;

        return [
            'metrics' => [
                'total_employees' => 1, // Only show self
                'active_employees' => $employee->isActiveEmployment() ? 1 : 0,
                'pending_leave_applications' => $this->getPersonalPendingLeaveApplications($employee->id),
                'employees_on_leave_today' => $this->isEmployeeOnLeaveToday($employee->id) ? 1 : 0,
                'new_hires_this_month' => 0, // Not relevant for employee view
                'average_leave_days' => $this->getPersonalAverageLeaveDays($employee->id),
            ],
            'upcoming_birthdays' => collect([$employee]), // Only show own birthday if upcoming
            'leave_statistics' => $this->getPersonalLeaveStatistics($employee->id),
            'leave_applications_by_month' => $this->getPersonalLeaveApplicationsByMonth($employee->id),
            'department_metrics' => [
                'departments' => [$employee->department],
                'counts' => [1],
                'total_departments' => 1,
            ],
            'employment_status_metrics' => [
                'statuses' => [$employee->employment_status],
                'counts' => [1],
            ],
            'most_requested_leave_types' => $this->getPersonalMostRequestedLeaveTypes($employee->id),
        ];
    }

    /**
     * Get dashboard data based on specific permissions (fallback method)
     */
    private function getPermissionBasedData(User $user): array
    {
        $data = $this->getEmptyDashboardData();

        // Add data based on specific permissions
        if ($user->can('employee.view')) {
            if ($user->employee) {
                // If user has employee.view but limited scope, show department data
                $department = $user->employee->department;
                if ($department) {
                    $data['metrics']['total_employees'] = $this->getDepartmentEmployeeCount($department);
                    $data['department_metrics'] = [
                        'departments' => [$department],
                        'counts' => [$this->getDepartmentEmployeeCount($department)],
                        'total_departments' => 1,
                    ];
                }
            }
        }

        if ($user->can('leave.view')) {
            if ($user->can('leave.approve')) {
                // Can see all leave data
                $data['metrics']['pending_leave_applications'] = $this->getPendingLeaveApplications();
                $data['leave_statistics'] = $this->getLeaveStatistics();
            } else {
                // Can only see personal leave data
                if ($user->employee) {
                    $data['metrics']['pending_leave_applications'] = $this->getPersonalPendingLeaveApplications($user->employee->id);
                    $data['leave_statistics'] = $this->getPersonalLeaveStatistics($user->employee->id);
                }
            }
        }

        return $data;
    }

    /**
     * Add role-specific data to dashboard
     */
    private function addRoleSpecificData(array $data, User $user): array
    {
        // If user is an employee, add personal metrics
        if ($user->employee && $user->hasRole('Employee')) {
            $data['personal'] = [
                'my_leave_applications' => $user->employee->leaveApplications()
                    ->whereYear('applied_date', now()->year)
                    ->count(),
                'my_pending_applications' => $user->employee->leaveApplications()
                    ->where('status', 'pending')
                    ->count(),
                'my_approved_leaves' => $user->employee->leaveApplications()
                    ->where('status', 'approved')
                    ->whereYear('applied_date', now()->year)
                    ->count(),
            ];
        }

        // If user can approve leaves, add supervisor metrics
        if ($user->can('leave.approve')) {
            $data['supervisor'] = [
                'pending_approvals' => $this->getPendingLeaveApplications(),
                'approvals_this_month' => LeaveApplication::where('approved_by', $user->id)
                    ->whereMonth('approved_date', now()->month)
                    ->whereYear('approved_date', now()->year)
                    ->count(),
            ];
        }

        return $data;
    }

    /**
     * Get empty dashboard data structure for error cases
     */
    private function getEmptyDashboardData(): array
    {
        return [
            'metrics' => [
                'total_employees' => 0,
                'active_employees' => 0,
                'pending_leave_applications' => 0,
                'employees_on_leave_today' => 0,
                'new_hires_this_month' => 0,
                'average_leave_days' => 0,
            ],
            'upcoming_birthdays' => collect([]),
            'leave_statistics' => [
                'approved' => 0,
                'pending' => 0,
                'rejected' => 0,
                'cancelled' => 0,
                'total' => 0,
            ],
            'leave_applications_by_month' => array_fill(0, 12, 0),
            'department_metrics' => [
                'departments' => [],
                'counts' => [],
                'total_departments' => 0,
            ],
            'employment_status_metrics' => [
                'statuses' => [],
                'counts' => [],
            ],
            'most_requested_leave_types' => [
                'names' => [],
                'counts' => [],
            ],
        ];
    }

    /**
     * Clear all dashboard caches
     */
    public function clearCache(): void
    {
        // Clear organization-wide cache keys
        $cacheKeys = [
            'dashboard.total_employees',
            'dashboard.active_employees',
            'dashboard.pending_leave_applications',
            'dashboard.employees_on_leave_today',
            'dashboard.upcoming_birthdays_5',
            'dashboard.leave_statistics',
            'dashboard.leave_applications_by_month',
            'dashboard.department_metrics',
            'dashboard.employment_status_metrics',
            'dashboard.new_hires_this_month',
            'dashboard.most_requested_leave_types_5',
            'dashboard.average_leave_days',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Clear department-specific caches
        $departments = Employee::distinct()->pluck('department')->filter();
        foreach ($departments as $department) {
            $deptCacheKeys = [
                "dashboard.dept_{$department}.total_employees",
                "dashboard.dept_{$department}.active_employees",
                "dashboard.dept_{$department}.pending_leave_applications",
                "dashboard.dept_{$department}.employees_on_leave_today",
                "dashboard.dept_{$department}.new_hires_this_month",
                "dashboard.dept_{$department}.average_leave_days",
                "dashboard.dept_{$department}.upcoming_birthdays_5",
                "dashboard.dept_{$department}.leave_statistics",
                "dashboard.dept_{$department}.leave_applications_by_month",
                "dashboard.dept_{$department}.employment_status_metrics",
                "dashboard.dept_{$department}.most_requested_leave_types_5",
            ];

            foreach ($deptCacheKeys as $key) {
                Cache::forget($key);
            }
        }

        // Clear employee-specific caches
        $employeeIds = Employee::pluck('id');
        foreach ($employeeIds as $employeeId) {
            $empCacheKeys = [
                "dashboard.employee_{$employeeId}.pending_leave_applications",
                "dashboard.employee_{$employeeId}.on_leave_today",
                "dashboard.employee_{$employeeId}.average_leave_days",
                "dashboard.employee_{$employeeId}.leave_statistics",
                "dashboard.employee_{$employeeId}.leave_applications_by_month",
                "dashboard.employee_{$employeeId}.most_requested_leave_types_5",
            ];

            foreach ($empCacheKeys as $key) {
                Cache::forget($key);
            }
        }

        Log::info('Dashboard cache cleared', [
            'departments_cleared' => $departments->count(),
            'employees_cleared' => $employeeIds->count(),
        ]);
    }

    /**
     * Warm up the cache by pre-loading common dashboard data
     */
    public function warmCache(): void
    {
        try {
            $this->getDashboardData();
            Log::info('Dashboard cache warmed successfully');
        } catch (\Exception $e) {
            Log::error('Dashboard cache warming failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =============================================================================
    // DEPARTMENT-SCOPED METHODS FOR DEPARTMENT HEAD ROLE
    // =============================================================================

    /**
     * Get department employee count
     */
    private function getDepartmentEmployeeCount(string $department): int
    {
        return Cache::remember("dashboard.dept_{$department}.total_employees", self::CACHE_DURATION, function () use ($department) {
            return Employee::where('department', $department)->count();
        });
    }

    /**
     * Get department active employee count
     */
    private function getDepartmentActiveEmployeeCount(string $department): int
    {
        return Cache::remember("dashboard.dept_{$department}.active_employees", self::CACHE_DURATION, function () use ($department) {
            return Employee::where('department', $department)
                ->active()
                ->count();
        });
    }

    /**
     * Get department pending leave applications
     */
    private function getDepartmentPendingLeaveApplications(string $department): int
    {
        return Cache::remember("dashboard.dept_{$department}.pending_leave_applications", self::CACHE_DURATION, function () use ($department) {
            return LeaveApplication::whereHas('employee', function ($query) use ($department) {
                $query->where('department', $department);
            })->where('status', 'pending')->count();
        });
    }

    /**
     * Get department employees on leave today
     */
    private function getDepartmentEmployeesOnLeaveToday(string $department): int
    {
        return Cache::remember("dashboard.dept_{$department}.employees_on_leave_today", self::CACHE_DURATION, function () use ($department) {
            $today = now()->toDateString();
            return LeaveApplication::whereHas('employee', function ($query) use ($department) {
                $query->where('department', $department);
            })
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->count();
        });
    }

    /**
     * Get department new hires this month
     */
    private function getDepartmentNewHiresThisMonth(string $department): int
    {
        return Cache::remember("dashboard.dept_{$department}.new_hires_this_month", self::CACHE_DURATION, function () use ($department) {
            return Employee::where('department', $department)
                ->whereMonth('date_hired', now()->month)
                ->whereYear('date_hired', now()->year)
                ->count();
        });
    }

    /**
     * Get department average leave days
     */
    private function getDepartmentAverageLeaveDays(string $department): float
    {
        return Cache::remember("dashboard.dept_{$department}.average_leave_days", self::CACHE_DURATION, function () use ($department) {
            return (float) LeaveApplication::whereHas('employee', function ($query) use ($department) {
                $query->where('department', $department);
            })
            ->whereYear('applied_date', now()->year)
            ->avg('days_requested') ?? 0;
        });
    }

    /**
     * Get department upcoming birthdays
     */
    private function getDepartmentUpcomingBirthdays(string $department, int $limit = 5): Collection
    {
        return Cache::remember("dashboard.dept_{$department}.upcoming_birthdays_{$limit}", self::CACHE_DURATION, function () use ($department, $limit) {
            $currentMonth = now()->month;
            $currentDay = now()->day;
            $birthdayDayExpression = $this->birthdayDayExpression();

            // Get birthdays for current month from today onwards
            $currentMonthBirthdays = Employee::where('department', $department)
                ->whereMonth('birth_date', $currentMonth)
                ->whereDay('birth_date', '>=', $currentDay)
                ->orderByRaw("{$birthdayDayExpression} ASC")
                ->take($limit)
                ->get();

            // If we need more birthdays, get from next month
            if ($currentMonthBirthdays->count() < $limit) {
                $nextMonth = $currentMonth == 12 ? 1 : $currentMonth + 1;
                $remaining = $limit - $currentMonthBirthdays->count();
                
                $nextMonthBirthdays = Employee::where('department', $department)
                    ->whereMonth('birth_date', $nextMonth)
                    ->orderByRaw("{$birthdayDayExpression} ASC")
                    ->take($remaining)
                    ->get();

                return $currentMonthBirthdays->merge($nextMonthBirthdays);
            }

            return $currentMonthBirthdays;
        });
    }

    private function birthdayDayExpression(): string
    {
        return DatabaseExpression::datePart('day', 'birth_date');
    }

    private function monthExpression(string $column): string
    {
        return DatabaseExpression::datePart('month', $column);
    }

    /**
     * Get department leave statistics
     */
    private function getDepartmentLeaveStatistics(string $department): array
    {
        return Cache::remember("dashboard.dept_{$department}.leave_statistics", self::CACHE_DURATION, function () use ($department) {
            $currentYear = now()->year;
            
            $query = LeaveApplication::whereHas('employee', function ($q) use ($department) {
                $q->where('department', $department);
            })->whereYear('applied_date', $currentYear);

            return [
                'approved' => (clone $query)->where('status', 'approved')->count(),
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'rejected' => (clone $query)->where('status', 'rejected')->count(),
                'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
                'total' => $query->count(),
            ];
        });
    }

    /**
     * Get department leave applications by month
     */
    private function getDepartmentLeaveApplicationsByMonth(string $department): array
    {
        return Cache::remember("dashboard.dept_{$department}.leave_applications_by_month", self::CACHE_DURATION, function () use ($department) {
            $currentYear = now()->year;
            $monthExpression = $this->monthExpression('applied_date');
            
            $monthlyData = LeaveApplication::whereHas('employee', function ($query) use ($department) {
                $query->where('department', $department);
            })
            ->selectRaw("{$monthExpression} as month, COUNT(*) as count")
            ->whereYear('applied_date', $currentYear)
            ->groupByRaw($monthExpression)
            ->orderByRaw($monthExpression)
            ->pluck('count', 'month')
            ->toArray();

            // Fill missing months with 0
            $result = [];
            for ($i = 1; $i <= 12; $i++) {
                $result[] = $monthlyData[$i] ?? 0;
            }

            return $result;
        });
    }

    /**
     * Get department employment status metrics
     */
    private function getDepartmentEmploymentStatusMetrics(string $department): array
    {
        return Cache::remember("dashboard.dept_{$department}.employment_status_metrics", self::CACHE_DURATION, function () use ($department) {
            $statusCounts = Employee::where('department', $department)
                ->select('employment_status', DB::raw('count(*) as count'))
                ->whereNotNull('employment_status')
                ->groupBy('employment_status')
                ->get();

            return [
                'statuses' => $statusCounts->pluck('employment_status')->toArray(),
                'counts' => $statusCounts->pluck('count')->toArray(),
            ];
        });
    }

    /**
     * Get department most requested leave types
     */
    private function getDepartmentMostRequestedLeaveTypes(string $department, int $limit = 5): array
    {
        return Cache::remember("dashboard.dept_{$department}.most_requested_leave_types_{$limit}", self::CACHE_DURATION, function () use ($department, $limit) {
            $leaveTypes = LeaveApplication::join('employees', 'leave_applications.employee_id', '=', 'employees.id')
                ->join('leave_types', 'leave_applications.leave_type_id', '=', 'leave_types.id')
                ->where('employees.department', $department)
                ->select('leave_types.name', DB::raw('count(*) as count'))
                ->whereYear('leave_applications.applied_date', now()->year)
                ->groupBy('leave_types.id', 'leave_types.name')
                ->orderBy('count', 'desc')
                ->take($limit)
                ->get();

            return [
                'names' => $leaveTypes->pluck('name')->toArray(),
                'counts' => $leaveTypes->pluck('count')->toArray(),
            ];
        });
    }

    // =============================================================================
    // PERSONAL METHODS FOR EMPLOYEE ROLE
    // =============================================================================

    /**
     * Get personal pending leave applications
     */
    private function getPersonalPendingLeaveApplications(int $employeeId): int
    {
        return Cache::remember("dashboard.employee_{$employeeId}.pending_leave_applications", self::CACHE_DURATION, function () use ($employeeId) {
            return LeaveApplication::where('employee_id', $employeeId)
                ->where('status', 'pending')
                ->count();
        });
    }

    /**
     * Check if employee is on leave today
     */
    private function isEmployeeOnLeaveToday(int $employeeId): bool
    {
        return Cache::remember("dashboard.employee_{$employeeId}.on_leave_today", self::CACHE_DURATION, function () use ($employeeId) {
            $today = now()->toDateString();
            return LeaveApplication::where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->exists();
        });
    }

    /**
     * Get personal average leave days
     */
    private function getPersonalAverageLeaveDays(int $employeeId): float
    {
        return Cache::remember("dashboard.employee_{$employeeId}.average_leave_days", self::CACHE_DURATION, function () use ($employeeId) {
            return (float) LeaveApplication::where('employee_id', $employeeId)
                ->whereYear('applied_date', now()->year)
                ->avg('days_requested') ?? 0;
        });
    }

    /**
     * Get personal leave statistics
     */
    private function getPersonalLeaveStatistics(int $employeeId): array
    {
        return Cache::remember("dashboard.employee_{$employeeId}.leave_statistics", self::CACHE_DURATION, function () use ($employeeId) {
            $currentYear = now()->year;
            
            $query = LeaveApplication::where('employee_id', $employeeId)
                ->whereYear('applied_date', $currentYear);

            return [
                'approved' => (clone $query)->where('status', 'approved')->count(),
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'rejected' => (clone $query)->where('status', 'rejected')->count(),
                'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
                'total' => $query->count(),
            ];
        });
    }

    /**
     * Get personal leave applications by month
     */
    private function getPersonalLeaveApplicationsByMonth(int $employeeId): array
    {
        return Cache::remember("dashboard.employee_{$employeeId}.leave_applications_by_month", self::CACHE_DURATION, function () use ($employeeId) {
            $currentYear = now()->year;
            $monthExpression = $this->monthExpression('applied_date');
            
            $monthlyData = LeaveApplication::where('employee_id', $employeeId)
                ->selectRaw("{$monthExpression} as month, COUNT(*) as count")
                ->whereYear('applied_date', $currentYear)
                ->groupByRaw($monthExpression)
                ->orderByRaw($monthExpression)
                ->pluck('count', 'month')
                ->toArray();

            // Fill missing months with 0
            $result = [];
            for ($i = 1; $i <= 12; $i++) {
                $result[] = $monthlyData[$i] ?? 0;
            }

            return $result;
        });
    }

    /**
     * Get personal most requested leave types
     */
    private function getPersonalMostRequestedLeaveTypes(int $employeeId, int $limit = 5): array
    {
        return Cache::remember("dashboard.employee_{$employeeId}.most_requested_leave_types_{$limit}", self::CACHE_DURATION, function () use ($employeeId, $limit) {
            $leaveTypes = LeaveApplication::join('leave_types', 'leave_applications.leave_type_id', '=', 'leave_types.id')
                ->where('leave_applications.employee_id', $employeeId)
                ->select('leave_types.name', DB::raw('count(*) as count'))
                ->whereYear('leave_applications.applied_date', now()->year)
                ->groupBy('leave_types.id', 'leave_types.name')
                ->orderBy('count', 'desc')
                ->take($limit)
                ->get();

            return [
                'names' => $leaveTypes->pluck('name')->toArray(),
                'counts' => $leaveTypes->pluck('count')->toArray(),
            ];
        });
    }
    /**
     * Get aggregated dashboard data for a user with optimized caching
     */
    public function getAggregatedDashboardData(User $user): array
    {
        // specific cache key for this user's dashboard view
        $cacheKey = "dashboard_aggregated_{$user->id}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user) {
            // Use the centralized role-based data gathering method
            $rawData = $this->getDashboardData($user);

            $data = [
                'user' => [
                    'name' => $user->full_name ?? $user->name,
                    'roles' => $user->getRoleNames()->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                ],
                'metrics' => [
                    'total_employees' => $rawData['metrics']['total_employees'],
                    'active_employees' => $rawData['metrics']['active_employees'],
                    'pending_leaves' => $rawData['metrics']['pending_leave_applications'],
                    'on_leave_today' => $rawData['metrics']['employees_on_leave_today'], 
                    'new_hires' => $rawData['metrics']['new_hires_this_month'],
                ],
                'charts' => [
                    'employees_by_dept' => $rawData['department_metrics'],
                ],
                'tables' => [
                    'upcoming_birthdays' => $rawData['upcoming_birthdays'],
                ],
                'features' => [
                    'opcr' => $user->can('opcr.view') || $user->hasAnyRole(['Department Head', 'Assessor', 'Final Approver']),
                    'leave_approval' => $user->can('leave.approve'),
                ],
                // Pass the role view flag to the frontend
                'role_view' => $rawData['role_view'] ?? 'employee',
            ];

            return $data;
        });
    }

}
