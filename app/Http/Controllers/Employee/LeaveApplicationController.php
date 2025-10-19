<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\LeaveApplicationService;
use App\Services\LeaveCardService;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeaveApplicationController extends Controller
{
    public function __construct(
        private LeaveApplicationService $leaveApplicationService,
        private LeaveCardService $leaveCardService
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
                    'current_balance' => match($type->code) {
                        'VL' => $currentBalances['vl_balance'],
                        'SL' => $currentBalances['sl_balance'],
                        default => null,
                    },
                ];
            }),
            'current_balances' => $currentBalances,
        ]);
    }

    /**
     * Store a new leave application
     */
    public function store(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:500'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:2048'],
        ]);

        try {
            // Check for overlapping applications
            $this->validateNoOverlap($employee, $validated['start_date'], $validated['end_date']);

            // Validate against leave policy
            $daysRequested = $this->calculateDaysRequested(
                $validated['start_date'],
                $validated['end_date']
            );

            $application = $this->leaveApplicationService->createApplication(
                $employee,
                $validated['leave_type_id'],
                $validated['start_date'],
                $validated['end_date'],
                $daysRequested,
                $validated['reason']
            );

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
     * Get employee's leave applications
     */
    public function index(Request $request): JsonResponse
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
    public function saveDraft(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:2048'],
        ]);

        try {
            // Calculate days if dates are provided
            $daysRequested = 0;
            if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
                $daysRequested = $this->calculateDaysRequested(
                    $validated['start_date'],
                    $validated['end_date']
                );
            }

            // Create draft application
            $application = LeaveApplication::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'days_requested' => $daysRequested,
                'reason' => $validated['reason'] ?? '',
                'status' => 'draft',
                'applied_date' => now(),
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
     * Calculate days requested for leave
     */
    private function calculateDaysRequested(string $startDate, string $endDate): float
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        // Simple calculation - can be enhanced to exclude weekends/holidays
        return $start->diffInDays($end) + 1;
    }

  }