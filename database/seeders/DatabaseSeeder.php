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

        // Load baseline 2025 holiday data
        $this->call(PhilippineHolidays2025Seeder::class);

        // Ensure default work calendars are available
        $this->call(WorkCalendarSeeder::class);

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
                'department' => 'Human Resource Management Office',
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
                 'department' => 'Human Resource Management Office',
                 'position' => 'HR Manager'
             ])
            ->user->assignRole('HR Admin');

        // Create Department Head for Office of the Municipal Mayor
        Employee::factory()
            ->has(User::factory()->mayorDepartmentHead())
            ->create([
                'first_name' => 'Roberto',
                'last_name' => 'Mendoza',
                'email' => 'depthead.mayor@dasol.gov.ph',
                'department' => 'Office of the Municipal Mayor',
                'position' => 'Department Head'
            ])
            ->user->assignRole('Department Head');

        // Create Assessor (Performance Management Team)
        Employee::factory()
            ->has(User::factory()->assessor())
            ->create([
                'first_name' => 'Carmela',
                'last_name' => 'Reyes',
                'email' => 'assessor.pmt@dasol.gov.ph',
                'department' => 'Performance Management Team',
                'position' => 'Assessor'
            ])
            ->user->assignRole('Assessor');

        // Create Final Approver (Senior Management)
        Employee::factory()
            ->has(User::factory()->finalApprover())
            ->create([
                'first_name' => 'Antonio',
                'last_name' => 'Santos',
                'email' => 'administrator@dasol.gov.ph',
                'department' => 'Office of the Municipal Administrator',
                'position' => 'Final Approver'
            ])
            ->user->assignRole('Final Approver');

        // Seed salary grades for civil service career management
        $this->call(SalaryGradeSeeder::class);

        // Seed sample reports for demonstration
        $this->call(ReportSeeder::class);

        // Assign Employee roles to municipal office workers who don't have roles
        $this->call(EmployeeRoleSeeder::class);

        // Seed leave types and policies before credits
        $this->call(LeaveTypesSeeder::class);
        $this->call(LeavePolicySeeder::class);

        // Seed leave credits for employees (initial VL/SL balances)
        $this->call(SampleLeaveCreditsSeeder::class);

        // Seed municipal offices structure
        $this->call(OfficeSeeder::class);

        // Seed office assignments for users
        $this->call(OfficeAssignmentSeeder::class);

        // Seed Major Final Outputs for OPCR
        $this->call(MajorFinalOutputSeeder::class);

        // Seed Success Indicators linked to MFOs
        $this->call(SuccessIndicatorsSeeder::class);

        // Seed OPCR Workflows for demonstration
        $this->call(OPCRWorkflowSeeder::class);

        // Seed sample IPCR data for testing the new module
        $this->call(SampleIPCRSeeder::class);
    }
}
