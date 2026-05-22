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

        // Get leave types
        $leaveTypes = DB::table('leave_types')->pluck('id', 'name');

        // Get actual employees
        $employees = DB::table('employees')->limit(3)->get();

        if ($employees->isEmpty()) {
            $this->command->warn('No employees found to attach leave applications to.');
            return;
        }

        $emp1 = $employees->first();
        $emp2 = $employees->skip(1)->first() ?? $emp1;
        $emp3 = $employees->skip(2)->first() ?? $emp2;

        $today = Carbon::today();

        // Sample leave applications mapped to real employees
        $applications = [
            [
                'employee_id' => $emp1->id,
                'leave_type' => 'Vacation Leave',
                'start_date' => $today->copy()->subDays(2),
                'end_date' => $today->copy()->addDays(2),
                'reason' => 'Family vacation and personal downtime',
                'status' => 'approved',
                'with_pay' => true,
            ],
            [
                'employee_id' => $emp2->id,
                'leave_type' => 'Sick Leave',
                'start_date' => $today->copy()->subMonths(1)->setDay(10),
                'end_date' => $today->copy()->subMonths(1)->setDay(10),
                'reason' => 'Fever and flu',
                'status' => 'approved',
                'with_pay' => true,
            ],
            [
                'employee_id' => $emp3->id,
                'leave_type' => 'Maternity Leave',
                'start_date' => $today->copy()->subMonths(2)->setDay(1),
                'end_date' => $today->copy()->subMonths(2)->addDays(105), // Maternity leave is usually 105 days in PH
                'reason' => 'Maternity leave and recovery',
                'status' => 'approved',
                'with_pay' => true,
            ],
            [
                'employee_id' => $emp1->id,
                'leave_type' => 'Sick Leave',
                'start_date' => $today->copy()->addWeeks(2),
                'end_date' => $today->copy()->addWeeks(2)->addDays(1),
                'reason' => 'Scheduled medical check-up',
                'status' => 'pending',
                'with_pay' => true,
            ],
            [
                'employee_id' => $emp2->id,
                'leave_type' => 'Special Emergency (Calamity) Leave',
                'start_date' => $today->copy()->subMonths(4),
                'end_date' => $today->copy()->subMonths(4),
                'reason' => 'Community flood emergency response',
                'status' => 'approved',
                'with_pay' => false,
            ],
        ];

        foreach ($applications as $app) {
            $daysRequested = $app['start_date']->diffInDays($app['end_date']) + 1;
            $leaveTypeId = $leaveTypes[$app['leave_type']] ?? $leaveTypes->first();

            DB::table('leave_applications')->insert([
                'employee_id' => $app['employee_id'],
                'leave_type_id' => $leaveTypeId,
                'start_date' => $app['start_date']->toDateString(),
                'end_date' => $app['end_date']->toDateString(),
                'days_requested' => $daysRequested,
                'reason' => $app['reason'],
                'status' => $app['status'],
                'applied_date' => $app['start_date']->copy()->subDays(7)->toDateString(),
                'approved_by' => $app['status'] === 'approved' ? 1 : null, // Assuming user ID 1 is an approver
                'approved_date' => $app['status'] === 'approved' ? $app['start_date']->copy()->subDays(5)->toDateString() : null,
                'created_at' => $app['start_date']->copy()->subDays(7),
                'updated_at' => $app['status'] === 'approved' ? $app['start_date']->copy()->subDays(5) : now(),
            ]);
        }
    }
}