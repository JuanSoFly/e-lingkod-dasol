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
        $superAdminUser = User::firstOrCreate([
            'email' => 'admin@example.com'
        ], [
            'name' => 'Super Admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'employee_id' => null, // Super Admin may not be an employee
        ]);
        $superAdminUser->assignRole('Super Admin');

        // Create a sample Employee with a User account
        $employee = Employee::factory()
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
            ]);

        optional($employee->fresh()->user)->assignRole('Employee');

        // Create an HR Admin
        $hrAdmin = Employee::factory()
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
             ]);

        optional($hrAdmin->fresh()->user)->assignRole('HR Admin');

        // Create a Supervisor (immediate approver for leave workflows)
        $supervisor = Employee::factory()
            ->has(User::factory()->state(function (array $attributes, Employee $employee) {
                return [
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'email' => $employee->email,
                ];
            }))
            ->create([
                'first_name' => 'Sofia',
                'last_name' => 'Lopez',
                'email' => 'supervisor@example.com',
                'department' => 'Human Resource Management Office',
                'position' => 'HR Supervisor'
            ]);

        optional($supervisor->fresh()->user)->assignRole('Supervisor');

        // Create Municipal Mayor as sole Final Approver
        $mayor = Employee::factory()
            ->has(User::factory()->state(function (array $attributes, Employee $employee) {
                return [
                    'name' => 'Municipal Mayor',
                    'email' => 'mayor@dasol.gov.ph',
                ];
            }))
            ->create([
                'first_name' => 'Municipal',
                'last_name' => 'Mayor',
                'email' => 'mayor@dasol.gov.ph',
                'department' => 'Office of the Municipal Mayor',
                'position' => 'Municipal Mayor',
            ]);

        optional($mayor->fresh()->user)->assignRole('Final Approver');

        // Create Assessor (Performance Management Team)
        $assessor = Employee::factory()
            ->has(User::factory()->assessor())
            ->create([
                'first_name' => 'Carmela',
                'last_name' => 'Reyes',
                'email' => 'assessor.pmt@dasol.gov.ph',
                'department' => 'Performance Management Team',
                'position' => 'Assessor'
            ]);

        optional($assessor->fresh()->user)->assignRole('Assessor');

        // Seed salary grades for civil service career management
        $this->call(SalaryGradeSeeder::class);

        // Seed sample reports for demonstration
        $this->call(ReportSeeder::class);

        // Assign Employee roles to municipal office workers who don't have roles
        $this->call(EmployeeRoleSeeder::class);

        // Seed leave types and policies before credits
        $this->call(LeaveTypesSeeder::class);
        $this->call(LeavePolicySeeder::class);
                // Seed Leave Workflows
        $this->call(LeaveWorkflowSeeder::class);

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
