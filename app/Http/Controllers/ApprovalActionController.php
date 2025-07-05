<?php

namespace App\Http\Controllers;

use App\Contracts\DocumentApprovalServiceInterface;
use App\Http\Requests\ApprovalActionRequest;
use App\Models\DocumentApprovalRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ApprovalActionController extends Controller
{
    use AuthorizesRequests;
    
    protected $documentApprovalService;

    public function __construct(DocumentApprovalServiceInterface $documentApprovalService)
    {
        $this->documentApprovalService = $documentApprovalService;
    }

    public function approve(ApprovalActionRequest $request, DocumentApprovalRequest $documentApprovalRequest)
    {
        try {
            $this->documentApprovalService->processApprovalAction(
                $documentApprovalRequest,
                auth()->user(),
                'approve',
                $request->validated()
            );

            return redirect()
                ->route('document-approvals.show', $documentApprovalRequest)
                ->with('success', 'Request approved successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to approve request: ' . $e->getMessage()]);
        }
    }

    public function reject(ApprovalActionRequest $request, DocumentApprovalRequest $documentApprovalRequest)
    {
        try {
            $this->documentApprovalService->processApprovalAction(
                $documentApprovalRequest,
                auth()->user(),
                'reject',
                $request->validated()
            );

            return redirect()
                ->route('document-approvals.show', $documentApprovalRequest)
                ->with('success', 'Request rejected successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to reject request: ' . $e->getMessage()]);
        }
    }

    public function requestChanges(ApprovalActionRequest $request, DocumentApprovalRequest $documentApprovalRequest)
    {
        try {
            $this->documentApprovalService->processApprovalAction(
                $documentApprovalRequest,
                auth()->user(),
                'request_changes',
                $request->validated()
            );

            return redirect()
                ->route('document-approvals.show', $documentApprovalRequest)
                ->with('success', 'Changes requested successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to request changes: ' . $e->getMessage()]);
        }
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'request_ids' => 'required|array|min:1',
            'request_ids.*' => 'exists:document_approval_requests,id',
            'comments' => 'nullable|string|max:2000'
        ]);

        $this->authorize('bulkApprove', DocumentApprovalRequest::class);

        try {
            $approved = $this->documentApprovalService->bulkApprove(
                $request->request_ids,
                auth()->user(),
                $request->comments
            );

            return back()->with('success', "Successfully approved {$approved} requests.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to bulk approve: ' . $e->getMessage()]);
        }
    }

    public function bulkReject(Request $request)
    {
        $request->validate([
            'request_ids' => 'required|array|min:1',
            'request_ids.*' => 'exists:document_approval_requests,id',
            'comments' => 'required|string|max:2000'
        ]);

        $this->authorize('bulkReject', DocumentApprovalRequest::class);

        try {
            $rejected = $this->documentApprovalService->bulkReject(
                $request->request_ids,
                auth()->user(),
                $request->comments
            );

            return back()->with('success', "Successfully rejected {$rejected} requests.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to bulk reject: ' . $e->getMessage()]);
        }
    }

    public function addComment(Request $request, DocumentApprovalRequest $documentApprovalRequest)
    {
        $request->validate([
            'comment' => 'required|string|max:2000',
            'is_internal' => 'boolean'
        ]);

        $this->authorize('comment', $documentApprovalRequest);

        try {
            $this->documentApprovalService->addComment(
                $documentApprovalRequest,
                auth()->user(),
                $request->comment,
                $request->boolean('is_internal', false)
            );

            return redirect()
                ->route('document-approvals.show', $documentApprovalRequest)
                ->with('success', 'Comment added successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to add comment: ' . $e->getMessage()]);
        }
    }

    public function assignApprover(Request $request, DocumentApprovalRequest $documentApprovalRequest)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'step_order' => 'required|integer|min:1'
        ]);

        $this->authorize('manage', $documentApprovalRequest);

        try {
            $this->documentApprovalService->assignApprover(
                $documentApprovalRequest,
                $request->user_id,
                $request->step_order
            );

            return redirect()
                ->route('document-approvals.show', $documentApprovalRequest)
                ->with('success', 'Approver assigned successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to assign approver: ' . $e->getMessage()]);
        }
    }

    public function reassign(Request $request, DocumentApprovalRequest $documentApprovalRequest)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500'
        ]);

        $this->authorize('reassign', $documentApprovalRequest);

        try {
            $this->documentApprovalService->reassignCurrentStep(
                $documentApprovalRequest,
                $request->user_id,
                $request->reason
            );

            return redirect()
                ->route('document-approvals.show', $documentApprovalRequest)
                ->with('success', 'Request reassigned successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to reassign request: ' . $e->getMessage()]);
        }
    }

    public function escalate(Request $request, DocumentApprovalRequest $documentApprovalRequest)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $this->authorize('escalate', $documentApprovalRequest);

        try {
            $this->documentApprovalService->escalateRequest(
                $documentApprovalRequest,
                $request->reason
            );

            return redirect()
                ->route('document-approvals.show', $documentApprovalRequest)
                ->with('success', 'Request escalated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to escalate request: ' . $e->getMessage()]);
        }
    }
}