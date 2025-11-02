<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Employee;
use App\Services\MFOHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class OPCROfficeController extends Controller
{
    private MFOHierarchyService $mfoHierarchyService;

    public function __construct(MFOHierarchyService $mfoHierarchyService)
    {
        $this->mfoHierarchyService = $mfoHierarchyService;

        $this->middleware('auth');
        $this->middleware('permission:opcr.view')->only(['index', 'show']);
        $this->middleware('permission:opcr.settings')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of offices for OPCR management
     */
    public function index(Request $request): View
    {
        $query = Office::with(['parent', 'departmentHead', 'children'])
            ->withCount(['activeMajorFinalOutputs', 'opcrWorkflows']);

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('level')) {
            $query->byLevel($request->level);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $offices = $query->orderBy('level')
            ->orderBy('code')
            ->paginate(12)
            ->withQueryString();

        return view('opcr.offices.index', compact('offices'));
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
            'employees' => function ($query) {
                $query->where('is_active', true)
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
     * Show the form for creating a new office
     */
    public function create(): View
    {
        $parentOffices = Office::where('is_active', true)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return view('opcr.offices.create', compact('parentOffices'));
    }

    /**
     * Store a newly created office
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:offices,code',
            'description' => 'nullable|string',
            'head_of_office' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'parent_office_code' => 'nullable|string|exists:offices,code',
            'level' => 'required|integer|min:1',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        try {
            $office = Office::create($validated);

            return redirect()
                ->route('opcr.offices.show', $office)
                ->with('success', 'Office created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create office: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified office
     */
    public function edit(Office $office): View
    {
        $parentOffices = Office::where('is_active', true)
            ->where('id', '!=', $office->id)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return view('opcr.offices.edit', compact('office', 'parentOffices'));
    }

    /**
     * Update the specified office
     */
    public function update(Request $request, Office $office): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:offices,code,' . $office->id,
            'description' => 'nullable|string',
            'head_of_office' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'parent_office_code' => 'nullable|string|exists:offices,code',
            'level' => 'required|integer|min:1',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;

        try {
            $office->update($validated);

            return redirect()
                ->route('opcr.offices.show', $office)
                ->with('success', 'Office updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update office: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified office
     */
    public function destroy(Office $office): \Illuminate\Http\RedirectResponse
    {
        try {
            // Check if office has employees or assignments
            if ($office->employees()->exists()) {
                return back()->withErrors(['error' => 'Cannot delete office with assigned employees.']);
            }

            if ($office->assignments()->exists()) {
                return back()->withErrors(['error' => 'Cannot delete office with active assignments.']);
            }

            $office->delete();

            return redirect()
                ->route('opcr.offices.index')
                ->with('success', 'Office deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete office: ' . $e->getMessage()]);
        }
    }

    /**
     * Get offices as JSON for Select2
     */
    public function json(Request $request): JsonResponse
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
                    'text' => $office->name,
                    'code' => $office->code,
                    'level' => $office->level,
                ];
            });

        return response()->json([
            'results' => $offices,
            'pagination' => [
                'more' => false,
            ],
        ]);
    }
}
