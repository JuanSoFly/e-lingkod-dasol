<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Services\LeaveApplicationService;
use App\Services\LeaveCardService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\StoreLeaveApplicationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveApplicationController extends Controller
{
    use AuthorizesRequests;
    
    protected $leaveApplicationService;
    protected $leaveCardService;

    public function __construct(LeaveApplicationService $leaveApplicationService, LeaveCardService $leaveCardService)
    {
        $this->leaveApplicationService = $leaveApplicationService;
        $this->leaveCardService = $leaveCardService;
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
        
        // Additional role-based check - only Employees should apply for leave
        if (!Auth::user()->hasRole('Employee')) {
            abort(403, 'Only employees can apply for leave.');
        }
        
        $leaveTypes = LeaveType::where('is_active', true)->get();
        return view('leave_applications.create', compact('leaveTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
     public function store(StoreLeaveApplicationRequest $request)
    {
        $this->authorize('leave.create');
        
        // Additional role-based check - only Employees should apply for leave
        if (!Auth::user()->hasRole('Employee')) {
            abort(403, 'Only employees can apply for leave.');
        }
        
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
     * Approve leave application and update leave card
     */
    public function approve(LeaveApplication $leaveApplication, Request $request): JsonResponse
    {
        $this->authorize('leave.approve');

        try {
            DB::beginTransaction();

            // Update application status
            $leaveApplication->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_date' => now(),
                'remarks' => $request->input('remarks', $leaveApplication->remarks),
            ]);

            // Create approval record
            $leaveApplication->approvals()->create([
                'approver_id' => auth()->id(),
                'action' => 'approved',
                'remarks' => $request->input('remarks'),
                'action_date' => now(),
            ]);

            // Update leave card
            $leaveCard = $this->leaveCardService->processApprovedLeave($leaveApplication);

            DB::commit();

            return response()->json([
                'message' => 'Leave application approved successfully',
                'application' => $leaveApplication->load(['employee', 'leaveType', 'approver']),
                'leave_card' => $leaveCard,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to approve leave application',
                'error' => $e->getMessage(),
            ], 500);
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

    /**
     * Display the leave card for a specific employee.
     */
    public function showLeaveCard(Request $request, $employeeId = null)
    {
        $this->authorize('leave.view');

        // Use provided employee ID or current user
        $targetEmployeeId = $employeeId ?? Auth::user()->employee_id;

        // Non-HR users can only view their own leave card
        if (!Auth::user()->can('leave.approve') && $targetEmployeeId !== Auth::user()->employee_id) {
            abort(403, 'Unauthorized');
        }

        $employee = Employee::findOrFail($targetEmployeeId);
        $year = $request->get('year', date('Y'));

        // Get leave applications for the year
        $leaveApplications = LeaveApplication::with('leaveType')
            ->where('employee_id', $targetEmployeeId)
            ->whereYear('start_date', $year)
            ->orderBy('start_date', 'desc')
            ->get();

        // Get leave credits summary
        $leaveCredits = DB::table('leave_credits as lc')
            ->join('leave_types as lt', 'lc.leave_type_id', '=', 'lt.id')
            ->where('lc.employee_id', $targetEmployeeId)
            ->where('lc.year', $year)
            ->select(
                'lt.name',
                'lc.earned_credits as credits_earned',
                'lc.used_credits as credits_used',
                'lc.remaining_credits as credits_balance'
            )
            ->get();

        return view('leave-applications.leave-card', compact(
            'employee',
            'year',
            'leaveApplications',
            'leaveCredits'
        ));
    }

    /**
     * Print the leave card (print-friendly view).
     */
    public function printLeaveCard(Request $request, $employeeId = null)
    {
        $this->authorize('leave.view');

        // Use provided employee ID or current user
        $targetEmployeeId = $employeeId ?? Auth::user()->employee_id;

        // Non-HR users can only view their own leave card
        if (!Auth::user()->can('leave.approve') && $targetEmployeeId !== Auth::user()->employee_id) {
            abort(403, 'Unauthorized');
        }

        $employee = Employee::findOrFail($targetEmployeeId);
        $year = $request->get('year', date('Y'));

        // Get leave applications for the year
        $leaveApplications = LeaveApplication::with('leaveType')
            ->where('employee_id', $targetEmployeeId)
            ->whereYear('start_date', $year)
            ->orderBy('start_date', 'desc')
            ->get();

        // Get leave credits summary
        $leaveCredits = DB::table('leave_credits as lc')
            ->join('leave_types as lt', 'lc.leave_type_id', '=', 'lt.id')
            ->where('lc.employee_id', $targetEmployeeId)
            ->where('lc.year', $year)
            ->select(
                'lt.name',
                'lc.earned_credits as credits_earned',
                'lc.used_credits as credits_used',
                'lc.remaining_credits as credits_balance'
            )
            ->get();

        return view('leave-applications.print-leave-card', compact(
            'employee',
            'year',
            'leaveApplications',
            'leaveCredits'
        ));
    }
}