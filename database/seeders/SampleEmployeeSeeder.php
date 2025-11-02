<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SampleEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample employees with various municipality departments
        $departments = [
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
            'Municipal Disaster Risk Reduction and Management Office'
        ];

        $positions = [
            'Office Head',
            'Assistant Department Head',
            'Administrative Officer III',
            'Administrative Assistant',
            'Clerk II',
            'Project Development Officer',
            'Accountant',
            'Budget Officer',
            'Engineer',
            'Sanitary Inspector',
            'Social Welfare Officer',
            'Agriculturist',
            'Nurse',
            'Assessor'
        ];

        // Create 20 sample employees with their associated user accounts
        for ($i = 1; $i <= 20; $i++) {
            $employee = Employee::factory()->create([
                'department' => $departments[array_rand($departments)],
                'position' => $positions[array_rand($positions)],
            ]);

            // Create corresponding user account for each employee
            User::factory()->create([
                'name' => trim($employee->first_name . ' ' . $employee->last_name),
                'email' => $employee->email,
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'employee_id' => $employee->id,
                'remember_token' => Str::random(10),
            ]);
        }

        $this->command->info('20 sample employees with user accounts created successfully.');
    }
}