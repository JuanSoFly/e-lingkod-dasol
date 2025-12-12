<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Models\OfficeAssignment; // Add import
use App\Models\LeaveCredit;
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
            $request->get('status'),
            $request
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
        // Use the workflow service to determine if user can approve this application
        $workflowService = new \App\Services\LeaveWorkflowService();
        $canApprove = $workflowService->canUserApproveApplication($application, $user);

        if ($canApprove) {
            \Log::info('User can approve workflow step', [
                'user_id' => $user->id,
                'step_name' => $workflowStep->step_name
            ]);
        } else {
            \Log::warning('User cannot approve workflow step', [
                'user_id' => $user->id,
                'step_name' => $workflowStep->step_name
            ]);
        }

        return $canApprove;
    }

    /**
     * Display the leave card for a specific employee.
     */
    public function showLeaveCard(Request $request, $employeeId = null)
    {
        $this->authorize('leave.view');

        $user = Auth::user();
        $canViewAllCards = $this->userCanViewAllLeaveCards($user);
        $canViewOfficeCards = $this->userHasManagerScope($user); // Check if they are Dept Head OR Supervisor
        // $userOfficeId removed as we use getManagerOfficeIds now

        // HR users without employee ID should see employee selection list
        // Update: Also separate logic for Supervisors/Dept Heads who need to pick an employee from their list
        if (($canViewAllCards || $canViewOfficeCards) && !$employeeId) {
            $employees = Employee::query()
                ->when($canViewOfficeCards && !$canViewAllCards, function ($query) use ($user) {
                     // Get all managed office IDs
                     $officeIds = $this->getManagerOfficeIds($user);
                     $query->whereIn('office_id', $officeIds);
                })
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

        // Use provided employee ID or current user (Department Head limited to office)
        $targetEmployeeId = ($canViewAllCards || $canViewOfficeCards)
            ? ($employeeId ?? $user->employee_id)
            : $user->employee_id;

        if (!$targetEmployeeId) {
            abort(404, 'Employee profile not found');
        }

        $employee = Employee::findOrFail($targetEmployeeId);

        if (!$this->employeeWithinLeaveCardScope($user, $employee)) {
            abort(403, 'Unauthorized');
        }
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

        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        return view('leave-applications.leave-card', compact(
            'employee',
            'year',
            'leaveApplications',
            'leaveCredits',
            'leaveTypes'
        ));
    }

    /**
     * Update or create leave credit record for an employee (manager-only).
     */
    public function updateLeaveCredit(Request $request, $employeeId)
    {
        $this->authorize('employee.manage');

        $employee = Employee::findOrFail($employeeId);

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'earned_credits' => 'required|numeric|min:0',
            'used_credits' => 'required|numeric|min:0',
            'remaining_credits' => 'nullable|numeric|min:0',
            'effective_date' => 'nullable|date',
        ]);

        $remaining = $validated['remaining_credits'] ?? max(0, $validated['earned_credits'] - $validated['used_credits']);

        DB::transaction(function () use ($validated, $employee, $remaining) {
            $leaveCredit = LeaveCredit::withTrashed()->firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $validated['leave_type_id'],
                    'year' => $validated['year'],
                ],
                [
                    'earned_credits' => 0,
                    'used_credits' => 0,
                    'remaining_credits' => 0,
                    'effective_date' => now(),
                ]
            );

            if ($leaveCredit->trashed()) {
                $leaveCredit->restore();
            }

            $leaveCredit->earned_credits = $validated['earned_credits'];
            $leaveCredit->used_credits = $validated['used_credits'];
            $leaveCredit->remaining_credits = $remaining;
            $leaveCredit->effective_date = $validated['effective_date'] ?? now();
            $leaveCredit->updated_by = Auth::id();
            $leaveCredit->save();

            // Keep legacy LeaveCard balances in sync for VL/SL codes
            $leaveType = LeaveType::find($validated['leave_type_id']);
            if (in_array($leaveType->code, ['VL', 'SL'])) {
                $leaveCard = \App\Models\LeaveCard::getOrCreateCard($employee, (int) $validated['year']);
                if ($leaveType->code === 'VL') {
                    $leaveCard->vl_balance = $remaining;
                } else {
                    $leaveCard->sl_balance = $remaining;
                }
                $leaveCard->last_updated = now();
                $leaveCard->save();
            }
        });

        return redirect()->back()->with('status', 'Leave credit updated successfully.');
    }

    /**
     * Print the leave card (print-friendly view).
     */
    public function printLeaveCard(Request $request, $employeeId = null)
    {
        $this->authorize('leave.view');

        $user = Auth::user();
        $canViewAllCards = $this->userCanViewAllLeaveCards($user);
        $canViewOfficeCards = $this->userHasManagerScope($user);

        // Use provided employee ID or current user
        $targetEmployeeId = ($canViewAllCards || $canViewOfficeCards)
            ? ($employeeId ?? $user->employee_id)
            : $user->employee_id;

        if (!$targetEmployeeId) {
            abort(404, 'Employee profile not found');
        }

        $employee = Employee::findOrFail($targetEmployeeId);

        if (!$this->employeeWithinLeaveCardScope($user, $employee)) {
            abort(403, 'Unauthorized');
        }
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

    private function userCanViewAllLeaveCards(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'HR Admin']) || $user->can('employee.manage');
    }

    /**
     * Check if user has Department Head or Supervisor role with active assignments.
     */
    private function userHasManagerScope(User $user): bool
    {
        if (!$user->hasAnyRole(['Department Head', 'Supervisor'])) {
            return false;
        }
        
        // Check for active assignments
        return OfficeAssignment::where('user_id', $user->id)
            ->whereIn('role', ['Department Head', 'Supervisor'])
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get IDs of offices managed by the user
     */
    private function getManagerOfficeIds(User $user): array
    {
        return OfficeAssignment::where('user_id', $user->id)
            ->whereIn('role', ['Department Head', 'Supervisor'])
            ->where('is_active', true)
            ->pluck('office_id')
            ->toArray();
    }

    /**
     * Determine if a target employee is within the viewer's allowed scope.
     */
    private function employeeWithinLeaveCardScope(User $user, Employee $employee): bool
    {
        if ($this->userCanViewAllLeaveCards($user)) {
            return true;
        }

        if ($this->userHasManagerScope($user)) {
             $managedOfficeIds = $this->getManagerOfficeIds($user);
             return in_array($employee->office_id, $managedOfficeIds);
        }

        return optional($user->employee)->id === $employee->id;
    }
}
