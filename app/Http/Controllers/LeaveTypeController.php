<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    use AuthorizesRequests;

    

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leaveTypes = LeaveType::latest()->paginate(10);
        return view('leave_types.index', compact('leaveTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('leave_types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeaveTypeRequest $request)
    {
        LeaveType::create($request->validated());
        return redirect()->route('leave-types.index')->with('success', 'Leave type created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveType $leaveType)
    {
        return view('leave_types.edit', compact('leaveType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType)
    {
        $leaveType->update($request->validated());
        return redirect()->route('leave-types.index')->with('success', 'Leave type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveType $leaveType)
    {
        // Add a check to prevent deletion if it's being used in leave applications
        if ($leaveType->leaveApplications()->exists()) {
            return back()->with('error', 'Cannot delete leave type. It is already in use.');
        }

        $leaveType->delete();
        return redirect()->route('leave-types.index')->with('success', 'Leave type deleted successfully.');
    }
}