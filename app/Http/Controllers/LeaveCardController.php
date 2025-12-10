<?php

namespace App\Http\Controllers;

use App\Services\LeaveCardService;
use App\Models\Employee;
use App\Models\LeaveCard;
use App\Models\LeaveCredit;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LeaveCardController extends Controller
{
    private LeaveCardService $leaveCardService;

    public function __construct(LeaveCardService $leaveCardService)
    {
        $this->leaveCardService = $leaveCardService;
        $this->middleware('auth');
    }

    /**
     * Display employee's leave card
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $canViewAllCards = $this->userCanViewAllLeaveCards($user);
        $canViewOfficeCards = $this->userHasDepartmentHeadOfficeScope($user);

        if (!$canViewAllCards && !$canViewOfficeCards) {
            $employee = $user->employee;
            if (!$employee) {
                return response()->json(['error' => 'Employee profile not found'], 404);
            }
        } else {
            $employeeId = $request->input('employee_id');
            $employee = $employeeId ? Employee::findOrFail($employeeId) : ($user->employee ?? null);

            if (!$employee) {
                return response()->json(['error' => 'Please select an employee'], 400);
            }

            if (!$this->employeeWithinLeaveCardScope($user, $employee)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $year = $request->input('year', now()->year);
        $leaveHistory = $this->leaveCardService->getLeaveHistory($employee, $year);
        $currentBalances = $this->leaveCardService->getCurrentBalances($employee);

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_id' => $employee->employee_id,
            ],
            'year' => $year,
            'current_balances' => $currentBalances,
            'leave_history' => $leaveHistory['entries'],
        ]);
    }

    /**
     * Display leave card for specific employee (HR/Admin only)
     */
    public function show(Employee $employee, Request $request): JsonResponse
    {
        $this->authorize('employee.view');

        $user = Auth::user();

        if (!$this->employeeWithinLeaveCardScope($user, $employee)) {
            abort(403, 'Unauthorized');
        }

        $year = $request->input('year', now()->year);
        $leaveHistory = $this->leaveCardService->getLeaveHistory($employee, $year);
        $currentBalances = $this->leaveCardService->getCurrentBalances($employee);

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_id' => $employee->employee_id,
            ],
            'year' => $year,
            'current_balances' => $currentBalances,
            'leave_history' => $leaveHistory['entries'],
        ]);
    }

    /**
     * Initialize yearly balances for employee
     */
    public function initializeBalances(Employee $employee, Request $request): JsonResponse
    {
        $this->authorize('employee.manage');

        $year = $request->input('year', now()->year);
        $leaveCard = $this->leaveCardService->initializeYearlyBalances($employee, $year);

        return response()->json([
            'message' => 'Leave card balances initialized successfully',
            'leave_card' => $leaveCard,
        ]);
    }

    /**
     * Create manual leave card entry
     */
    public function createManualEntry(Request $request): JsonResponse
    {
        $this->authorize('employee.manage');

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_code' => 'required|string|max:10',
            'date' => 'required|date',
            'days' => 'required|numeric|min:0.1|max:365',
            'remarks' => 'required|string|max:255',
            'entry_type' => 'required|in:credit,deduction,adjustment',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        try {
            $entry = $this->leaveCardService->createManualEntry($employee, $request->all());

            return response()->json([
                'message' => 'Manual entry created successfully',
                'entry' => $entry,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create manual entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get leave card data via API
     */
    public function getLeaveCardData(Request $request): JsonResponse
    {
        $user = Auth::user();
        $canViewAllCards = $this->userCanViewAllLeaveCards($user);
        $canViewOfficeCards = $this->userHasDepartmentHeadOfficeScope($user);

        if (!$canViewAllCards && !$canViewOfficeCards) {
            $employee = $user->employee;
            if (!$employee) {
                return response()->json(['error' => 'Employee profile not found'], 404);
            }
        } else {
            $employeeId = $request->input('employee_id');
            $employee = $employeeId ? Employee::findOrFail($employeeId) : ($user->employee ?? null);

            if (!$employee) {
                return response()->json(['error' => 'Please select an employee'], 400);
            }

            if (!$this->employeeWithinLeaveCardScope($user, $employee)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $year = $request->input('year', now()->year);
        $leaveHistory = $this->leaveCardService->getLeaveHistory($employee, $year);
        $currentBalances = $this->leaveCardService->getCurrentBalances($employee);

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_number' => $employee->employee_number,
            ],
            'year' => $year,
            'current_balances' => $currentBalances,
            'leave_history' => $leaveHistory['entries'],
        ]);
    }

    /**
     * Get all leave cards for HR/Admin view
     */
    public function getAllLeaveCards(Request $request): JsonResponse
    {
        $this->authorize('employee.manage');

        $year = $request->input('year', now()->year);
        $filters = $request->only(['employee_id', 'department']);

        $leaveCards = $this->leaveCardService->getAllLeaveCards($year, $filters);

        return response()->json([
            'leave_cards' => $leaveCards->map(function ($card) {
                return [
                    'id' => $card->id,
                    'employee' => [
                        'id' => $card->employee->id,
                        'name' => $card->employee->full_name,
                        'employee_number' => $card->employee->employee_number,
                    ],
                    'year' => $card->year,
                    'vl_balance' => $card->vl_balance,
                    'sl_balance' => $card->sl_balance,
                    'last_updated' => $card->last_updated->format('Y-m-d'),
                    'entries_count' => $card->entries->count(),
                ];
            }),
        ]);
    }

    /**
     * Get printable leave card data
     */
    public function printLeaveCard(Request $request, $employeeId = null): JsonResponse
    {
        $user = auth()->user();
        $canViewAllCards = $this->userCanViewAllLeaveCards($user);
        $canViewOfficeCards = $this->userHasDepartmentHeadOfficeScope($user);
        $userEmployeeId = optional($user->employee)->id;

        // Use provided employee ID or current user
        $targetEmployeeId = ($canViewAllCards || $canViewOfficeCards)
            ? ($employeeId ?? $userEmployeeId)
            : $userEmployeeId;

        if (!$targetEmployeeId) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $employee = Employee::findOrFail($targetEmployeeId);
        
        if (!$this->employeeWithinLeaveCardScope($user, $employee)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $year = $request->get('year', date('Y'));

        $leaveHistory = $this->leaveCardService->getLeaveHistory($employee, $year);
        $currentBalances = $this->leaveCardService->getCurrentBalances($employee);

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_id' => $employee->employee_id,
            ],
            'year' => $year,
            'current_balances' => $currentBalances,
            'leave_history' => $leaveHistory['entries'],
            'printable' => true,
        ]);
    }

    /**
     * Show printable leave card view
     */
    public function showPrintableLeaveCard(Request $request, $employeeId = null)
    {
        $user = auth()->user();
        $canViewAllCards = $this->userCanViewAllLeaveCards($user);
        $canViewOfficeCards = $this->userHasDepartmentHeadOfficeScope($user);
        $userEmployeeId = optional($user->employee)->id;

        // Use provided employee ID or current user
        $targetEmployeeId = ($canViewAllCards || $canViewOfficeCards)
            ? ($employeeId ?? $userEmployeeId)
            : $userEmployeeId;

        if (!$targetEmployeeId) {
            abort(404, 'Employee not found');
        }

        $employee = Employee::findOrFail($targetEmployeeId);
        
        if (!$this->employeeWithinLeaveCardScope($user, $employee)) {
            abort(403, 'Unauthorized');
        }
        $year = $request->get('year', date('Y'));

        $leaveHistory = $this->leaveCardService->getLeaveHistory($employee, $year);
        $currentBalances = $this->leaveCardService->getCurrentBalances($employee);

        // Override VL/SL balances with the selected year's leave card when available
        $leaveCard = LeaveCard::where('employee_id', $employee->id)
            ->where('year', $year)
            ->first();

        if ($leaveCard) {
            $currentBalances['vl_balance'] = $leaveCard->vl_balance;
            $currentBalances['sl_balance'] = $leaveCard->sl_balance;
        }

        // Build per-leave-type summary using LeaveCredit for the selected year
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('code')->get();
        $credits = LeaveCredit::where('employee_id', $employee->id)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type_id');

        $summary = $leaveTypes->map(function ($type) use ($credits, $leaveCard) {
            $credit = $credits->get($type->id);

            $earned = $credit->earned_credits ?? 0;
            $used = $credit->used_credits ?? 0;
            $balance = $credit->remaining_credits ?? 0;

            // Keep VL/SL aligned with leave card balances when present
            if ($leaveCard) {
                if ($type->code === 'VL') {
                    $balance = $leaveCard->vl_balance;
                    $used = max(0, $earned - $balance);
                }
                if ($type->code === 'SL') {
                    $balance = $leaveCard->sl_balance;
                    $used = max(0, $earned - $balance);
                }
            }

            return [
                'name' => $type->name,
                'code' => $type->code,
                'earned' => $earned,
                'used' => $used,
                'balance' => $balance,
            ];
        });

        return view('leave-cards.print', compact(
            'employee',
            'year',
            'currentBalances',
            'leaveHistory',
            'summary'
        ));
    }

    private function userCanViewAllLeaveCards(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'HR Admin']) || $user->can('employee.manage');
    }

    /**
     * Department Head visibility is limited to their own office.
     */
    private function userHasDepartmentHeadOfficeScope(User $user): bool
    {
        return $user->hasRole('Department Head') && optional($user->employee)->office_id !== null;
    }

    /**
     * Determine if a target employee is within the viewer's allowed scope.
     */
    private function employeeWithinLeaveCardScope(User $user, Employee $employee): bool
    {
        if ($this->userCanViewAllLeaveCards($user)) {
            return true;
        }

        if ($this->userHasDepartmentHeadOfficeScope($user)) {
            $viewerOfficeId = optional($user->employee)->office_id;
            return $viewerOfficeId && (int) $employee->office_id === (int) $viewerOfficeId;
        }

        return optional($user->employee)->id === $employee->id;
    }
}
