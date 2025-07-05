<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Services\LeaveApplicationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\StoreLeaveApplicationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveApplicationController extends Controller
{
    use AuthorizesRequests;
    
    protected $leaveApplicationService;

    public function __construct(LeaveApplicationService $leaveApplicationService)
    {
        $this->leaveApplicationService = $leaveApplicationService;
    }

    /**
     * Display a listing of the resource.
     */
     public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->can('leave.approve')) {
            $this->authorize('leave.view');
        }

        $applications = $this->leaveApplicationService->getApplicationsForUser(
            $user, 
            $request->get('status')
        );
        
        return view('leave_applications.index', compact('applications'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('leave.create');
        $leaveTypes = LeaveType::where('is_active', true)->get();
        return view('leave_applications.create', compact('leaveTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
     public function store(StoreLeaveApplicationRequest $request)
    {
        $this->authorize('leave.create');
        
        try {
            $application = $this->leaveApplicationService->createApplication(
                $request->validated(),
                Auth::user()
            );

            return redirect()->route('leave-applications.index')
                ->with('success', 'Leave application submitted successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while submitting your application. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveApplication $leaveApplication)
    {
        $this->authorize('view', $leaveApplication);
        return view('leave_applications.show', compact('leaveApplication'));
    }

    /**
     * Approve the specified leave application.
     */
    public function approve(Request $request, LeaveApplication $leaveApplication)
    {
        $this->authorize('leave.approve');
        
        try {
            $this->leaveApplicationService->approveApplication(
                $leaveApplication,
                Auth::user(),
                $request->input('remarks')
            );

            return redirect()->route('leave-applications.index', ['status' => 'pending'])
                ->with('success', 'Leave application approved.');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while approving the application.');
        }
    }

    public function reject(Request $request, LeaveApplication $leaveApplication)
    {
        $this->authorize('leave.approve');
        $request->validate(['remarks' => 'required|string|max:255']);

        try {
            $this->leaveApplicationService->rejectApplication(
                $leaveApplication,
                Auth::user(),
                $request->remarks
            );

            return redirect()->route('leave-applications.index', ['status' => 'pending'])
                ->with('success', 'Leave application rejected.');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while rejecting the application.');
        }
    }
}