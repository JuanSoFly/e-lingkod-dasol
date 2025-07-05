<?php

namespace App\Services;

use App\Contracts\DocumentApprovalServiceInterface;
use App\Models\DocumentApprovalRequest;
use App\Models\DocumentApprovalStep;
use App\Models\DocumentApprovalWorkflow;
use App\Models\DocumentApprovalAttachment;
use App\Models\DocumentApprovalComment;
use App\Models\User;
use App\Notifications\DocumentApprovalRequestSubmitted;
use App\Notifications\DocumentApprovalStepAssigned;
use App\Notifications\DocumentApprovalRequestApproved;
use App\Notifications\DocumentApprovalRequestRejected;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class DocumentApprovalService implements DocumentApprovalServiceInterface
{
    const CACHE_DURATION = 300; // 5 minutes
    const CACHE_PREFIX = 'document_approval';

    /**
     * Get paginated list of document approval requests with filters
     */
    public function getRequestsList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $user = auth()->user();
        
        $query = DocumentApprovalRequest::with(['requester', 'employee', 'steps.approver'])
            ->orderBy('created_at', 'desc');

        // Apply role-based filtering
        if ($user->hasRole('Employee')) {
            // Employees only see their own requests
            $query->where('employee_id', $user->employee?->id);
        } elseif ($user->hasRole('HR Admin')) {
            // HR Admin sees all requests (no additional filtering)
        } elseif ($user->hasRole('Super Admin')) {
            // Super Admin sees all requests (no additional filtering)
        } else {
            // Unknown role - see nothing
            $query->whereRaw('1 = 0');
        }

        // Apply additional filters
        if (!empty($filters['employee_id']) && $user->hasAnyRole(['HR Admin', 'Super Admin'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['document_type'])) {
            $query->where('document_type', $filters['document_type']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['requester_id']) && $user->hasAnyRole(['HR Admin', 'Super Admin'])) {
            $query->where('requester_id', $filters['requester_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get pending approvals for a specific user
     */
    public function getPendingApprovalsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DocumentApprovalRequest::with(['requester', 'employee', 'steps'])
            ->whereHas('steps', function ($query) use ($user) {
                $query->where('approver_id', $user->id)
                      ->where('status', 'pending');
            })
            ->whereIn('status', ['submitted', 'under_review']);

        // Apply filters
        if (isset($filters['document_type'])) {
            $query->where('document_type', $filters['document_type']);
        }

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['workflow_id'])) {
            $query->where('workflow_id', $filters['workflow_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('deadline', 'asc')
                    ->orderBy('priority', 'desc')
                    ->paginate($perPage);
    }

    /**
     * Create a new document approval request
     */
    public function createRequest(array $data, User $requester): DocumentApprovalRequest
    {
        return DB::transaction(function () use ($data, $requester) {
            $request = DocumentApprovalRequest::create([
                'document_type' => $data['document_type'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'requester_id' => $requester->id,
                'employee_id' => $data['employee_id'] ?? $requester->employee?->id,
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'draft',
            ]);

            // Get workflow configuration
            $workflow = $this->getWorkflowForDocumentType($data['document_type']);
            if ($workflow) {
                $request->workflow_config = $workflow->steps;
                $request->deadline = $workflow->getDefaultDeadlineDate();
                $request->save();

                // Create workflow steps
                $this->createWorkflowSteps($request, $workflow);
            }

            Log::info('Document approval request created', [
                'request_id' => $request->id,
                'requester_id' => $requester->id,
                'document_type' => $data['document_type'],
            ]);

            return $request;
        });
    }

    /**
     * Update an existing document approval request
     */
    public function updateRequest(DocumentApprovalRequest $request, array $data): DocumentApprovalRequest
    {
        if (!in_array($request->status, ['draft', 'returned'])) {
            throw new \Exception('Cannot update request in current status');
        }

        $request->update([
            'title' => $data['title'] ?? $request->title,
            'description' => $data['description'] ?? $request->description,
            'priority' => $data['priority'] ?? $request->priority,
        ]);

        Log::info('Document approval request updated', [
            'request_id' => $request->id,
            'updated_by' => auth()->id(),
        ]);

        return $request->fresh();
    }

    /**
     * Submit a draft request for approval
     */
    public function submitRequest(DocumentApprovalRequest $request): bool
    {
        if (!$request->canBeSubmitted()) {
            return false;
        }

        return DB::transaction(function () use ($request) {
            $request->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Assign first step
            $firstStep = $request->steps()->where('step_number', 1)->first();
            if ($firstStep) {
                $firstStep->update([
                    'assigned_at' => now(),
                    'deadline' => now()->addDays(3), // Default 3 days for each step
                ]);

                $request->update(['status' => 'under_review']);

                // Send notification to approver
                if ($firstStep->approver) {
                    $firstStep->approver->notify(new DocumentApprovalStepAssigned($request, $firstStep));
                }
            }

            // Add system comment
            DocumentApprovalComment::createSystemComment(
                $request->id,
                $firstStep?->id,
                'Request submitted for approval'
            );

            Log::info('Document approval request submitted', [
                'request_id' => $request->id,
                'first_approver_id' => $firstStep?->approver_id,
            ]);

            return true;
        });
    }

    /**
     * Withdraw a submitted request
     */
    public function withdrawRequest(DocumentApprovalRequest $request, string $reason = null): bool
    {
        if (!$request->canBeWithdrawn()) {
            return false;
        }

        return DB::transaction(function () use ($request, $reason) {
            $request->update(['status' => 'draft']);

            // Reset all pending steps
            $request->steps()->where('status', 'pending')->update([
                'status' => 'pending',
                'assigned_at' => null,
                'deadline' => null,
            ]);

            // Add system comment
            DocumentApprovalComment::createSystemComment(
                $request->id,
                null,
                'Request withdrawn' . ($reason ? ": {$reason}" : '')
            );

            Log::info('Document approval request withdrawn', [
                'request_id' => $request->id,
                'withdrawn_by' => auth()->id(),
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Approve a step in the approval process
     */
    public function approveStep(DocumentApprovalStep $step, User $approver, string $comments = null): bool
    {
        if (!$step->canBeApproved()) {
            return false;
        }

        return DB::transaction(function () use ($step, $approver, $comments) {
            $step->approve($comments);

            $request = $step->request;

            // Check if this was the final step
            $nextStep = $request->getNextStep();
            if ($nextStep) {
                // Move to next step
                $request->update(['current_step' => $nextStep->step_number]);
                
                $nextStep->update([
                    'assigned_at' => now(),
                    'deadline' => now()->addDays(3),
                ]);

                // Notify next approver
                if ($nextStep->approver) {
                    $nextStep->approver->notify(new DocumentApprovalStepAssigned($request, $nextStep));
                }
            } else {
                // All steps completed - approve request
                $request->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);

                // Notify requester
                $request->requester->notify(new DocumentApprovalRequestApproved($request));
            }

            // Add approval comment
            DocumentApprovalComment::createApprovalComment(
                $request->id,
                $step->id,
                $approver->id,
                'approved',
                $comments
            );

            Log::info('Document approval step approved', [
                'request_id' => $request->id,
                'step_id' => $step->id,
                'approver_id' => $approver->id,
            ]);

            return true;
        });
    }

    /**
     * Reject a step in the approval process
     */
    public function rejectStep(DocumentApprovalStep $step, User $approver, string $comments = null): bool
    {
        if (!$step->canBeRejected()) {
            return false;
        }

        return DB::transaction(function () use ($step, $approver, $comments) {
            $step->reject($comments);

            $request = $step->request;
            $request->update([
                'status' => 'rejected',
                'rejected_at' => now(),
            ]);

            // Add rejection comment
            DocumentApprovalComment::createApprovalComment(
                $request->id,
                $step->id,
                $approver->id,
                'rejected',
                $comments
            );

            // Notify requester
            $request->requester->notify(new DocumentApprovalRequestRejected($request, $comments));

            Log::info('Document approval step rejected', [
                'request_id' => $request->id,
                'step_id' => $step->id,
                'approver_id' => $approver->id,
            ]);

            return true;
        });
    }

    /**
     * Return a request for revision
     */
    public function returnForRevision(DocumentApprovalStep $step, User $approver, string $comments): bool
    {
        return DB::transaction(function () use ($step, $approver, $comments) {
            $request = $step->request;
            $request->update([
                'status' => 'returned',
                'current_step' => 1, // Reset to first step
            ]);

            // Reset all steps
            $request->steps()->update([
                'status' => 'pending',
                'assigned_at' => null,
                'deadline' => null,
                'approved_at' => null,
                'rejected_at' => null,
            ]);

            // Add return comment
            DocumentApprovalComment::createSystemComment(
                $request->id,
                $step->id,
                "Request returned for revision: {$comments}",
                ['returned_by' => $approver->id]
            );

            // Notify requester
            $request->requester->notify(new DocumentApprovalRequestRejected($request, $comments));

            Log::info('Document approval request returned for revision', [
                'request_id' => $request->id,
                'step_id' => $step->id,
                'approver_id' => $approver->id,
            ]);

            return true;
        });
    }

    /**
     * Upload an attachment to a request
     */
    public function uploadAttachment(DocumentApprovalRequest $request, UploadedFile $file, User $uploader, string $description = null): bool
    {
        try {
            $path = $file->store("document_approvals/{$request->id}", 'private');

            DocumentApprovalAttachment::create([
                'request_id' => $request->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => $uploader->id,
                'description' => $description,
                'is_current_version' => true,
            ]);

            Log::info('Document attachment uploaded', [
                'request_id' => $request->id,
                'file_name' => $file->getClientOriginalName(),
                'uploaded_by' => $uploader->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to upload document attachment', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Add a comment to a request
     */
    public function addComment(DocumentApprovalRequest $request, User $user, string $comment, bool $isInternal = false, int $stepId = null): bool
    {
        try {
            DocumentApprovalComment::createUserComment(
                $request->id,
                $stepId,
                $user->id,
                $comment,
                $isInternal
            );

            Log::info('Comment added to document approval request', [
                'request_id' => $request->id,
                'user_id' => $user->id,
                'is_internal' => $isInternal,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to add comment', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get workflow configuration for a document type
     */
    public function getWorkflowForDocumentType(string $documentType): ?DocumentApprovalWorkflow
    {
        return Cache::remember(
            self::CACHE_PREFIX . ".workflow.{$documentType}",
            self::CACHE_DURATION,
            function () use ($documentType) {
                return DocumentApprovalWorkflow::active()
                    ->byDocumentType($documentType)
                    ->first();
            }
        );
    }

    /**
     * Create workflow steps for a request
     */
    public function createWorkflowSteps(DocumentApprovalRequest $request, DocumentApprovalWorkflow $workflow): bool
    {
        try {
            $steps = $workflow->steps ?? [];

            foreach ($steps as $step) {
                DocumentApprovalStep::create([
                    'request_id' => $request->id,
                    'step_number' => $step['step_number'],
                    'step_name' => $step['name'],
                    'approver_id' => $this->getApproverForStep($step),
                    'approver_role' => $step['role'] ?? null,
                    'status' => 'pending',
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create workflow steps', [
                'request_id' => $request->id,
                'workflow_id' => $workflow->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get dashboard statistics for document approvals
     */
    public function getDashboardStats(User $user): array
    {
        $cacheKey = self::CACHE_PREFIX . ".dashboard.{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user) {
            $stats = [
                'total_requests' => DocumentApprovalRequest::count(),
                'pending_requests' => DocumentApprovalRequest::pending()->count(),
                'approved_requests' => DocumentApprovalRequest::where('status', 'approved')->count(),
                'rejected_requests' => DocumentApprovalRequest::where('status', 'rejected')->count(),
                'overdue_requests' => DocumentApprovalRequest::overdue()->count(),
                'my_pending_approvals' => 0,
                'my_requests' => 0,
            ];

            if ($user->hasRole(['Super Admin', 'HR Admin'])) {
                $stats['my_pending_approvals'] = DocumentApprovalRequest::forApprover($user->id)->count();
            }

            $stats['my_requests'] = DocumentApprovalRequest::where('requester_id', $user->id)->count();

            return $stats;
        });
    }

    /**
     * Get approval analytics data
     */
    public function getAnalyticsData(array $filters = []): array
    {
        $cacheKey = self::CACHE_PREFIX . '.analytics.' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters) {
            $query = DocumentApprovalRequest::query();

            // Apply date filters
            if (isset($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            return [
                'requests_by_status' => $query->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'requests_by_type' => $query->select('document_type', DB::raw('count(*) as count'))
                    ->groupBy('document_type')
                    ->pluck('count', 'document_type')
                    ->toArray(),
                'average_processing_time' => $this->getAverageProcessingTime($filters),
                'approval_rate' => $this->getApprovalRate($filters),
            ];
        });
    }

    /**
     * Send notifications for pending approvals
     */
    public function sendPendingApprovalNotifications(): int
    {
        $pendingSteps = DocumentApprovalStep::with(['request', 'approver'])
            ->pending()
            ->whereHas('request', function ($query) {
                $query->whereIn('status', ['submitted', 'under_review']);
            })
            ->get();

        $notificationCount = 0;

        foreach ($pendingSteps as $step) {
            if ($step->approver) {
                $step->approver->notify(new DocumentApprovalStepAssigned($step->request, $step));
                $notificationCount++;
            }
        }

        Log::info('Pending approval notifications sent', [
            'count' => $notificationCount,
        ]);

        return $notificationCount;
    }

    /**
     * Check for overdue requests and send escalation notifications
     */
    public function processOverdueRequests(): int
    {
        $overdueSteps = DocumentApprovalStep::overdue()
            ->with(['request', 'approver'])
            ->get();

        $escalationCount = 0;

        foreach ($overdueSteps as $step) {
            // Send escalation notification to supervisor or HR Admin
            $supervisors = User::role(['Super Admin', 'HR Admin'])->get();
            
            foreach ($supervisors as $supervisor) {
                $supervisor->notify(new DocumentApprovalStepAssigned($step->request, $step));
            }

            $escalationCount++;
        }

        Log::info('Overdue requests processed', [
            'count' => $escalationCount,
        ]);

        return $escalationCount;
    }

    /**
     * Bulk approve multiple requests
     */
    public function bulkApprove(array $stepIds, User $approver, string $comments = null): array
    {
        $results = [];

        foreach ($stepIds as $stepId) {
            $step = DocumentApprovalStep::find($stepId);
            if ($step && $step->canBeApproved()) {
                $results[$stepId] = $this->approveStep($step, $approver, $comments);
            } else {
                $results[$stepId] = false;
            }
        }

        return $results;
    }

    /**
     * Bulk reject multiple requests
     */
    public function bulkReject(array $stepIds, User $approver, string $comments = null): array
    {
        $results = [];

        foreach ($stepIds as $stepId) {
            $step = DocumentApprovalStep::find($stepId);
            if ($step && $step->canBeRejected()) {
                $results[$stepId] = $this->rejectStep($step, $approver, $comments);
            } else {
                $results[$stepId] = false;
            }
        }

        return $results;
    }

    /**
     * Get request history for auditing
     */
    public function getRequestHistory(DocumentApprovalRequest $request): array
    {
        return [
            'steps' => $request->steps()->with('approver')->orderBy('step_number')->get(),
            'comments' => $request->comments()->with('user')->orderBy('created_at')->get(),
            'attachments' => $request->attachments()->with('uploader')->orderBy('created_at')->get(),
        ];
    }

    /**
     * Export requests to Excel/PDF
     */
    public function exportRequests(array $filters = [], string $format = 'excel'): string
    {
        // Implementation would depend on export library (maatwebsite/excel)
        // This is a placeholder for the actual implementation
        return "export_file_{$format}_" . now()->format('Y-m-d_H-i-s');
    }

    /**
     * Get available document types for approval
     */
    public function getAvailableDocumentTypes(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . '.document_types',
            self::CACHE_DURATION,
            function () {
                return [
                    'leave_application' => 'Leave Application',
                    'performance_evaluation' => 'Performance Evaluation',
                    'training_certificate' => 'Training Certificate',
                    'disciplinary_action' => 'Disciplinary Action',
                    'employee_document' => 'Employee Document',
                    'policy_acknowledgment' => 'Policy Acknowledgment',
                    'overtime_request' => 'Overtime Request',
                    'travel_order' => 'Travel Order',
                ];
            }
        );
    }

    /**
     * Validate if user can perform action on request
     */
    public function canUserPerformAction(User $user, DocumentApprovalRequest $request, string $action): bool
    {
        switch ($action) {
            case 'view':
                return $user->id === $request->requester_id || 
                       $user->hasRole(['Super Admin', 'HR Admin']) ||
                       $request->steps()->where('approver_id', $user->id)->exists();
                       
            case 'edit':
                return $user->id === $request->requester_id && 
                       in_array($request->status, ['draft', 'returned']);
                       
            case 'submit':
                return $user->id === $request->requester_id && 
                       $request->status === 'draft';
                       
            case 'withdraw':
                return $user->id === $request->requester_id && 
                       in_array($request->status, ['submitted', 'under_review']);
                       
            case 'approve':
                return $request->steps()
                    ->where('approver_id', $user->id)
                    ->where('status', 'pending')
                    ->exists();
                    
            default:
                return false;
        }
    }

    /**
     * Helper method to get approver for a workflow step
     */
    private function getApproverForStep(array $step): ?int
    {
        if (isset($step['user_id'])) {
            return $step['user_id'];
        }

        if (isset($step['role'])) {
            $user = User::role($step['role'])->first();
            return $user?->id;
        }

        return null;
    }

    /**
     * Helper method to calculate average processing time
     */
    private function getAverageProcessingTime(array $filters = []): float
    {
        $query = DocumentApprovalRequest::where('status', 'approved')
            ->whereNotNull('approved_at')
            ->whereNotNull('submitted_at');

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $requests = $query->get();
        
        if ($requests->count() === 0) {
            return 0;
        }

        $totalDays = $requests->sum(function ($request) {
            return $request->submitted_at->diffInDays($request->approved_at);
        });

        return round($totalDays / $requests->count(), 2);
    }

    /**
     * Helper method to calculate approval rate
     */
    private function getApprovalRate(array $filters = []): float
    {
        $query = DocumentApprovalRequest::whereIn('status', ['approved', 'rejected']);

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $total = $query->count();
        $approved = $query->where('status', 'approved')->count();

        return $total > 0 ? round(($approved / $total) * 100, 2) : 0;
    }
}