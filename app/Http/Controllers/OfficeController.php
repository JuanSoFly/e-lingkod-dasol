<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Employee;
use App\Models\MajorFinalOutput;
use App\Services\MFOHierarchyService;
use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\Activity;

class OfficeController extends Controller
{
    private MFOHierarchyService $mfoHierarchyService;

    public function __construct(MFOHierarchyService $mfoHierarchyService)
    {
        $this->mfoHierarchyService = $mfoHierarchyService;

        $this->middleware('auth');
        $this->middleware('permission:office.view')->only(['index', 'show', 'hierarchy', 'tree']);
        $this->middleware('permission:office.create')->only(['create', 'store']);
        $this->middleware('permission:office.edit')->only(['edit', 'update', 'toggle']);
        $this->middleware('permission:office.delete')->only(['destroy']);
    }

    /**
     * Display a listing of offices
     */
    public function index(Request $request): View
    {
        $query = Office::with(['parent', 'departmentHead', 'children'])
            ->withCount(['employees', 'activeMajorFinalOutputs', 'opcrWorkflows']);

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('level')) {
            $query->byLevel($request->level);
        }

        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $offices = $query->orderBy('level')
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        // Get filter options
        $levels = Office::select('level')->distinct()->orderBy('level')->pluck('level');
        $parentOffices = Office::where('is_active', true)->orderBy('name')->get();

        // Get statistics
        $stats = [
            'total_offices' => Office::count(),
            'active_offices' => Office::where('is_active', true)->count(),
            'root_offices' => Office::whereNull('parent_id')->count(),
            'total_employees' => Employee::count(),
        ];

        return view('admin.offices.index', compact(
            'offices',
            'levels',
            'parentOffices',
            'stats'
        ));
    }

    /**
     * Show the office hierarchy tree
     */
    public function hierarchy(): View
    {
        $hierarchy = Office::getHierarchyTree();
        $flatList = Office::getFlatList();

        return view('admin.offices.hierarchy', compact('hierarchy', 'flatList'));
    }

    /**
     * Get office hierarchy as JSON (for AJAX/Select2)
     */
    public function tree(Request $request): JsonResponse
    {
        $query = Office::where('is_active', true);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $offices = $query->orderBy('level')
            ->orderBy('code')
            ->get()
            ->map(function ($office) {
                return [
                    'id' => $office->id,
                    'text' => $office->full_path,
                    'code' => $office->code,
                    'name' => $office->name,
                    'level' => $office->level,
                    'parent_id' => $office->parent_id,
                    'has_children' => $office->hasChildren(),
                ];
            });

        return response()->json([
            'results' => $offices,
            'pagination' => [
                'more' => false,
            ],
        ]);
    }

    /**
     * Show the form for creating a new office
     */
    public function create(): View
    {
        $parentOffices = Office::where('is_active', true)
            ->orderBy('full_path')
            ->get();

        $employees = Employee::whereNull('deleted_at')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('admin.offices.create', compact('parentOffices', 'employees'));
    }

    /**
     * Store a newly created office
     */
    public function store(StoreOfficeRequest $request): RedirectResponse
    {
        try {
            $office = Office::createWithAutoCode($request->validated());

            Activity::log('Office created', [
                'office_id' => $office->id,
                'office_name' => $office->name,
                'created_by' => Auth::id(),
            ]);

            return redirect()
                ->route('offices.show', $office)
                ->with('success', 'Office created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create office: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified office
     */
    public function show(Office $office): View
    {
        $office->load([
            'parent',
            'children' => function ($query) {
                $query->where('is_active', true);
            },
            'departmentHead',
            'activeMajorFinalOutputs.activeSuccessIndicators',
            'activeAssignments.user',
            'opcrWorkflows' => function ($query) {
                $query->with(['period', 'committedBy', 'assessedBy', 'approvedBy'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10);
            }
        ]);

        // Get office statistics
        $statistics = $office->statistics;

        // Get OPCR performance summary
        $opcrSummary = $office->opcr_performance_summary;

        // Get recent activities
        $recentActivities = DB::table('activity_log')
            ->where('subject_type', Office::class)
            ->where('subject_id', $office->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.offices.show', compact(
            'office',
            'statistics',
            'opcrSummary',
            'recentActivities'
        ));
    }

    /**
     * Show the form for editing the specified office
     */
    public function edit(Office $office): View
    {
        $parentOffices = Office::where('is_active', true)
            ->where('id', '!=', $office->id)
            ->orderBy('full_path')
            ->get();

        $employees = Employee::whereNull('deleted_at')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('admin.offices.edit', compact('office', 'parentOffices', 'employees'));
    }

    /**
     * Update the specified office
     */
    public function update(UpdateOfficeRequest $request, Office $office): RedirectResponse
    {
        try {
            $office->update($request->validated());

            Activity::log('Office updated', [
                'office_id' => $office->id,
                'office_name' => $office->name,
                'updated_by' => Auth::id(),
            ]);

            return redirect()
                ->route('offices.show', $office)
                ->with('success', 'Office updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update office: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle office active status
     */
    public function toggle(Office $office): RedirectResponse
    {
        try {
            $newStatus = !$office->is_active;
            $office->update(['is_active' => $newStatus]);

            $statusText = $newStatus ? 'activated' : 'deactivated';

            Activity::log("Office {$statusText}", [
                'office_id' => $office->id,
                'office_name' => $office->name,
                'new_status' => $newStatus,
                'updated_by' => Auth::id(),
            ]);

            return redirect()
                ->route('offices.show', $office)
                ->with('success', "Office {$statusText} successfully.");
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to toggle office status: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified office
     */
    public function destroy(Office $office): RedirectResponse
    {
        if (!$office->canBeDeleted()) {
            return back()
                ->withErrors(['error' => 'This office cannot be deleted because it has associated records.']);
        }

        try {
            $officeName = $office->name;
            $officeId = $office->id;

            $office->delete();

            Activity::log('Office deleted', [
                'office_id' => $officeId,
                'office_name' => $officeName,
                'deleted_by' => Auth::id(),
            ]);

            return redirect()
                ->route('offices.index')
                ->with('success', 'Office deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete office: ' . $e->getMessage()]);
        }
    }

    /**
     * Show office MFOs
     */
    public function mfos(Office $office): View
    {
        $mfos = $office->activeMajorFinalOutputs()
            ->with(['successIndicators' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('code')
            ->get();

        return view('admin.offices.mfos', compact('office', 'mfos'));
    }

    /**
     * Show office personnel
     */
    public function personnel(Office $office): View
    {
        $assignments = $office->activeAssignments()
            ->with(['user', 'employee'])
            ->orderBy('role')
            ->orderBy('assigned_at')
            ->get();

        $employees = $office->employees()
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('admin.offices.personnel', compact('office', 'assignments', 'employees'));
    }

    /**
     * Show office OPCR workflows
     */
    public function workflows(Office $office): View
    {
        $workflows = $office->opcrWorkflows()
            ->with(['period', 'committedBy', 'assessedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $summary = $office->opcr_performance_summary;

        return view('admin.offices.workflows', compact('office', 'workflows', 'summary'));
    }

    /**
     * Get office statistics API endpoint
     */
    public function statistics(Office $office): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $office->statistics,
        ]);
    }

    /**
     * Get office children API endpoint
     */
    public function children(Office $office): JsonResponse
    {
        $children = $office->children()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(function ($child) {
                return [
                    'id' => $child->id,
                    'code' => $child->code,
                    'name' => $child->name,
                    'level' => $child->level,
                    'has_children' => $child->hasChildren(),
                    'statistics' => $child->statistics,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $children,
        ]);
    }

    /**
     * Export office data to Excel
     */
    public function export(Request $request)
    {
        try {
            $offices = Office::with(['parent', 'departmentHead', 'children']);

            // Apply filters
            if ($request->filled('is_active')) {
                $offices->where('is_active', $request->boolean('is_active'));
            }

            if ($request->filled('level')) {
                $offices->where('level', $request->level);
            }

            $data = $offices->get()->map(function ($office) {
                return [
                    'ID' => $office->id,
                    'Code' => $office->code,
                    'Name' => $office->name,
                    'Description' => $office->description,
                    'Level' => $office->level,
                    'Parent Office' => $office->parent?->name ?? 'N/A',
                    'Department Head' => $office->departmentHead?->full_name ?? 'N/A',
                    'Contact Number' => $office->contact_number,
                    'Email' => $office->email,
                    'Location' => $office->location,
                    'Employees Count' => $office->employees_count ?? 0,
                    'Active MFOs' => $office->active_major_final_outputs_count ?? 0,
                    'OPCR Workflows' => $office->opcr_workflows_count ?? 0,
                    'Status' => $office->is_active ? 'Active' : 'Inactive',
                    'Created At' => $office->created_at->format('Y-m-d H:i:s'),
                    'Updated At' => $office->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            $filename = 'offices_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

            // Implementation would use Laravel Excel package
            // This is a placeholder for the actual export implementation

            return back()->with('success', 'Export functionality will be implemented with Laravel Excel package.');

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to export offices: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk update offices
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'office_ids' => 'required|array',
            'office_ids.*' => 'exists:offices,id',
            'action' => 'required|in:activate,deactivate,update_parent',
            'parent_id' => 'nullable|exists:offices,id',
        ]);

        try {
            $count = 0;

            DB::transaction(function () use ($request, &$count) {
                foreach ($request->office_ids as $officeId) {
                    $office = Office::find($officeId);

                    switch ($request->action) {
                        case 'activate':
                            $office->update(['is_active' => true]);
                            break;
                        case 'deactivate':
                            $office->update(['is_active' => false]);
                            break;
                        case 'update_parent':
                            if ($request->filled('parent_id')) {
                                $office->update([
                                    'parent_id' => $request->parent_id,
                                    'level' => Office::find($request->parent_id)?->level + 1 ?? 1,
                                ]);
                            }
                            break;
                    }

                    $count++;
                }
            });

            Activity::log('Bulk office update', [
                'action' => $request->action,
                'count' => $count,
                'updated_by' => Auth::id(),
            ]);

            return back()
                ->with('success', "Successfully updated {$count} offices.");
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to bulk update offices: ' . $e->getMessage()]);
        }
    }

    // OPCR-specific methods
    /**
     * Display a listing of offices for OPCR management
     */
    public function opcrIndex(Request $request): View
    {
        $query = Office::where('is_active', true);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $offices = $query->orderBy('level')
            ->orderBy('code')
            ->paginate(12)
            ->withQueryString();

        return view('opcr.offices.index', compact('offices'));
    }

    /**
     * Show the form for creating a new office for OPCR
     */
    public function opcrCreate(): View
    {
        $parentOffices = Office::where('is_active', true)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return view('opcr.offices.create', compact('parentOffices'));
    }

    /**
     * Display the specified office for OPCR
     */
    public function opcrShow(Office $office): View
    {
        $office->load([
            'parent',
            'children' => function ($query) {
                $query->where('is_active', true);
            },
            'departmentHead',
            'employees' => function ($query) {
                $query->where('employees.employment_status', 'active')
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->limit(10);
            },
            'assignments' => function ($query) {
                $query->with(['user', 'employee'])
                    ->where('is_active', true)
                    ->orderBy('role');
            }
        ]);

        // Get office statistics
        $stats = [
            'total_employees' => $office->employees_count ?? 0,
            'active_assignments' => $office->assignments->count(),
            'active_mfos' => $office->activeMajorFinalOutputs->count(),
            'opcr_workflows' => $office->opcrWorkflows->count(),
        ];

        return view('opcr.offices.show', compact('office', 'stats'));
    }

    /**
     * Show the form for editing the specified office for OPCR
     */
    public function opcrEdit(Office $office): View
    {
        $parentOffices = Office::where('is_active', true)
            ->where('id', '!=', $office->id)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return view('opcr.offices.edit', compact('office', 'parentOffices'));
    }
}