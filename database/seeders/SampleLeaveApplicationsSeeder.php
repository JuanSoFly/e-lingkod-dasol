<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SampleLeaveApplicationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing leave applications
        DB::table('leave_applications')->delete();

        $currentYear = date('Y');

        // Get leave types
        $leaveTypes = DB::table('leave_types')->pluck('id', 'name');

        // Get employees
        $employees = DB::table('employees')->pluck('id', 'employee_number');

        // Sample leave applications
        $applications = [
            [
                'employee_number' => 'EMP-36115',
                'leave_type' => 'Vacation Leave',
                'start_date' => Carbon::create($currentYear, 3, 15),
                'end_date' => Carbon::create($currentYear, 3, 17),
                'reason' => 'Family vacation',
                'status' => 'approved',
                'with_pay' => true,
            ],
            [
                'employee_number' => 'EMP-36115',
                'leave_type' => 'Sick Leave',
                'start_date' => Carbon::create($currentYear, 6, 10),
                'end_date' => Carbon::create($currentYear, 6, 10),
                'reason' => 'Fever and flu',
                'status' => 'approved',
                'with_pay' => true,
            ],
            [
                'employee_number' => 'EMP-05041',
                'leave_type' => 'Maternity Leave',
                'start_date' => Carbon::create($currentYear, 5, 1),
                'end_date' => Carbon::create($currentYear, 8, 15),
                'reason' => 'Maternity leave',
                'status' => 'approved',
                'with_pay' => true,
            ],
            [
                'employee_number' => 'EMP-05041',
                'leave_type' => 'Vacation Leave',
                'start_date' => Carbon::create($currentYear, 2, 20),
                'end_date' => Carbon::create($currentYear, 2, 22),
                'reason' => 'Personal matters',
                'status' => 'approved',
                'with_pay' => true,
            ],
            [
                'employee_number' => 'EMP-50793',
                'leave_type' => 'Sick Leave',
                'start_date' => Carbon::create($currentYear, 7, 5),
                'end_date' => Carbon::create($currentYear, 7, 6),
                'reason' => 'Medical check-up',
                'status' => 'pending',
                'with_pay' => true,
            ],
            [
                'employee_number' => 'EMP-50793',
                'leave_type' => 'Emergency Leave',
                'start_date' => Carbon::create($currentYear, 1, 15),
                'end_date' => Carbon::create($currentYear, 1, 15),
                'reason' => 'Family emergency',
                'status' => 'approved',
                'with_pay' => false,
            ],
        ];

        foreach ($applications as $app) {
            $daysRequested = $app['start_date']->diffInDays($app['end_date']) + 1;

            DB::table('leave_applications')->insert([
                'employee_id' => $employees[$app['employee_number']],
                'leave_type_id' => $leaveTypes[$app['leave_type']],
                'start_date' => $app['start_date'],
                'end_date' => $app['end_date'],
                'days_requested' => $daysRequested,
                'reason' => $app['reason'],
                'status' => $app['status'],
                'applied_date' => $app['start_date']->copy()->subDays(7),
                'approved_by' => $app['status'] === 'approved' ? 1 : null, // Assuming user ID 1 is an approver
                'approved_date' => $app['status'] === 'approved' ? $app['start_date']->copy()->addDays(2) : null,
                'created_at' => $app['start_date']->copy()->subDays(7),
                'updated_at' => $app['status'] === 'approved' ? $app['start_date']->copy()->addDays(2) : now(),
            ]);
        }
    }
}