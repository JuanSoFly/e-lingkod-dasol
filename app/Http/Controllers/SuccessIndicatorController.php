<?php

namespace App\Http\Controllers;

use App\Models\SuccessIndicator;
use App\Models\MajorFinalOutput;
use App\Services\MFOHierarchyService;
use App\Services\QETRatingCalculationService;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SuccessIndicatorController extends Controller
{
    private MFOHierarchyService $mfoService;
    private QETRatingCalculationService $ratingService;
    private AuditTrailService $auditTrailService;

    public function __construct(
        MFOHierarchyService $mfoService,
        QETRatingCalculationService $ratingService,
        AuditTrailService $auditTrailService
    ) {
        $this->mfoService = $mfoService;
        $this->ratingService = $ratingService;
        $this->auditTrailService = $auditTrailService;

        // Apply authentication and authorization middleware
        $this->middleware(['auth']);
        $this->middleware('permission:si.view')->only(['index', 'show']);
        $this->middleware('permission:si.create')->only(['create', 'store', 'generateCode']);
        $this->middleware('permission:si.edit')->only(['edit', 'update', 'updateAccomplishments']);
        $this->middleware('permission:si.delete')->only(['destroy']);
    }

    /**
     * Display a listing of Success Indicators
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = SuccessIndicator::with(['mfo.office', 'ratings']);

        // Filter based on user's access level
        if (!$user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            $accessibleOfficeIds = $this->getUserAccessibleOffices($user)->pluck('id');
            $query->whereHas('mfo', function ($subQuery) use ($accessibleOfficeIds) {
                $subQuery->whereIn('office_id', $accessibleOfficeIds);
            });
        }

        // Apply filters
        if ($request->filled('mfo_id')) {
            $query->where('mfo_id', $request->mfo_id);
        }

        if ($request->filled('office_id')) {
            $query->whereHas('mfo', function ($subQuery) use ($request) {
                $subQuery->where('office_id', $request->office_id);
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $indicators = $query->orderBy('mfo_id')
            ->orderBy('code')
            ->paginate(15);

        $offices = $this->getUserAccessibleOffices($user);
        $mfos = $this->getAccessibleMFOs($user);

        return view('success-indicators.index', [
            'indicators' => $indicators,
            'offices' => $offices,
            'mfos' => $mfos,
            'filters' => $request->only(['mfo_id', 'office_id', 'is_active', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new Success Indicator
     */
    public function create(Request $request): View
    {
        $user = Auth::user();
        $mfoId = $request->get('mfo_id');
        $mfos = $this->getManageableMFOs($user);

        if ($mfos->isEmpty()) {
            return view('success-indicators.no-access', [
                'message' => 'You are not assigned to manage any Major Final Outputs. Please contact your administrator.'
            ]);
        }

        return view('success-indicators.create', [
            'mfos' => $mfos,
            'selectedMFO' => $mfoId,
            'indicatorCode' => $mfoId ? $this->generateInitialSICode($mfoId) : '',
        ]);
    }

    /**
     * Store a newly created Success Indicator
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mfo_id' => 'required|exists:major_final_outputs,id',
            'code' => 'required|string|max:30|unique:success_indicators,code,NULL,id,mfo_id,' . $request->mfo_id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_quantity' => 'nullable|numeric|min:0',
            'target_efficiency' => 'nullable|string|max:100',
            'target_timeliness' => 'nullable|string|max:100',
            'measurement_unit' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $this->authorizeMFOAccess(MajorFinalOutput::find($validated['mfo_id']));

            $validated['created_by'] = Auth::id();
            $validated['is_active'] = $validated['is_active'] ?? true;

            $indicator = SuccessIndicator::create($validated);

            DB::commit();

            Log::info('Success Indicator created', [
                'indicator_id' => $indicator->id,
                'user_id' => Auth::id(),
                'mfo_id' => $indicator->mfo_id,
                'indicator_code' => $indicator->code,
            ]);

            return redirect()
                ->route('success-indicators.show', $indicator)
                ->with('success', 'Success Indicator created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create Success Indicator', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'validated_data' => $validated,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create Success Indicator: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified Success Indicator
     */
    public function show(SuccessIndicator $indicator): View
    {
        $this->authorizeIndicatorAccess($indicator);

        $indicator->load([
            'mfo.office',
            'ratings' => function ($query) {
                $query->with(['ratedBy', 'performanceTarget.employee', 'performancePeriod'])
                      ->orderBy('created_at', 'desc');
            },
            'performanceTargets.employee',
        ]);

        $userRole = $this->getUserIndicatorRole(Auth::user(), $indicator);
        $canEdit = $this->canEditIndicator($indicator, $userRole);
        $canDelete = $this->canDeleteIndicator($indicator, $userRole);
        $canRate = $this->canRateIndicator($indicator, $userRole);

        return view('success-indicators.show', [
            'indicator' => $indicator,
            'userRole' => $userRole,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
            'canRate' => $canRate,
            'performanceStats' => $this->getIndicatorPerformanceStats($indicator),
            'recentRatings' => $indicator->ratings->take(5),
        ]);
    }

    /**
     * Show the form for editing the specified Success Indicator
     */
    public function edit(SuccessIndicator $indicator): View
    {
        $this->authorizeIndicatorAccess($indicator);

        if (!$this->canEditIndicator($indicator, $this->getUserIndicatorRole(Auth::user(), $indicator))) {
            abort(403, 'You cannot edit this Success Indicator.');
        }

        $user = Auth::user();
        $mfos = $this->getManageableMFOs($user);

        return view('success-indicators.edit', [
            'indicator' => $indicator,
            'mfos' => $mfos,
        ]);
    }

    /**
     * Update the specified Success Indicator
     */
    public function update(Request $request, SuccessIndicator $indicator): RedirectResponse
    {
        $this->authorizeIndicatorAccess($indicator);

        if (!$this->canEditIndicator($indicator, $this->getUserIndicatorRole(Auth::user(), $indicator))) {
            abort(403, 'You cannot edit this Success Indicator.');
        }

        $validated = $request->validate([
            'mfo_id' => 'required|exists:major_final_outputs,id',
            'code' => 'required|string|max:30|unique:success_indicators,code,' . $indicator->id . ',id,mfo_id,' . $request->mfo_id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_quantity' => 'nullable|numeric|min:0',
            'target_efficiency' => 'nullable|string|max:100',
            'target_timeliness' => 'nullable|string|max:100',
            'measurement_unit' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $this->authorizeMFOAccess(MajorFinalOutput::find($validated['mfo_id']));

            $validated['updated_by'] = Auth::id();
            $validated['is_active'] = $validated['is_active'] ?? $indicator->is_active;

            $indicator->update($validated);

            DB::commit();

            Log::info('Success Indicator updated', [
                'indicator_id' => $indicator->id,
                'user_id' => Auth::id(),
                'changes' => array_diff_assoc($validated, $indicator->getOriginal()),
            ]);

            return redirect()
                ->route('success-indicators.show', $indicator)
                ->with('success', 'Success Indicator updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update Success Indicator', [
                'indicator_id' => $indicator->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update Success Indicator: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified Success Indicator
     */
    public function destroy(SuccessIndicator $indicator): RedirectResponse
    {
        $this->authorizeIndicatorAccess($indicator);

        if (!$this->canDeleteIndicator($indicator, $this->getUserIndicatorRole(Auth::user(), $indicator))) {
            abort(403, 'You cannot delete this Success Indicator.');
        }

        try {
            DB::beginTransaction();

            // Check if indicator has associated performance targets
            $hasTargets = $indicator->performanceTargets()->exists();
            if ($hasTargets) {
                throw new \InvalidArgumentException('Cannot delete Success Indicator with associated performance targets.');
            }

            $indicatorId = $indicator->id;
            $indicator->delete();

            DB::commit();

            Log::info('Success Indicator deleted', [
                'indicator_id' => $indicatorId,
                'user_id' => Auth::id(),
            ]);

            return redirect()
                ->route('success-indicators.index')
                ->with('success', 'Success Indicator deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete Success Indicator', [
                'indicator_id' => $indicator->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to delete Success Indicator: ' . $e->getMessage());
        }
    }

    /**
     * Update indicator accomplishments
     */
    public function updateAccomplishments(Request $request, SuccessIndicator $indicator): RedirectResponse
    {
        $this->authorizeIndicatorAccess($indicator);

        if (!$this->canUpdateAccomplishments($indicator, $this->getUserIndicatorRole(Auth::user(), $indicator))) {
            abort(403, 'You cannot update accomplishments for this Success Indicator.');
        }

        $validated = $request->validate([
            'accomplished_quantity' => 'nullable|numeric|min:0',
            'accomplished_efficiency' => 'nullable|string|max:100',
            'accomplished_timeliness' => 'nullable|string|max:100',
            'accomplishment_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $validated['updated_by'] = Auth::id();

            // Calculate performance percentage and target status
            $performanceData = $this->ratingService->calculatePerformance(
                $indicator,
                $validated
            );

            $indicator->update(array_merge($validated, $performanceData));

            DB::commit();

            Log::info('Success Indicator accomplishments updated', [
                'indicator_id' => $indicator->id,
                'user_id' => Auth::id(),
                'performance_percentage' => $indicator->performance_percentage,
                'is_target_met' => $indicator->is_target_met,
            ]);

            return redirect()
                ->back()
                ->with('success', 'Accomplishments updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update accomplishments', [
                'indicator_id' => $indicator->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update accomplishments: ' . $e->getMessage());
        }
    }

    /**
     * Generate Success Indicator code for MFO
     */
    public function generateCode(Request $request): \Illuminate\Http\JsonResponse
    {
        $mfoId = $request->get('mfo_id');
        $mfo = MajorFinalOutput::find($mfoId);

        if (!$mfo) {
            return response()->json(['error' => 'Major Final Output not found'], 404);
        }

        $this->authorizeMFOAccess($mfo);

        $code = $this->mfoService->generateSuccessIndicatorCode($mfo);

        return response()->json(['code' => $code]);
    }

    /**
     * Get user's accessible offices
     */
    private function getUserAccessibleOffices($user): \Illuminate\Database\Eloquent\Collection
    {
        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return \App\Models\Office::where('is_active', true)->orderBy('name')->get();
        }

        return \App\Models\Office::whereHas('officeAssignments', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Get accessible MFOs
     */
    private function getAccessibleMFOs($user): \Illuminate\Database\Eloquent\Collection
    {
        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return MajorFinalOutput::with('office')
                ->where('is_active', true)
                ->orderBy('office_id')
                ->orderBy('code')
                ->get();
        }

        $accessibleOfficeIds = $this->getUserAccessibleOffices($user)->pluck('id');

        return MajorFinalOutput::with('office')
            ->whereIn('office_id', $accessibleOfficeIds)
            ->where('is_active', true)
            ->orderBy('office_id')
            ->orderBy('code')
            ->get();
    }

    /**
     * Get manageable MFOs
     */
    private function getManageableMFOs($user): \Illuminate\Database\Eloquent\Collection
    {
        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return MajorFinalOutput::with('office')
                ->where('is_active', true)
                ->orderBy('office_id')
                ->orderBy('code')
                ->get();
        }

        $accessibleOfficeIds = $this->getUserAccessibleOffices($user)->pluck('id');

        return MajorFinalOutput::with('office')
            ->whereIn('office_id', $accessibleOfficeIds)
            ->where('is_active', true)
            ->orderBy('office_id')
            ->orderBy('code')
            ->get();
    }

    /**
     * Get user's Success Indicator role for a specific indicator
     */
    private function getUserIndicatorRole($user, SuccessIndicator $indicator): string
    {
        if ($user->hasRole('Super Admin')) {
            return 'Super Admin';
        }

        if ($user->hasRole('HR Admin')) {
            return 'HR Admin';
        }

        $officeAssignment = $user->officeAssignments()
            ->where('office_id', $indicator->mfo->office_id)
            ->first();

        if ($officeAssignment) {
            return $officeAssignment->role;
        }

        return 'Employee';
    }

    /**
     * Authorize Success Indicator access
     */
    private function authorizeIndicatorAccess(SuccessIndicator $indicator): void
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return;
        }

        $hasAccess = $user->officeAssignments()
            ->where('office_id', $indicator->mfo->office_id)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'You do not have permission to access this Success Indicator.');
        }
    }

    /**
     * Authorize MFO access
     */
    private function authorizeMFOAccess(MajorFinalOutput $mfo): void
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return;
        }

        $hasAccess = $user->officeAssignments()
            ->where('office_id', $mfo->office_id)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'You do not have permission to access this Major Final Output.');
        }
    }

    /**
     * Check if user can edit Success Indicator
     */
    private function canEditIndicator(SuccessIndicator $indicator, string $userRole): bool
    {
        return in_array($userRole, ['Super Admin', 'HR Admin', 'Department Head']);
    }

    /**
     * Check if user can delete Success Indicator
     */
    private function canDeleteIndicator(SuccessIndicator $indicator, string $userRole): bool
    {
        return in_array($userRole, ['Super Admin', 'HR Admin']);
    }

    /**
     * Check if user can rate Success Indicator
     */
    private function canRateIndicator(SuccessIndicator $indicator, string $userRole): bool
    {
        return in_array($userRole, ['Super Admin', 'HR Admin', 'Assessor']);
    }

    /**
     * Check if user can update accomplishments
     */
    private function canUpdateAccomplishments(SuccessIndicator $indicator, string $userRole): bool
    {
        return in_array($userRole, ['Super Admin', 'HR Admin', 'Department Head']);
    }

    /**
     * Generate initial Success Indicator code
     */
    private function generateInitialSICode(int $mfoId): string
    {
        $mfo = MajorFinalOutput::find($mfoId);
        return $this->mfoService->generateSuccessIndicatorCode($mfo);
    }

    /**
     * Get Success Indicator performance statistics
     */
    private function getIndicatorPerformanceStats(SuccessIndicator $indicator): array
    {
        $ratings = $indicator->ratings;
        $totalRatings = $ratings->count();
        $averageRating = $ratings->avg('average_rating') ?? 0;

        $targets = $indicator->performanceTargets;
        $totalTargets = $targets->count();
        $metTargets = $targets->where('is_target_met', true)->count();

        return [
            'total_ratings' => $totalRatings,
            'average_rating' => round($averageRating, 2),
            'rating_distribution' => $this->getRatingDistribution($ratings),
            'total_targets' => $totalTargets,
            'met_targets' => $metTargets,
            'target_completion_rate' => $totalTargets > 0 ? round(($metTargets / $totalTargets) * 100, 2) : 0,
            'current_performance' => $indicator->performance_percentage,
            'target_status' => $indicator->is_target_met,
            'last_accomplishment_update' => $indicator->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get rating distribution for statistics
     */
    private function getRatingDistribution($ratings): array
    {
        $distribution = [
            '5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0
        ];

        foreach ($ratings as $rating) {
            $ratingValue = (int) round($rating->average_rating);
            if (isset($distribution[$ratingValue])) {
                $distribution[$ratingValue]++;
            }
        }

        return $distribution;
    }
}
