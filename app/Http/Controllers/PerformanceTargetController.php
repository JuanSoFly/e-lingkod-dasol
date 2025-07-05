<?php

namespace App\Http\Controllers;

use App\Models\PerformancePeriod;
use App\Models\PerformanceTarget;
use App\Http\Requests\StorePerformanceTargetRequest;
use App\Http\Requests\UpdatePerformanceTargetRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerformanceTargetController extends Controller
{
    use AuthorizesRequests;
    public function __construct()
    {
        $this->middleware('can:performance.view');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Your user account is not linked to an employee profile.');
        }

        $periods = PerformancePeriod::where('status', 'active')->orWhereHas('targets', fn($q) => $q->where('employee_id', $employee->id))->latest()->get();
        $selectedPeriodId = $request->input('period_id', $periods->first()?->id);

        $targets = PerformanceTarget::where('employee_id', $employee->id)
            ->where('period_id', $selectedPeriodId)
            ->with('rating')
            ->get();

        return view('performance_targets.index', [
            'employee' => $employee,
            'targets' => $targets,
            'periods' => $periods,
            'selectedPeriodId' => $selectedPeriodId,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize('performance.create');
        $periodId = $request->query('period_id');
        if (!$periodId || !PerformancePeriod::where('id', $periodId)->where('status', 'active')->exists()) {
            return redirect()->route('performance-targets.index')->with('error', 'You must select an active performance period to add targets.');
        }
        $period = PerformancePeriod::find($periodId);
        return view('performance_targets.create', compact('period'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePerformanceTargetRequest $request)
    {
        $this->authorize('performance.create');
        $validated = $request->validated();

        $employee = Auth::user()->employee;

        PerformanceTarget::create(array_merge($validated, [
            'employee_id' => $employee->id,
        ]));

        return redirect()->route('performance-targets.index', ['period_id' => $validated['period_id']])
            ->with('success', 'Performance target added successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PerformanceTarget $performanceTarget)
    {
        $this->authorize('update', $performanceTarget);
        return view('performance_targets.edit', compact('performanceTarget'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePerformanceTargetRequest $request, PerformanceTarget $performanceTarget)
    {
        $this->authorize('update', $performanceTarget);
        $validated = $request->validated();
        $performanceTarget->update($validated);

        return redirect()->route('performance-targets.index', ['period_id' => $performanceTarget->period_id])
            ->with('success', 'Performance target updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PerformanceTarget $performanceTarget)
    {
        $this->authorize('delete', $performanceTarget);
        $periodId = $performanceTarget->period_id;
        $performanceTarget->delete();
        return redirect()->route('performance-targets.index', ['period_id' => $periodId])
            ->with('success', 'Performance target deleted successfully.');
    }
}