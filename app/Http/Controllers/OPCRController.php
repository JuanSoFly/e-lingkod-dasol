<?php

namespace App\Http\Controllers;

use App\Events\OpcrWorkflowApproved;
use App\Models\OPCRWorkflow;
use App\Models\PerformancePeriod;
use App\Models\Office;
use App\Models\OfficeAssignment;
use App\Models\Employee;
use App\Models\User;
use App\Models\PerformanceEvaluation;
use App\Models\PerformanceTarget;
use App\Services\OPCRManagementService;
use App\Services\OPCRWorkflowService;
use App\Services\DashboardAnalyticsService;
use App\Services\AuditTrailService;
use App\Services\OPCRPerformanceOptimizationService;
use App\Exports\OPCRArchiveExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\EvaluateOPCRRequest;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class OPCRController extends Controller
{
    private OPCRManagementService $opcrService;
    private OPCRWorkflowService $workflowService;
    private DashboardAnalyticsService $analyticsService;
    private AuditTrailService $auditTrailService;
    private OPCRPerformanceOptimizationService $optimizationService;

    public function __construct(
        OPCRManagementService $opcrService,
        OPCRWorkflowService $workflowService,
        DashboardAnalyticsService $analyticsService,
        AuditTrailService $auditTrailService,
        OPCRPerformanceOptimizationService $optimizationService
    ) {
        $this->opcrService = $opcrService;
        $this->workflowService = $workflowService;
        $this->analyticsService = $analyticsService;
        $this->auditTrailService = $auditTrailService;
        $this->optimizationService = $optimizationService;

        // Apply authentication and authorization middleware
        $this->middleware(['auth', 'permission:opcr.view']);
        $this->middleware('permission:opcr.create')->only(['create', 'store']);
        $this->middleware('permission:opcr.edit')->only(['edit', 'update']);
        $this->middleware('permission:opcr.delete')->only(['destroy']);
        $this->middleware('permission:opcr.assess')->only(['evaluate', 'submitEvaluation']);
        $this->middleware('permission:opcr.approve')->only(['review', 'finalApprove', 'reject']);
        $this->middleware('permission:opcr.planning_review')->only(['planningReview']);
        $this->middleware('permission:opcr.pmt_review')->only(['pmtReview']);
    }

    /**
     * Display OPCR dashboard
     */
    public function dashboard(Request $request): View
    {
        $user = Auth::user();
        $periodId = $request->get('period_id');
        $officeId = $request->get('office_id');

        $context = $this->getUserOPCRRoleWithOffice($user);

        if (!$officeId && $context['role'] === OfficeAssignment::ROLE_DEPARTMENT_HEAD) {
            $officeId = $context['primary_office_id'];
        }

        // Get user's accessible offices
        $accessibleOffices = $this->getUserAccessibleOffices($user, $context);
        $periods = PerformancePeriod::orderBy('start_date', 'desc')->get();

        // Get OPCR metrics and analytics
        $opcrData = $this->analyticsService->getPerformanceSummary($periodId, $officeId);

        // Get pending items for the user
        $pendingItems = $this->getPendingItemsForUser($user, $context);

        // For Super Admin and HR Admin, use analytics pending actions for overview
        $userRole = $context['role'];
        if (in_array($userRole, ['Super Admin', 'HR Admin'])) {
            $pendingItems['total_pending'] = $opcrData['pending_actions'];
        }

        return view('opcr.dashboard', [
            'opcrData' => $opcrData,
            'periods' => $periods,
            'offices' => $accessibleOffices,
            'selectedPeriod' => $periodId,
            'selectedOffice' => $officeId,
            'pendingItems' => $pendingItems,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Display a listing of OPCR workflows
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $context = $this->getUserOPCRRoleWithOffice($user);

        // Get filters with default values to ensure all expected keys exist
        $filters = array_merge([
            'period_id' => '',
            'office_id' => '',
            'workflow_state' => '',
            'search' => '',
            'sort_by' => 'updated_at',
            'sort_order' => 'desc'
        ], $request->only(['period_id', 'office_id', 'workflow_state', 'search', 'sort_by', 'sort_order']));

        // Apply office-based access control
        if (!$user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            if (!$context['has_cross_office_access'] && $context['all_office_ids']->isNotEmpty()) {
                $filters['accessible_office_ids'] = $context['all_office_ids']->toArray();
            } elseif (!$context['has_cross_office_access']) {
                $filters['accessible_office_ids'] = [-1];
            }
        }

        // Use optimized service for large datasets
        $workflows = $this->optimizationService->getOptimizedWorkflows($filters, 15);

        // Get filter options (with caching)
        $periods = Cache::remember('performance_periods_list', 60, function () {
            return PerformancePeriod::orderBy('start_date', 'desc')->get(['id', 'year', 'semester']);
        });

        $offices = $this->getUserAccessibleOffices($user, $context);

        return view('opcr.workflows.index', [
            'workflows' => $workflows,
            'periods' => $periods,
            'offices' => $offices,
            'filters' => $filters,
            'userRole' => $context['role'],
            'opcrContext' => $context,
        ]);
    }

    /**
     * Show the form for creating a new OPCR workflow
     */
    public function create(Request $request): View
    {
        $user = Auth::user();
        $employeeId = $request->get('employee_id');

        // Get user's accessible offices for Department Head role
        $accessibleOffices = $this->getUserManageableOffices($user);
        $periods = PerformancePeriod::where('is_active', true)
            ->orWhere('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderBy('start_date', 'desc')
            ->get();

        if ($accessibleOffices->isEmpty()) {
            return view('opcr.workflows.no-access', [
                'message' => 'You are not assigned as a Department Head for any office. Please contact your administrator.'
            ]);
        }

        return view('opcr.workflows.create', [
            'offices' => $accessibleOffices,
            'periods' => $periods,
            'employeeId' => $employeeId,
        ]);
    }

    /**
     * Store a newly created OPCR workflow
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'office_id' => 'required|exists:offices,id',
            'period_id' => 'required|exists:performance_periods,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'targets' => 'required|array|min:1',
            'targets.*.mfo_id' => 'required|exists:major_final_outputs,id',
            'targets.*.success_indicator_id' => 'required|exists:success_indicators,id',
            'targets.*.target_quality' => 'nullable|numeric|min:0',
            'targets.*.target_efficiency' => 'nullable|string|max:100',
            'targets.*.target_timeliness' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $validated['committed_by'] = $user->id;

            // Create OPCR workflow
            $workflow = $this->opcrService->createWorkflow($validated);

            // Create performance targets
            $this->opcrService->createWorkflowTargets($workflow, $validated['targets']);

            DB::commit();

            Log::info('OPCR workflow created', [
                'workflow_id' => $workflow->id,
                'user_id' => $user->id,
                'office_id' => $workflow->office_id,
                'period_id' => $workflow->period_id,
            ]);

            return redirect()
                ->route('opcr.workflows.show', $workflow)
                ->with('success', 'OPCR workflow created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create OPCR workflow', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'validated_data' => $validated,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create OPCR workflow: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified OPCR workflow
     */
    public function show(OPCRWorkflow $workflow): View
    {
        $this->authorizeWorkflowAccess($workflow);

        $workflow->load([
            'office',
            'period',
            'committedBy.employee',
            'targets.mfo',
            'targets.successIndicator',
            'targets.ratings',
            'documents.documentVersion.employeeDocument'
        ]);

        $userRole = $this->getUserOPCRRole(Auth::user(), $workflow);
        $canEdit = $this->canEditWorkflow($workflow, $userRole);
        $canEvaluate = $this->canEvaluateWorkflow($workflow, $userRole);
        $canApprove = $this->canApproveWorkflow($workflow, $userRole);

        return view('opcr.workflows.show', [
            'workflow' => $workflow,
            'userRole' => $userRole,
            'canEdit' => $canEdit,
            'canEvaluate' => $canEvaluate,
            'canApprove' => $canApprove,
            'stateHistory' => $this->workflowService->getStateHistory($workflow),
        ]);
    }

    /**
     * Show the form for editing the specified OPCR workflow
     */
    public function edit(OPCRWorkflow $workflow): View
    {
        // Debug logging
        \Log::info('OPCR Edit attempt', [
            'workflow_id' => $workflow->id,
            'workflow_state' => $workflow->workflow_state,
            'user_id' => Auth::id(),
            'user_roles' => Auth::user()->getRoleNames()->toArray(),
        ]);

        $this->authorizeWorkflowAccess($workflow);

        $userRole = $this->getUserOPCRRole(Auth::user(), $workflow);
        $canEdit = $this->canEditWorkflow($workflow, $userRole);

        \Log::info('OPCR Edit authorization check', [
            'user_role' => $userRole,
            'can_edit' => $canEdit,
            'workflow_state' => $workflow->workflow_state,
        ]);

        if (!$canEdit) {
            abort(403, 'You cannot edit this OPCR workflow in its current state.');
        }

        $workflow->load(['targets.mfo', 'targets.successIndicator']);

        return view('opcr.workflows.edit', [
            'workflow' => $workflow,
        ]);
    }

    /**
     * Update the specified OPCR workflow
     */
    public function update(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorizeWorkflowAccess($workflow);

        if (!$this->canEditWorkflow($workflow, $this->getUserOPCRRole(Auth::user(), $workflow))) {
            abort(403, 'You cannot edit this OPCR workflow in its current state.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'targets' => 'required|array|min:1',
            'targets.*.id' => 'nullable|exists:performance_targets,id',
            'targets.*.mfo_id' => 'required|exists:major_final_outputs,id',
            'targets.*.success_indicator_id' => 'required|exists:success_indicators,id',
            'targets.*.target_quality' => 'nullable|numeric|min:0',
            'targets.*.target_efficiency' => 'nullable|string|max:100',
            'targets.*.target_timeliness' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Update workflow
            $workflow->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
            ]);

            // Update targets
            $this->opcrService->updateWorkflowTargets($workflow, $validated['targets']);

            DB::commit();

            Log::info('OPCR workflow updated', [
                'workflow_id' => $workflow->id,
                'user_id' => Auth::id(),
            ]);

            return redirect()
                ->route('opcr.workflows.show', $workflow)
                ->with('success', 'OPCR workflow updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update OPCR workflow', [
                'workflow_id' => $workflow->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update OPCR workflow: ' . $e->getMessage());
        }
    }

    /**
     * Submit OPCR workflow for evaluation
     */
    public function submit(OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorizeWorkflowAccess($workflow);

        if (!$this->canSubmitWorkflow($workflow, $this->getUserOPCRRole(Auth::user(), $workflow))) {
            abort(403, 'You cannot submit this OPCR workflow in its current state.');
        }

        try {
            $period = $workflow->period ?? $workflow->period()->first();
            if ($period && $period->planning_deadline && now()->greaterThan($period->planning_deadline)) {
                return back()->with('error', 'Planning submission deadline has passed for this period.');
            }

            $this->workflowService->transitionState($workflow, OPCRWorkflow::STATE_PLANNING_REVIEW, [
                'committed_by' => Auth::id(),
                'committed_at' => now(),
            ]);

            Log::info('OPCR workflow submitted', [
                'workflow_id' => $workflow->id,
                'user_id' => Auth::id(),
                'new_state' => OPCRWorkflow::STATE_PLANNING_REVIEW,
            ]);

            return redirect()
                ->route('opcr.workflows.show', $workflow)
                ->with('success', 'OPCR workflow submitted to Planning for review.');

        } catch (\Exception $e) {
            Log::error('Failed to submit OPCR workflow', [
                'workflow_id' => $workflow->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to submit OPCR workflow: ' . $e->getMessage());
        }
    }

    /**
     * Planning Office review (approve/return)
     */
    public function planningReview(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorizeWorkflowAccess($workflow);

        if ($workflow->workflow_state !== OPCRWorkflow::STATE_PLANNING_REVIEW) {
            abort(403, 'Planning review is only allowed while in Planning Review state.');
        }

        if (!$request->user()->can('opcr.planning_review')) {
            abort(403, 'You do not have permission to perform Planning review.');
        }

        $validated = $request->validate([
            'action' => 'required|string|in:approve,return',
            'remarks' => 'nullable|string|max:2000',
        ]);

        try {
            $period = $workflow->period ?? $workflow->period()->first();
            if ($period && $period->planning_deadline && now()->greaterThan($period->planning_deadline) && $validated['action'] === 'approve') {
                return back()->with('error', 'Planning deadline has passed; cannot approve without reopening the period.');
            }

            DB::beginTransaction();

            if ($validated['action'] === 'approve') {
                $this->workflowService->transitionState($workflow, OPCRWorkflow::STATE_PMT_REVIEW, [
                    'planning_reviewer_id' => $request->user()->id,
                    'planning_reviewed_at' => now(),
                    'planning_remarks' => $validated['remarks'] ?? null,
                ]);

                $message = 'Planning review completed. Forwarded to PMT.';
            } else {
                $this->workflowService->transitionState($workflow, OPCRWorkflow::STATE_RETURNED, [
                    'return_reason' => $validated['remarks'] ?? 'Returned by Planning',
                    'return_source' => 'planning',
                ]);
                $message = 'OPCR returned by Planning for revision.';
            }

            DB::commit();

            return redirect()
                ->route('opcr.workflows.show', $workflow)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Planning review failed', [
                'workflow_id' => $workflow->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Planning review failed: ' . $e->getMessage());
        }
    }

    /**
     * PMT review (approve/return)
     */
    public function pmtReview(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorizeWorkflowAccess($workflow);

        if ($workflow->workflow_state !== OPCRWorkflow::STATE_PMT_REVIEW) {
            abort(403, 'PMT review is only allowed while in PMT Review state.');
        }

        if (!$request->user()->can('opcr.pmt_review')) {
            abort(403, 'You do not have permission to perform PMT review.');
        }

        $validated = $request->validate([
            'action' => 'required|string|in:approve,return',
            'remarks' => 'nullable|string|max:2000',
        ]);

        try {
            $period = $workflow->period ?? $workflow->period()->first();
            if ($period && $period->pmt_deadline && now()->greaterThan($period->pmt_deadline) && $validated['action'] === 'approve') {
                return back()->with('error', 'PMT deadline has passed; cannot approve without reopening the period.');
            }

            DB::beginTransaction();

            if ($validated['action'] === 'approve') {
                $this->workflowService->transitionState($workflow, OPCRWorkflow::STATE_COMMITTED, [
                    'pmt_recommender_id' => $request->user()->id,
                    'pmt_recommended_at' => now(),
                    'pmt_remarks' => $validated['remarks'] ?? null,
                ]);

                $message = 'PMT endorsed the OPCR. It is now committed for assessment.';
            } else {
                $this->workflowService->transitionState($workflow, OPCRWorkflow::STATE_RETURNED, [
                    'return_reason' => $validated['remarks'] ?? 'Returned by PMT',
                    'return_source' => 'pmt',
                ]);
                $message = 'OPCR returned by PMT for revision.';
            }

            DB::commit();

            return redirect()
                ->route('opcr.workflows.show', $workflow)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('PMT review failed', [
                'workflow_id' => $workflow->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'PMT review failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified OPCR workflow
     */
    public function destroy(OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorizeWorkflowAccess($workflow);

        if (!$this->canDeleteWorkflow($workflow, $this->getUserOPCRRole(Auth::user(), $workflow))) {
            abort(403, 'You cannot delete this OPCR workflow in its current state.');
        }

        try {
            DB::beginTransaction();

            $workflowId = $workflow->id;
            $workflow->delete();

            DB::commit();

            Log::info('OPCR workflow deleted', [
                'workflow_id' => $workflowId,
                'user_id' => Auth::id(),
            ]);

            return redirect()
                ->route('opcr.workflows.index')
                ->with('success', 'OPCR workflow deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete OPCR workflow', [
                'workflow_id' => $workflow->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to delete OPCR workflow: ' . $e->getMessage());
        }
    }

    /**
     * Get user's accessible offices based on OPCR role context.
     */
    private function getUserAccessibleOffices($user, ?array $context = null): \Illuminate\Database\Eloquent\Collection
    {
        $context = $context ?? $this->getUserOPCRRoleWithOffice($user);

        if (in_array($context['role'], ['Super Admin', 'HR Admin'])) {
            return Office::where('is_active', true)->orderBy('name')->get();
        }

        if ($context['has_cross_office_access']) {
            return Office::where('is_active', true)->orderBy('name')->get();
        }

        if ($context['all_office_ids']->isEmpty()) {
            return Office::whereKey([])->get();
        }

        return Office::whereIn('id', $context['all_office_ids'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get user's manageable offices (Department Head role)
     */
    private function getUserManageableOffices($user, ?array $context = null): \Illuminate\Database\Eloquent\Collection
    {
        $context = $context ?? $this->getUserOPCRRoleWithOffice($user);

        if (in_array($context['role'], ['Super Admin', 'HR Admin'])) {
            return Office::where('is_active', true)->orderBy('name')->get();
        }

        if ($context['department_head_office_ids']->isEmpty()) {
            return Office::whereKey([])->get();
        }

        return Office::whereIn('id', $context['department_head_office_ids'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Unified OPCR role context helper.
     */
    private function getUserOPCRRoleWithOffice($user, ?OPCRWorkflow $workflow = null): array
    {
        $context = [
            'role' => 'Employee',
            'department_head_office_ids' => collect(),
            'assessor_office_ids' => collect(),
            'final_approver_office_ids' => collect(),
            'all_office_ids' => collect(),
            'primary_office_id' => null,
            'has_cross_office_access' => false,
        ];

        if (!$user) {
            return $context;
        }

        if ($user->hasRole('Super Admin')) {
            $context['role'] = 'Super Admin';
            $context['has_cross_office_access'] = true;
            return $context;
        }

        if ($user->hasRole('HR Admin')) {
            $context['role'] = 'HR Admin';
            $context['has_cross_office_access'] = true;
            return $context;
        }

        if ($user->can('opcr.planning_review')) {
            $context['role'] = 'Planning Reviewer';
            $context['has_cross_office_access'] = true;
            return $context;
        }

        if ($user->can('opcr.pmt_review')) {
            $context['role'] = 'PMT Reviewer';
            $context['has_cross_office_access'] = true;
            return $context;
        }

        $assignments = $user->officeAssignments()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->whereIn('role', [
                OfficeAssignment::ROLE_DEPARTMENT_HEAD,
                OfficeAssignment::ROLE_ASSESSOR,
                OfficeAssignment::ROLE_FINAL_APPROVER,
            ])
            ->get();

        if ($assignments->isEmpty()) {
            return $context;
        }

        $context['department_head_office_ids'] = $assignments
            ->where('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD)
            ->pluck('office_id')
            ->unique()
            ->values();

        $context['assessor_office_ids'] = $assignments
            ->where('role', OfficeAssignment::ROLE_ASSESSOR)
            ->pluck('office_id')
            ->unique()
            ->values();

        $context['final_approver_office_ids'] = $assignments
            ->where('role', OfficeAssignment::ROLE_FINAL_APPROVER)
            ->pluck('office_id')
            ->unique()
            ->values();

        $context['all_office_ids'] = $assignments->pluck('office_id')->unique()->values();
        $context['primary_office_id'] = $context['department_head_office_ids']->first()
            ?? $context['all_office_ids']->first();

        $context['has_cross_office_access'] = $context['assessor_office_ids']->isNotEmpty()
            || $context['final_approver_office_ids']->isNotEmpty();

        if ($workflow && $context['department_head_office_ids']->contains($workflow->office_id)) {
            $context['role'] = OfficeAssignment::ROLE_DEPARTMENT_HEAD;
            return $context;
        }

        if ($workflow && $context['assessor_office_ids']->isNotEmpty()) {
            $context['role'] = OfficeAssignment::ROLE_ASSESSOR;
            return $context;
        }

        if ($workflow && $context['final_approver_office_ids']->isNotEmpty()) {
            $context['role'] = OfficeAssignment::ROLE_FINAL_APPROVER;
            return $context;
        }

        if ($context['department_head_office_ids']->isNotEmpty()) {
            $context['role'] = OfficeAssignment::ROLE_DEPARTMENT_HEAD;
            return $context;
        }

        if ($context['assessor_office_ids']->isNotEmpty()) {
            $context['role'] = OfficeAssignment::ROLE_ASSESSOR;
            return $context;
        }

        if ($context['final_approver_office_ids']->isNotEmpty()) {
            $context['role'] = OfficeAssignment::ROLE_FINAL_APPROVER;
            return $context;
        }

        return $context;
    }

    /**
     * Get user's OPCR role for a specific workflow
     */
    private function getUserOPCRRole($user, ?OPCRWorkflow $workflow = null): string
    {
        return $this->getUserOPCRRoleWithOffice($user, $workflow)['role'];
    }

    /**
     * Authorize workflow access
     */
    private function authorizeWorkflowAccess(OPCRWorkflow $workflow): void
    {
        $user = Auth::user();
        $context = $this->getUserOPCRRoleWithOffice($user, $workflow);

        if (in_array($context['role'], ['Super Admin', 'HR Admin'])) {
            return;
        }

        if ($user->can('opcr.planning_review') || $user->can('opcr.pmt_review')) {
            return;
        }

        if (in_array($context['role'], [OfficeAssignment::ROLE_ASSESSOR, OfficeAssignment::ROLE_FINAL_APPROVER])) {
            return;
        }

        if ($context['role'] === OfficeAssignment::ROLE_DEPARTMENT_HEAD &&
            $context['department_head_office_ids']->contains($workflow->office_id)) {
            return;
        }

        abort(403, 'You do not have permission to access this OPCR workflow.');
    }

    /**
     * Check if user can edit workflow
     */
    private function canEditWorkflow(OPCRWorkflow $workflow, string $userRole): bool
    {
        $editableStates = ['draft', 'returned'];

        if (!in_array($workflow->workflow_state, $editableStates)) {
            return false;
        }

        return in_array($userRole, ['Super Admin', 'HR Admin', 'Department Head']);
    }

    /**
     * Check if user can submit workflow
     */
    private function canSubmitWorkflow(OPCRWorkflow $workflow, string $userRole): bool
    {
        $submittableStates = ['draft', 'returned'];

        if (!in_array($workflow->workflow_state, $submittableStates)) {
            return false;
        }

        return in_array($userRole, ['Super Admin', 'HR Admin', 'Department Head']);
    }

    /**
     * Check if user can evaluate workflow
     */
    private function canEvaluateWorkflow(OPCRWorkflow $workflow, string $userRole): bool
    {
        $evaluatableStates = ['committed', 'in_progress', 'evaluation'];

        if (!in_array($workflow->workflow_state, $evaluatableStates)) {
            return false;
        }

        return in_array($userRole, ['Super Admin', 'HR Admin', 'Assessor']);
    }

    /**
     * Check if user can approve workflow
     */
    private function canApproveWorkflow(OPCRWorkflow $workflow, string $userRole): bool
    {
        $approvableStates = ['evaluation', 'final_approval'];

        if (!in_array($workflow->workflow_state, $approvableStates)) {
            return false;
        }

        return in_array($userRole, ['Super Admin', 'HR Admin', 'Final Approver']);
    }

    /**
     * Check if user can delete workflow
     */
    private function canDeleteWorkflow(OPCRWorkflow $workflow, string $userRole): bool
    {
        $deletableStates = ['draft'];

        if ($workflow->workflow_state !== $deletableStates[0]) {
            return false;
        }

        return in_array($userRole, ['Super Admin', 'HR Admin', 'Department Head']);
    }

    /**
     * Check if evaluation is complete for a workflow
     */
    private function isEvaluationComplete(OPCRWorkflow $workflow): bool
    {
        // Load workflow with targets and their success indicators
        $workflow->load(['targets.successIndicator']);

        $totalTargets = $workflow->targets->count();

        if ($totalTargets === 0) {
            return false;
        }

        $completedTargets = 0;

        foreach ($workflow->targets as $target) {
            $successIndicator = $target->successIndicator;

            // Check if all required evaluation data is present
            if ($successIndicator &&
                $successIndicator->accomplished_quality !== null &&
                $successIndicator->accomplished_efficiency !== null &&
                $successIndicator->accomplished_timeliness !== null &&
                $successIndicator->rating_quality !== null &&
                $successIndicator->rating_efficiency !== null &&
                $successIndicator->rating_timeliness !== null &&
                $successIndicator->average_rating !== null) {
                $completedTargets++;
            }
        }

        // Evaluation is complete if all targets have been evaluated
        return $completedTargets === $totalTargets;
    }

    /**
     * Progress workflow state after successful assessor evaluation
     */
    private function advanceWorkflowAfterAssessment(OPCRWorkflow $workflow, array $workflowData, $user): string
    {
        $workflow->refresh();
        $currentState = $workflow->workflow_state;

        if ($currentState === OPCRWorkflow::STATE_COMMITTED) {
            $this->workflowService->transitionState($workflow, OPCRWorkflow::STATE_IN_PROGRESS, [
                'submitted_by' => $user->id,
                'submitted_at' => now(),
            ]);

            $workflow->refresh();
            $currentState = $workflow->workflow_state;
        }

        if ($currentState === OPCRWorkflow::STATE_IN_PROGRESS) {
            $this->workflowService->transitionState($workflow, OPCRWorkflow::STATE_EVALUATION, $workflowData);

            Log::info('OPCR workflow moved to evaluation stage', [
                'workflow_id' => $workflow->id,
                'user_id' => $user->id,
                'new_state' => OPCRWorkflow::STATE_EVALUATION,
            ]);

            return 'OPCR evaluation submitted successfully. Forwarded to Final Approver.';
        }

        if ($currentState === OPCRWorkflow::STATE_EVALUATION) {
            // Check if evaluation is complete
            if ($this->isEvaluationComplete($workflow)) {
                // Advance to final approval
                $this->workflowService->transitionState($workflow, OPCRWorkflow::STATE_FINAL_APPROVAL, $workflowData);

                Log::info('OPCR evaluation complete, advanced to final approval', [
                    'workflow_id' => $workflow->id,
                    'user_id' => $user->id,
                    'assessor_id' => $user->id,
                    'new_state' => OPCRWorkflow::STATE_FINAL_APPROVAL,
                ]);

                // TODO: Trigger notification to final approver
                // $this->notifyFinalApprover($workflow, $user);

                return 'OPCR evaluation complete. Forwarded to Final Approver for final approval.';
            } else {
                // Update workflow but keep in evaluation state
                $workflow->update($workflowData);

                Log::info('OPCR evaluation updated but not yet complete', [
                    'workflow_id' => $workflow->id,
                    'user_id' => $user->id,
                ]);

                return 'OPCR evaluation updated. Complete all required evaluations to proceed to final approval.';
            }
        }

        $workflow->update($workflowData);

        Log::info('OPCR evaluation updated without state transition', [
            'workflow_id' => $workflow->id,
            'user_id' => $user->id,
            'workflow_state' => $workflow->workflow_state,
        ]);

        return 'OPCR evaluation updated successfully.';
    }

    /**
     * Compute performance metrics for a target based on accomplished quality
     */
    private function computeTargetPerformanceMetrics(PerformanceTarget $target, float $accomplishedQuality): array
    {
        $targetQuality = $target->target_quality;

        if (($targetQuality === null || (float) $targetQuality === 0.0) && $target->relationLoaded('successIndicator')) {
            $targetQuality = $target->successIndicator?->target_quality;
        } elseif ($targetQuality === null || (float) $targetQuality === 0.0) {
            $targetQuality = $target->successIndicator()->value('target_quality');
        }

        if ($targetQuality === null || (float) $targetQuality === 0.0) {
            return [
                'performance_percentage' => null,
                'is_target_met' => false,
            ];
        }

        $performancePercentage = round(($accomplishedQuality / (float) $targetQuality) * 100, 2);

        return [
            'performance_percentage' => $performancePercentage,
            'is_target_met' => $performancePercentage >= 100,
        ];
    }

    /**
     * Show the evaluation form for an OPCR workflow
     */
    public function evaluate(OPCRWorkflow $workflow): View
    {
        $this->authorizeWorkflowAccess($workflow);

        if (!$this->canEvaluateWorkflow($workflow, $this->getUserOPCRRole(Auth::user(), $workflow))) {
            abort(403, 'You cannot evaluate this OPCR workflow in its current state.');
        }

        // Load workflow with all necessary relationships
        $workflow->load([
            'office',
            'period',
            'committedBy.employee',
            'targets.mfo',
            'targets.successIndicator',
            'targets.ratings'
        ]);

        // Handle edge case: workflow with no targets
        if ($workflow->targets->isEmpty()) {
            return view('opcr.workflows.evaluate-empty', [
                'workflow' => $workflow,
                'userRole' => $this->getUserOPCRRole(Auth::user(), $workflow),
                'canReturn' => in_array($this->getUserOPCRRole(Auth::user(), $workflow), ['Super Admin', 'HR Admin', 'Assessor']),
            ]);
        }

        return view('opcr.workflows.evaluate', [
            'workflow' => $workflow,
            'userRole' => $this->getUserOPCRRole(Auth::user(), $workflow),
            'canReturn' => in_array($this->getUserOPCRRole(Auth::user(), $workflow), ['Super Admin', 'HR Admin', 'Assessor']),
        ]);
    }

    /**
     * Show the final review page for an OPCR workflow
     */
    public function review(OPCRWorkflow $workflow): View
    {
        $this->authorizeWorkflowAccess($workflow);

        if (!$this->canApproveWorkflow($workflow, $this->getUserOPCRRole(Auth::user(), $workflow))) {
            abort(403, 'You cannot review this OPCR workflow in its current state.');
        }

        // Load workflow with all necessary relationships for final review
        $workflow->load([
            'office',
            'period',
            'committedBy.employee',
            'assessedBy.employee',
            'targets.mfo',
            'targets.successIndicator',
            'targets.ratings'
        ]);

        // Handle edge case: workflow with no targets - use main view with empty state
        // if ($workflow->targets->isEmpty()) {
        //     return view('opcr.workflows.review-empty', [
        //         'workflow' => $workflow,
        //         'userRole' => $this->getUserOPCRRole(Auth::user(), $workflow),
        //     ]);
        // }

        // Calculate performance summary
        $performanceSummary = $this->calculatePerformanceSummary($workflow);

        return view('opcr.workflows.review', [
            'workflow' => $workflow,
            'userRole' => $this->getUserOPCRRole(Auth::user(), $workflow),
            'performanceSummary' => $performanceSummary,
            'calculateAdjectivalRating' => function ($rating) {
                return $this->getRatingCategory($rating);
            },
        ]);
    }

    /**
     * Submit evaluation for an OPCR workflow
     */
    public function submitEvaluation(EvaluateOPCRRequest $request, OPCRWorkflow $workflow): RedirectResponse
    {
        Log::info('submitEvaluation method called', [
            'workflow_id' => $workflow->id,
            'user_id' => Auth::id(),
            'request_data' => $request->all(),
        ]);

        $this->authorizeWorkflowAccess($workflow);

        if (!$this->canEvaluateWorkflow($workflow, $this->getUserOPCRRole(Auth::user(), $workflow))) {
            abort(403, 'You cannot evaluate this OPCR workflow in its current state.');
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $validated = $request->validated();
            $saveAsDraft = $request->boolean('save_as_draft');
            $action = $saveAsDraft ? 'draft' : ($validated['action'] ?? null);

            // Process each evaluation
            foreach ($validated['evaluations'] as $targetKey => $evaluationData) {
                $targetId = $evaluationData['target_id'] ?? (is_numeric($targetKey) ? (int)$targetKey : null);

                Log::info('Processing OPCR evaluation target', [
                    'workflow_id' => $workflow->id,
                    'target_key' => $targetKey,
                    'target_id' => $targetId,
                    'evaluation_data' => $evaluationData,
                ]);

                if (empty($targetId)) {
                    Log::error('Missing target ID in evaluation data', [
                        'workflow_id' => $workflow->id,
                        'target_key' => $targetKey,
                        'evaluation_data' => $evaluationData,
                    ]);
                    throw ValidationException::withMessages([
                        "evaluations.{$targetKey}.target_id" => 'Target reference is missing for one of the evaluation entries.',
                    ]);
                }

                $target = $workflow->targets()->find($targetId);

                if (!$target) {
                    Log::error('Target not found for workflow', [
                        'workflow_id' => $workflow->id,
                        'target_id' => $targetId,
                        'target_key' => $targetKey,
                    ]);
                    throw ValidationException::withMessages([
                        "evaluations.{$targetKey}.target_id" => 'Selected target could not be located for this workflow.',
                    ]);
                }

                $target->loadMissing('successIndicator');
                $successIndicator = $target->successIndicator;

                if (!$successIndicator) {
                    Log::error('Success indicator not found for target', [
                        'workflow_id' => $workflow->id,
                        'target_id' => $targetId,
                        'target_key' => $targetKey,
                    ]);
                    throw ValidationException::withMessages([
                        "evaluations.{$targetKey}.target_id" => 'Success indicator for the selected target is no longer available.',
                    ]);
                }

                $accomplishedQuality = (float) $evaluationData['accomplished_quality'];
                $accomplishedEfficiency = trim($evaluationData['accomplished_efficiency']);
                $accomplishedTimeliness = trim($evaluationData['accomplished_timeliness']);
                $remarks = trim($evaluationData['remarks'] ?? '') ?: null;

                Log::info('Updating success indicator', [
                    'workflow_id' => $workflow->id,
                    'target_id' => $target->id,
                    'success_indicator_id' => $successIndicator->id,
                    'accomplished_quality' => $accomplishedQuality,
                    'accomplished_efficiency' => $accomplishedEfficiency,
                    'accomplished_timeliness' => $accomplishedTimeliness,
                    'evaluation_data' => $evaluationData,
                ]);

                Log::info('About to call updateAccomplishments', [
                    'success_indicator_id' => $successIndicator->id,
                    'accomplishments_array' => [
                        'accomplished_quality' => $accomplishedQuality,
                        'accomplished_efficiency' => $accomplishedEfficiency,
                        'accomplished_timeliness' => $accomplishedTimeliness,
                        'remarks' => $remarks,
                    ],
                ]);

                $updatedAccomplishments = $successIndicator->updateAccomplishments([
                    'accomplished_quality' => $accomplishedQuality,
                    'accomplished_efficiency' => $accomplishedEfficiency,
                    'accomplished_timeliness' => $accomplishedTimeliness,
                    'remarks' => $remarks,
                ]);

                Log::info('updateAccomplishments result', [
                    'result' => $updatedAccomplishments,
                    'success_indicator_id' => $successIndicator->id,
                ]);

                $updatedRatings = $successIndicator->updateQETRatings([
                    'rating_quality' => (int) $evaluationData['quality_rating'],
                    'rating_efficiency' => (int) $evaluationData['efficiency_rating'],
                    'rating_timeliness' => (int) $evaluationData['timeliness_rating'],
                    'remarks' => $remarks,
                ]);

                Log::info('Success indicator updates completed', [
                    'workflow_id' => $workflow->id,
                    'target_id' => $target->id,
                    'success_indicator_id' => $successIndicator->id,
                    'updated_accomplishments' => $updatedAccomplishments,
                    'updated_ratings' => $updatedRatings,
                ]);

                if (!$updatedAccomplishments || !$updatedRatings) {
                    Log::error('Failed to update success indicator', [
                        'workflow_id' => $workflow->id,
                        'target_id' => $target->id,
                        'success_indicator_id' => $successIndicator->id,
                        'updated_accomplishments' => $updatedAccomplishments,
                        'updated_ratings' => $updatedRatings,
                    ]);
                    throw new \RuntimeException("Failed to persist success indicator data for target ID {$target->id}.");
                }

                $successIndicator->refresh();

                Log::info('Success indicator refreshed', [
                    'workflow_id' => $workflow->id,
                    'target_id' => $target->id,
                    'success_indicator_id' => $successIndicator->id,
                    'current_accomplished_quality' => $successIndicator->accomplished_quality,
                    'current_accomplished_efficiency' => $successIndicator->accomplished_efficiency,
                    'current_accomplished_timeliness' => $successIndicator->accomplished_timeliness,
                ]);

                $metrics = $this->computeTargetPerformanceMetrics($target, $accomplishedQuality);

                $averageRating = round(
                    (
                        $evaluationData['quality_rating'] +
                        $evaluationData['efficiency_rating'] +
                        $evaluationData['timeliness_rating']
                    ) / 3,
                    2
                );

                Log::info('Updating performance target', [
                    'workflow_id' => $workflow->id,
                    'target_id' => $target->id,
                    'accomplished_quality' => $accomplishedQuality,
                    'accomplished_efficiency' => $accomplishedEfficiency,
                    'accomplished_timeliness' => $accomplishedTimeliness,
                    'performance_percentage' => $metrics['performance_percentage'],
                    'is_target_met' => $metrics['is_target_met'],
                    'updated_by' => $user->id,
                ]);

                $target->forceFill([
                    'accomplished_quality' => $accomplishedQuality,
                    'accomplished_efficiency' => $accomplishedEfficiency,
                    'accomplished_timeliness' => $accomplishedTimeliness,
                    'performance_percentage' => $metrics['performance_percentage'],
                    'is_target_met' => $metrics['is_target_met'],
                    'updated_by' => $user->id,
                ]);

                if (!$target->save()) {
                    Log::error('Failed to save performance target', [
                        'workflow_id' => $workflow->id,
                        'target_id' => $target->id,
                        'target_data' => $target->toArray(),
                    ]);
                    throw new \RuntimeException("Failed to persist performance target data for ID {$target->id}.");
                }

                Log::info('Performance target updated successfully', [
                    'workflow_id' => $workflow->id,
                    'target_id' => $target->id,
                    'current_accomplished_quality' => $target->accomplished_quality,
                    'current_accomplished_efficiency' => $target->accomplished_efficiency,
                    'current_accomplished_timeliness' => $target->accomplished_timeliness,
                    'performance_percentage' => $target->performance_percentage,
                    'is_target_met' => $target->is_target_met,
                ]);

                $ratingPayload = [
                    'self_rating' => $averageRating,
                    'supervisor_rating' => $averageRating,
                    'final_rating' => $averageRating,
                    'remarks' => $evaluationData['remarks'] ?? null,
                    'rating_quality' => (int) $evaluationData['quality_rating'],
                    'rating_efficiency' => (int) $evaluationData['efficiency_rating'],
                    'rating_timeliness' => (int) $evaluationData['timeliness_rating'],
                    'average_qet_rating' => $averageRating,
                    'adjectival_rating' => $this->getRatingCategory($averageRating),
                    'accomplished_quality' => $accomplishedQuality,
                    'accomplished_efficiency' => $accomplishedEfficiency,
                    'accomplished_timeliness' => $accomplishedTimeliness,
                    'assessed_by' => $user->id,
                    'assessed_at' => now(),
                    'office_id' => $workflow->office_id,
                    'updated_by' => $user->id,
                ];

                $rating = $target->ratings()->updateOrCreate(
                    ['target_id' => $target->id],
                    $ratingPayload
                );

                if (!$rating->created_by) {
                    $rating->created_by = $user->id;
                    $rating->save();
                }

                Log::info('OPCR evaluation target persisted', [
                    'workflow_id' => $workflow->id,
                    'target_id' => $target->id,
                    'success_indicator_id' => $successIndicator->id,
                    'performance_percentage' => $metrics['performance_percentage'],
                    'is_target_met' => $metrics['is_target_met'],
                    'action' => $action,
                    'draft' => $saveAsDraft,
                    'user_id' => $user->id,
                ]);
            }

            // Create or update PerformanceEvaluation record when submitting evaluation
            if (!$saveAsDraft && $action === 'submit') {
                $this->createOrUpdatePerformanceEvaluation($workflow, $user, $validated);
            }

            // Update workflow based on action
            $workflowData = [
                'assessor_remarks' => $validated['overall_remarks'] ?? null,
                'recommendations' => $validated['recommendations'] ?? null,
            ];

            if (!$saveAsDraft) {
                $workflowData['assessed_by'] = $user->id;
                $workflowData['assessed_at'] = now();
            }

            if ($saveAsDraft) {
                $workflow->update($workflowData);

                Log::info('OPCR evaluation draft saved', [
                    'workflow_id' => $workflow->id,
                    'user_id' => $user->id,
                    'targets_evaluated' => count($validated['evaluations']),
                ]);

                $message = 'OPCR evaluation draft saved successfully.';
            } elseif ($action === 'submit') {
                $message = $this->advanceWorkflowAfterAssessment($workflow, $workflowData, $user);
            } else {
                // Return for revision
                $workflowData['return_reason'] = $validated['return_reason'] ?? null;
                $workflowData['returned_by'] = $user->id;
                $workflowData['returned_at'] = now();

                $this->workflowService->transitionState($workflow, OPCRWorkflow::STATE_RETURNED, $workflowData);

                Log::info('OPCR workflow returned for revision', [
                    'workflow_id' => $workflow->id,
                    'user_id' => $user->id,
                    'new_state' => 'returned',
                    'return_reason' => $validated['return_reason'] ?? null,
                ]);

                $message = 'OPCR returned to Department Head for revision.';
            }

            // Create audit trail
            $this->auditTrailService->log(
                'opcr_evaluation',
                $workflow->id,
                match (true) {
                    $saveAsDraft => 'OPCR evaluation draft saved',
                    $action === 'submit' => 'OPCR evaluation submitted',
                    default => 'OPCR returned for revision',
                },
                [
                    'workflow_id' => $workflow->id,
                    'action' => $action,
                    'is_draft' => $saveAsDraft,
                    'evaluations_count' => count($validated['evaluations']),
                    'user_id' => $user->id,
                ]
            );

            DB::commit();

            return redirect()
                ->route('opcr.workflows.show', $workflow)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to submit OPCR evaluation', [
                'workflow_id' => $workflow->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'validated_data' => $validated ?? [],
                'action' => $action ?? null,
                'is_draft' => $saveAsDraft ?? null,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to submit OPCR evaluation: ' . $e->getMessage());
        }
    }

    /**
     * Return OPCR workflow for revision
     */
    public function returnForRevision(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorizeWorkflowAccess($workflow);

        if (!$this->canEvaluateWorkflow($workflow, $this->getUserOPCRRole(Auth::user(), $workflow))) {
            abort(403, 'You cannot return this OPCR workflow for revision.');
        }

        $validated = $request->validate([
            'return_reason' => 'required|string|min:10|max:1000',
            'assessor_remarks' => 'nullable|string|max:2000',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $workflowData = [
                'return_reason' => $validated['return_reason'],
                'assessor_remarks' => $validated['assessor_remarks'] ?? null,
                'returned_by' => $user->id,
                'returned_at' => now(),
            ];

            $this->workflowService->transitionState($workflow, OPCRWorkflow::STATE_RETURNED, $workflowData);

            // Create audit trail
            $this->auditTrailService->log(
                'opcr_returned',
                $workflow->id,
                'OPCR returned for revision',
                [
                    'workflow_id' => $workflow->id,
                    'return_reason' => $validated['return_reason'],
                    'user_id' => $user->id,
                ]
            );

            Log::info('OPCR workflow returned for revision', [
                'workflow_id' => $workflow->id,
                'user_id' => $user->id,
                'return_reason' => $validated['return_reason'],
            ]);

            DB::commit();

            return redirect()
                ->route('opcr.workflows.show', $workflow)
                ->with('success', 'OPCR returned to Department Head for revision.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to return OPCR workflow for revision', [
                'workflow_id' => $workflow->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to return OPCR for revision: ' . $e->getMessage());
        }
    }

    /**
     * Final approval handling for OPCR workflow
     * Handles approve, return, and reject actions based on approval_status
     */
    public function finalApprove(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorizeWorkflowAccess($workflow);
        if (!$this->canApproveWorkflow($workflow, $this->getUserOPCRRole(Auth::user(), $workflow))) {
            abort(403, 'You cannot approve this OPCR workflow in its current state.');
        }

        $validated = $request->validate([
            'final_remarks' => 'nullable|string|max:2000',
            'recommendations_next_period' => 'nullable|string|max:2000',
            'final_rating' => 'nullable|integer|min:1|max:5',
            'performance_level' => 'nullable|string|in:exceeds_expectations,meets_expectations,needs_improvement,unsatisfactory',
            'approval_status' => 'required|string|in:approved,returned,rejected',
            'hrmo_override' => 'sometimes|boolean',
            'hrmo_override_reason' => 'required_if:hrmo_override,true|string|max:2000',
        ]);

        try {
            $period = $workflow->period ?? $workflow->period()->first();
            if ($period && $period->lce_deadline && now()->greaterThan($period->lce_deadline) && $validated['approval_status'] === 'approved') {
                return back()->with('error', 'LCE approval deadline has passed for this period.');
            }

            DB::beginTransaction();
            $user = Auth::user();

            // Handle different approval statuses
            switch ($validated['approval_status']) {
                case 'approved':
                    return $this->handleFinalApproval($workflow, $validated, $user);

                case 'returned':
                    return $this->handleReturnForRevision($workflow, $validated, $user);

                case 'rejected':
                    return $this->handleRejection($workflow, $validated, $user);

                default:
                    throw new \InvalidArgumentException('Invalid approval status: ' . $validated['approval_status']);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to process OPCR workflow approval', [
                'workflow_id' => $workflow->id,
                'user_id' => Auth::id(),
                'approval_status' => $validated['approval_status'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to process OPCR workflow: ' . $e->getMessage());
        }
    }

    /**
     * Manually trigger IPCR cascading for an already approved workflow.
     */
    public function cascadeIpcr(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        if ($workflow->workflow_state !== OPCRWorkflow::STATE_FINAL_APPROVAL) {
            return back()->with('error', 'IPCR cascading is only available for approved workflows.');
        }

        event(new OpcrWorkflowApproved($workflow->fresh(), $request->user(), true));

        return back()->with('status', 'IPCR cascading queued. After the queue job finishes, review the supervisor and head dashboards.');
    }

    /**
     * Handle final approval with rating overrides
     */
    private function handleFinalApproval(OPCRWorkflow $workflow, array $validated, User $user): RedirectResponse
    {
        $workflowData = [
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approver_remarks' => $validated['final_remarks'] ?? null,
            'approval_status' => 'approved',
        ];

        // Use workflow service for proper state transition
        $this->workflowService->transitionState($workflow, OPCRWorkflow::STATE_FINAL_APPROVAL, $workflowData);

        $workflow->refresh()->load([
            'targets.ratings',
            'period',
            'committedBy.employee',
            'assessedBy.employee',
            'approvedBy.employee',
            'finalApprover.employee',
            'assessor.employee',
        ]);

        $performanceSummary = $this->calculatePerformanceSummary($workflow);

        // Handle rating overrides
        $finalRating = $validated['final_rating'] ?? null;
        $performanceLevel = $validated['performance_level'] ?? null;

        $useOverride = false;
        $finalOverallRating = $performanceSummary['average_rating'];
        $finalAdjectivalRating = $this->getRatingCategory($performanceSummary['average_rating']);

        // Apply rating overrides if provided
        if ($finalRating !== null) {
            $finalOverallRating = (float) $finalRating;
            $finalAdjectivalRating = $this->getRatingCategory($finalOverallRating);
            $useOverride = true;

            Log::info('OPCR rating override applied', [
                'workflow_id' => $workflow->id,
                'user_id' => $user->id,
                'calculated_rating' => $performanceSummary['average_rating'],
                'override_rating' => $finalRating,
                'override_adjectival' => $finalAdjectivalRating,
            ]);
        }

        [$hrmoOverrideUsed, $hrmoOverrideReason, $ipcrAverage] = $this->enforceHrmoConsistency(
            $workflow,
            $finalOverallRating,
            $validated
        );

        // Update workflow with final ratings and approval data
        $workflow->update([
            'overall_rating' => $finalOverallRating,
            'overall_adjectival_rating' => $finalAdjectivalRating,
            'final_rating_override' => $useOverride ? $finalOverallRating : null,
            'performance_level' => $performanceLevel,
            'rating_override_justification' => $useOverride ? $validated['final_remarks'] : null,
            'hrmo_override' => $hrmoOverrideUsed,
            'hrmo_override_reason' => $hrmoOverrideReason,
        ]);

        // Pass override information to syncPerformanceEvaluation
        $enhancedPerformanceSummary = array_merge($performanceSummary, [
            'use_override' => $useOverride,
            'final_rating' => $finalOverallRating,
            'final_adjectival_rating' => $finalAdjectivalRating,
            'performance_level' => $performanceLevel,
        ]);

        $this->syncPerformanceEvaluation($workflow, $enhancedPerformanceSummary);

        $workflow->targets->each(function ($target) use ($user) {
            $rating = $target->ratings?->first();

            if ($rating) {
                $rating->update([
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'updated_by' => $user->id,
                ]);
            }
        });

        Log::info('OPCR workflow finally approved', [
            'workflow_id' => $workflow->id,
            'user_id' => $user->id,
            'approved_at' => $workflowData['approved_at'],
            'rating_override_used' => $useOverride,
        ]);

        // Create audit trail
        $auditData = [
            'approved_by' => $user->id,
            'approval_remarks' => $validated['final_remarks'] ?? null,
            'action_description' => 'OPCR workflow finally approved',
            'final_rating' => $finalOverallRating,
            'final_adjectival_rating' => $finalAdjectivalRating,
            'rating_override_used' => $useOverride,
            'hrmo_override' => $hrmoOverrideUsed,
            'ipcr_average_rating' => $ipcrAverage,
        ];

        if ($useOverride) {
            $auditData['original_calculated_rating'] = $performanceSummary['average_rating'];
            $auditData['override_rating'] = $finalRating;
            $auditData['performance_level'] = $performanceLevel;
        }

        $this->auditTrailService->logOPCRActivity('opcr_final_approval', $workflow, $auditData);

        DB::commit();

        event(new OpcrWorkflowApproved($workflow->fresh(), $user));

        return redirect()
            ->route('opcr.workflows.show', $workflow)
            ->with('success', 'OPCR workflow has been finally approved and archived.');
    }

    /**
     * Enforce HRMO consistency: IPCR averages must not exceed OPCR rating unless overridden.
     *
     * @return array{0: bool, 1: ?string, 2: float|null} [overrideUsed, overrideReason, ipcrAverage]
     *
     * @throws ValidationException
     */
    private function enforceHrmoConsistency(OPCRWorkflow $workflow, float $finalOverallRating, array $validated): array
    {
        $ipcrAverage = $workflow->ipcrs()
            ->whereNotNull('overall_score')
            ->avg('overall_score');

        $overrideUsed = (bool) ($validated['hrmo_override'] ?? false);
        $overrideReason = $validated['hrmo_override_reason'] ?? null;

        if ($ipcrAverage === null) {
            return [$overrideUsed, $overrideReason, null];
        }

        $ipcrAverage = round($ipcrAverage, 2);

        if ($ipcrAverage <= $finalOverallRating + 0.01) {
            // No override needed if IPCR is not higher
            return [false, null, $ipcrAverage];
        }

        if (!$overrideUsed) {
            throw ValidationException::withMessages([
                'hrmo_override' => "Average IPCR score ({$ipcrAverage}) exceeds OPCR rating (" . number_format($finalOverallRating, 2) . "). HRMO override with justification is required.",
            ]);
        }

        if (!$overrideReason) {
            throw ValidationException::withMessages([
                'hrmo_override_reason' => 'Override justification is required when forcing approval.',
            ]);
        }

        return [true, $overrideReason, $ipcrAverage];
    }

    /**
     * Handle return for revision
     */
    private function handleReturnForRevision(OPCRWorkflow $workflow, array $validated, User $user): RedirectResponse
    {
        $workflowData = [
            'return_reason' => $validated['final_remarks'] ?? 'Return for revision',
            'assessor_remarks' => $validated['recommendations_next_period'] ?? null,
            'returned_by' => $user->id,
            'returned_at' => now(),
            'approval_status' => 'returned',
            'return_source' => $validated['return_source'] ?? 'final_approval',
        ];

        $this->workflowService->transitionState($workflow, OPCRWorkflow::STATE_RETURNED, $workflowData);

        // Create audit trail
        $this->auditTrailService->log(
            'opcr_returned',
            $workflow->id,
            'OPCR returned for revision',
            [
                'workflow_id' => $workflow->id,
                'return_reason' => $workflowData['return_reason'],
                'user_id' => $user->id,
            ]
        );

        Log::info('OPCR workflow returned for revision', [
            'workflow_id' => $workflow->id,
            'user_id' => $user->id,
            'return_reason' => $workflowData['return_reason'],
        ]);

        DB::commit();

        return redirect()
            ->route('opcr.workflows.show', $workflow)
            ->with('success', 'OPCR workflow has been returned for revision.');
    }

    /**
     * Handle rejection
     */
    private function handleRejection(OPCRWorkflow $workflow, array $validated, User $user): RedirectResponse
    {
        $workflowData = [
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approver_remarks' => $validated['final_remarks'] ?? null,
            'return_reason' => $validated['final_remarks'] ?? 'Rejected',
            'returned_by' => $user->id,
            'returned_at' => now(),
            'approval_status' => 'rejected',
            'workflow_state' => 'returned', // Set to returned state
        ];

        // Update workflow with rejection data
        $workflow->update($workflowData);

        Log::info('OPCR workflow rejected', [
            'workflow_id' => $workflow->id,
            'user_id' => $user->id,
            'rejection_reason' => $workflowData['return_reason'],
        ]);

        // Create audit trail
        $this->auditTrailService->logOPCRActivity(
            'opcr_rejected',
            $workflow,
            [
                'rejected_by' => $user->id,
                'rejection_reason' => $workflowData['return_reason'],
                'approval_remarks' => $workflowData['approver_remarks'],
                'action_description' => 'OPCR workflow rejected',
            ]
        );

        DB::commit();

        return redirect()
            ->route('opcr.workflows.show', $workflow)
            ->with('success', 'OPCR workflow has been rejected.');
    }

    /**
     * Reject OPCR workflow
     */
    public function reject(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $this->authorizeWorkflowAccess($workflow);
        if (!$this->canApproveWorkflow($workflow, $this->getUserOPCRRole(Auth::user(), $workflow))) {
            abort(403, 'You cannot reject this OPCR workflow in its current state.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:2000',
            'approval_remarks' => 'nullable|string|max:2000',
        ]);

        try {
            DB::beginTransaction();
            $user = Auth::user();

            $workflowData = [
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_remarks' => $validated['approval_remarks'] ?? null,
                'return_reason' => $validated['rejection_reason'],
                'returned_by' => $user->id,
                'returned_at' => now(),
                'workflow_state' => 'returned', // Set to returned state
            ];

            // Update workflow with rejection data
            $workflow->update($workflowData);

            Log::info('OPCR workflow rejected', [
                'workflow_id' => $workflow->id,
                'user_id' => $user->id,
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            // Create audit trail
            $this->auditTrailService->logOPCRActivity(
                'opcr_rejected',
                $workflow,
                [
                    'rejected_by' => $user->id,
                    'rejection_reason' => $validated['rejection_reason'],
                    'approval_remarks' => $validated['approval_remarks'] ?? null,
                    'action_description' => 'OPCR workflow rejected',
                ]
            );

            DB::commit();

            return redirect()
                ->route('opcr.workflows.show', $workflow)
                ->with('success', 'OPCR workflow has been rejected and returned to Department Head for revision.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to reject OPCR workflow', [
                'workflow_id' => $workflow->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to reject OPCR workflow: ' . $e->getMessage());
        }
    }

    /**
     * Display historical OPCR archive with advanced filtering
     */
    public function archive(Request $request): View
    {
        $user = Auth::user();
        $query = OPCRWorkflow::with(['office', 'period', 'committedBy.employee'])
            ->where('workflow_state', 'final_approval'); // Only approved/archived workflows

        // Filter based on user's access level
        if (!$user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            $accessibleOfficeIds = $this->getUserAccessibleOffices($user)->pluck('id');
            $query->whereIn('office_id', $accessibleOfficeIds);
        }

        // Apply advanced filters
        if ($request->filled('period_id')) {
            $query->where('period_id', $request->period_id);
        }

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('committed_by')) {
            $query->where('committed_by', $request->committed_by);
        }

        if ($request->filled('rating_range')) {
            $ratingRange = explode('-', $request->rating_range);
            if (count($ratingRange) === 2) {
                $query->whereHas('targets.ratings', function ($q) use ($ratingRange) {
                    $q->whereBetween('final_rating', [$ratingRange[0], $ratingRange[1]]);
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('office', function ($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('committedBy.employee', function ($subQ) use ($search) {
                      $subQ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting options
        $sortBy = $request->get('sort_by', 'updated_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if (in_array($sortBy, ['created_at', 'updated_at', 'title', 'workflow_state'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        $workflows = $query->paginate(20);

        // Get filter options
        $periods = PerformancePeriod::orderBy('start_date', 'desc')->get();
        $offices = $this->getUserAccessibleOffices($user);
        $departmentHeads = Employee::whereHas('user.officeAssignments', function ($q) {
            $q->where('role', 'Department Head');
        })->with('user')->orderBy('last_name')->get();

        // Calculate statistics
        $archiveStats = $this->getArchiveStatistics($query->getQuery());

        return view('opcr.archive.index', [
            'workflows' => $workflows,
            'periods' => $periods,
            'offices' => $offices,
            'departmentHeads' => $departmentHeads,
            'filters' => array_merge([
                'period_id' => '',
                'office_id' => '',
                'date_from' => '',
                'date_to' => '',
                'committed_by' => '',
                'rating_range' => '',
                'search' => '',
                'sort_by' => 'updated_at',
                'sort_order' => 'desc'
            ], $request->only([
                'period_id', 'office_id', 'date_from', 'date_to',
                'committed_by', 'rating_range', 'search', 'sort_by', 'sort_order'
            ])),
            'stats' => $archiveStats,
        ]);
    }

    /**
     * Export archived OPCR data to Excel
     */
    public function exportArchive(Request $request)
    {
        $user = Auth::user();

        // Validate export permissions
        if (!$user->hasAnyPermission(['opcr.export', 'opcr.manage'])) {
            abort(403, 'You do not have permission to export OPCR archive data.');
        }

        $query = OPCRWorkflow::with(['office', 'period', 'committedBy.employee', 'targets.mfo', 'targets.successIndicator', 'targets.ratings'])
            ->where('workflow_state', 'final_approval')
            ->withTrashed();

        // Apply same filters as archive view
        if (!$user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            $accessibleOfficeIds = $this->getUserAccessibleOffices($user)->pluck('id');
            $query->whereIn('office_id', $accessibleOfficeIds);
        }

        if ($request->filled('period_id')) {
            $query->where('period_id', $request->period_id);
        }

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $workflows = $query->get();

        // Generate Excel export using new OPCR Archive export class
        try {
            $filename = "opcr_archive_" . now()->format('Y-m-d_H-i-s') . ".xlsx";

            return Excel::download(new OPCRArchiveExport($workflows), $filename);
        } catch (\Exception $e) {
            Log::error('Failed to export OPCR archive', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to export OPCR archive: ' . $e->getMessage());
        }
    }

    /**
     * Get pending items for user
     */
    private function getPendingItemsForUser($user, ?array $context = null): array
    {
        $context = $context ?? $this->getUserOPCRRoleWithOffice($user);
        $userRole = $context['role'];
        $pendingItems = [];

        switch ($userRole) {
            case 'Planning Reviewer':
                $pendingItems['planning_review'] = OPCRWorkflow::where('workflow_state', OPCRWorkflow::STATE_PLANNING_REVIEW)->count();
                break;
            case 'PMT Reviewer':
                $pendingItems['pmt_review'] = OPCRWorkflow::where('workflow_state', OPCRWorkflow::STATE_PMT_REVIEW)->count();
                break;
            case OfficeAssignment::ROLE_DEPARTMENT_HEAD:
                $officeIds = $context['department_head_office_ids'];

                if ($officeIds->isEmpty()) {
                    $pendingItems['draft_workflows'] = 0;
                    $pendingItems['returned_workflows'] = 0;
                    break;
                }

                $pendingItems['draft_workflows'] = OPCRWorkflow::whereIn('office_id', $officeIds)
                    ->where('workflow_state', 'draft')
                    ->count();

                $pendingItems['returned_workflows'] = OPCRWorkflow::whereIn('office_id', $officeIds)
                    ->where('workflow_state', 'returned')
                    ->count();
                break;

            case OfficeAssignment::ROLE_ASSESSOR:
                $pendingItems['evaluation_workflows'] = OPCRWorkflow::where('workflow_state', 'evaluation')->count();
                break;

            case OfficeAssignment::ROLE_FINAL_APPROVER:
                $pendingItems['final_approval_workflows'] = OPCRWorkflow::where('workflow_state', 'final_approval')->count();
                break;
        }

        return $pendingItems;
    }

    /**
     * Get archive statistics
     */
    private function getArchiveStatistics($query): array
    {
        // Calculate actual average rating from OPCR workflows
        $averageRating = DB::table('opcr_workflows')
            ->whereNotNull('overall_rating')
            ->avg('overall_rating') ?? 0;

        // Calculate top performing offices based on actual ratings, not just count
        $topPerformingOffices = DB::table('opcr_workflows as ow')
            ->join('offices as o', 'ow.office_id', '=', 'o.id')
            ->select([
                'ow.office_id',
                'o.name',
                DB::raw('COUNT(*) as workflow_count'),
                DB::raw('AVG(ow.overall_rating) as avg_rating'),
                DB::raw('SUM(CASE WHEN ow.workflow_state = "final_approval" THEN 1 ELSE 0 END) as completed_count')
            ])
            ->whereNotNull('ow.overall_rating')
            ->groupBy('ow.office_id', 'o.name')
            ->orderByDesc('avg_rating')
            ->orderByDesc('completed_count')
            ->limit(5)
            ->get();

        return [
            'total_workflows' => $query->count(),
            'current_period' => PerformancePeriod::where('is_active', true)->first(),
            'average_rating' => round($averageRating, 2),
            'top_performing_offices' => $topPerformingOffices,
        ];
    }

    /**
     * Calculate performance summary for a workflow
     */
    private function syncPerformanceEvaluation(OPCRWorkflow $workflow, array $summary): void
    {
        $workflow->loadMissing([
            'period',
            'committedBy.employee',
            'assessedBy.employee',
            'approvedBy.employee',
            'finalApprover.employee',
            'assessor.employee',
        ]);

        $employeeId = optional($workflow->committedBy?->employee)->id;

        if (!$employeeId) {
            Log::warning('Unable to sync performance evaluation, missing employee record', [
                'workflow_id' => $workflow->id,
            ]);

            return;
        }

        $period = $workflow->period;
        $ratingCategory = $this->getRatingCategory($summary['average_rating']);
        $dimensionRatings = $summary['dimension_ratings'] ?? [];

        $evaluationData = [
            'employee_id' => $employeeId,
            'evaluation_period' => $period?->name ?? ($period ? "{$period->year} {$period->semester}" : 'Current Period'),
            'evaluation_date' => now(),
            'period_start' => $period?->start_date ?? now()->startOfYear(),
            'period_end' => $period?->end_date ?? now()->endOfYear(),
            'overall_rating' => $summary['average_rating'],
            'quality_rating' => $dimensionRatings['quality'] ?? null,
            'efficiency_rating' => $dimensionRatings['efficiency'] ?? null,
            'timeliness_rating' => $dimensionRatings['timeliness'] ?? null,
            'goal_achievement_percentage' => $summary['goal_achievement_percentage'] ?? 0,
            'achievements' => $workflow->summary,
            'goals_next_period' => $workflow->recommendations,
            'evaluator_comments' => $workflow->assessor_remarks,
            'employee_comments' => $workflow->department_head_remarks,
            'evaluation_status' => 'approved',
            'evaluator_id' => optional($workflow->assessedBy?->employee)->id,
            'approved_by' => optional($workflow->approvedBy?->employee)->id,
            'submitted_at' => $workflow->assessed_at,
            'approved_at' => $workflow->approved_at ?? now(),
            'performance_level' => $ratingCategory,
            'opcr_workflow_id' => $workflow->id,
        ];

        PerformanceEvaluation::updateOrCreate(
            ['opcr_workflow_id' => $workflow->id],
            $evaluationData
        );
    }

    /**
     * Calculate performance summary for a workflow
     */
    private function calculatePerformanceSummary(OPCRWorkflow $workflow): array
    {
        $targets = $workflow->targets;

        if ($targets->isEmpty()) {
            return [
                'total_targets' => 0,
                'average_rating' => 0,
                'total_accomplished' => 0,
                'performance_rating' => 0,
                'targets_by_rating' => [],
            ];
        }

        $totalRating = 0;
        $ratedTargets = 0;
        $targetsByRating = [];
        $qualityRatings = [];
        $efficiencyRatings = [];
        $timelinessRatings = [];
        $metTargets = 0;

        foreach ($targets as $target) {
            $ratingRecord = $target->ratings?->first();

            if ($ratingRecord && $ratingRecord->final_rating !== null) {
                $rating = (float) $ratingRecord->final_rating;
                $totalRating += $rating;
                $ratedTargets++;

                $ratingCategory = $this->getRatingCategory($rating);
                if (!isset($targetsByRating[$ratingCategory])) {
                    $targetsByRating[$ratingCategory] = 0;
                }
                $targetsByRating[$ratingCategory]++;
            }

            if ($ratingRecord) {
                if ($ratingRecord->rating_quality !== null) {
                    $qualityRatings[] = (float) $ratingRecord->rating_quality;
                }
                if ($ratingRecord->rating_efficiency !== null) {
                    $efficiencyRatings[] = (float) $ratingRecord->rating_efficiency;
                }
                if ($ratingRecord->rating_timeliness !== null) {
                    $timelinessRatings[] = (float) $ratingRecord->rating_timeliness;
                }
            }

            if ($target->is_target_met) {
                $metTargets++;
            }
        }

        $averageRating = $ratedTargets > 0 ? round($totalRating / $ratedTargets, 2) : 0;
        $dimensionRatings = [
            'quality' => !empty($qualityRatings) ? round(array_sum($qualityRatings) / count($qualityRatings), 2) : null,
            'efficiency' => !empty($efficiencyRatings) ? round(array_sum($efficiencyRatings) / count($efficiencyRatings), 2) : null,
            'timeliness' => !empty($timelinessRatings) ? round(array_sum($timelinessRatings) / count($timelinessRatings), 2) : null,
        ];
        $goalAchievement = $targets->count() > 0
            ? round(($metTargets / $targets->count()) * 100, 2)
            : 0;

        return [
            'total_targets' => $targets->count(),
            'average_rating' => $averageRating,
            'total_accomplished' => $ratedTargets,
            'performance_rating' => $averageRating,
            'targets_by_rating' => $targetsByRating,
            'dimension_ratings' => $dimensionRatings,
            'goal_achievement_percentage' => $goalAchievement,
        ];
    }

    /**
     * Get rating category based on numerical rating
     */
    public function getRatingCategory(float $rating): string
    {
        if ($rating >= 4.51) return 'Outstanding';
        if ($rating >= 3.76) return 'Very Satisfactory';
        if ($rating >= 3.01) return 'Satisfactory';
        if ($rating >= 2.51) return 'Fairly Satisfactory';
        if ($rating >= 1.51) return 'Poor';
        return 'Very Poor';
    }

    /**
     * Create or update PerformanceEvaluation record for OPCR workflow
     */
    private function createOrUpdatePerformanceEvaluation(OPCRWorkflow $workflow, $user, array $validated): PerformanceEvaluation
    {
        // Calculate overall rating from the evaluation data
        $overallRating = $this->calculateOverallRatingFromEvaluation($validated);

        // Get or find an appropriate employee for this evaluation
        $employee = $this->getEmployeeForEvaluation($workflow);

        // Create or update PerformanceEvaluation record
        $evaluation = PerformanceEvaluation::updateOrCreate(
            [
                'opcr_workflow_id' => $workflow->id,
            ],
            [
                'employee_id' => $employee->id,
                'office_id' => $workflow->office_id,
                'period_id' => $workflow->period_id,
                'evaluation_period' => $this->getEvaluationPeriodFromWorkflow($workflow),
                'evaluation_date' => now()->format('Y-m-d'),
                'period_start' => $this->getPeriodStartDate($workflow->period_id),
                'period_end' => $this->getPeriodEndDate($workflow->period_id),
                'overall_rating' => $overallRating,
                'overall_qet_rating' => $overallRating,
                'overall_adjectival_rating' => $this->getRatingCategory($overallRating),
                'goal_achievement_percentage' => $overallRating * 20, // Convert to percentage
                'achievements' => $validated['overall_remarks'] ?? null,
                'areas_for_improvement' => $validated['recommendations'] ?? null,
                'evaluator_comments' => $validated['overall_remarks'] ?? null,
                'evaluation_status' => 'completed',
                'workflow_state' => 'evaluation',
                'opcr_type' => 'organizational',
                'evaluator_id' => $user->id,
                'assessor_id' => $user->id,
                'assessment_completed_at' => now(),
                'committed_at' => $workflow->committed_at,
                'committed_by' => $workflow->committed_by,
                'submitted_at' => now(),
                'created_at' => $workflow->created_at,
                'updated_at' => now(),
            ]
        );

        // Update OPCR workflow with evaluation linkage
        $workflow->update([
            'performance_evaluation_id' => $evaluation->id,
            'updated_at' => now(),
        ]);

        Log::info('PerformanceEvaluation created/updated for OPCR workflow', [
            'workflow_id' => $workflow->id,
            'evaluation_id' => $evaluation->id,
            'employee_id' => $employee->id,
            'overall_rating' => $overallRating,
            'user_id' => $user->id,
        ]);

        return $evaluation;
    }

    /**
     * Calculate overall rating from evaluation data
     */
    private function calculateOverallRatingFromEvaluation(array $validated): float
    {
        $totalRatings = [];

        foreach ($validated['evaluations'] as $evaluationData) {
            if (isset($evaluationData['quality_rating'], $evaluationData['efficiency_rating'], $evaluationData['timeliness_rating'])) {
                $averageRating = round(
                    (
                        $evaluationData['quality_rating'] +
                        $evaluationData['efficiency_rating'] +
                        $evaluationData['timeliness_rating']
                    ) / 3,
                    2
                );
                $totalRatings[] = $averageRating;
            }
        }

        return !empty($totalRatings) ? round(array_sum($totalRatings) / count($totalRatings), 2) : 3.0;
    }

    /**
     * Get appropriate employee for evaluation
     */
    private function getEmployeeForEvaluation(OPCRWorkflow $workflow): Employee
    {
        // Try to get employee from office assignments first
        $officeAssignment = DB::table('office_assignments')
            ->where('office_id', $workflow->office_id)
            ->whereNotNull('employee_id')
            ->where('is_active', true)
            ->first();

        if ($officeAssignment) {
            return Employee::findOrFail($officeAssignment->employee_id);
        }

        // Fallback to any employee
        return Employee::whereNull('deleted_at')->firstOrFail();
    }

    /**
     * Get evaluation period from workflow
     */
    private function getEvaluationPeriodFromWorkflow(OPCRWorkflow $workflow): string
    {
        $period = DB::table('performance_periods')
            ->where('id', $workflow->period_id)
            ->first();

        return $period ? $period->name : 'Annual Evaluation';
    }

    /**
     * Get period start date
     */
    private function getPeriodStartDate($periodId): string
    {
        $period = DB::table('performance_periods')
            ->where('id', $periodId)
            ->first();

        return $period ? $period->start_date : '2024-01-01';
    }

    /**
     * Get period end date
     */
    private function getPeriodEndDate($periodId): string
    {
        $period = DB::table('performance_periods')
            ->where('id', $periodId)
            ->first();

        return $period ? $period->end_date : '2024-12-31';
    }

    // ========== Office Assignment Management ==========

    /**
     * Display a listing of office assignments for a specific office.
     */
    public function officeAssignments(Request $request, Office $office): View
    {
        // Base query for all assignments with search and role filters
        $query = OfficeAssignment::with(['user.employee', 'assignedBy'])
            ->where('office_id', $office->id);

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($subQuery) use ($search) {
                    $subQuery->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('employee_number', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('user', function ($subQuery) use ($search) {
                    $subQuery->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Get filtered assignments and separate by status
        $allAssignments = $query->orderBy('is_active', 'desc')
            ->orderBy('role')
            ->orderBy('created_at', 'desc')
            ->get();

        // Separate active and inactive assignments
        $activeAssignments = $allAssignments->where('is_active', true);
        $inactiveAssignments = $allAssignments->where('is_active', false);

        // Get counts for display
        $activeCount = $activeAssignments->count();
        $inactiveCount = $inactiveAssignments->count();

        return view('opcr.offices.assignments.index', compact(
            'office',
            'activeAssignments',
            'inactiveAssignments',
            'activeCount',
            'inactiveCount'
        ));
    }

    /**
     * Show the form for creating a new office assignment.
     */
    public function officeAssignmentCreate(Office $office): View
    {
        // Debug: Log what office we're working with
        \Log::info('OPCR Assignment Create - Office: ' . $office->name . ' (ID: ' . $office->id . ')');

        // Get employees by department matching for assignment creation
        $employees = Employee::where(function ($query) use ($office) {
            // Direct department name match
            $query->where('department', $office->name)
                  // Also check if employee's department contains the office code
                  ->orWhere('department', 'like', '%' . $office->code . '%');
        })
        ->whereNull('archived_at')
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        \Log::info('Found ' . $employees->count() . ' employees for office: ' . $office->name);

        // Check for sync errors
        $syncErrors = [];
        foreach ($employees as $employee) {
            if ($employee->user && $employee->department !== $employee->user->department) {
                $syncErrors[] = [
                    'employee' => $employee->full_name,
                    'employee_dept' => $employee->department,
                    'user_dept' => $employee->user->department
                ];
            }
        }

        if (!empty($syncErrors)) {
            \Log::warning('Department sync errors found', [
                'office' => $office->name,
                'errors' => $syncErrors
            ]);
        }

        $availableRoles = [
            'Member' => 'Regular member of the office',
            'Department Head' => 'Head of the office/department',
            'Assessor' => 'Performance Management Team (PMT) member',
            'Final Approver' => 'Has authority for final approval (e.g., Mayor)'
        ];

        return view('opcr.offices.assignments.create', compact(
            'office',
            'employees',
            'availableRoles',
            'syncErrors'
        ));
    }

    /**
     * Store a newly created office assignment.
     */
    public function officeAssignmentStore(Request $request, Office $office): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|in:Member,Department Head,Assessor,Final Approver',
            'assigned_date' => 'nullable|date',
            'ended_date' => 'nullable|date|after_or_equal:assigned_date',
            'metadata' => 'nullable|array',
        ], [
            'user_id.required' => 'Please select a user',
            'user_id.exists' => 'Selected user is invalid',
            'employee_id.required' => 'Please select an employee',
            'employee_id.exists' => 'Selected employee is invalid',
            'role.required' => 'Please select a role',
            'role.in' => 'Invalid role selected',
            'assigned_date.after_or_equal' => 'End date must be after or same as assigned date',
        ]);

        try {
            // Check if user already has an active assignment for this office
            $existingAssignment = OfficeAssignment::where('office_id', $office->id)
                ->where('user_id', $validated['user_id'])
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('ended_date')
                          ->orWhere('ended_date', '>=', now());
                })
                ->first();

            if ($existingAssignment) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['user_id' => 'This user already has an active assignment for this office. Please end the existing assignment first.']);
            }

            DB::beginTransaction();

            // Create the assignment
                $assignment = OfficeAssignment::create([
                    'office_id' => $office->id,
                    'user_id' => $validated['user_id'],
                    'employee_id' => $validated['employee_id'],
                    'role' => $validated['role'],
                    'assigned_date' => $validated['assigned_date'] ?? now(),
                    'ended_date' => $validated['ended_date'] ?? null,
                    'is_active' => true,
                    'assigned_by' => auth()->id(),
                    'metadata' => $validated['metadata'] ?? [],
                ]);

            // Sync the employee's department with the office
            $this->syncEmployeeDepartment($assignment->employee_id);

            // Sync user roles if employee has user account
            $this->syncUserRoles($validated['user_id'], $office);

            DB::commit();

            \Log::info('Office assignment created successfully', [
                'assignment_id' => $assignment->id,
                'office_id' => $office->id,
                'user_id' => $validated['user_id'],
                'employee_id' => $validated['employee_id'],
                'role' => $validated['role'],
                'created_by' => auth()->id(),
            ]);

            return redirect()
                ->route('opcr.offices.assignments.index', $office)
                ->with('success', 'Office assignment created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create office assignment', [
                'error' => $e->getMessage(),
                'office_id' => $office->id,
                'validated' => $validated,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create office assignment: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified office assignment.
     */
    public function officeAssignmentEdit(Office $office, OfficeAssignment $assignment): View
    {
        // Load assignment with relationships
        $assignment->load(['user.employee', 'assignedBy', 'office']);

        // Get employees (filtered by department)
        $employees = Employee::where(function ($query) use ($office) {
            // Direct department name match
            $query->where('department', $office->name)
                  // Also check if employee's department contains the office code
                  ->orWhere('department', 'like', '%' . $office->code . '%');
        })
        ->whereNull('archived_at')
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        // Check if editing own assignment (Department Head editing themselves)
        $isOwnAssignment = auth()->id() === $assignment->user_id &&
                          auth()->user()->hasRole('Department Head');

        // Define available roles based on permissions
        $availableRoles = [
            'Member' => 'Regular member of the office',
        ];

        // Add higher-level roles only if user has sufficient permissions
        if (auth()->user()->hasAnyRole(['Super Admin', 'HR Admin'])) {
            $availableRoles['Department Head'] = 'Head of the office/department';
            $availableRoles['Assessor'] = 'Performance Management Team (PMT) member';
            $availableRoles['Final Approver'] = 'Has authority for final approval (e.g., Mayor)';
        }

        return view('opcr.offices.assignments.edit', compact(
            'office',
            'assignment',
            'employees',
            'availableRoles',
            'isOwnAssignment'
        ));
    }

    /**
     * Update the specified office assignment.
     */
    public function officeAssignmentUpdate(Request $request, Office $office, OfficeAssignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|in:Member,Department Head,Assessor,Final Approver',
            'assigned_date' => 'nullable|date',
            'ended_date' => 'nullable|date|after_or_equal:assigned_date',
            'metadata' => 'nullable|array',
        ], [
            'user_id.required' => 'Please select a user',
            'user_id.exists' => 'Selected user is invalid',
            'employee_id.required' => 'Please select an employee',
            'employee_id.exists' => 'Selected employee is invalid',
            'role.required' => 'Please select a role',
            'role.in' => 'Invalid role selected',
            'assigned_date.after_or_equal' => 'End date must be after or same as assigned date',
        ]);

        try {
            // Check for conflicts with other assignments (excluding current assignment)
            $conflictingAssignment = OfficeAssignment::where('office_id', $office->id)
                ->where('user_id', $validated['user_id'])
                ->where('id', '!=', $assignment->id)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('ended_date')
                          ->orWhere('ended_date', '>=', now());
                })
                ->first();

            if ($conflictingAssignment) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['user_id' => 'This user already has an active assignment for this office. Please end the existing assignment first.']);
            }

            DB::beginTransaction();

            // Update the assignment
            $updateData = [
                'user_id' => $validated['user_id'],
                'employee_id' => $validated['employee_id'],
                'role' => $validated['role'],
                'assigned_date' => $validated['assigned_date'] ?? $assignment->assigned_date,
                'ended_date' => $validated['ended_date'] ?? null,
                'metadata' => $validated['metadata'] ?? $assignment->metadata ?? [],
                'updated_at' => now(),
            ];

            // Automatically set is_active to false if ended_date is in the past
            if (!empty($validated['ended_date']) && $validated['ended_date'] < now()) {
                $updateData['is_active'] = false;
            } elseif ($assignment->is_active === false && empty($validated['ended_date'])) {
                // Re-activate if was inactive and no end date is provided
                $updateData['is_active'] = true;
            }

            $assignment->update($updateData);

            // Sync the employee's department with the office
            $this->syncEmployeeDepartment($validated['employee_id']);

            // Sync user roles if employee has user account
            $this->syncUserRoles($validated['user_id'], $office);

            DB::commit();

            \Log::info('Office assignment updated successfully', [
                'assignment_id' => $assignment->id,
                'office_id' => $office->id,
                'user_id' => $validated['user_id'],
                'employee_id' => $validated['employee_id'],
                'role' => $validated['role'],
                'updated_by' => auth()->id(),
            ]);

            return redirect()
                ->route('opcr.offices.assignments.index', $office)
                ->with('success', 'Office assignment updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to update office assignment', [
                'error' => $e->getMessage(),
                'assignment_id' => $assignment->id,
                'office_id' => $office->id,
                'validated' => $validated,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update office assignment: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified office assignment.
     */
    public function officeAssignmentDestroy(Office $office, OfficeAssignment $assignment): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Store assignment details for logging
            $assignmentDetails = [
                'id' => $assignment->id,
                'office_id' => $assignment->office_id,
                'user_id' => $assignment->user_id,
                'employee_id' => $assignment->employee_id,
                'role' => $assignment->role,
            ];

            // Delete the assignment (this will trigger cascading deletes for related records if properly set up)
            $assignment->delete();

            // Sync user roles after assignment deletion
            if ($assignment->user_id) {
                $this->syncUserRoles($assignment->user_id, $office);
            }

            DB::commit();

            \Log::info('Office assignment deleted successfully', [
                'assignment_details' => $assignmentDetails,
                'deleted_by' => auth()->id(),
                'deleted_at' => now()->toDateTimeString(),
            ]);

            return redirect()
                ->route('opcr.offices.assignments.index', $office)
                ->with('success', 'Office assignment deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to delete office assignment', [
                'error' => $e->getMessage(),
                'assignment_id' => $assignment->id,
                'office_id' => $office->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('opcr.offices.assignments.index', $office)
                ->with('error', 'Failed to delete office assignment: ' . $e->getMessage());
        }
    }

    /**
     * Sync employee's department information based on their office assignment
     */
    private function syncEmployeeDepartment(int $employeeId): void
    {
        try {
            $employee = Employee::findOrFail($employeeId);

            // Get the most recent active office assignment
            $activeAssignment = OfficeAssignment::where('employee_id', $employee->id)
                ->where('is_active', true)
                ->with('office')
                ->latest('assigned_date')
                ->first();

            if ($activeAssignment && $activeAssignment->office) {
                $departmentName = $activeAssignment->office->name;

                // Update employee department
                $employee->department = $departmentName;
                $employee->saveQuietly(); // Save without triggering events

                // Update associated user department if exists
                if ($employee->user) {
                    $employee->user->department = $departmentName;
                    $employee->user->saveQuietly();
                }

                \Log::info('Department synchronized for employee', [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'department' => $departmentName,
                    'office_id' => $activeAssignment->office_id
                ]);

            } else {
                // No active assignment found, clear department
                $employee->department = null;
                $employee->saveQuietly();

                if ($employee->user) {
                    $employee->user->department = null;
                    $employee->user->saveQuietly();
                }

                \Log::warning('No active assignment found for employee, department cleared', [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to sync employee department', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync user roles based on their office assignments
     */
    private function syncUserRoles(int $userId, Office $office): void
    {
        try {
            $user = User::findOrFail($userId);

            // Get all active assignments for this user
            $activeAssignments = OfficeAssignment::where('user_id', $userId)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('ended_date')
                          ->orWhere('ended_date', '>=', now());
                })
                ->with('office')
                ->get();

            // Define role mapping
            $roleMapping = [
                'Department Head' => 'Department Head',
                'Assessor' => 'Assessor',
                'Final Approver' => 'Final Approver',
            ];

            $rolesToAssign = [];
            foreach ($activeAssignments as $assignment) {
                if (isset($roleMapping[$assignment->role])) {
                    $rolesToAssign[] = $roleMapping[$assignment->role];
                }
            }

            // Remove existing department-related roles
            $existingRoles = $user->roles()->whereIn('name', array_values($roleMapping))->get();
            foreach ($existingRoles as $role) {
                if (!in_array($role->name, $rolesToAssign)) {
                    $user->removeRole($role);
                    \Log::info('Removed role from user', [
                        'user_id' => $userId,
                        'role' => $role->name,
                        'office_id' => $office->id
                    ]);
                }
            }

            // Assign new roles
            foreach ($rolesToAssign as $roleName) {
                if (!$user->hasRole($roleName)) {
                    $user->assignRole($roleName);
                    \Log::info('Assigned role to user', [
                        'user_id' => $userId,
                        'role' => $roleName,
                        'office_id' => $office->id
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Log::error('Failed to sync user roles', [
                'user_id' => $userId,
                'office_id' => $office->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
