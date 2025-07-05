<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface DashboardServiceInterface
{
    /**
     * Get total number of employees
     */
    public function getTotalEmployees(): int;

    /**
     * Get number of active employees
     */
    public function getActiveEmployees(): int;

    /**
     * Get number of pending leave applications
     */
    public function getPendingLeaveApplications(): int;

    /**
     * Get employees on leave today
     */
    public function getEmployeesOnLeaveToday(): int;

    /**
     * Get upcoming birthdays
     */
    public function getUpcomingBirthdays(int $limit = 5): Collection;

    /**
     * Get leave statistics including approved, pending, and rejected counts
     */
    public function getLeaveStatistics(): array;

    /**
     * Get leave applications by month for the current year
     */
    public function getLeaveApplicationsByMonth(): array;

    /**
     * Get department metrics including employee count and distribution
     */
    public function getDepartmentMetrics(): array;

    /**
     * Get employment status distribution
     */
    public function getEmploymentStatusMetrics(): array;

    /**
     * Get new hires for current month
     */
    public function getNewHiresThisMonth(): int;

    /**
     * Get most requested leave types
     */
    public function getMostRequestedLeaveTypes(int $limit = 5): array;

    /**
     * Get average days per leave application
     */
    public function getAverageLeaveDays(): float;

    /**
     * Get comprehensive dashboard data for role-based views
     */
    public function getDashboardData(?User $user = null): array;

    /**
     * Clear all dashboard caches
     */
    public function clearCache(): void;

    /**
     * Warm up the cache by pre-loading common dashboard data
     */
    public function warmCache(): void;
}