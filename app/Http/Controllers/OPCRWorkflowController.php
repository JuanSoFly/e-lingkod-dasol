<?php

namespace App\Http\Controllers;

use App\Models\OPCRWorkflow;
use App\Models\Office;
use App\Models\PerformancePeriod;
use App\Models\User;
use App\Models\OfficeAssignment;
use App\Services\OPCRWorkflowService;
use App\Services\OPCRManagementService;
use App\Http\Requests\StoreOPCRWorkflowRequest;
use App\Http\Requests\UpdateOPCRWorkflowRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\Activity;

class OPCRWorkflowController extends Controller
{
    private OPCRWorkflowService $workflowService;
    private OPCRManagementService $opcrManagementService;

    public function __construct(
        OPCRWorkflowService $workflowService,
        OPCRManagementService $opcrManagementService
    ) {
        $this->workflowService = $workflowService;
        $this->opcrManagementService = $opcrManagementService;

        $this->middleware('auth');
        $this->middleware('permission:opcr.view')->only(['index', 'show']);
        $this->middleware('permission:opcr.create')->only(['create', 'store']);
        $this->middleware('permission:opcr.edit')->only(['edit', 'update']);
        $this->middleware('permission:opcr.commit')->only(['commit']);
        $this->middleware('permission:opcr.submit')->only(['submit']);
        $this->middleware('permission:opcr.assess')->only(['assess', 'processAssessment']);
        $this->middleware('permission:opcr.approve')->only(['approve', 'processApproval']);
        $this->middleware('permission:opcr.return')->only(['return']);
        $this->middleware(OPCRWorkflowStateMiddleware::class)->only([
            'commit', 'submit', 'assess', 'approve', 'return'
        ]);
    }

    /**
     * Display a listing of OPCR workflows
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = OPCRWorkflow::with(['office', 'period', 'committedBy', 'assessedBy', 'approvedBy']);

        $hasCrossOfficeRole = $user->officeAssignments()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->whereIn('role', [
                OfficeAssignment::ROLE_ASSESSOR,
                OfficeAssignment::ROLE_FINAL_APPROVER,
            ])
            ->exists();

        // Filter based on user role and permissions
        if (!$user->hasPermissionTo('opcr.admin') && !$hasCrossOfficeRole) {
            $query->whereHas('office', function ($q) use ($user) {
                $q->whereHas('officeAssignments', function ($subQ) use ($user) {
                    $subQ->where('user_id', $user->id)
                         ->where('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD)
                         ->where('is_active', true)
                         ->where(function ($inner) {
                             $inner->whereNull('ended_date')
                                   ->orWhere('ended_date', '>=', now());
                         });
                });
            });
        }

        // Apply filters
        if ($request->filled('state')) {
            $query->byState($request->state);
        }

        if ($request->filled('office_id')) {
            $query->forOffice($request->office_id);
        }

        if ($request->filled('period_id')) {
            $query->forPeriod($request->period_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhereHas('office', function ($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $workflows = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Get filter options
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $periods = PerformancePeriod::where('is_active', true)->orderBy('year', 'desc')->get();
        $states = OPCRWorkflow::getAvailableStates();

        // Get workflow analytics for dashboard
        $analytics = $this->workflowService->getWorkflowAnalytics();

        return view('admin.opcr.workflows.index', compact(
            'workflows',
            'offices',
            'periods',
            'states',
            'analytics'
        ));
    }

    /**
     * Show the form for creating a new OPCR workflow
     */
    public function create(): View
    {
        $user = Auth::user();

        // Get offices where user can create workflows
        $offices = Office::whereHas('officeAssignments', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('role', 'Department Head')
                  ->where('is_active', true);
        })->where('is_active', true)->get();

        $periods = PerformancePeriod::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderBy('year', 'desc')
            ->get();

        return view('admin.opcr.workflows.create', compact('offices', 'periods'));
    }

    /**
     * Store a newly created OPCR workflow
     */
    public function store(StoreOPCRWorkflowRequest $request): RedirectResponse
    {
        try {
            $workflow = $this->workflowService->initializeWorkflow($request->validated());

            Activity::log('OPCR workflow created', [
                'opcr_workflow_id' => $workflow->id,
                'office_id' => $workflow->office_id,
                'created_by' => Auth::id(),
            ]);

            return redirect()
                ->route('opcr.workflows.show', $workflow)
                ->with('success', 'OPCR workflow created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create OPCR workflow: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified OPCR workflow
     */
    public function show(OPCRWorkflow $workflow): View
    {
        $this->authorize('view', $workflow);

        $workflow->load([
            'office.activeMajorFinalOutputs.activeSuccessIndicators',
            'period',
            'committedBy',
            'assessedBy',
            'approvedBy',
            'ratings'
        ]);

        // Get workflow history
        $history = $this->workflowService->getUserWorkflowHistory(Auth::user());

        // Get user permissions for this workflow
        $userPermissions = $this->getUserWorkflowPermissions($workflow);

        return view('admin.opcr.workflows.show', compact(
            'workflow',
            'history',
            'userPermissions'
        ));
    }

    /**
     * Show the form for editing the specified OPCR workflow
     */
    public function edit(OPCRWorkflow $workflow): View
    {
        $this->authorize('edit', $workflow);

        if (!$workflow->canBeEdited()) {
            abort(403, 'This workflow cannot be edited in its current state.');
        }

        $workflow->load([
            'office.activeMajorFinalOutputs.activeSuccessIndicators'
        ]);

        return view('admin.opcr.workflows.edit', compact('workflow'));
    }

    /**
     * Update the specified OPCR workflow
     */
    public function update(UpdateOPCRWorkflowRequest $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorize('edit', $workflow);

        if (!$workflow->canBeEdited()) {
            abort(403, 'This workflow cannot be edited in its current state.');
        }

        try {
            $workflow->update($request->validated());

            Activity::log('OPCR workflow updated', [
                'opcr_workflow_id' => $workflow->id,
                'updated_by' => Auth::id(),
            ]);

            return redirect()
                ->route('opcr.workflows.show', $workflow)
                ->with('success', 'OPCR workflow updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update OPCR workflow: ' . $e->getMessage()]);
        }
    }

    /**
     * Commit the OPCR workflow
     */
    public function commit(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorize('commit', $workflow);

        if (!$workflow->canBeCommitted()) {
            abort(403, 'This workflow cannot be committed in its current state.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
        ]);

        try {
            $success = $this->workflowService->commitWorkflow($workflow, $request->all());

            if ($success) {
                return redirect()
                    ->route('opcr.workflows.show', $workflow)
                    ->with('success', 'OPCR workflow committed successfully.');
            } else {
                return back()
                    ->withErrors(['error' => 'Failed to commit OPCR workflow.']);
            }
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to commit OPCR workflow: ' . $e->getMessage()]);
        }
    }

    /**
     * Submit the OPCR workflow for evaluation
     */
    public function submit(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorize('submit', $workflow);

        if (!$workflow->canBeSubmitted()) {
            abort(403, 'This workflow cannot be submitted in its current state.');
        }

        $request->validate([
            'accomplishments' => 'required|array',
            'accomplishments.*' => 'required|string',
        ]);

        try {
            $success = $this->workflowService->submitForEvaluation($workflow, $request->all());

            if ($success) {
                return redirect()
                    ->route('opcr.workflows.show', $workflow)
                    ->with('success', 'OPCR workflow submitted for evaluation.');
            } else {
                return back()
                    ->withErrors(['error' => 'Failed to submit OPCR workflow.']);
            }
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to submit OPCR workflow: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the assessment form
     */
    public function assess(OPCRWorkflow $workflow): View
    {
        $this->authorize('assess', $workflow);

        if (!$workflow->canBeAssessed()) {
            abort(403, 'This workflow cannot be assessed in its current state.');
        }

        $workflow->load([
            'office.activeMajorFinalOutputs.activeSuccessIndicators.ratings'
        ]);

        return view('admin.opcr.workflows.assess', compact('workflow'));
    }

    /**
     * Process the assessment
     */
    public function processAssessment(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorize('assess', $workflow);

        if (!$workflow->canBeAssessed()) {
            abort(403, 'This workflow cannot be assessed in its current state.');
        }

        $request->validate([
            'ratings' => 'required|array',
            'ratings.*.quantity_rating' => 'required|integer|min:1|max:5',
            'ratings.*.efficiency_rating' => 'required|integer|min:1|max:5',
            'ratings.*.timeliness_rating' => 'required|integer|min:1|max:5',
            'assessor_remarks' => 'nullable|string',
        ]);

        try {
            $success = $this->workflowService->assessWorkflow($workflow, $request->all());

            if ($success) {
                return redirect()
                    ->route('opcr.workflows.show', $workflow)
                    ->with('success', 'OPCR workflow assessed successfully.');
            } else {
                return back()
                    ->withErrors(['error' => 'Failed to assess OPCR workflow.']);
            }
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to assess OPCR workflow: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the approval form
     */
    public function approve(OPCRWorkflow $workflow): View
    {
        $this->authorize('approve', $workflow);

        if (!$workflow->canBeApproved()) {
            abort(403, 'This workflow cannot be approved in its current state.');
        }

        $workflow->load([
            'office.activeMajorFinalOutputs.activeSuccessIndicators.ratings',
            'assessedBy'
        ]);

        return view('admin.opcr.workflows.approve', compact('workflow'));
    }

    /**
     * Process the final approval
     */
    public function processApproval(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorize('approve', $workflow);

        if (!$workflow->canBeApproved()) {
            abort(403, 'This workflow cannot be approved in its current state.');
        }

        $request->validate([
            'overall_rating' => 'required|numeric|min:1|max:5',
            'approver_remarks' => 'nullable|string',
        ]);

        try {
            $success = $this->workflowService->approveWorkflow($workflow, $request->all());

            if ($success) {
                return redirect()
                    ->route('opcr.workflows.show', $workflow)
                    ->with('success', 'OPCR workflow approved successfully.');
            } else {
                return back()
                    ->withErrors(['error' => 'Failed to approve OPCR workflow.']);
            }
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to approve OPCR workflow: ' . $e->getMessage()]);
        }
    }

    /**
     * Return workflow for revision
     */
    public function return(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorize('return', $workflow);

        if (!$workflow->canBeReturned()) {
            abort(403, 'This workflow cannot be returned in its current state.');
        }

        $request->validate([
            'return_reason' => 'required|string|max:1000',
        ]);

        try {
            $success = $this->workflowService->returnWorkflow($workflow, $request->all());

            if ($success) {
                return redirect()
                    ->route('opcr.workflows.show', $workflow)
                    ->with('success', 'OPCR workflow returned for revision.');
            } else {
                return back()
                    ->withErrors(['error' => 'Failed to return OPCR workflow.']);
            }
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to return OPCR workflow: ' . $e->getMessage()]);
        }
    }

    /**
     * Get user workflow permissions
     */
    private function getUserWorkflowPermissions(OPCRWorkflow $workflow): array
    {
        $user = Auth::user();
        $permissions = [];

        // Basic permissions
        $permissions['can_view'] = $user->can('view', $workflow);
        $permissions['can_edit'] = $user->can('edit', $workflow) && $workflow->canBeEdited();
        $permissions['can_commit'] = $user->can('commit', $workflow) && $workflow->canBeCommitted();
        $permissions['can_submit'] = $user->can('submit', $workflow) && $workflow->canBeSubmitted();
        $permissions['can_assess'] = $user->can('assess', $workflow) && $workflow->canBeAssessed();
        $permissions['can_approve'] = $user->can('approve', $workflow) && $workflow->canBeApproved();
        $permissions['can_return'] = $user->can('return', $workflow) && $workflow->canBeReturned();

        return $permissions;
    }

    /**
     * Get workflow statistics API endpoint
     */
    public function statistics(Request $request): JsonResponse
    {
        $analytics = $this->workflowService->getWorkflowAnalytics();

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get user workflow history API endpoint
     */
    public function history(Request $request): JsonResponse
    {
        $history = $this->workflowService->getUserWorkflowHistory(Auth::user());

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Export workflow to PDF
     */
    public function export(OPCRWorkflow $workflow)
    {
        $this->authorize('view', $workflow);

        try {
            $pdf = $this->opcrManagementService->exportWorkflowToPDF($workflow);

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, "opcr_workflow_{$workflow->id}.pdf", [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to export OPCR workflow: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified OPCR workflow
     */
    public function destroy(OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorize('delete', $workflow);

        if ($workflow->isActive()) {
            abort(403, 'Active workflows cannot be deleted.');
        }

        try {
            $workflowId = $workflow->id;
            $workflow->delete();

            Activity::log('OPCR workflow deleted', [
                'opcr_workflow_id' => $workflowId,
                'deleted_by' => Auth::id(),
            ]);

            return redirect()
                ->route('opcr.workflows.index')
                ->with('success', 'OPCR workflow deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete OPCR workflow: ' . $e->getMessage()]);
        }
    }
}
