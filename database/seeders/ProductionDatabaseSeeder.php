<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use App\Models\OfficeAssignment;
use App\Models\Office;
use Illuminate\Database\Seeder;

class ProductionDatabaseSeeder extends Seeder
{
    /**
     * Seed the production database with essential data only.
     * This excludes sample/demo data that shouldn't be in production.
     */
    public function run(): void
    {
        // Essential system data
        $this->call(RoleAndPermissionSeeder::class);

        // Load current year holiday data (2025 for production)
        $this->call(PhilippineHolidays2025Seeder::class);

        // Ensure default work calendars are available
        $this->call(WorkCalendarSeeder::class);

        // Seed leave types and policies for leave management
        $this->call(LeaveTypesSeeder::class);
        $this->call(LeavePolicySeeder::class);

        // Seed municipal offices structure
        $this->call(OfficeSeeder::class);

        // Seed Major Final Outputs for OPCR
        $this->call(MajorFinalOutputSeeder::class);

        // Seed Success Indicators linked to MFOs
        $this->call(SuccessIndicatorsSeeder::class);

        // Create production admin accounts
        $this->createProductionAccounts();

        // Seed OPCR Workflows
        $this->call(OPCRWorkflowSeeder::class);

        // Seed Leave Workflows
        $this->call(LeaveWorkflowSeeder::class);

        // Seed salary grades for civil service career management
        $this->call(SalaryGradeSeeder::class);

        $this->command->info('Production database seeded with essential data.');
    }

    /**
     * Create essential production accounts.
     * These are the minimum accounts needed for system operation.
     */
    private function createProductionAccounts(): void
    {
        // Create a Super Admin who is not an employee
        $superAdminUser = User::factory()->create([
            'name' => 'System Administrator',
            'email' => 'admin123@edumail.edu.pl',
            'employee_id' => null, // Super Admin may not be an employee
        ]);
        $superAdminUser->assignRole('Super Admin');

        // Create HR Admin (Mr. Bryan's equivalent)
        $hrAdmin = Employee::factory()
            ->has(User::factory()->state(function (array $attributes, Employee $employee) {
                return [
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'email' => $employee->email,
                ];
            }))
            ->create([
                'first_name' => 'Hr',
                'last_name' => 'Admin',
                'email' => 'juajow@best-hosting.biz',
                'department' => 'Human Resource Management Office',
                'position' => 'Human Resource Management Officer'
            ])
            ->user->assignRole('HR Admin');

        // Create Municipal Mayor account
        $mayor = Employee::factory()
            ->has(User::factory()->state(function (array $attributes, Employee $employee) {
                return [
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'email' => $employee->email,
                ];
            }))
            ->create([
                'first_name' => 'Municipal',
                'last_name' => 'Mayor',
                'email' => 'juansofly@goomail.club',
                'department' => 'Office of the Municipal Mayor',
                'position' => 'Municipal Mayor'
            ])
            ->user->assignRole('Department Head');

        // Create a regular employee
        Employee::factory()
            ->has(User::factory()->state(function (array $attributes, Employee $employee) {
                return [
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'email' => $employee->email,
                ];
            }))
            ->create([
                'first_name' => 'Juan',
                'last_name' => 'Santos',
                'email' => 'juan.santos@goomail.club',
                'department' => 'Municipal Planning and Development Office',
                'position' => 'Administrative Officer II'
            ])
            ->user->assignRole('Employee');

        // Lookup MPDO office for assignments
        $mpdoOffice = Office::where('code', 'MPDO')->first();

        // Create a Department Head
        $departmentHead = Employee::factory()
            ->has(User::factory()->state(function (array $attributes, Employee $employee) {
                return [
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'email' => $employee->email,
                ];
            }))
            ->create([
                'first_name' => 'Maria',
                'last_name' => 'Reyes',
                'email' => 'maria.reyes@swiftlist.io',
                'department' => 'Municipal Planning and Development Office',
                'position' => 'Municipal Planning and Development Coordinator'
            ])
            ->user->assignRole('Department Head');

        // Assign Department Head to their office
        if ($mpdoOffice && $departmentHead) {
            OfficeAssignment::create([
                'user_id' => $departmentHead->id,
                'employee_id' => $departmentHead->employee?->id,
                'office_id' => $mpdoOffice->id,
                'role' => 'Department Head',
                'is_active' => true,
                'assigned_date' => now()->toDateString(),
                'assigned_by' => $superAdminUser->id ?? 1,
                'remarks' => 'Department Head for Municipal Planning and Development Office',
            ]);
        }

        // Create a Supervisor (distinct from Department Head)
        $supervisor = Employee::factory()
            ->has(User::factory()->state(function (array $attributes, Employee $employee) {
                return [
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'email' => $employee->email,
                ];
            }))
            ->create([
                'first_name' => 'Carlos',
                'last_name' => 'Santos',
                'email' => 'carlos.santos@swiftlist.io',
                'department' => 'Municipal Planning and Development Office',
                'position' => 'Senior Planning Officer'
            ])
            ->user->assignRole('Supervisor');

        // Assign Supervisor to their office
        if ($mpdoOffice && $supervisor) {
            OfficeAssignment::create([
                'user_id' => $supervisor->id,
                'employee_id' => $supervisor->employee?->id,
                'office_id' => $mpdoOffice->id,
                'role' => 'Supervisor',
                'is_active' => true,
                'assigned_date' => now()->toDateString(),
                'assigned_by' => $superAdminUser->id ?? 1,
                'remarks' => 'Supervisor for Municipal Planning and Development Office',
            ]);
        }

        // Create an Assessor
        Employee::factory()
            ->has(User::factory()->state(function (array $attributes, Employee $employee) {
                return [
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'email' => $employee->email,
                ];
            }))
            ->create([
                'first_name' => 'Roberto',
                'last_name' => 'Cruz',
                'email' => 'roberto.cruz@swiftlist.io',
                'department' => 'Human Resource Management Office',
                'position' => 'HR Assistant II'
            ])
            ->user->assignRole('Assessor');

        // Create a Final Approver
        Employee::factory()
            ->has(User::factory()->state(function (array $attributes, Employee $employee) {
                return [
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'email' => $employee->email,
                ];
            }))
            ->create([
                'first_name' => 'Elena',
                'last_name' => 'Mendoza',
                'email' => 'elena.mendoza@swiftlist.io',
                'department' => 'Office of the Municipal Mayor',
                'position' => 'Executive Assistant III'
            ])
            ->user->assignRole('Final Approver');

        $this->command->info('Created production admin accounts:');
        $this->command->info('- Super Admin: admin123@edumail.edu.pl');
        $this->command->info('- HR Admin: juajow@best-hosting.biz (HR Admin)');
        $this->command->info('- Mayor: juansofly@goomail.club');
        $this->command->info('- Employee: juan.santos@goomail.club (Employee)');
        $this->command->info('- Department Head: maria.reyes@swiftlist.io');
        $this->command->info('- Supervisor: carlos.santos@swiftlist.io');
        $this->command->info('- Assessor: roberto.cruz@swiftlist.io (Assessor)');
        $this->command->info('- Final Approver: elena.mendoza@swiftlist.io (Final Approver)');
    }
}
