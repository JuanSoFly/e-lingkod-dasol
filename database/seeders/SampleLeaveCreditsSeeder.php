<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleLeaveCreditsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing leave credits
        DB::table('leave_credits')->delete();

        $currentYear = date('Y');

        // Get leave types
        $leaveTypes = DB::table('leave_types')->pluck('id', 'name');

        // Get employees
        $employees = DB::table('employees')->pluck('id', 'employee_number');

        // Create sample leave credits for each employee
        foreach ($employees as $employeeNumber => $employeeId) {
            // Vacation Leave Credits - start at zero; monthly accrual will build balances
            DB::table('leave_credits')->insert([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypes['Vacation Leave'],
                'year' => $currentYear,
                'earned_credits' => 0.0,
                'used_credits' => 0.0,
                'remaining_credits' => 0.0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Sick Leave Credits - start at zero
            DB::table('leave_credits')->insert([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypes['Sick Leave'],
                'year' => $currentYear,
                'earned_credits' => 0.0,
                'used_credits' => 0.0,
                'remaining_credits' => 0.0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Maternity Leave (only for female employees)
            if (in_array($employeeNumber, ['EMP-05041'])) {
                DB::table('leave_credits')->insert([
                    'employee_id' => $employeeId,
                    'leave_type_id' => $leaveTypes['Maternity Leave'],
                    'year' => $currentYear,
                'earned_credits' => 105.0,
                'used_credits' => 0.0,
                'remaining_credits' => 105.0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        }

        // Keep remaining_credits aligned for seeded data
        DB::statement('UPDATE leave_credits SET remaining_credits = earned_credits - used_credits');
    }
}
