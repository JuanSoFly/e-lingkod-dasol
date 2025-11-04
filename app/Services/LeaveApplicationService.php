<?php

namespace App\Services;

use App\Models\LeaveApplication;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Notifications\LeaveApplicationActioned;
use App\Notifications\LeaveApplicationSubmitted;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveApplicationService
{
    public function getApplicationsForUser(User $user, ?string $status = null)
    {
        $query = LeaveApplication::with(['employee', 'leaveType']);

        if ($user->can('leave.approve') && $status === 'pending') {
            // Approvers see all pending applications
            $query->where('status', 'pending');
        } else {
            // Employees see only their own applications
            if ($user->employee) {
                $query->where('employee_id', $user->employee->id);
            }
        }

        return $query->latest()->paginate(10);
    }

    public function createApplication(array $data, User $user): LeaveApplication
    {
        if (!$user->employee) {
            throw new \InvalidArgumentException('User account is not linked to an employee profile.');
        }

        return DB::transaction(function () use ($data, $user) {
            $application = LeaveApplication::create(array_merge($data, [
                'employee_id' => $user->employee->id,
                'status' => 'pending',
                'applied_date' => now(),
            ]));

            // Initialize workflow for the application
            $workflowService = new LeaveWorkflowService();
            $workflowService->initializeWorkflow($application);

            // Update notification method to use workflow-based routing
            $this->notifyWorkflowApprovers($application);

            Log::info('Leave application created with workflow', [
                'application_id' => $application->id,
                'employee_id' => $user->employee->id,
                'leave_type' => $application->leaveType->name ?? 'Unknown',
                'days_requested' => $application->days_requested,
            ]);

            return $application;
        });
    }

    public function approveApplication(LeaveApplication $application, User $approver, ?string $remarks = null): LeaveApplication
    {
        return DB::transaction(function () use ($application, $approver, $remarks) {
            $application->update([
                'status' => 'approved',
                'remarks' => $remarks ?? 'Approved',
                'approved_by' => $approver->id,
                'approved_date' => now(),
            ]);

            $this->notifyApplicant($application);
            
            Log::info('Leave application approved', [
                'application_id' => $application->id,
                'approved_by' => $approver->id,
                'employee_id' => $application->employee_id,
            ]);

            return $application;
        });
    }

    public function rejectApplication(LeaveApplication $application, User $approver, string $remarks): LeaveApplication
    {
        return DB::transaction(function () use ($application, $approver, $remarks) {
            $application->update([
                'status' => 'rejected',
                'remarks' => $remarks,
                'approved_by' => $approver->id,
                'approved_date' => now(),
            ]);

            $this->notifyApplicant($application);
            
            Log::info('Leave application rejected', [
                'application_id' => $application->id,
                'approved_by' => $approver->id,
                'employee_id' => $application->employee_id,
                'reason' => $remarks,
            ]);

            return $application;
        });
    }

    public function cancelApplication(LeaveApplication $application, User $user): LeaveApplication
    {
        if ($application->employee->user_id !== $user->id) {
            throw new \InvalidArgumentException('User can only cancel their own applications.');
        }

        if (!in_array($application->status, ['pending', 'approved'])) {
            throw new \InvalidArgumentException('Only pending or approved applications can be cancelled.');
        }

        $application->update([
            'status' => 'cancelled',
            'remarks' => 'Cancelled by employee',
        ]);

        Log::info('Leave application cancelled', [
            'application_id' => $application->id,
            'employee_id' => $application->employee_id,
            'cancelled_by' => $user->id,
        ]);

        return $application;
    }

    private function notifyWorkflowApprovers(LeaveApplication $application): void
    {
        try {
            // Get current pending workflow step approvers
            $currentStep = \App\Models\LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
                ->where('status', 'pending')
                ->orderBy('step_order')
                ->first();

            if ($currentStep) {
                $approvers = $currentStep->leaveWorkflowStep->getCurrentApprovers($application);
                if (!empty($approvers)) {
                    Notification::send($approvers, new LeaveApplicationSubmitted($application));
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify workflow approvers', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyApprovers(LeaveApplication $application): void
    {
        try {
            $approvers = User::permission('leave.approve')->get();
            if ($approvers->isNotEmpty()) {
                Notification::send($approvers, new LeaveApplicationSubmitted($application));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify approvers', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyApplicant(LeaveApplication $application): void
    {
        try {
            if ($application->employee->user) {
                $application->employee->user->notify(new LeaveApplicationActioned($application));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify applicant', [
                'application_id' => $application->id,
                'employee_id' => $application->employee_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getLeaveBalance(User $user, int $leaveTypeId): int
    {
        if (!$user->employee) {
            return 0;
        }

        // This would typically calculate based on leave credits and used leave
        // For now, returning a placeholder
        return 15; // Placeholder - implement actual calculation
    }

    /**
     * Attach document to leave application
     */
    public function attachDocument(LeaveApplication $application, $file, User $user): EmployeeDocument
    {
        // Store file
        $filename = $this->generateUniqueFilename($file);
        $filePath = $file->storeAs('leave-applications/' . $application->id, $filename, 'local');

        // Create document record
        $document = EmployeeDocument::create([
            'employee_id' => $application->employee_id,
            'document_type' => 'Leave Application Supporting Document',
            'category' => 'Leave Documents',
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'description' => 'Supporting document for leave application',
            'is_verified' => false,
            'uploaded_by' => $user->id,
        ]);

        // Link document to application (assuming there's a relationship table)
        $application->documents()->attach($document->id);

        return $document;
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = \Illuminate\Support\Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = time();

        return "{$basename}_{$timestamp}.{$extension}";
    }
}