<?php

namespace Database\Seeders;

use App\Models\LeaveWorkflow;
use App\Models\LeaveWorkflowStep;
use Illuminate\Database\Seeder;

class LeaveWorkflowSeeder extends Seeder
{
    /**
     * Align leave workflow with CSC/LGU approval chain (Supervisor → Dept Head → LCE).
     */
    public function run(): void
    {
        // Deactivate any existing workflows to enforce CSC chain
        LeaveWorkflow::query()->update(['is_active' => false]);

        $workflow = LeaveWorkflow::create([
            'name' => 'CSC Standard Leave Approval',
            'description' => 'CSC Form 6 routing: Immediate Supervisor recommendation, Department/Office Head approval, Final Approval (Mayor, HR Admin, Super Admin receive notifications)',
            'is_active' => true,
            'conditions' => [],
            'approval_steps' => [
                ['step_order' => 1, 'step_name' => 'Immediate Supervisor Recommendation', 'step_type' => 'role_based'],
                ['step_order' => 2, 'step_name' => 'Department/Office Head Approval', 'step_type' => 'role_based'],
                ['step_order' => 3, 'step_name' => 'Final Approval (Mayor)', 'step_type' => 'role_based'],
                ['step_order' => 3, 'step_name' => 'HR Administrative Approval', 'step_type' => 'role_based'],
            ],
        ]);

        // Step 1: Immediate Supervisor (recommendation)
        LeaveWorkflowStep::create([
            'leave_workflow_id' => $workflow->id,
            'step_order' => 1,
            'step_type' => 'role_based',
            'step_name' => 'Immediate Supervisor Recommendation',
            'approvers' => [
                ['role' => 'direct_supervisor'],
            ],
            'required_all' => false,
            'escalation_hours' => null,
            'escalation_to' => [],
            'sla_working_days' => 5,
            'is_recommendation' => true,
        ]);

        // Step 2: Department/Office Head approval
        LeaveWorkflowStep::create([
            'leave_workflow_id' => $workflow->id,
            'step_order' => 2,
            'step_type' => 'role_based',
            'step_name' => 'Department/Office Head Approval',
            'approvers' => [
                ['role' => 'department_head'],
            ],
            'required_all' => false,
            'escalation_hours' => null,
            'escalation_to' => [],
            'sla_working_days' => 5,
            'is_recommendation' => false,
        ]);

        // Step 3a: Final Approval (Mayor)
        LeaveWorkflowStep::create([
            'leave_workflow_id' => $workflow->id,
            'step_order' => 3,
            'step_type' => 'role_based',
            'step_name' => 'Final Approval (Mayor)',
            'approvers' => [
                ['role' => 'final_approver'],
            ],
            'required_all' => false,
            'escalation_hours' => null,
            'escalation_to' => [],
            'sla_working_days' => 5,
            'is_recommendation' => false,
        ]);

        // Step 3b: HR Administrative Approval
        LeaveWorkflowStep::create([
            'leave_workflow_id' => $workflow->id,
            'step_order' => 3, // Same order as Mayor -> Parallel approval
            'step_type' => 'role_based',
            'step_name' => 'HR Administrative Approval',
            'approvers' => [
                ['role' => 'hr_admin'],
            ],
            'required_all' => false,
            'escalation_hours' => null,
            'escalation_to' => [],
            'sla_working_days' => 5,
            'is_recommendation' => false,
        ]);

        $this->command?->info('CSC Standard Leave Approval workflow seeded.');
    }
}
