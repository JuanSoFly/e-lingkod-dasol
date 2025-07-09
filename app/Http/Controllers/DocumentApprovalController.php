<?php

namespace App\Http\Controllers;

use App\Contracts\DocumentApprovalServiceInterface;
use App\Http\Requests\StoreDocumentApprovalRequest;
use App\Http\Requests\UpdateDocumentApprovalRequest;
use App\Models\DocumentApprovalRequest;
use App\Models\DocumentApprovalWorkflow;
use App\Models\Employee;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\AuditService;

class DocumentApprovalController extends Controller
{
    use AuthorizesRequests;
    
    protected $documentApprovalService;

    public function __construct(DocumentApprovalServiceInterface $documentApprovalService)
    {
        $this->documentApprovalService = $documentApprovalService;
    }

    public function index(Request $request)
    {
        // Log access attempt
        AuditService::logDocumentAccess(0, 'index_accessed', [
            'filters' => $request->only(['status', 'type', 'employee_id', 'workflow_id', 'date_from', 'date_to']),
        ]);
        
        // Redirect employees to their own requests
        if (auth()->user()->hasRole('Employee')) {
            AuditService::logPrivacyViolation('employee_tried_to_access_all_document_requests', [
                'route' => 'document-approvals.index',
            ]);
            return redirect()->route('document-approvals.my-requests');
        }
        
        // Only HR Admin and Super Admin can see all requests
        $this->authorize('viewAny', DocumentApprovalRequest::class);

        $filters = $request->only(['status', 'type', 'employee_id', 'workflow_id', 'date_from', 'date_to']);
        
        $requests = $this->documentApprovalService->getRequestsList($filters);
        $workflows = DocumentApprovalWorkflow::active()->get();
        $employees = Employee::active()->orderBy('last_name')->get();

        return view('document-approvals.index', compact('requests', 'workflows', 'employees', 'filters'));
    }

    public function create()
    {
        $this->authorize('document-approval.create');

        $workflows = DocumentApprovalWorkflow::active()->get();
        $employees = Employee::active()->orderBy('last_name')->get();

        return view('document-approvals.create', compact('workflows', 'employees'));
    }

    public function store(StoreDocumentApprovalRequest $request)
    {
        try {
            $documentRequest = $this->documentApprovalService->createRequest($request->validated(), auth()->user());

            return redirect()
                ->route('document-approvals.show', $documentRequest)
                ->with('success', 'Document approval request created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create request: ' . $e->getMessage()]);
        }
    }

    public function show(DocumentApprovalRequest $documentApprovalRequest)
    {
        // Log specific document access
        AuditService::logDocumentAccess($documentApprovalRequest->id, 'document_viewed', [
            'request_type' => $documentApprovalRequest->document_type,
            'employee_id' => $documentApprovalRequest->employee_id,
            'status' => $documentApprovalRequest->status,
        ]);
        
        // Check if employee is trying to access another employee's request
        if (auth()->user()->hasRole('Employee') && auth()->user()->employee?->id !== $documentApprovalRequest->employee_id) {
            AuditService::logPrivacyViolation('employee_tried_to_access_other_employee_document', [
                'attempted_request_id' => $documentApprovalRequest->id,
                'request_owner_id' => $documentApprovalRequest->employee_id,
                'own_employee_id' => auth()->user()->employee?->id,
            ]);
            abort(403, 'You can only view your own document requests.');
        }
        
        // Employees can only view their own requests
        if (auth()->user()->hasRole('Employee')) {
            $this->authorize('view', $documentApprovalRequest);
        } else {
            $this->authorize('viewAny', DocumentApprovalRequest::class);
        }

        $documentApprovalRequest->load([
            'employee',
            'workflow.steps.users',
            'approvalSteps.user',
            'attachments',
            'comments.user'
        ]);

        $canApprove = Gate::allows('approve', $documentApprovalRequest);
        $currentStep = $documentApprovalRequest->getCurrentApprovalStep();

        return view('document-approvals.show', compact(
            'documentApprovalRequest',
            'canApprove',
            'currentStep'
        ));
    }

    public function edit(DocumentApprovalRequest $documentApprovalRequest)
    {
        $this->authorize('update', $documentApprovalRequest);

        if ($documentApprovalRequest->status !== 'draft') {
            return redirect()
                ->route('document-approvals.show', $documentApprovalRequest)
                ->withErrors(['error' => 'Only draft requests can be edited.']);
        }

        $workflows = DocumentApprovalWorkflow::active()->get();
        $employees = Employee::active()->orderBy('last_name')->get();

        return view('document-approvals.edit', compact(
            'documentApprovalRequest',
            'workflows',
            'employees'
        ));
    }

    public function update(UpdateDocumentApprovalRequest $request, DocumentApprovalRequest $documentApprovalRequest)
    {
        try {
            $this->documentApprovalService->updateRequest($documentApprovalRequest, $request->validated());

            return redirect()
                ->route('document-approvals.show', $documentApprovalRequest)
                ->with('success', 'Document approval request updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update request: ' . $e->getMessage()]);
        }
    }

    public function destroy(DocumentApprovalRequest $documentApprovalRequest)
    {
        $this->authorize('delete', $documentApprovalRequest);

        if ($documentApprovalRequest->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft requests can be deleted.']);
        }

        try {
            $this->documentApprovalService->deleteRequest($documentApprovalRequest);

            return redirect()
                ->route('document-approvals.index')
                ->with('success', 'Document approval request deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete request: ' . $e->getMessage()]);
        }
    }

    public function submit(DocumentApprovalRequest $documentApprovalRequest)
    {
        $this->authorize('submit', $documentApprovalRequest);

        if ($documentApprovalRequest->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft requests can be submitted.']);
        }

        try {
            $this->documentApprovalService->submitRequest($documentApprovalRequest);

            return redirect()
                ->route('document-approvals.show', $documentApprovalRequest)
                ->with('success', 'Document approval request submitted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to submit request: ' . $e->getMessage()]);
        }
    }

    public function withdraw(Request $request, DocumentApprovalRequest $documentApprovalRequest)
    {
        $this->authorize('update', $documentApprovalRequest);

        if (!in_array($documentApprovalRequest->status, ['submitted', 'under_review'])) {
            return back()->withErrors(['error' => 'Only submitted or under review requests can be withdrawn.']);
        }

        try {
            $reason = $request->input('reason', 'Request withdrawn by employee');
            $this->documentApprovalService->withdrawRequest($documentApprovalRequest, $reason);

            return redirect()
                ->route('document-approvals.my-requests')
                ->with('success', 'Document approval request withdrawn successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to withdraw request: ' . $e->getMessage()]);
        }
    }

    public function download(DocumentApprovalRequest $documentApprovalRequest, $attachmentId)
    {
        $this->authorize('view', $documentApprovalRequest);

        $attachment = $documentApprovalRequest->attachments()->findOrFail($attachmentId);

        return $this->documentApprovalService->downloadAttachment($attachment);
    }

    public function dashboard()
    {
        // Only HR Admin and Super Admin can view dashboard
        $this->authorize('viewAny', DocumentApprovalRequest::class);

        $stats = $this->documentApprovalService->getDashboardStats(auth()->user());

        return view('document-approvals.dashboard', compact('stats'));
    }

    public function myRequests(Request $request)
    {
        $filters = $request->only(['status', 'type', 'date_from', 'date_to']);
        $filters['employee_id'] = auth()->user()->employee?->id;

        $requests = $this->documentApprovalService->getRequestsList($filters);

        return view('document-approvals.my-requests', compact('requests', 'filters'));
    }

    public function pendingApprovals(Request $request)
    {
        $this->authorize('document-approval.approve');

        $filters = $request->only(['type', 'employee_id', 'workflow_id', 'date_from', 'date_to']);
        
        $requests = $this->documentApprovalService->getPendingApprovalsForUser(auth()->user(), $filters);
        $workflows = DocumentApprovalWorkflow::active()->get();
        $employees = Employee::active()->orderBy('last_name')->get();

        return view('document-approvals.pending-approvals', compact('requests', 'workflows', 'employees', 'filters'));
    }
}