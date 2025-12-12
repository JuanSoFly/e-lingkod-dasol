<?php

namespace App\Http\Controllers;

use App\Models\GovernmentBenefit;
use App\Models\Employee;
use App\Models\BenefitContribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Services\GovernmentComplianceService;

class GovernmentBenefitController extends Controller
{
    protected $complianceService;

    public function __construct(GovernmentComplianceService $complianceService)
    {
        $this->complianceService = $complianceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $benefits = GovernmentBenefit::with(['employee:id,first_name,last_name', 'verifiedBy:id,name']) // Optimized eager loading
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

        try {
            $employee = Employee::findOrFail($validated['employee_id']);
            
            if (!$this->complianceService->isEligibleForBenefit($employee, $validated['benefit_type'])) {
                return back()->withInput()->withErrors(['employee_id' => "This employee is not eligible for {$validated['benefit_type']} based on their employment status."]);
            }

            // Calculate initial contribution rates
            $rates = $this->complianceService->getCurrentContributionRates($validated['benefit_type']);
            $validated['employee_contribution_rate'] = $rates['employee_rate'];
            $validated['employer_contribution_rate'] = $rates['employer_rate'];
            $validated['monthly_contribution_cap'] = $rates['monthly_cap'];

            $validated['created_by'] = Auth::id();
            
            $benefit = GovernmentBenefit::create($validated);
            
            return redirect()->route('benefits.show', $benefit)
                ->with('success', 'Government benefit enrollment created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'An error occurred while creating the benefit enrollment: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(GovernmentBenefit $benefit)
    {
        $benefit->load(['employee', 'benefitContributions', 'verifiedBy', 'createdBy']);
        
        // Compliance status check via service
        $complianceStatus = $this->complianceService->getComplianceStatus($benefit);

        return view('benefits.show', compact('benefit', 'complianceStatus'));
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

        try {
            // Check eligibility if employee or benefit type changed (though usually these shouldn't change for an existing record easily without re-creation, but good to check)
            if ($benefit->employee_id != $validated['employee_id'] || $benefit->benefit_type != $validated['benefit_type']) {
                $employee = Employee::findOrFail($validated['employee_id']);
                if (!$this->complianceService->isEligibleForBenefit($employee, $validated['benefit_type'])) {
                    return back()->withInput()->withErrors(['employee_id' => "This employee is not eligible for {$validated['benefit_type']} based on their employment status."]);
                }
            }

            $validated['updated_by'] = Auth::id();
            
            $benefit->update($validated);
            
            return redirect()->route('benefits.show', $benefit)
                ->with('success', 'Government benefit enrollment updated successfully.');
        } catch (\Exception $e) {
             return back()->withInput()->withErrors(['error' => 'An error occurred while updating the benefit enrollment: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GovernmentBenefit $benefit)
    {
        try {
            $benefit->delete();
            
            return redirect()->route('benefits.index')
                ->with('success', 'Government benefit enrollment deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete benefit enrollment.']);
        }
    }
}
