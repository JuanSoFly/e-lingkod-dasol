<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use Spatie\Permission\Models\Role;

class EmployeeRoleSeeder extends Seeder
{
    /**
     * List of municipal offices that should have Employee role access
     */
    private $municipalOffices = [
        'Office of the Municipal Mayor',
        'Office of the Municipal Accountant',
        'Office of the Municipal Budget Office',
        'Municipal Planning and Development Office',
        'General Services Office',
        'Municipal Treasurer\'s Office',
        'Municipal Assessor\'s Office',
        'Business Permits and Licensing Office',
        'Municipal Tourism and Cultural Affairs Office',
        'Local Civil Registry Office',
        'Human Resource Management Office',
        'Office of the Building Official',
        'Municipal Engineer\'s Office',
        'Municipal Agriculturist\'s Office',
        'Municipal Social Welfare and Development Office',
        'Rural Health Unit',
        'Sangguniang Bayan/Secretary to the SB Office',
        'Municipal Disaster Risk Reduction and Management Office',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Employee Role Assignment for Municipal Office Workers...');

        // Get or create the Employee role
        $employeeRole = Role::firstOrCreate(['name' => 'Employee']);

        // Find users who:
        // 1. Have an associated employee record
        // 2. Work in municipal offices
        // 3. Don't have any role assigned yet
        $usersToAssign = User::with('employee')
            ->whereHas('employee', function ($query) {
                $query->whereIn('department', $this->municipalOffices);
            })
            ->whereDoesntHave('roles')
            ->get();

        $assignedCount = 0;
        $skippedCount = 0;

        foreach ($usersToAssign as $user) {
            try {
                // Double check user doesn't have roles (race condition safety)
                if ($user->roles()->count() === 0) {
                    $user->assignRole($employeeRole);
                    $assignedCount++;

                    $this->command->info("✓ Assigned Employee role to: {$user->name} ({$user->employee->department})");
                } else {
                    $skippedCount++;
                    $this->command->line("⚠ Skipped {$user->name} - already has roles assigned");
                }
            } catch (\Exception $e) {
                $this->command->error("✗ Failed to assign role to {$user->name}: {$e->getMessage()}");
                $skippedCount++;
            }
        }

        // Summary
        $this->command->info('');
        $this->command->info('Employee Role Assignment Summary:');
        $this->command->info("✓ Total users assigned Employee role: {$assignedCount}");
        $this->command->info("⚠ Total users skipped: {$skippedCount}");

        if ($assignedCount > 0) {
            $this->command->info('');
            $this->command->info('🎉 Municipal office employees can now access their Quick Options!');
            $this->command->info('   - Leave Dashboard');
            $this->command->info('   - Request Documents');
            $this->command->info('   - Update Personal Data Sheet');
            $this->command->info('   - View Service Record');
            $this->command->info('   - View 201 File');
        } else {
            $this->command->info('');
            $this->command->info('ℹ No new role assignments were needed.');
        }
    }

    /**
     * Get the list of municipal offices
     */
    public function getMunicipalOffices(): array
    {
        return $this->municipalOffices;
    }

    /**
     * Check if a department is a municipal office
     */
    public function isMunicipalOffice(string $department): bool
    {
        return in_array($department, $this->municipalOffices);
    }
}