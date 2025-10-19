<?php

namespace Database\Seeders;

use App\Models\LeaveWorkflow;
use App\Models\LeaveWorkflowStep;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeaveWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default Single Approval Workflow
        $defaultWorkflow = LeaveWorkflow::create([
            'name' => 'Default Single Approval',
            'description' => 'Standard workflow with single approval by department head',
            'is_active' => true,
            'conditions' => [], // Applies to all applications by default
            'approval_steps' => [
                [
                    'step_order' => 1,
                    'step_name' => 'Department Head Approval',
                    'step_type' => 'role_based',
                ]
            ],
        ]);

        LeaveWorkflowStep::create([
            'leave_workflow_id' => $defaultWorkflow->id,
            'step_order' => 1,
            'step_type' => 'role_based',
            'step_name' => 'Department Head Approval',
            'approvers' => [
                ['role' => 'department_head']
            ],
            'required_all' => false,
            'escalation_hours' => 48,
            'escalation_to' => [
                ['role' => 'hr_admin']
            ],
        ]);

        // Multi-Level Approval Workflow for Extended Leave
        $extendedWorkflow = LeaveWorkflow::create([
            'name' => 'Extended Leave Approval',
            'description' => 'Multi-level approval for leave requests exceeding 5 days',
            'is_active' => true,
            'conditions' => [
                'min_days' => 6, // Applies to leave requests of 6+ days
            ],
            'approval_steps' => [
                [
                    'step_order' => 1,
                    'step_name' => 'Direct Supervisor Approval',
                    'step_type' => 'role_based',
                ],
                [
                    'step_order' => 2,
                    'step_name' => 'Department Head Approval',
                    'step_type' => 'role_based',
                ]
            ],
        ]);

        LeaveWorkflowStep::create([
            'leave_workflow_id' => $extendedWorkflow->id,
            'step_order' => 1,
            'step_type' => 'role_based',
            'step_name' => 'Direct Supervisor Approval',
            'approvers' => [
                ['role' => 'direct_supervisor']
            ],
            'required_all' => false,
            'escalation_hours' => 24,
            'escalation_to' => [
                ['role' => 'department_head']
            ],
        ]);

        LeaveWorkflowStep::create([
            'leave_workflow_id' => $extendedWorkflow->id,
            'step_order' => 2,
            'step_type' => 'role_based',
            'step_name' => 'Department Head Approval',
            'approvers' => [
                ['role' => 'department_head']
            ],
            'required_all' => false,
            'escalation_hours' => 48,
            'escalation_to' => [
                ['role' => 'hr_admin']
            ],
        ]);

        // Medical Leave Workflow with HR Approval
        $medicalLeaveWorkflow = LeaveWorkflow::create([
            'name' => 'Medical Leave Approval',
            'description' => 'Medical leave requires HR approval after supervisor approval',
            'is_active' => true,
            'conditions' => [
                'leave_types' => [], // Will be populated with medical leave type IDs
            ],
            'approval_steps' => [
                [
                    'step_order' => 1,
                    'step_name' => 'Direct Supervisor Approval',
                    'step_type' => 'role_based',
                ],
                [
                    'step_order' => 2,
                    'step_name' => 'HR Admin Approval',
                    'step_type' => 'role_based',
                ]
            ],
        ]);

        LeaveWorkflowStep::create([
            'leave_workflow_id' => $medicalLeaveWorkflow->id,
            'step_order' => 1,
            'step_type' => 'role_based',
            'step_name' => 'Direct Supervisor Approval',
            'approvers' => [
                ['role' => 'direct_supervisor']
            ],
            'required_all' => false,
            'escalation_hours' => 24,
            'escalation_to' => [
                ['role' => 'hr_admin']
            ],
        ]);

        LeaveWorkflowStep::create([
            'leave_workflow_id' => $medicalLeaveWorkflow->id,
            'step_order' => 2,
            'step_type' => 'role_based',
            'step_name' => 'HR Admin Approval',
            'approvers' => [
                ['role' => 'hr_admin']
            ],
            'required_all' => false,
            'escalation_hours' => 48,
            'escalation_to' => [
                ['role' => 'super_admin']
            ],
        ]);

        // Update medical leave workflow with actual leave type IDs
        $this->updateMedicalLeaveTypeIds($medicalLeaveWorkflow);

        // Create additional specialized workflows
        $this->createSpecialWorkflows();

        $this->command->info('Leave workflows seeded successfully!');
    }

    /**
     * Update medical leave workflow with actual leave type IDs
     */
    private function updateMedicalLeaveTypeIds(LeaveWorkflow $workflow): void
    {
        // Find medical leave types with broader search patterns
        $medicalLeaveTypes = \App\Models\LeaveType::where(function ($query) {
            $query->where('name', 'like', '%medical%')
                  ->orWhere('name', 'like', '%sick%')
                  ->orWhere('name', 'like', '%health%')
                  ->orWhere('code', 'like', '%MED%')
                  ->orWhere('code', 'like', '%SL%')
                  ->orWhere('description', 'like', '%medical%');
        })->pluck('id')
          ->toArray();

        if (!empty($medicalLeaveTypes)) {
            $workflow->update([
                'conditions' => [
                    'leave_types' => $medicalLeaveTypes
                ]
            ]);

            $this->command->info('Medical leave workflow configured with ' . count($medicalLeaveTypes) . ' leave types.');
        } else {
            // If no medical leave types found, set default common leave types
            $allLeaveTypes = \App\Models\LeaveType::pluck('id')->toArray();
            if (!empty($allLeaveTypes)) {
                $workflow->update([
                    'conditions' => [
                        'leave_types' => $allLeaveTypes // Apply to all leave types for demonstration
                    ]
                ]);
                $this->command->info('Medical leave workflow configured for all leave types (no specific medical types found).');
            }
        }
    }

    /**
     * Create additional specialized workflows
     */
    private function createSpecialWorkflows(): void
    {
        // Emergency Leave Workflow (Immediate approval needed)
        $emergencyWorkflow = LeaveWorkflow::create([
            'name' => 'Emergency Leave Approval',
            'description' => 'Fast-track approval for emergency situations',
            'is_active' => true,
            'conditions' => [
                'leave_types' => [], // Will be populated with emergency leave types
                'emergency_flag' => true,
            ],
            'approval_steps' => [
                [
                    'step_order' => 1,
                    'step_name' => 'HR Admin Immediate Approval',
                    'step_type' => 'role_based',
                ]
            ],
        ]);

        LeaveWorkflowStep::create([
            'leave_workflow_id' => $emergencyWorkflow->id,
            'step_order' => 1,
            'step_type' => 'role_based',
            'step_name' => 'HR Admin Immediate Approval',
            'approvers' => [
                ['role' => 'hr_admin']
            ],
            'required_all' => false,
            'escalation_hours' => 2, // 2 hours escalation for emergencies
            'escalation_to' => [
                ['role' => 'super_admin']
            ],
        ]);

        // High-Level Executive Approval Workflow
        $executiveWorkflow = LeaveWorkflow::create([
            'name' => 'Executive Leave Approval',
            'description' => 'Multi-level approval for executive-level positions',
            'is_active' => true,
            'conditions' => [
                'employment_types' => [], // Will be populated with executive employment types
                'min_days' => 3, // Only for leave 3+ days
            ],
            'approval_steps' => [
                [
                    'step_order' => 1,
                    'step_name' => 'Direct Supervisor Approval',
                    'step_type' => 'role_based',
                ],
                [
                    'step_order' => 2,
                    'step_name' => 'Department Head Approval',
                    'step_type' => 'role_based',
                ],
                [
                    'step_order' => 3,
                    'step_name' => 'Executive Director Approval',
                    'step_type' => 'role_based',
                ]
            ],
        ]);

        // Executive workflow steps
        LeaveWorkflowStep::create([
            'leave_workflow_id' => $executiveWorkflow->id,
            'step_order' => 1,
            'step_type' => 'role_based',
            'step_name' => 'Direct Supervisor Approval',
            'approvers' => [
                ['role' => 'direct_supervisor']
            ],
            'required_all' => false,
            'escalation_hours' => 24,
            'escalation_to' => [
                ['role' => 'department_head']
            ],
        ]);

        LeaveWorkflowStep::create([
            'leave_workflow_id' => $executiveWorkflow->id,
            'step_order' => 2,
            'step_type' => 'role_based',
            'step_name' => 'Department Head Approval',
            'approvers' => [
                ['role' => 'department_head']
            ],
            'required_all' => false,
            'escalation_hours' => 48,
            'escalation_to' => [
                ['position' => 'executive']
            ],
        ]);

        LeaveWorkflowStep::create([
            'leave_workflow_id' => $executiveWorkflow->id,
            'step_order' => 3,
            'step_type' => 'role_based',
            'step_name' => 'Executive Director Approval',
            'approvers' => [
                ['position' => 'executive']
            ],
            'required_all' => false,
            'escalation_hours' => 72,
            'escalation_to' => [
                ['role' => 'super_admin']
            ],
        ]);

        // Parallel Approval Workflow (Multiple approvers required)
        $parallelWorkflow = LeaveWorkflow::create([
            'name' => 'Parallel Approval Workflow',
            'description' => 'Requires approval from multiple approvers simultaneously',
            'is_active' => true,
            'conditions' => [
                'departments' => [], // Will be populated with specific departments
                'min_days' => 10, // For extended leave periods
            ],
            'approval_steps' => [
                [
                    'step_order' => 1,
                    'step_name' => 'Multi-Department Approval',
                    'step_type' => 'parallel',
                ]
            ],
        ]);

        LeaveWorkflowStep::create([
            'leave_workflow_id' => $parallelWorkflow->id,
            'step_order' => 1,
            'step_type' => 'parallel',
            'step_name' => 'Multi-Department Approval',
            'approvers' => [
                ['role' => 'department_head'],
                ['role' => 'hr_admin'],
                ['position' => 'executive']
            ],
            'required_all' => true, // All approvers must approve
            'escalation_hours' => 48,
            'escalation_to' => [
                ['role' => 'super_admin']
            ],
        ]);

        $this->command->info('Special workflows created successfully!');
    }
}
