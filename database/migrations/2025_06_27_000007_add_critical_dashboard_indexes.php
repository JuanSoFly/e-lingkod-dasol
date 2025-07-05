<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds critical database indexes specifically for dashboard performance optimization.
     * These indexes target the most expensive queries identified in the DashboardService:
     * - Birthday queries using birth_date lookups
     * - Department and employment status combined filters 
     * - Leave applications with status, start_date, and end_date combinations
     */
    public function up(): void
    {
        // Add critical index for birthday queries (employees.birth_date)
        // This optimizes getUpcomingBirthdays() method which uses whereMonth() and whereDay() on birth_date
        Schema::table('employees', function (Blueprint $table) {
            $table->index('birth_date', 'idx_employees_birth_date');
        });

        // Add composite index for department and employment status queries
        // This optimizes getDepartmentMetrics() and getEmploymentStatusMetrics() methods
        // as well as filtering active employees by department
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['department', 'employment_status'], 'idx_employees_dept_status');
        });

        // Add critical composite index for leave applications status with date range
        // This optimizes getEmployeesOnLeaveToday() method which filters by:
        // - status = 'approved' 
        // - start_date <= today
        // - end_date >= today
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->index(['status', 'start_date', 'end_date'], 'idx_leave_apps_status_dates');
        });

        // Add index for applied_date year filtering
        // This optimizes getLeaveStatistics() and getLeaveApplicationsByMonth() methods
        // which heavily use whereYear('applied_date', $year) queries
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->index('applied_date', 'idx_leave_apps_applied_date');
        });

        // Add composite index for leave type statistics
        // This optimizes getMostRequestedLeaveTypes() method
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->index(['leave_type_id', 'applied_date'], 'idx_leave_apps_type_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_employees_birth_date');
            $table->dropIndex('idx_employees_dept_status');
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropIndex('idx_leave_apps_status_dates');
            $table->dropIndex('idx_leave_apps_applied_date');
            $table->dropIndex('idx_leave_apps_type_date');
        });
    }
};