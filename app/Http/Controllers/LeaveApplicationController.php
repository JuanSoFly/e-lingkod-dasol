<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Models\User;
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
     * Approve leave application through workflow
     */
    public function approve(Request $request, LeaveApplication $leaveApplication)
    {
        $this->authorize('leave.approve');

        // Validate sufficient balance before approval
        $currentBalances = $this->leaveCardService->getCurrentBalances($leaveApplication->employee);
        $leaveTypeCode = $leaveApplication->leaveType->code;

        // For VL and SL, check LeaveCard balances
        $availableBalance = 0;
        if ($leaveTypeCode === 'VL') {
            $availableBalance = $currentBalances['vl_balance'] ?? 0;
        } elseif ($leaveTypeCode === 'SL') {
            $availableBalance = $currentBalances['sl_balance'] ?? 0;
        } else {
            // For other leave types, check LeaveCredit records
            $availableBalance = $currentBalances[$leaveTypeCode] ?? 0;
        }

        if ($availableBalance < $leaveApplication->days_requested) {
            return back()->with('error', "Insufficient leave balance. Available: {$availableBalance} days, Requested: {$leaveApplication->days_requested} days.");
        }

        try {
            // Find the current pending workflow step for this approver
            $currentStep = $this->findApprovableWorkflowStep($leaveApplication, auth()->user());

            if (!$currentStep) {
                return back()->with('error', 'No pending approval step found for your action. You may not have permission to approve this application at its current stage.');
            }

            // Process approval through workflow service
            $workflowService = new \App\Services\LeaveWorkflowService();
            $success = $workflowService->processApproval(
                $leaveApplication,
                $currentStep,
                auth()->user(),
                'approved',
                $request->input('remarks')
            );

            if (!$success) {
                return back()->with('error', 'Failed to process approval through workflow.');
            }

            return redirect()->route('leave-applications.index', ['status' => 'pending'])
                ->with('success', 'Leave application approved successfully.');

        } catch (\Exception $e) {
            \Log::error('Leave approval error', [
                'application_id' => $leaveApplication->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to approve leave application: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, LeaveApplication $leaveApplication)
    {
        $this->authorize('leave.approve');
        $request->validate(['remarks' => 'required|string|max:255']);

        try {
            // Find the current pending workflow step for this approver
            $currentStep = $this->findApprovableWorkflowStep($leaveApplication, auth()->user());

            if (!$currentStep) {
                return back()->with('error', 'No pending approval step found for your action. You may not have permission to approve this application at its current stage.');
            }

            // Process rejection through workflow service
            $workflowService = new \App\Services\LeaveWorkflowService();
            $success = $workflowService->processApproval(
                $leaveApplication,
                $currentStep,
                auth()->user(),
                'rejected',
                $request->remarks
            );

            if (!$success) {
                return back()->with('error', 'Failed to process rejection through workflow.');
            }

            return redirect()->route('leave-applications.index', ['status' => 'pending'])
                ->with('success', 'Leave application rejected.');
        } catch (\Exception $e) {
            \Log::error('Leave rejection error', [
                'application_id' => $leaveApplication->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to reject leave application: ' . $e->getMessage());
        }
    }

    /**
     * Find approvable workflow step for the given user and application
     */
    private function findApprovableWorkflowStep(LeaveApplication $application, User $user): ?\App\Models\LeaveApplicationWorkflowStep
    {
        \Log::info('Finding approvable workflow step', [
            'application_id' => $application->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_roles' => $user->roles->pluck('name')->toArray()
        ]);

        // Get all pending workflow steps for this application
        $pendingSteps = \App\Models\LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
            ->where('status', 'pending')
            ->with('leaveWorkflowStep')
            ->orderBy('step_order')
            ->get();

        \Log::info('Found pending steps', [
            'count' => $pendingSteps->count(),
            'steps' => $pendingSteps->map(fn($step) => [
                'step_id' => $step->id,
                'step_order' => $step->step_order,
                'step_name' => $step->leaveWorkflowStep->step_name,
                'approvers' => $step->leaveWorkflowStep->approvers
            ])->toArray()
        ]);

        foreach ($pendingSteps as $step) {
            // Check if user can approve this step
            if ($this->canUserApproveStep($user, $application, $step->leaveWorkflowStep)) {
                \Log::info('User can approve workflow step', [
                    'step_id' => $step->id,
                    'step_name' => $step->leaveWorkflowStep->step_name,
                    'user_id' => $user->id
                ]);
                return $step;
            }
        }

        \Log::warning('User cannot approve any pending steps', [
            'application_id' => $application->id,
            'user_id' => $user->id
        ]);

        return null;
    }

    /**
     * Check if user can approve a specific workflow step
     */
    private function canUserApproveStep(User $user, LeaveApplication $application, \App\Models\LeaveWorkflowStep $workflowStep): bool
    {
        // Super Admin and users with leave.approve permission can approve any step
        if ($user->can('leave.approve') && ($user->hasRole('Super Admin') || $user->hasRole('hr_admin'))) {
            \Log::info('Super Admin/HR user can approve any step', [
                'user_id' => $user->id,
                'step_name' => $workflowStep->step_name
            ]);
            return true;
        }

        // Department Heads with leave.approve permission can also approve any step
        if ($user->can('leave.approve') && $user->hasRole('Department Head')) {
            \Log::info('Department Head with leave.approve permission can approve any step', [
                'user_id' => $user->id,
                'step_name' => $workflowStep->step_name
            ]);
            return true;
        }

        // For regular workflow-based approval, check if user is in the approvers list
        $currentApprovers = $workflowStep->getCurrentApprovers($application);

        foreach ($currentApprovers as $approver) {
            if ($approver->id === $user->id) {
                \Log::info('User found in current approvers', [
                    'user_id' => $user->id,
                    'step_name' => $workflowStep->step_name
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Display the leave card for a specific employee.
     */
    public function showLeaveCard(Request $request, $employeeId = null)
    {
        $this->authorize('leave.view');

        // HR users without employee ID should see employee selection list
        if (Auth::user()->can('leave.approve') && !$employeeId) {
            $employees = Employee::query()
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = $request->get('search');
                    $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'LIKE', "%{$search}%")
                          ->orWhere('last_name', 'LIKE', "%{$search}%")
                          ->orWhere('employee_number', $search);
                    });
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(20);

            return view('leave-applications.employee-selection', compact('employees'));
        }

        // Use provided employee ID or current user
        $targetEmployeeId = $employeeId ?? Auth::user()->employee_id;

        // Non-HR users can only view their own leave card
        if (!Auth::user()->can('leave.approve') && $targetEmployeeId !== Auth::user()->employee_id) {
            abort(403, 'Unauthorized');
        }

        $employee = Employee::findOrFail($targetEmployeeId);
        $year = $request->get('year', date('Y'));

        // Get approved leave applications for the year
        $leaveApplications = LeaveApplication::with('leaveType')
            ->where('employee_id', $targetEmployeeId)
            ->whereYear('start_date', $year)
            ->where('status', 'approved')
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

        // Get approved leave applications for the year
        $leaveApplications = LeaveApplication::with('leaveType')
            ->where('employee_id', $targetEmployeeId)
            ->whereYear('start_date', $year)
            ->where('status', 'approved')
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