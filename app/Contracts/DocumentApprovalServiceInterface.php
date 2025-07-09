<?php

namespace App\Contracts;

use App\Models\DocumentApprovalRequest;
use App\Models\DocumentApprovalStep;
use App\Models\DocumentApprovalWorkflow;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

interface DocumentApprovalServiceInterface
{
    /**
     * Get paginated list of document approval requests with filters
     */
    public function getRequestsList(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get pending approvals for a specific user
     */
    public function getPendingApprovalsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new document approval request
     */
    public function createRequest(array $data, User $requester): DocumentApprovalRequest;

    /**
     * Update an existing document approval request
     */
    public function updateRequest(DocumentApprovalRequest $request, array $data): DocumentApprovalRequest;

    /**
     * Submit a draft request for approval
     */
    public function submitRequest(DocumentApprovalRequest $request): bool;

    /**
     * Withdraw a submitted request
     */
    public function withdrawRequest(DocumentApprovalRequest $request, string $reason = null): bool;

    /**
     * Approve a step in the approval process
     */
    public function approveStep(DocumentApprovalStep $step, User $approver, string $comments = null): bool;

    /**
     * Reject a step in the approval process
     */
    public function rejectStep(DocumentApprovalStep $step, User $approver, string $comments = null): bool;

    /**
     * Return a request for revision
     */
    public function returnForRevision(DocumentApprovalStep $step, User $approver, string $comments): bool;

    /**
     * Upload an attachment to a request
     */
    public function uploadAttachment(DocumentApprovalRequest $request, UploadedFile $file, User $uploader, string $description = null): bool;

    /**
     * Add a comment to a request
     */
    public function addComment(DocumentApprovalRequest $request, User $user, string $comment, bool $isInternal = false, int $stepId = null): bool;

    /**
     * Get workflow configuration for a document type
     */
    public function getWorkflowForDocumentType(string $documentType): ?DocumentApprovalWorkflow;

    /**
     * Create workflow steps for a request
     */
    public function createWorkflowSteps(DocumentApprovalRequest $request, DocumentApprovalWorkflow $workflow): bool;

    /**
     * Get dashboard statistics for document approvals
     */
    public function getDashboardStats(User $user): array;

    /**
     * Get approval analytics data
     */
    public function getAnalyticsData(array $filters = []): array;

    /**
     * Send notifications for pending approvals
     */
    public function sendPendingApprovalNotifications(): int;

    /**
     * Check for overdue requests and send escalation notifications
     */
    public function processOverdueRequests(): int;

    /**
     * Bulk approve multiple requests
     */
    public function bulkApprove(array $stepIds, User $approver, string $comments = null): array;

    /**
     * Bulk reject multiple requests
     */
    public function bulkReject(array $stepIds, User $approver, string $comments = null): array;

    /**
     * Get request history for auditing
     */
    public function getRequestHistory(DocumentApprovalRequest $request): array;

    /**
     * Export requests to Excel/PDF
     */
    public function exportRequests(array $filters = [], string $format = 'excel'): string;

    /**
     * Get available document types for approval
     */
    public function getAvailableDocumentTypes(): array;

    /**
     * Validate if user can perform action on request
     */
    public function canUserPerformAction(User $user, DocumentApprovalRequest $request, string $action): bool;

    /**
     * Delete a document approval request
     */
    public function deleteRequest(DocumentApprovalRequest $request): bool;

    /**
     * Download request attachment
     */
    public function downloadAttachment($attachment);

    /**
     * Process approval action (approve/reject/return)
     */
    public function processApprovalAction(DocumentApprovalRequest $request, User $user, string $action, array $data): bool;

    /**
     * Assign approver to a specific step
     */
    public function assignApprover(DocumentApprovalRequest $request, int $userId, int $stepOrder): bool;

    /**
     * Reassign current step to different approver
     */
    public function reassignCurrentStep(DocumentApprovalRequest $request, int $userId, string $reason): bool;

    /**
     * Escalate request to next level
     */
    public function escalateRequest(DocumentApprovalRequest $request, string $reason): bool;
}