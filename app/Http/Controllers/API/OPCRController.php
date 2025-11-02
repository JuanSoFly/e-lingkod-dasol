<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MajorFinalOutput;
use App\Models\OPCRWorkflow;
use App\Models\PerformancePeriod;
use App\Models\Office;
use App\Models\Employee;
use App\Services\OPCRManagementService;
use App\Services\OPCRWorkflowService;
use App\Services\QETRatingCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OPCRController extends Controller
{
    protected $opcrManagementService;
    protected $opcrWorkflowService;
    protected $qetRatingService;

    public function __construct(
        OPCRManagementService $opcrManagementService,
        OPCRWorkflowService $opcrWorkflowService,
        QETRatingCalculationService $qetRatingService
    ) {
        $this->opcrManagementService = $opcrManagementService;
        $this->opcrWorkflowService = $opcrWorkflowService;
        $this->qetRatingService = $qetRatingService;

        // API rate limiting
        $this->middleware('throttle:60,1')->except(['login', 'refresh']);

        // API authentication
        $this->middleware('auth:sanctum')->except(['login', 'refresh']);
    }

    /**
     * API authentication for mobile apps
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string|max:255',
            'device_platform' => 'nullable|string|in:ios,android,web',
            'app_version' => 'nullable|string|max:50'
        ]);

        try {
            $user = Auth::attempt($validated);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $authUser = Auth::user();

            // Check if user has required OPCR permissions
            if (!$authUser->canAny(['opcr.view', 'opcr.create', 'opcr.rate', 'opcr.assess', 'opcr.approve'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have OPCR access permissions'
                ], 403);
            }

            // Create API token
            $token = $authUser->createToken($validated['device_name'], [
                'opcr:read', 'opcr:write', 'opcr:rate', 'opcr:assess'
            ]);

            // Log mobile login
            Log::info('Mobile API login successful', [
                'user_id' => $authUser->id,
                'email' => $authUser->email,
                'device_name' => $validated['device_name'],
                'device_platform' => $validated['device_platform'] ?? 'unknown',
                'app_version' => $validated['app_version'] ?? 'unknown',
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'token' => $token->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $token->accessToken->expires_at,
                    'user' => [
                        'id' => $authUser->id,
                        'name' => $authUser->name,
                        'email' => $authUser->email,
                        'roles' => $authUser->getRoleNames(),
                        'permissions' => $authUser->getAllPermissions()->pluck('name'),
                        'office_assignments' => $authUser->officeAssignments()->with('office')->get()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Mobile API login failed', [
                'email' => $validated['email'],
                'error' => $e->getMessage(),
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed'
            ], 500);
        }
    }

    /**
     * Refresh API token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            // Create new token
            $token = $user->createToken('mobile-refresh', [
                'opcr:read', 'opcr:write', 'opcr:rate', 'opcr:assess'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $token->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $token->accessToken->expires_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed'
            ], 500);
        }
    }

    /**
     * Get MFOs for the specified office with active success indicators.
     */
    public function officeMfos(Request $request, Office $office): JsonResponse
    {
        $user = $request->user();

        if (!$user && Auth::check()) {
            $user = Auth::user();
        }

        if (!$this->userCanAccessOffice($user, $office)) {
            return response()->json([
                'message' => 'You do not have permission to access this office.'
            ], 403);
        }

        if (!$office->is_active) {
            return response()->json([], 200);
        }

        $includeInactive = $request->boolean('include_inactive', false);

        $mfos = MajorFinalOutput::query()
            ->select(['id', 'code', 'title', 'description', 'parent_id', 'level', 'office_id'])
            ->forOffice($office->id)
            ->when(!$includeInactive, fn ($query) => $query->active())
            ->orderBy('level')
            ->orderBy('code')
            ->with(['successIndicators' => function ($query) use ($includeInactive) {
                $query->select([
                    'id',
                    'mfo_id',
                    'code',
                    'title',
                    'description',
                    'target_quantity',
                    'target_efficiency',
                    'target_timeliness',
                    'is_active',
                ])->orderBy('code');

                if (!$includeInactive) {
                    $query->where('is_active', true);
                }
            }])
            ->get()
            ->keyBy('id');

        if ($mfos->isEmpty()) {
            return response()->json([]);
        }

        $payload = $mfos->mapWithKeys(function (MajorFinalOutput $mfo) use ($mfos) {
            return [
                (string) $mfo->id => [
                    'id' => $mfo->id,
                    'code' => $mfo->code,
                    'title' => $mfo->title,
                    'description' => $mfo->description,
                    'level' => $mfo->level,
                    'parent_id' => $mfo->parent_id,
                    'full_path' => $this->buildMfoPath($mfo, $mfos, 'title', ' > '),
                    'full_code_path' => $this->buildMfoPath($mfo, $mfos, 'code', '.'),
                    'success_indicators' => $mfo->successIndicators->map(function ($indicator) {
                        return [
                            'id' => $indicator->id,
                            'code' => $indicator->code,
                            'title' => $indicator->title,
                            'description' => $indicator->description,
                            'target_quantity' => $indicator->target_quantity,
                            'target_efficiency' => $indicator->target_efficiency,
                            'target_timeliness' => $indicator->target_timeliness,
                        ];
                    })->values(),
                ],
            ];
        });

        return response()->json($payload);
    }

    /**
     * Get user's OPCR workflows
     */
    public function workflows(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:draft,committed,in_progress,evaluation,final_approval,completed,archived',
            'period_id' => 'nullable|exists:performance_periods,id',
            'office_id' => 'nullable|exists:offices,id',
            'per_page' => 'nullable|integer|min:10|max:100',
            'include_details' => 'boolean'
        ]);

        try {
            $user = $request->user();

            // Get workflows based on user's role and permissions
            $workflows = $this->opcrManagementService->getUserWorkflows($user, $validated);

            return response()->json([
                'success' => true,
                'data' => [
                    'workflows' => $workflows,
                    'pagination' => $workflows->toArray(),
                    'user_role' => $user->getRoleNames()->first(),
                    'available_actions' => $this->getUserAvailableActions($user)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API workflows fetch failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch workflows'
            ], 500);
        }
    }

    /**
     * Get specific OPCR workflow details
     */
    public function workflowShow(OPCRWorkflow $workflow, Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check user permissions for this workflow
            if (!$this->canAccessWorkflow($user, $workflow)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $workflowDetails = $this->opcrManagementService->getWorkflowDetails($workflow, [
                'include_mfos' => true,
                'include_targets' => true,
                'include_ratings' => true,
                'include_workflow_history' => true,
                'include_attachments' => true
            ]);

            return response()->json([
                'success' => true,
                'data' => $workflowDetails,
                'user_actions' => $this->getWorkflowUserActions($user, $workflow)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch workflow details'
            ], 500);
        }
    }

    /**
     * Create/update OPCR workflow via mobile
     */
    public function workflowStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_id' => 'required|exists:performance_periods,id',
            'office_id' => 'required|exists:offices,id',
            'employee_id' => 'nullable|exists:employees,id',
            'mfo_data' => 'required|array',
            'mfo_data.*.title' => 'required|string|max:255',
            'mfo_data.*.description' => 'nullable|string|max:1000',
            'mfo_data.*.targets' => 'required|array',
            'mfo_data.*.targets.*.description' => 'required|string|max:500',
            'mfo_data.*.targets.*.quantity_target' => 'required|integer|min:1',
            'mfo_data.*.targets.*.efficiency_target' => 'required|integer|min:1',
            'mfo_data.*.targets.*.timeliness_target' => 'required|integer|min:1',
            'save_as_draft' => 'boolean'
        ]);

        try {
            $user = $request->user();

            if (!$user->can('opcr.create')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            $workflowData = array_merge($validated, [
                'created_by' => $user->id,
                'office_id' => $this->getUserOfficeId($user) ?: $validated['office_id']
            ]);

            $result = $this->opcrManagementService->createWorkflowFromMobile($workflowData);

            // Log mobile workflow creation
            Log::info('Mobile OPCR workflow created', [
                'user_id' => $user->id,
                'workflow_id' => $result['workflow']->id,
                'period_id' => $validated['period_id'],
                'office_id' => $validated['office_id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OPCR workflow created successfully',
                'data' => [
                    'workflow_id' => $result['workflow']->id,
                    'status' => $result['workflow']->current_state,
                    'next_actions' => $result['next_actions']
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Mobile workflow creation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create workflow'
            ], 500);
        }
    }

    /**
     * Submit QET ratings via mobile
     */
    public function submitRatings(Request $request, OPCRWorkflow $workflow): JsonResponse
    {
        $validated = $request->validate([
            'target_id' => 'required|exists:performance_targets,id',
            'quantity_rating' => 'required|integer|min:1|max:5',
            'efficiency_rating' => 'required|integer|min:1|max:5',
            'timeliness_rating' => 'required|integer|min:1|max:5',
            'quantity_remarks' => 'nullable|string|max:1000',
            'efficiency_remarks' => 'nullable|string|max:1000',
            'timeliness_remarks' => 'nullable|string|max:1000',
            'evidence_photos' => 'nullable|array',
            'evidence_photos.*' => 'image|mimes:jpeg,jpg,png|max:2048',
            'location_data' => 'nullable|array',
            'location_data.latitude' => 'numeric|between:-90,90',
            'location_data.longitude' => 'numeric|between:-180,180'
        ]);

        try {
            $user = $request->user();

            if (!$this->canRateWorkflow($user, $workflow)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to rate this workflow'
                ], 403);
            }

            $ratingData = array_merge($validated, [
                'user_id' => $user->id,
                'workflow_id' => $workflow->id,
                'device_platform' => $request->header('User-Agent'),
                'submission_source' => 'mobile_api'
            ]);

            $result = $this->qetRatingService->submitMobileRatings($ratingData);

            // Update workflow state if needed
            if ($result['advances_workflow']) {
                $this->opcrWorkflowService->advanceWorkflowState($workflow, 'ratings_submitted');
            }

            return response()->json([
                'success' => true,
                'message' => 'Ratings submitted successfully',
                'data' => [
                    'rating_id' => $result['rating_id'],
                    'adjectival_rating' => $result['adjectival_rating'],
                    'workflow_status' => $workflow->fresh()->current_state
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Mobile rating submission failed', [
                'user_id' => $request->user()->id,
                'workflow_id' => $workflow->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit ratings'
            ], 500);
        }
    }

    /**
     * Get workflow actions history
     */
    public function workflowHistory(OPCRWorkflow $workflow, Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$this->canAccessWorkflow($user, $workflow)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $history = $this->opcrWorkflowService->getWorkflowHistory($workflow);

            return response()->json([
                'success' => true,
                'data' => [
                    'history' => $history,
                    'workflow_id' => $workflow->id,
                    'current_state' => $workflow->current_state
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch workflow history'
            ], 500);
        }
    }

    /**
     * Get available performance periods
     */
    public function periods(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:active,inactive,upcoming,closed',
            'office_id' => 'nullable|exists:offices,id'
        ]);

        try {
            $user = $request->user();
            $periods = $this->opcrManagementService->getAvailablePeriods($user, $validated);

            return response()->json([
                'success' => true,
                'data' => [
                    'periods' => $periods,
                    'default_period' => $periods->where('status', 'active')->first()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch periods'
            ], 500);
        }
    }

    /**
     * Get office information
     */
    public function offices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $offices = $this->opcrManagementService->getUserAccessibleOffices($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'offices' => $offices,
                    'user_office_assignments' => $user->officeAssignments()->with('office')->get()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch offices'
            ], 500);
        }
    }

    /**
     * Get app configuration and settings
     */
    public function configuration(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $config = [
                'app_version' => config('app.version', '1.0.0'),
                'api_version' => 'v1',
                'features' => [
                    'qet_ratings' => config('opcr.features.qet_ratings', true),
                    'mobile_photos' => config('opcr.features.mobile_photos', true),
                    'location_tracking' => config('opcr.features.location_tracking', false),
                    'offline_mode' => config('opcr.features.offline_mode', true)
                ],
                'rating_scales' => $this->qetRatingService->getMobileRatingScales(),
                'workflow_stages' => $this->opcrWorkflowService->getMobileWorkflowStages(),
                'user_permissions' => $user->getAllPermissions()->pluck('name'),
                'maintenance_mode' => config('app.maintenance_mode', false),
                'rate_limits' => [
                    'requests_per_minute' => 60,
                    'requests_per_hour' => 1000
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $config
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch configuration'
            ], 500);
        }
    }

    /**
     * Build hierarchical path for MFO display.
     */
    private function buildMfoPath(MajorFinalOutput $mfo, Collection $index, string $attribute, string $separator): string
    {
        $segments = [];
        $current = $mfo;

        while ($current) {
            $value = $current->{$attribute} ?? null;

            if ($value !== null && $value !== '') {
                array_unshift($segments, $value);
            }

            $current = $current->parent_id ? $index->get($current->parent_id) : null;
        }

        return implode($separator, $segments);
    }

    /**
     * Determine if the authenticated user can access the office data.
     */
    private function userCanAccessOffice($user, Office $office): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return true;
        }

        return $user->officeAssignments()
            ->where('office_id', $office->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                    ->orWhere('ended_date', '>=', now());
            })
            ->exists();
    }

    /**
     * Sync offline data
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sync_type' => 'required|in:full,incremental',
            'last_sync_timestamp' => 'nullable|date',
            'data_types' => 'array',
            'data_types.*' => 'in:workflows,ratings,periods,offices'
        ]);

        try {
            $user = $request->user();

            $syncData = $this->opcrManagementService->getSyncData($user, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Sync data prepared',
                'data' => [
                    'sync_timestamp' => now()->toISOString(),
                    'data' => $syncData,
                    'requires_full_sync' => $validated['sync_type'] === 'full'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Mobile sync failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync failed'
            ], 500);
        }
    }

    /**
     * Helper method to get user's office ID
     */
    private function getUserOfficeId($user): ?int
    {
        $officeAssignment = $user->officeAssignments()->first();
        return $officeAssignment ? $officeAssignment->office_id : null;
    }

    /**
     * Check if user can access specific workflow
     */
    private function canAccessWorkflow($user, OPCRWorkflow $workflow): bool
    {
        // Super admins can access all workflows
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // HR admins can access all workflows
        if ($user->hasRole('HR Admin')) {
            return true;
        }

        // Department heads can access workflows from their office
        if ($user->hasRole('Department Head')) {
            $userOfficeId = $this->getUserOfficeId($user);
            return $workflow->office_id === $userOfficeId;
        }

        // Assessors can access workflows assigned to them
        if ($user->hasRole('Assessor')) {
            return $workflow->assessor_id === $user->id;
        }

        // Final approvers can access workflows ready for final approval
        if ($user->hasRole('Final Approver')) {
            return in_array($workflow->current_state, ['final_approval', 'ready_for_approval']);
        }

        // Check if user created the workflow
        return $workflow->created_by === $user->id;
    }

    /**
     * Check if user can rate specific workflow
     */
    private function canRateWorkflow($user, OPCRWorkflow $workflow): bool
    {
        if (!$this->canAccessWorkflow($user, $workflow)) {
            return false;
        }

        // Department heads can rate workflows in their office
        if ($user->hasRole('Department Head')) {
            $userOfficeId = $this->getUserOfficeId($user);
            return $workflow->office_id === $userOfficeId &&
                   in_array($workflow->current_state, ['committed', 'in_progress']);
        }

        // Assessors can rate workflows assigned to them
        if ($user->hasRole('Assessor')) {
            return $workflow->assessor_id === $user->id &&
                   $workflow->current_state === 'evaluation';
        }

        return false;
    }

    /**
     * Get available actions for user
     */
    private function getUserAvailableActions($user): array
    {
        $actions = [];

        if ($user->can('opcr.create')) {
            $actions[] = 'create_workflow';
        }

        if ($user->can('opcr.rate')) {
            $actions[] = 'submit_ratings';
        }

        if ($user->can('opcr.assess')) {
            $actions[] = 'assess_workflow';
        }

        if ($user->can('opcr.approve')) {
            $actions[] = 'approve_workflow';
        }

        return $actions;
    }

    /**
     * Get available actions for specific workflow
     */
    private function getWorkflowUserActions($user, OPCRWorkflow $workflow): array
    {
        $actions = [];

        if (!$this->canAccessWorkflow($user, $workflow)) {
            return $actions;
        }

        switch ($workflow->current_state) {
            case 'draft':
                if ($user->can('opcr.create') && $workflow->created_by === $user->id) {
                    $actions[] = 'edit';
                    $actions[] = 'submit';
                }
                break;

            case 'committed':
                if ($user->can('opcr.rate') && $this->canRateWorkflow($user, $workflow)) {
                    $actions[] = 'rate';
                }
                break;

            case 'evaluation':
                if ($user->can('opcr.assess') && $this->canRateWorkflow($user, $workflow)) {
                    $actions[] = 'assess';
                }
                break;

            case 'final_approval':
                if ($user->can('opcr.approve')) {
                    $actions[] = 'approve';
                    $actions[] = 'return';
                }
                break;
        }

        if ($user->can('opcr.view')) {
            $actions[] = 'view';
        }

        return $actions;
    }
}
