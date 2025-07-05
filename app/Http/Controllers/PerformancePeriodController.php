<?php

namespace App\Http\Controllers;

use App\Models\PerformancePeriod;
use App\Http\Requests\StorePerformancePeriodRequest;
use App\Http\Requests\UpdatePerformancePeriodRequest;

class PerformancePeriodController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:user.manage');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $periods = PerformancePeriod::latest()->paginate(10);
        return view('performance_periods.index', compact('periods'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('performance_periods.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePerformancePeriodRequest $request)
    {
        PerformancePeriod::create($request->validated());
        return redirect()->route('performance-periods.index')->with('success', 'Performance period created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PerformancePeriod $performancePeriod)
    {
        return view('performance_periods.edit', compact('performancePeriod'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePerformancePeriodRequest $request, PerformancePeriod $performancePeriod)
    {
        $performancePeriod->update($request->validated());
        return redirect()->route('performance-periods.index')->with('success', 'Performance period updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PerformancePeriod $performancePeriod)
    {
        if ($performancePeriod->targets()->exists()) {
            return back()->with('error', 'Cannot delete this period as it has performance targets associated with it.');
        }
        $performancePeriod->delete();
        return redirect()->route('performance-periods.index')->with('success', 'Performance period deleted successfully.');
    }
}