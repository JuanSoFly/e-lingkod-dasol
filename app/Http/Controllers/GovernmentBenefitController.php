<?php

namespace App\Http\Controllers;

use App\Models\GovernmentBenefit;
use App\Models\Employee;
use App\Models\BenefitContribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GovernmentBenefitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $benefits = GovernmentBenefit::with(['employee', 'verifiedBy'])
            ->when(request('benefit_type'), function ($query, $type) {
                return $query->byBenefitType($type);
            })
            ->when(request('status'), function ($query, $status) {
                return $query->where('enrollment_status', $status);
            })
            ->when(request('employee'), function ($query, $employee) {
                return $query->whereHas('employee', function ($q) use ($employee) {
                    $q->where('first_name', 'like', "%{$employee}%")
                      ->orWhere('last_name', 'like', "%{$employee}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get statistics for dashboard
        $stats = [
            'total_enrollments' => GovernmentBenefit::count(),
            'active_enrollments' => GovernmentBenefit::active()->count(),
            'pending_verification' => GovernmentBenefit::requiringVerification()->count(),
            'active_loans' => GovernmentBenefit::withActiveLoans()->count(),
        ];

        // Get benefit type counts
        $benefitTypeCounts = GovernmentBenefit::select('benefit_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('benefit_type')
            ->pluck('count', 'benefit_type')
            ->toArray();

        // Get filter options
        $benefitTypes = ['GSIS', 'PhilHealth', 'Pag-IBIG', 'SSS'];
        $statusOptions = ['active', 'inactive', 'pending', 'suspended'];
        $employees = Employee::orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return view('benefits.index', compact(
            'benefits',
            'stats',
            'benefitTypeCounts',
            'benefitTypes',
            'statusOptions',
            'employees'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::orderBy('last_name')->get();
        $benefitTypes = ['GSIS', 'PhilHealth', 'Pag-IBIG', 'SSS'];
        
        return view('benefits.create', compact('employees', 'benefitTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'benefit_type' => 'required|in:GSIS,PhilHealth,Pag-IBIG,SSS',
            'member_number' => 'required|string|max:50',
            'enrollment_date' => 'required|date',
            'enrollment_status' => 'required|in:active,inactive,pending,suspended',
            'coverage_type' => 'nullable|string|max:100',
            'coverage_amount' => 'nullable|numeric|min:0',
        ]);

        $validated['created_by'] = Auth::id();
        
        $benefit = GovernmentBenefit::create($validated);
        
        return redirect()->route('benefits.show', $benefit)
            ->with('success', 'Government benefit enrollment created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(GovernmentBenefit $benefit)
    {
        $benefit->load(['employee', 'benefitContributions', 'verifiedBy', 'createdBy']);
        
        return view('benefits.show', compact('benefit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GovernmentBenefit $benefit)
    {
        $employees = Employee::orderBy('last_name')->get();
        $benefitTypes = ['GSIS', 'PhilHealth', 'Pag-IBIG', 'SSS'];
        
        return view('benefits.edit', compact('benefit', 'employees', 'benefitTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GovernmentBenefit $benefit)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'benefit_type' => 'required|in:GSIS,PhilHealth,Pag-IBIG,SSS',
            'member_number' => 'required|string|max:50',
            'enrollment_date' => 'required|date',
            'enrollment_status' => 'required|in:active,inactive,pending,suspended',
            'coverage_type' => 'nullable|string|max:100',
            'coverage_amount' => 'nullable|numeric|min:0',
        ]);

        $validated['updated_by'] = Auth::id();
        
        $benefit->update($validated);
        
        return redirect()->route('benefits.show', $benefit)
            ->with('success', 'Government benefit enrollment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GovernmentBenefit $benefit)
    {
        $benefit->delete();
        
        return redirect()->route('benefits.index')
            ->with('success', 'Government benefit enrollment deleted successfully.');
    }
}
