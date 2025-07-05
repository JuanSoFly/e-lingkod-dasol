<?php

namespace Database\Seeders;

use App\Models\DocumentApprovalRequest;
use App\Models\DocumentApprovalWorkflow;
use App\Models\DocumentApprovalWorkflowStep;
use App\Models\DocumentApprovalStep;
use App\Models\DocumentApprovalComment;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class DocumentApprovalSeeder extends Seeder
{
    public function run(): void
    {
        // Create workflows with steps
        $this->createWorkflows();
        
        // Create sample requests
        $this->createSampleRequests();
    }

    private function createWorkflows(): void
    {
        // Standard Document Approval Workflow
        DocumentApprovalWorkflow::create([
            'name' => 'Standard Document Approval',
            'document_type' => 'General',
            'description' => 'Standard workflow for general document approvals (Supervisor → HR)',
            'steps' => [
                ['name' => 'Supervisor Review', 'step_order' => 1, 'is_required' => true, 'role' => 'HR Admin'],
                ['name' => 'HR Approval', 'step_order' => 2, 'is_required' => true, 'role' => 'HR Admin']
            ],
            'is_active' => true
        ]);

        // HR Document Approval Workflow
        DocumentApprovalWorkflow::create([
            'name' => 'HR Document Approval',
            'document_type' => 'HR',
            'description' => 'Workflow for HR-related documents (HR Admin only)',
            'steps' => [
                ['name' => 'HR Review', 'step_order' => 1, 'is_required' => true, 'role' => 'HR Admin']
            ],
            'is_active' => true
        ]);

        // Executive Approval Workflow
        DocumentApprovalWorkflow::create([
            'name' => 'Executive Document Approval',
            'document_type' => 'Executive',
            'description' => 'High-level approval workflow (Supervisor → HR → Department Head → Mayor)',
            'steps' => [
                ['name' => 'Supervisor Review', 'step_order' => 1, 'is_required' => true, 'role' => 'HR Admin'],
                ['name' => 'HR Review', 'step_order' => 2, 'is_required' => true, 'role' => 'HR Admin'],
                ['name' => 'Department Head Approval', 'step_order' => 3, 'is_required' => true, 'role' => 'Super Admin'],
                ['name' => 'Executive Approval', 'step_order' => 4, 'is_required' => true, 'role' => 'Super Admin']
            ],
            'is_active' => true
        ]);

        // Financial Document Approval
        DocumentApprovalWorkflow::create([
            'name' => 'Financial Document Approval',
            'document_type' => 'Financial',
            'description' => 'Workflow for financial and salary-related documents',
            'steps' => [
                ['name' => 'Supervisor Review', 'step_order' => 1, 'is_required' => true, 'role' => 'HR Admin'],
                ['name' => 'Finance Review', 'step_order' => 2, 'is_required' => true, 'role' => 'HR Admin'],
                ['name' => 'HR Approval', 'step_order' => 3, 'is_required' => true, 'role' => 'HR Admin']
            ],
            'is_active' => true
        ]);
    }


    private function createSampleRequests(): void
    {
        $employees = Employee::limit(20)->get();
        $workflows = DocumentApprovalWorkflow::where('is_active', true)->get();

        if ($employees->isEmpty() || $workflows->isEmpty()) {
            $this->command->warn('No employees or workflows found. Skipping sample request creation.');
            return;
        }

        // Create various types of requests
        $this->command->info('Creating sample document approval requests...');

        // Draft requests
        foreach ($employees->random(3) as $employee) {
            DocumentApprovalRequest::factory()
                ->draft()
                ->create([
                    'employee_id' => $employee->id,
                    'requester_id' => $employee->user?->id ?? User::whereHas('roles', function($q) {
                        $q->where('name', 'Employee');
                    })->first()?->id ?? 1
                ]);
        }

        // Submitted requests
        foreach ($employees->random(5) as $employee) {
            DocumentApprovalRequest::factory()
                ->submitted()
                ->create([
                    'employee_id' => $employee->id,
                    'requester_id' => $employee->user?->id ?? User::whereHas('roles', function($q) {
                        $q->where('name', 'Employee');
                    })->first()?->id ?? 1
                ]);
        }

        // Under review requests
        foreach ($employees->random(4) as $employee) {
            DocumentApprovalRequest::factory()
                ->underReview()
                ->create([
                    'employee_id' => $employee->id,
                    'requester_id' => $employee->user?->id ?? User::whereHas('roles', function($q) {
                        $q->where('name', 'Employee');
                    })->first()?->id ?? 1
                ]);
        }

        // Approved requests
        foreach ($employees->random(6) as $employee) {
            DocumentApprovalRequest::factory()
                ->approved()
                ->create([
                    'employee_id' => $employee->id,
                    'requester_id' => $employee->user?->id ?? User::whereHas('roles', function($q) {
                        $q->where('name', 'Employee');
                    })->first()?->id ?? 1
                ]);
        }

        // Rejected requests
        foreach ($employees->random(2) as $employee) {
            DocumentApprovalRequest::factory()
                ->rejected()
                ->create([
                    'employee_id' => $employee->id,
                    'requester_id' => $employee->user?->id ?? User::whereHas('roles', function($q) {
                        $q->where('name', 'Employee');
                    })->first()?->id ?? 1
                ]);
        }

        // High priority urgent requests
        foreach ($employees->random(2) as $employee) {
            DocumentApprovalRequest::factory()
                ->urgent()
                ->submitted()
                ->create([
                    'employee_id' => $employee->id,
                    'requester_id' => $employee->user?->id ?? User::whereHas('roles', function($q) {
                        $q->where('name', 'Employee');
                    })->first()?->id ?? 1,
                    'document_type' => 'Emergency Certificate of Employment',
                    'title' => 'Emergency Certificate of Employment Request'
                ]);
        }

        $this->command->info('Created sample document approval requests with various statuses.');
    }
}