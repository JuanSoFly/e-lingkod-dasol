<?php

namespace App\Http\Controllers;

use App\Services\LeaveCardService;
use App\Models\Employee;
use App\Models\LeaveCard;
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

        // Employees can only see their own leave card
        if (!$user->hasPermissionTo('employee.manage')) {
            $employee = $user->employee;
            if (!$employee) {
                return response()->json(['error' => 'Employee profile not found'], 404);
            }
        } else {
            // HR/Admin can view any employee's leave card
            $employeeId = $request->input('employee_id');
            $employee = $employeeId ? Employee::findOrFail($employeeId) : $user->employee;

            if (!$employee) {
                return response()->json(['error' => 'Please select an employee'], 400);
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

        // Employees can only see their own leave card
        if (!$user->can('employee.manage') && !$user->can('leave.approve')) {
            $employee = $user->employee;
            if (!$employee) {
                return response()->json(['error' => 'Employee profile not found'], 404);
            }
        } else {
            // HR/Admin can view any employee's leave card
            $employeeId = $request->input('employee_id');
            $employee = $employeeId ? Employee::findOrFail($employeeId) : ($user->employee ?? null);

            if (!$employee) {
                return response()->json(['error' => 'Please select an employee'], 400);
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

        // Use provided employee ID or current user
        $targetEmployeeId = $employeeId ?? ($user->employee ? $user->employee->id : null);

        if (!$targetEmployeeId) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // Non-HR users can only view their own leave card
        if (!$user->hasPermissionTo('employee.manage')) {
            if ($user->employee && $targetEmployeeId != $user->employee->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $employee = Employee::findOrFail($targetEmployeeId);
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
}