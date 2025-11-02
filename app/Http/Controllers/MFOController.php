<?php

namespace App\Http\Controllers;

use App\Models\MajorFinalOutput;
use App\Models\Office;
use App\Services\MFOHierarchyService;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MFOController extends Controller
{
    private MFOHierarchyService $mfoService;
    private AuditTrailService $auditTrailService;

    public function __construct(
        MFOHierarchyService $mfoService,
        AuditTrailService $auditTrailService
    ) {
        $this->mfoService = $mfoService;
        $this->auditTrailService = $auditTrailService;

        // Apply authentication and authorization middleware
        $this->middleware(['auth']);
        $this->middleware('permission:mfo.view')->only(['index', 'show', 'hierarchy']);
        $this->middleware('permission:mfo.create')->only(['create', 'store', 'generateCode']);
        $this->middleware('permission:mfo.edit')->only(['edit', 'update']);
        $this->middleware('permission:mfo.delete')->only(['destroy']);
    }

    /**
     * Display a listing of Major Final Outputs
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = MajorFinalOutput::with(['office', 'successIndicators']);

        // Filter based on user's access level
        if (!$user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            $accessibleOfficeIds = $this->getUserAccessibleOffices($user)->pluck('id');
            $query->whereIn('office_id', $accessibleOfficeIds);
        }

        // Apply filters
        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
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

        $mfos = $query->orderBy('office_id')
            ->orderBy('code')
            ->paginate(15);

        $offices = $this->getUserAccessibleOffices($user);

        return view('admin.opcr.mfos.index', [
            'mfos' => $mfos,
            'offices' => $offices,
            'filters' => $request->only(['office_id', 'is_active', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new Major Final Output
     */
    public function create(): View
    {
        $user = Auth::user();
        $offices = $this->getUserManageableOffices($user);

        if ($offices->isEmpty()) {
            return view('mfo.no-access', [
                'message' => 'You are not assigned to manage any office. Please contact your administrator.'
            ]);
        }

        return view('mfo.create', [
            'offices' => $offices,
            'mfoCode' => $this->generateInitialMFOCode($offices->first()),
        ]);
    }

    /**
     * Store a newly created Major Final Output
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'office_id' => 'required|exists:offices,id',
            'code' => 'required|string|max:20|unique:major_final_outputs,code,NULL,id,office_id,' . $request->office_id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'required|integer|min:1|max:5',
            'parent_id' => 'nullable|exists:major_final_outputs,id',
            'is_active' => 'boolean',
        ]);

        $office = Office::find($validated['office_id']);
        $this->authorizeOfficeAccess($office);

        try {
            DB::beginTransaction();

            // Validate parent MFO belongs to same office
            if (!empty($validated['parent_id'])) {
                $parentMFO = MajorFinalOutput::find($validated['parent_id']);
                if ($parentMFO && $parentMFO->office_id !== $validated['office_id']) {
                    throw new \InvalidArgumentException('Parent MFO must belong to the same office.');
                }
            }

            $validated['created_by'] = Auth::id();
            $validated['is_active'] = $validated['is_active'] ?? true;

            $mfo = $this->mfoService->createMFO(
                $office,
                $validated
            );

            DB::commit();

            Log::info('MFO created', [
                'mfo_id' => $mfo->id,
                'user_id' => Auth::id(),
                'office_id' => $mfo->office_id,
                'mfo_code' => $mfo->code,
            ]);

            return redirect()
                ->route('opcr.mfos.show', $mfo)
                ->with('success', 'Major Final Output created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create MFO', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'validated_data' => $validated,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create Major Final Output: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified Major Final Output
     */
    public function show(MajorFinalOutput $mfo): View
    {
        $this->authorizeMFOAccess($mfo);

        $mfo->load([
            'office',
            'parent',
            'children',
            'successIndicators' => function ($query) {
                $query->orderBy('code');
            },
            'successIndicators.ratings',
        ]);

        $userRole = $this->getUserMFORole(Auth::user(), $mfo);
        $canEdit = $this->canEditMFO($mfo, $userRole);
        $canDelete = $this->canDeleteMFO($mfo, $userRole);

        return view('mfo.show', [
            'mfo' => $mfo,
            'userRole' => $userRole,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
            'mfoHierarchy' => $this->mfoService->getMFOHierarchy($mfo->office),
            'performanceStats' => $this->getMFOPerformanceStats($mfo),
        ]);
    }

    /**
     * Show the form for editing the specified Major Final Output
     */
    public function edit(MajorFinalOutput $mfo): View
    {
        $this->authorizeMFOAccess($mfo);

        if (!$this->canEditMFO($mfo, $this->getUserMFORole(Auth::user(), $mfo))) {
            abort(403, 'You cannot edit this Major Final Output.');
        }

        $user = Auth::user();
        $offices = $this->getUserManageableOffices($user);
        $parentMFOs = $this->getAvailableParentMFOs($mfo);

        return view('mfo.edit', [
            'mfo' => $mfo,
            'offices' => $offices,
            'parentMFOs' => $parentMFOs,
        ]);
    }

    /**
     * Update the specified Major Final Output
     */
    public function update(Request $request, MajorFinalOutput $mfo): RedirectResponse
    {
        $this->authorizeMFOAccess($mfo);

        if (!$this->canEditMFO($mfo, $this->getUserMFORole(Auth::user(), $mfo))) {
            abort(403, 'You cannot edit this Major Final Output.');
        }

        $validated = $request->validate([
            'office_id' => 'required|exists:offices,id',
            'code' => 'required|string|max:20|unique:major_final_outputs,code,' . $mfo->id . ',id,office_id,' . $request->office_id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'required|integer|min:1|max:5',
            'parent_id' => 'nullable|exists:major_final_outputs,id',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // Validate parent MFO belongs to same office and doesn't create circular reference
            if (!empty($validated['parent_id'])) {
                $parentMFO = MajorFinalOutput::find($validated['parent_id']);
                if ($parentMFO) {
                    if ($parentMFO->office_id !== $validated['office_id']) {
                        throw new \InvalidArgumentException('Parent MFO must belong to the same office.');
                    }
                    if ($this->wouldCreateCircularReference($mfo, $parentMFO)) {
                        throw new \InvalidArgumentException('This would create a circular reference in MFO hierarchy.');
                    }
                }
            }

            $validated['updated_by'] = Auth::id();
            $validated['is_active'] = $validated['is_active'] ?? $mfo->is_active;

            $mfo->update($validated);

            DB::commit();

            Log::info('MFO updated', [
                'mfo_id' => $mfo->id,
                'user_id' => Auth::id(),
                'changes' => array_diff_assoc($validated, $mfo->getOriginal()),
            ]);

            return redirect()
                ->route('opcr.mfos.show', $mfo)
                ->with('success', 'Major Final Output updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update MFO', [
                'mfo_id' => $mfo->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update Major Final Output: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified Major Final Output
     */
    public function destroy(MajorFinalOutput $mfo): RedirectResponse
    {
        $this->authorizeMFOAccess($mfo);

        if (!$this->canDeleteMFO($mfo, $this->getUserMFORole(Auth::user(), $mfo))) {
            abort(403, 'You cannot delete this Major Final Output.');
        }

        try {
            DB::beginTransaction();

            // Check if MFO has associated performance targets
            $hasTargets = $mfo->performanceTargets()->exists();
            if ($hasTargets) {
                throw new \InvalidArgumentException('Cannot delete MFO with associated performance targets.');
            }

            // Check if MFO has child MFOs
            $hasChildren = $mfo->children()->exists();
            if ($hasChildren) {
                throw new \InvalidArgumentException('Cannot delete MFO with child Major Final Outputs.');
            }

            $mfoId = $mfo->id;
            $mfo->delete();

            DB::commit();

            Log::info('MFO deleted', [
                'mfo_id' => $mfoId,
                'user_id' => Auth::id(),
            ]);

            return redirect()
                ->route('opcr.mfos.index')
                ->with('success', 'Major Final Output deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete MFO', [
                'mfo_id' => $mfo->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to delete Major Final Output: ' . $e->getMessage());
        }
    }

    /**
     * Generate MFO code for office
     */
    public function generateCode(Request $request): \Illuminate\Http\JsonResponse
    {
        $officeId = $request->get('office_id');
        $office = Office::find($officeId);

        if (!$office) {
            return response()->json(['error' => 'Office not found'], 404);
        }

        $this->authorizeOfficeAccess($office);

        $code = $this->mfoService->generateMFOCode($office);

        return response()->json(['code' => $code]);
    }

    /**
     * Get MFO hierarchy tree
     */
    public function hierarchy(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $officeId = $request->get('office_id');

        if (!$user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            $accessibleOfficeIds = $this->getUserAccessibleOffices($user)->pluck('id');
            if ($officeId && !in_array($officeId, $accessibleOfficeIds->toArray())) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $mfos = MajorFinalOutput::with(['children', 'successIndicators'])
            ->when($officeId, function ($query, $officeId) {
                $query->where('office_id', $officeId);
            })
            ->when(!$user->hasAnyRole(['Super Admin', 'HR Admin']), function ($query) use ($user) {
                $accessibleOfficeIds = $this->getUserAccessibleOffices($user)->pluck('id');
                $query->whereIn('office_id', $accessibleOfficeIds);
            })
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $hierarchy = $this->buildHierarchyTree($mfos);

        return response()->json(['hierarchy' => $hierarchy]);
    }

    /**
     * Get user's accessible offices
     */
    private function getUserAccessibleOffices($user): \Illuminate\Database\Eloquent\Collection
    {
        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return Office::where('is_active', true)->orderBy('name')->get();
        }

        return Office::whereHas('officeAssignments', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Get user's manageable offices
     */
    private function getUserManageableOffices($user): \Illuminate\Database\Eloquent\Collection
    {
        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return Office::where('is_active', true)->orderBy('name')->get();
        }

        return Office::whereHas('officeAssignments', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->whereIn('role', ['Department Head', 'HR Admin']);
        })->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Get user's MFO role for a specific MFO
     */
    private function getUserMFORole($user, MajorFinalOutput $mfo): string
    {
        if ($user->hasRole('Super Admin')) {
            return 'Super Admin';
        }

        if ($user->hasRole('HR Admin')) {
            return 'HR Admin';
        }

        $officeAssignment = $user->officeAssignments()
            ->where('office_id', $mfo->office_id)
            ->first();

        if ($officeAssignment) {
            return $officeAssignment->role;
        }

        return 'Employee';
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
     * Authorize office access
     */
    private function authorizeOfficeAccess(Office $office): void
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return;
        }

        $hasAccess = $user->officeAssignments()
            ->where('office_id', $office->id)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'You do not have permission to access this office.');
        }
    }

    /**
     * Check if user can edit MFO
     */
    private function canEditMFO(MajorFinalOutput $mfo, string $userRole): bool
    {
        return in_array($userRole, ['Super Admin', 'HR Admin', 'Department Head']);
    }

    /**
     * Check if user can delete MFO
     */
    private function canDeleteMFO(MajorFinalOutput $mfo, string $userRole): bool
    {
        return in_array($userRole, ['Super Admin', 'HR Admin']);
    }

    /**
     * Generate initial MFO code
     */
    private function generateInitialMFOCode(Office $office): string
    {
        return $this->mfoService->generateMFOCode($office);
    }

    /**
     * Get available parent MFOs (excluding current MFO and its descendants)
     */
    private function getAvailableParentMFOs(MajorFinalOutput $mfo): \Illuminate\Database\Eloquent\Collection
    {
        $descendantIds = $this->mfoService->getAllDescendantIds($mfo);

        return MajorFinalOutput::where('office_id', $mfo->office_id)
            ->where('id', '!=', $mfo->id)
            ->whereNotIn('id', $descendantIds)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    /**
     * Check if parent assignment would create circular reference
     */
    private function wouldCreateCircularReference(MajorFinalOutput $mfo, MajorFinalOutput $parent): bool
    {
        return in_array($parent->id, $this->mfoService->getAllDescendantIds($mfo));
    }

    /**
     * Build hierarchy tree for JSON response
     */
    private function buildHierarchyTree($mfos): array
    {
        $tree = [];
        foreach ($mfos as $mfo) {
            $tree[] = [
                'id' => $mfo->id,
                'code' => $mfo->code,
                'title' => $mfo->title,
                'level' => $mfo->level,
                'success_indicators_count' => $mfo->successIndicators->count(),
                'children' => $this->buildHierarchyTree($mfo->children),
            ];
        }
        return $tree;
    }

    /**
     * Get MFO performance statistics
     */
    private function getMFOPerformanceStats(MajorFinalOutput $mfo): array
    {
        $indicators = $mfo->successIndicators()->with('ratings')->get();
        $totalIndicators = $indicators->count();
        $ratedIndicators = $indicators->filter(function ($indicator) {
            return $indicator->ratings->isNotEmpty();
        })->count();

        $averageRating = $indicators->flatMap->ratings->avg('average_rating') ?? 0;
        $metTargets = $indicators->where('is_target_met', true)->count();

        return [
            'total_indicators' => $totalIndicators,
            'rated_indicators' => $ratedIndicators,
            'rating_completion_rate' => $totalIndicators > 0 ? round(($ratedIndicators / $totalIndicators) * 100, 2) : 0,
            'average_rating' => round($averageRating, 2),
            'met_targets' => $metTargets,
            'target_completion_rate' => $totalIndicators > 0 ? round(($metTargets / $totalIndicators) * 100, 2) : 0,
        ];
    }
}
