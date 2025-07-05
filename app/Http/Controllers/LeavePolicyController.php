<?php

namespace App\Http\Controllers;

use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Services\LeavePolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeavePolicyController extends Controller
{
    private LeavePolicyService $leavePolicyService;

    public function __construct(LeavePolicyService $leavePolicyService)
    {
        $this->leavePolicyService = $leavePolicyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $policies = LeavePolicy::with(['leaveType'])
            ->when(request('leave_type'), function ($query, $type) {
                return $query->where('leave_type_id', $type);
            })
            ->when(request('employment_status'), function ($query, $status) {
                return $query->whereJsonContains('employment_statuses', $status);
            })
            ->when(request('status'), function ($query, $status) {
                if ($status === 'active') {
                    return $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    return $query->where('is_active', false);
                }
                return $query;
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->paginate(15);

        // Get statistics for dashboard
        $stats = [
            'total_policies' => LeavePolicy::count(),
            'active_policies' => LeavePolicy::active()->count(),
            'government_policies' => LeavePolicy::where('is_government_policy', true)->count(),
            'custom_policies' => LeavePolicy::where('is_government_policy', false)->count(),
        ];

        // Get filter options
        $leaveTypes = LeaveType::orderBy('name')->get(['id', 'name']);
        $employmentStatuses = ['permanent', 'temporary', 'contractual', 'casual'];
        $statusOptions = ['active', 'inactive'];

        return view('leave-policies.index', compact(
            'policies',
            'stats',
            'leaveTypes',
            'employmentStatuses',
            'statusOptions'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $leaveTypes = LeaveType::orderBy('name')->get(['id', 'name']);
        $employmentStatuses = ['permanent', 'temporary', 'contractual', 'casual'];
        $accrualMethods = ['monthly', 'yearly', 'hourly', 'fixed'];
        
        return view('leave-policies.create', compact('leaveTypes', 'employmentStatuses', 'accrualMethods'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'leave_type_id' => 'required|exists:leave_types,id',
            'employment_statuses' => 'required|array',
            'employment_statuses.*' => 'in:permanent,temporary,contractual,casual',
            'max_days_per_year' => 'required|numeric|min:0',
            'accrual_method' => 'required|in:monthly,yearly,hourly,fixed',
            'is_active' => 'boolean',
            'is_government_policy' => 'boolean',
            'requires_approval' => 'boolean',
            'effective_start_date' => 'required|date',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_government_policy'] = $request->has('is_government_policy');
        $validated['requires_approval'] = $request->has('requires_approval');
        
        $policy = LeavePolicy::create($validated);
        
        return redirect()->route('leave-policies.show', $policy)
            ->with('success', 'Leave policy created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(LeavePolicy $leavePolicy)
    {
        $leavePolicy->load(['leaveType']);
        
        return view('leave-policies.show', compact('leavePolicy'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeavePolicy $leavePolicy)
    {
        $leaveTypes = LeaveType::orderBy('name')->get(['id', 'name']);
        $employmentStatuses = ['permanent', 'temporary', 'contractual', 'casual'];
        $accrualMethods = ['monthly', 'yearly', 'hourly', 'fixed'];
        
        return view('leave-policies.edit', compact('leavePolicy', 'leaveTypes', 'employmentStatuses', 'accrualMethods'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeavePolicy $leavePolicy)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'leave_type_id' => 'required|exists:leave_types,id',
            'employment_statuses' => 'required|array',
            'employment_statuses.*' => 'in:permanent,temporary,contractual,casual',
            'max_days_per_year' => 'required|numeric|min:0',
            'accrual_method' => 'required|in:monthly,yearly,hourly,fixed',
            'is_active' => 'boolean',
            'is_government_policy' => 'boolean',
            'requires_approval' => 'boolean',
            'effective_start_date' => 'required|date',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_government_policy'] = $request->has('is_government_policy');
        $validated['requires_approval'] = $request->has('requires_approval');
        
        $leavePolicy->update($validated);
        
        return redirect()->route('leave-policies.show', $leavePolicy)
            ->with('success', 'Leave policy updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeavePolicy $leavePolicy)
    {
        // Soft delete the policy
        $leavePolicy->delete();
        
        return redirect()->route('leave-policies.index')
            ->with('success', 'Leave policy deleted successfully.');
    }
}
