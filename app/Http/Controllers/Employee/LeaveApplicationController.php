<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\HolidayService;
use App\Services\LeaveApplicationService;
use App\Services\LeaveCardService;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Http\Requests\StoreEmployeeLeaveApplicationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeaveApplicationController extends Controller
{
    public function __construct(
        private LeaveApplicationService $leaveApplicationService,
        private LeaveCardService $leaveCardService,
        private HolidayService $holidayService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display available leave types and current balances
     */
    public function create(): JsonResponse
    {
        $employee = auth()->user()->employee;
        $currentBalances = $this->leaveCardService->getCurrentBalances($employee);

        $leaveTypes = LeaveType::with(['policies' => function ($query) use ($employee) {
            $query->where('is_active', true)
                ->where(function ($q) use ($employee) {
                    $q->whereNull('employee_type')
                        ->orWhere('employee_type', $employee->employment_status);
                });
        }])->get();

        return response()->json([
            'leave_types' => $leaveTypes->map(function ($type) use ($currentBalances) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'code' => $type->code,
                    'description' => $type->description,
                    'requires_document' => $type->requires_document,
                    'max_consecutive_days' => $type->max_consecutive_days,
                    'current_balance' => $currentBalances[$type->code] ?? 0,
                ];
            }),
            'current_balances' => $currentBalances,
        ]);
    }

    /**
     * Store a new leave application
     */
    public function store(StoreEmployeeLeaveApplicationRequest $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        $validated = $request->validated();

        // Debug logging for validation attempts
        \Log::info('Leave application submission attempt', [
            'employee_id' => $employee->id,
            'validated_data' => $validated,
            'dept_head_informed_value' => $validated['dept_head_informed'] ?? 'missing',
            'dept_head_informed_type' => gettype($validated['dept_head_informed'] ?? null)
        ]);

        try {
            // Check for overlapping applications
            $this->validateNoOverlap($employee, $validated['start_date'], $validated['end_date']);

            // Check for existing pending applications (NEW VALIDATION)
            $this->validateNoPendingApplications($employee);

            // Validate leave credits before submission (NEW VALIDATION)
            $this->validateLeaveCredits($employee, $validated['leave_type_id']);

            // Validate against leave policy
            $daysRequested = $this->calculateDaysRequested(
                $employee,
                $validated['start_date'],
                $validated['end_date']
            );

            $application = $this->leaveApplicationService->createApplication([
                'employee_id' => $employee->id,
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'days_requested' => $daysRequested,
                'reason' => $validated['reason'],
                'status' => 'pending',
                'applied_date' => now(),
                'dept_head_informed' => ($validated['dept_head_informed'] ?? '0') === '1',
                'dept_head_informed_date' => ($validated['dept_head_informed'] ?? '0') === '1' ? now() : null,
            ], auth()->user());

            // Handle document uploads
            if (!empty($validated['documents'])) {
                foreach ($validated['documents'] as $document) {
                    $this->leaveApplicationService->attachDocument(
                        $application,
                        $document,
                        auth()->user()
                    );
                }
            }

            return response()->json([
                'message' => 'Leave application submitted successfully',
                'application' => $application->load(['leaveType', 'employee']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit leave application',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display leave applications page
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $employee = auth()->user()->employee;

        return view('employee-portal.leave-applications.index', [
            'employee' => $employee,
        ]);
    }

    /**
     * Get employee's leave applications data (API endpoint)
     */
    public function getData(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        $applications = LeaveApplication::where('employee_id', $employee->id)
            ->with(['leaveType', 'approver'])
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('year'), function ($query, $year) {
                $query->whereYear('start_date', $year);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'applications' => $applications->map(function ($app) {
                return [
                    'id' => $app->id,
                    'leave_type' => $app->leaveType->name,
                    'leave_type_code' => $app->leaveType->code,
                    'start_date' => $app->start_date->format('Y-m-d'),
                    'end_date' => $app->end_date->format('Y-m-d'),
                    'days_requested' => $app->days_requested,
                    'reason' => $app->reason,
                    'status' => $app->status,
                    'applied_date' => $app->applied_date->format('Y-m-d'),
                    'approved_date' => $app->approved_date?->format('Y-m-d'),
                    'approver' => $app->approver?->name,
                    'remarks' => $app->remarks,
                    'documents' => $app->getSupportingDocuments()->map(function ($doc) {
                        return [
                            'id' => $doc->id,
                            'filename' => $doc->filename,
                            'file_path' => $doc->file_path,
                        ];
                    }),
                ];
            }),
            'pagination' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ]);
    }

    public function calculateDays(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Employee profile not found.',
                'error_code' => 'EMPLOYEE_NOT_FOUND'
            ], 404);
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        try {
            // Validate employee has work calendar assigned
            if (!$employee->workCalendar) {
                \Log::warning('Leave application attempt without work calendar', [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'work_calendar_id' => $employee->work_calendar_id,
                    'action' => 'leave_calculation_request'
                ]);

                return response()->json([
                    'message' => 'No work calendar assigned to employee. Please contact HR to ensure your work schedule is properly configured.',
                    'error_code' => 'NO_WORK_CALENDAR',
                    'employee_id' => $employee->id,
                    'work_calendar_id' => $employee->work_calendar_id,
                    'hr_action_required' => 'Assign work calendar to employee profile'
                ], 422);
            }

            $days = $this->calculateDaysRequested(
                $employee,
                $validated['start_date'],
                $validated['end_date']
            );

            $start = \Carbon\Carbon::parse($validated['start_date'], config('app.timezone', 'Asia/Manila'))->startOfDay();
            $end = \Carbon\Carbon::parse($validated['end_date'], config('app.timezone', 'Asia/Manila'))->startOfDay();

            $workWeek = $employee->workCalendar->work_week;

            $nonWorkingDates = $this->holidayService->getNonWorkingDates($start, $end, $employee, $workWeek);
            $holidaySummaries = $this->holidayService->getHolidaySummaries($start, $end, $employee);

            // Enhanced response with work calendar info for debugging
            return response()->json([
                'days' => $days,
                'non_working_dates' => $nonWorkingDates,
                'holidays' => $holidaySummaries,
                'work_calendar' => [
                    'id' => $employee->workCalendar->id,
                    'name' => $employee->workCalendar->name,
                    'work_week' => $workWeek,
                ],
                'calculation_breakdown' => [
                    'total_days' => $start->diffInDays($end) + 1,
                    'working_days' => $days,
                    'excluded_days_count' => count($nonWorkingDates),
                    'date_range' => [
                        'start' => $start->format('Y-m-d'),
                        'end' => $end->format('Y-m-d')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Leave calculation error', [
                'employee_id' => $employee->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'CALCULATION_ERROR',
                'employee_id' => $employee->id,
                'work_calendar_id' => $employee->work_calendar_id
            ], 422);
        }
    }

    /**
     * Withdraw a pending leave application
     */
    public function withdraw(LeaveApplication $leaveApplication): JsonResponse
    {
        $employee = auth()->user()->employee;

        // Ensure employee can only withdraw their own applications
        if ($leaveApplication->employee_id !== $employee->id) {
            return response()->json([
                'message' => 'Unauthorized action',
            ], 403);
        }

        // Can only withdraw pending applications
        if ($leaveApplication->status !== 'pending') {
            return response()->json([
                'message' => 'Can only withdraw pending applications',
            ], 422);
        }

        try {
            $leaveApplication->update([
                'status' => 'withdrawn',
                'remarks' => $leaveApplication->remarks . "\n\nWithdrawn by employee on " . now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'message' => 'Application withdrawn successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to withdraw application',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Save leave application as draft
     */
    public function saveDraft(StoreEmployeeLeaveApplicationRequest $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        // Add is_draft flag to request data
        $request->merge(['is_draft' => true]);
        $validated = $request->validated();

        try {
            // Calculate days if dates are provided
            $daysRequested = 0;
            if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
                $daysRequested = $this->calculateDaysRequested(
                    $employee,
                    $validated['start_date'],
                    $validated['end_date']
                );
            }

            // Create draft application
            $application = LeaveApplication::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'] ?? now()->format('Y-m-d'),
                'end_date' => $validated['end_date'] ?? $validated['start_date'] ?? now()->format('Y-m-d'),
                'days_requested' => $daysRequested,
                'reason' => $validated['reason'] ?? '',
                'status' => 'draft',
                'applied_date' => now(),
                'dept_head_informed' => ($validated['dept_head_informed'] ?? '0') === '1',
                'dept_head_informed_date' => ($validated['dept_head_informed'] ?? '0') === '1' ? now() : null,
            ]);

            // Handle document uploads if provided
            if (!empty($validated['documents'])) {
                foreach ($validated['documents'] as $document) {
                    $this->leaveApplicationService->attachDocument(
                        $application,
                        $document,
                        auth()->user()
                    );
                }
            }

            return response()->json([
                'message' => 'Draft saved successfully',
                'application' => $application->load(['leaveType']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save draft',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validate no overlapping leave applications
     */
    private function validateNoOverlap(Employee $employee, string $startDate, string $endDate): void
    {
        $overlappingApplications = LeaveApplication::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        if ($overlappingApplications) {
            throw new \Exception('You already have a leave application for this period.');
        }
    }

    /**
     * Validate that employee has no pending applications
     */
    private function validateNoPendingApplications(Employee $employee): void
    {
        $hasPendingApplication = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingApplication) {
            throw new \Exception('You already have a pending leave application. Please wait for it to be approved or rejected before submitting a new one.');
        }
    }

    /**
     * Validate that employee has sufficient leave credits
     */
    private function validateLeaveCredits(Employee $employee, int $leaveTypeId): void
    {
        $leaveType = \App\Models\LeaveType::find($leaveTypeId);
        if (!$leaveType) {
            throw new \Exception('Invalid leave type selected.');
        }

        $currentBalances = $this->leaveCardService->getCurrentBalances($employee);
        $availableCredits = $currentBalances[$leaveType->code] ?? 0;

        if ($availableCredits <= 0) {
            throw new \Exception("You have no available {$leaveType->name} credits. Please contact HR for assistance.");
        }

        // Log the validation for audit purposes
        \Log::info('Leave credit validation', [
            'employee_id' => $employee->id,
            'leave_type' => $leaveType->code,
            'available_credits' => $availableCredits
        ]);
    }

    /**
     * Calculate days requested for leave
     */
    private function calculateDaysRequested(\App\Models\Employee $employee, string $startDate, string $endDate): int
    {
        $start = \Carbon\Carbon::parse($startDate, config('app.timezone', 'Asia/Manila'))->startOfDay();
        $end = \Carbon\Carbon::parse($endDate, config('app.timezone', 'Asia/Manila'))->startOfDay();

        $workWeek = $employee->workCalendar?->work_week;

        $this->assertWorkingDay($employee, $start, 'start_date');
        $this->assertWorkingDay($employee, $end, 'end_date');

        $days = $this->holidayService->businessDaysBetween($start, $end, $employee, $workWeek);

        if ($days <= 0) {
            throw new \Exception('Selected date range does not include any working days.');
        }

        return $days;
    }

    private function assertWorkingDay(\App\Models\Employee $employee, \Carbon\Carbon $date, string $field): void
    {
        $workWeek = $employee->workCalendar?->work_week;

        if (!$this->holidayService->isWorkingWeekday($date, $workWeek)) {
            throw new \Exception(ucfirst(str_replace('_', ' ', $field)) . ' falls on a non-working day.');
        }

        if ($this->holidayService->isNonWorking($date, $employee)) {
            throw new \Exception(ucfirst(str_replace('_', ' ', $field)) . ' falls on a non-working holiday.');
        }
    }

  }
