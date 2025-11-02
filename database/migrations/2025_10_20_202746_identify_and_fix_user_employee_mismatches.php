<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, let's identify the current state of user-employee relationships
        $this->identifyOrphanedRecords();

        // Then fix the inconsistencies
        $this->fixOrphanedEmployees();

        // Add proper constraints to prevent future issues
        $this->addDatabaseConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the constraints we added
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });
    }

    /**
     * Identify and log orphaned records
     */
    private function identifyOrphanedRecords(): void
    {
        // Users without employee links
        $usersWithoutEmployees = DB::table('users')
            ->whereNull('employee_id')
            ->whereNotIn('email', ['admin@example.com']) // Exclude Super Admin
            ->count();

        // Employees without user accounts
        $employeesWithoutUsers = DB::table('employees')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.employee_id', 'employees.id');
            })
            ->count();

        // Log the findings for debugging
        if ($usersWithoutEmployees > 0 || $employeesWithoutUsers > 0) {
            \Log::info('User-Employee Mismatch Analysis', [
                'users_without_employees' => $usersWithoutEmployees,
                'employees_without_users' => $employeesWithoutUsers,
                'timestamp' => now()->toDateTimeString()
            ]);
        }
    }

    /**
     * Fix orphaned employees by creating user accounts
     */
    private function fixOrphanedEmployees(): void
    {
        $orphanedEmployees = DB::table('employees')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.employee_id', 'employees.id');
            })
            ->get();

        foreach ($orphanedEmployees as $employee) {
            // Create user account for orphaned employee
            $userId = DB::table('users')->insertGetId([
                'name' => trim($employee->first_name . ' ' . $employee->last_name),
                'email' => $employee->email ?? strtolower(str_replace(' ', '.', $employee->first_name . '.' . $employee->last_name)) . '@dasol.gov.ph',
                'password' => Hash::make('password123'), // Default password that should be changed
                'employee_id' => $employee->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign default Employee role
            DB::table('model_has_roles')->insert([
                'role_id' => 3, // Employee role ID
                'model_type' => 'App\Models\User',
                'model_id' => $userId,
            ]);

            \Log::info('Created user for orphaned employee', [
                'employee_id' => $employee->id,
                'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                'user_id' => $userId,
                'email' => $employee->email
            ]);
        }
    }

    /**
     * Add database constraints to prevent future mismatches
     */
    private function addDatabaseConstraints(): void
    {
        // We cannot add unique constraint directly in migration because it needs to allow NULL values
        // This will be handled at application level through validation
        // The foreign key constraint already exists and provides referential integrity
    }
};
