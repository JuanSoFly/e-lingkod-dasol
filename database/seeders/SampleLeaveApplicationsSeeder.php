<?php

namespace Database\Seeders;

use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Models\User;
use App\Models\LeaveApplicationWorkflowStep;
use App\Services\LeaveWorkflowService;
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
        // Clear existing leave-related application data to avoid duplicates/constraints
        $this->command->info('Clearing existing leave applications, workflow steps, and approvals...');
        DB::table('leave_application_workflow_steps')->delete();
        DB::table('leave_approvals')->delete();
        DB::table('leave_applications')->delete();

        // Get leave types
        $vacationLeave = LeaveType::where('name', 'Vacation Leave')->first();
        $sickLeave = LeaveType::where('name', 'Sick Leave')->first();
        $maternityLeave = LeaveType::where('name', 'Maternity Leave')->first();

        if (!$vacationLeave || !$sickLeave) {
            $this->command->error('Required leave types (Vacation/Sick Leave) not found. Please seed LeaveTypes first.');
            return;
        }

        // Get actual employees
        $juan = Employee::where('email', 'employee@example.com')->first();
        $sofia = Employee::where('email', 'supervisor@example.com')->first();
        $maria = Employee::where('email', 'hr@example.com')->first();

        // Fallbacks
        $employees = Employee::limit(3)->get();
        if ($employees->isEmpty()) {
            $this->command->warn('No employees found to attach leave applications to.');
            return;
        }

        $emp1 = $juan ?? $employees->first();
        $emp2 = $sofia ?? $employees->skip(1)->first() ?? $emp1;
        $emp3 = $maria ?? $employees->skip(2)->first() ?? $emp2;

        // Get users for approvals simulation
        $supervisorUser = User::where('email', 'supervisor@example.com')->first();
        $hrUser = User::where('email', 'hr@example.com')->first();
        $mayorUser = User::where('email', 'mayor@dasol.gov.ph')->first();

        if (!$supervisorUser || !$hrUser || !$mayorUser) {
            $this->command->warn('Workflow approver users (Supervisor, HR, Mayor) not found. Pending workflows will still be initialized, but automated approvals simulation for historical data will use fallbacks.');
            // Fallbacks
            $adminUser = User::whereHas('roles', function($q) { $q->where('name', 'Super Admin'); })->first() ?? User::first();
            $supervisorUser = $supervisorUser ?? $adminUser;
            $hrUser = $hrUser ?? $adminUser;
            $mayorUser = $mayorUser ?? $adminUser;
        }

        $today = Carbon::today();
        $workflowService = app(LeaveWorkflowService::class);

        // 1. Create PENDING Leave Applications
        $this->command->info('Creating pending leave applications and initializing workflows...');

        // Pending Sick Leave for Juan Dela Cruz
        $app1 = LeaveApplication::create([
            'employee_id' => $emp1->id,
            'leave_type_id' => $sickLeave->id,
            'start_date' => $today->copy()->addWeeks(1)->toDateString(),
            'end_date' => $today->copy()->addWeeks(1)->addDays(2)->toDateString(), // 3 days
            'days_requested' => 3,
            'reason' => 'Scheduled outpatient medical check-up and physical therapy.',
            'status' => 'pending',
            'applied_date' => $today->copy()->subDays(2)->toDateString(),
        ]);
        $workflowService->initializeWorkflow($app1);

        // Pending Vacation Leave for Sofia Lopez
        $app2 = LeaveApplication::create([
            'employee_id' => $emp2->id,
            'leave_type_id' => $vacationLeave->id,
            'start_date' => $today->copy()->addWeeks(3)->toDateString(),
            'end_date' => $today->copy()->addWeeks(3)->addDays(4)->toDateString(), // 5 days
            'days_requested' => 5,
            'reason' => 'Mid-year family vacation trip to Boracay.',
            'status' => 'pending',
            'applied_date' => $today->copy()->subDays(1)->toDateString(),
        ]);
        $workflowService->initializeWorkflow($app2);

        // 2. Create APPROVED Leave Applications (simulate approval flow)
        $this->command->info('Creating historical approved leave applications...');

        // Historical Approved Vacation Leave for Juan Dela Cruz
        $app3 = LeaveApplication::create([
            'employee_id' => $emp1->id,
            'leave_type_id' => $vacationLeave->id,
            'start_date' => $today->copy()->subMonths(1)->toDateString(),
            'end_date' => $today->copy()->subMonths(1)->addDays(4)->toDateString(), // 5 days
            'days_requested' => 5,
            'reason' => 'Personal rest, recovery, and home renovation oversight.',
            'status' => 'pending', // must start as pending to route workflow
            'applied_date' => $today->copy()->subMonths(1)->subDays(7)->toDateString(),
        ]);
        $workflowService->initializeWorkflow($app3);
        $this->approveWorkflow($app3, $supervisorUser, $hrUser, $mayorUser);

        // Historical Approved Sick Leave for Sofia Lopez
        $app4 = LeaveApplication::create([
            'employee_id' => $emp2->id,
            'leave_type_id' => $sickLeave->id,
            'start_date' => $today->copy()->subWeeks(3)->toDateString(),
            'end_date' => $today->copy()->subWeeks(3)->addDays(1)->toDateString(), // 2 days
            'days_requested' => 2,
            'reason' => 'Severe seasonal influenza and doctor recommended bed rest.',
            'status' => 'pending', // must start as pending to route workflow
            'applied_date' => $today->copy()->subWeeks(3)->subDays(2)->toDateString(),
        ]);
        $workflowService->initializeWorkflow($app4);
        $this->approveWorkflow($app4, $supervisorUser, $hrUser, $mayorUser);

        if ($maternityLeave && $emp3) {
            // Historical Approved Maternity Leave for Maria Clara
            $app5 = LeaveApplication::create([
                'employee_id' => $emp3->id,
                'leave_type_id' => $maternityLeave->id,
                'start_date' => $today->copy()->subMonths(3)->toDateString(),
                'end_date' => $today->copy()->subMonths(3)->addDays(104)->toDateString(), // 105 days
                'days_requested' => 105,
                'reason' => 'Maternity leave for childbirth and postpartum recovery.',
                'status' => 'pending',
                'applied_date' => $today->copy()->subMonths(3)->subDays(14)->toDateString(),
            ]);
            $workflowService->initializeWorkflow($app5);
            $this->approveWorkflow($app5, $supervisorUser, $hrUser, $mayorUser);
        }

        $this->command->info('Leave applications and workflows seeded successfully.');
    }

    /**
     * Simulate approval of workflow steps step-by-step
     */
    private function approveWorkflow(LeaveApplication $application, $supervisorUser, $hrUser, $mayorUser): void
    {
        $workflowService = app(LeaveWorkflowService::class);
        
        // 1. Approve Immediate Supervisor Recommendation
        $pendingSteps = LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->get();
            
        foreach ($pendingSteps as $step) {
            /** @var LeaveApplicationWorkflowStep $step */
            if ($step->step_order == 1) {
                $workflowService->processApproval($application, $step, $supervisorUser, 'approved', 'Highly recommended. Task handovers are in place.');
            }
        }

        // 2. Approve Department/Office Head Approval
        $pendingSteps = LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->get();
            
        foreach ($pendingSteps as $step) {
            /** @var LeaveApplicationWorkflowStep $step */
            if ($step->step_order == 2) {
                $workflowService->processApproval($application, $step, $hrUser, 'approved', 'Approved at the department level.');
            }
        }

        // 3. Approve Final Parallel Steps (Mayor and HR Administrative Approval)
        $pendingSteps = LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->get();
            
        foreach ($pendingSteps as $step) {
            /** @var LeaveApplicationWorkflowStep $step */
            if ($step->step_order == 3) {
                $isHR = str_contains(strtolower($step->leaveWorkflowStep->step_name), 'hr');
                $approver = $isHR ? $hrUser : $mayorUser;
                $workflowService->processApproval($application, $step, $approver, 'approved', 'Final executive clearance granted.');
            }
        }
    }
}