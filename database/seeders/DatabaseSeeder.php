<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run the role and permission seeder
        $this->call(RoleAndPermissionSeeder::class);

        // Create a Super Admin who is not an employee
        $superAdminUser = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'employee_id' => null, // Super Admin may not be an employee
        ]);
        $superAdminUser->assignRole('Super Admin');

        // Create a sample Employee with a User account
        Employee::factory()
            ->has(User::factory()->state(function (array $attributes, Employee $employee) {
                return [
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'email' => $employee->email,
                ];
            }))
            ->create([
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'email' => 'employee@example.com',
                'department' => 'HR',
                'position' => 'HR Staff'
            ])
            ->user->assignRole('Employee');

        // Create an HR Admin
        Employee::factory()
            ->has(User::factory()->state(function (array $attributes, Employee $employee) {
                return [
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'email' => $employee->email,
                ];
            }))
            ->create([
                 'first_name' => 'Maria',
                 'last_name' => 'Clara',
                 'email' => 'hr@example.com',
                 'department' => 'HR',
                 'position' => 'HR Manager'
             ])
            ->user->assignRole('HR Admin');

        // Seed salary grades for civil service career management
        $this->call(SalaryGradeSeeder::class);

        // Seed sample reports for demonstration
        $this->call(ReportSeeder::class);
    }
}