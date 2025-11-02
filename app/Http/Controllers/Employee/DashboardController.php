<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\LeaveCardService;
use App\Services\LeaveApplicationService;
use App\Services\HolidayService;
use App\Models\Employee;
use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private LeaveCardService $leaveCardService,
        private LeaveApplicationService $leaveApplicationService,
        private HolidayService $holidayService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display employee dashboard
     */
    public function index(Request $request)
    {
        $employee = auth()->user()->employee;
        $year = $request->input('year', now()->year);

        // Get current balances
        $currentBalances = $this->leaveCardService->getCurrentBalances($employee);

        // Get recent applications
        $recentApplications = LeaveApplication::where('employee_id', $employee->id)
            ->with(['leaveType', 'approver'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get leave history for the year
        $leaveHistory = $this->leaveCardService->getLeaveHistory($employee, $year);

        // Get upcoming approved leave (include ongoing leave)
        $upcomingLeave = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('end_date', '>=', now())
            ->with(['leaveType'])
            ->orderBy('start_date')
            ->limit(3)
            ->get();

        // Get pending applications count
        $pendingCount = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        return view('employee-portal.leave-dashboard', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_id' => $employee->employee_id,
                'position' => $employee->position,
                'department' => $employee->department,
            ],
            'current_balances' => $currentBalances,
            'recent_applications' => $recentApplications->map(function ($app) {
                return [
                    'id' => $app->id,
                    'leave_type' => $app->leaveType->name,
                    'start_date' => $app->start_date->format('M d, Y'),
                    'end_date' => $app->end_date->format('M d, Y'),
                    'days_requested' => $app->days_requested,
                    'status' => $app->status,
                    'applied_date' => $app->applied_date->format('M d, Y'),
                ];
            }),
            'upcoming_leave' => $upcomingLeave->map(function ($leave) {
                $today = now()->startOfDay();
                $endDate = $leave->end_date->startOfDay();

                // Calculate remaining days only if leave is still active/ongoing
                if ($today->lte($endDate)) {
                    $remainingDays = intval($today->diffInDays($endDate)) + 1;
                } else {
                    $remainingDays = 0;
                }

                return [
                    'id' => $leave->id,
                    'leave_type' => $leave->leaveType->name,
                    'start_date' => $leave->start_date->format('M d, Y'),
                    'end_date' => $leave->end_date->format('M d, Y'),
                    'days' => $leave->days_requested,
                    'remaining_days' => $remainingDays,
                ];
            }),
            'leave_history' => $leaveHistory['entries'],
            'statistics' => [
                'pending_applications' => $pendingCount,
                'total_leave_used' => $leaveHistory['entries']->sum('days'),
                'vl_used' => $leaveHistory['entries']->where('leave_type_code', 'VL')->sum('days'),
                'sl_used' => $leaveHistory['entries']->where('leave_type_code', 'SL')->sum('days'),
            ],
        ]);
    }

    /**
     * Get leave balance analytics
     */
    public function getBalanceAnalytics(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $year = $request->input('year', now()->year);

        $currentBalances = $this->leaveCardService->getCurrentBalances($employee);
        $leaveHistory = $this->leaveCardService->getLeaveHistory($employee, $year);

        // Calculate monthly usage
        $monthlyUsage = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthHistory = $leaveHistory['entries']->filter(function ($entry) use ($month) {
                return date('n', strtotime($entry['date'])) == $month;
            });

            $monthlyUsage[] = [
                'month' => date('M', mktime(0, 0, 0, $month, 1)),
                'vl_days' => $monthHistory->where('leave_type_code', 'VL')->sum('days'),
                'sl_days' => $monthHistory->where('leave_type_code', 'SL')->sum('days'),
                'total_days' => $monthHistory->sum('days'),
            ];
        }

        return response()->json([
            'current_balances' => $currentBalances,
            'monthly_usage' => $monthlyUsage,
            'yearly_totals' => [
                'vl_used' => $leaveHistory['entries']->where('leave_type_code', 'VL')->sum('days'),
                'sl_used' => $leaveHistory['entries']->where('leave_type_code', 'SL')->sum('days'),
                'total_used' => $leaveHistory['entries']->sum('days'),
            ],
        ]);
    }

    /**
     * Get calendar data for leave visualization
     */
    public function getCalendarData(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        $workWeek = $employee->workCalendar?->work_week;

        $approvedLeave = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->with(['leaveType'])
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'leave_type' => $leave->leaveType->name,
                    'start_date' => $leave->start_date->format('Y-m-d'),
                    'end_date' => $leave->end_date->format('Y-m-d'),
                    'days' => $leave->days_requested,
                    'status' => $leave->status,
                ];
            });

        $holidaySummaries = $this->holidayService->getHolidaySummaries($start, $end, $employee);
        $nonWorkingDates = $this->holidayService->getNonWorkingDates($start, $end, $employee, $workWeek);

        return response()->json([
            'approved_leave' => $approvedLeave,
            'holidays' => $holidaySummaries,
            'non_working_dates' => $nonWorkingDates,
            'work_week' => $workWeek,
        ]);
    }

    /**
     * Get HR announcements and policy updates
     */
    public function getAnnouncements(Request $request): JsonResponse
    {
        // Mock announcements data - in real implementation, this would come from database
        $announcements = [
            [
                'id' => 1,
                'title' => 'Leave Policy Update',
                'excerpt' => 'Updated guidelines for vacation and sick leave applications are now available.',
                'date' => 'Oct 15, 2025',
                'link' => '/policies/leave-updates',
                'priority' => 'high'
            ],
            [
                'id' => 2,
                'title' => 'Holiday Schedule 2025',
                'excerpt' => 'The list of official holidays for 2025 has been published.',
                'date' => 'Oct 10, 2025',
                'link' => '/holidays/2025',
                'priority' => 'medium'
            ],
            [
                'id' => 3,
                'title' => 'New Document Upload Feature',
                'excerpt' => 'You can now upload supporting documents for your leave applications.',
                'date' => 'Oct 5, 2025',
                'link' => '/help/document-uploads',
                'priority' => 'low'
            ]
        ];

        return response()->json([
            'announcements' => $announcements,
        ]);
    }

    /**
     * Get comprehensive leave history
     */
    public function getLeaveHistory(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        $query = LeaveApplication::where('employee_id', $employee->id)
            ->with(['leaveType', 'approver']);

        // Apply filters
        if ($request->input('leave_type')) {
            $query->whereHas('leaveType', function ($q) use ($request) {
                $q->where('code', $request->input('leave_type'));
            });
        }

        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->input('year')) {
            $query->whereYear('start_date', $request->input('year'));
        }

        $history = $query->orderBy('start_date', 'desc')->get()->map(function ($record) {
            return [
                'id' => $record->id,
                'leave_type' => $record->leaveType->name,
                'leave_type_code' => $record->leaveType->code,
                'start_date' => $record->start_date->format('Y-m-d'),
                'end_date' => $record->end_date->format('Y-m-d'),
                'days_requested' => $record->days_requested,
                'reason' => $record->reason,
                'status' => $record->status,
                'applied_date' => $record->applied_date->format('Y-m-d'),
                'approved_date' => $record->approved_date?->format('Y-m-d'),
                'approver' => $record->approver?->name,
                'remarks' => $record->remarks,
            ];
        });

        // Calculate analytics
        $approvedHistory = $history->where('status', 'approved');
        $currentYear = now()->year;
        $yearsActive = max(1, $history->count() > 0 ?
            now()->year - Carbon::parse($history->last()['applied_date'])->year + 1 : 1);

        $analytics = [
            'total_used' => $approvedHistory->sum('days_requested'),
            'vl_used' => $approvedHistory->where('leave_type_code', 'VL')->sum('days_requested'),
            'sl_used' => $approvedHistory->where('leave_type_code', 'SL')->sum('days_requested'),
            'average_yearly' => round($approvedHistory->sum('days_requested') / $yearsActive, 1),
        ];

        // Get current balances for projections
        $currentBalances = $this->leaveCardService->getCurrentBalances($employee);

        // Calculate projections (simplified)
        $projectedVl = $currentBalances['vl_balance'] - $analytics['vl_used'];
        $projectedSl = $currentBalances['sl_balance'] - $analytics['sl_used'];

        $projections = [
            'current_vl' => $currentBalances['vl_balance'],
            'projected_vl' => max(0, $projectedVl),
            'current_sl' => $currentBalances['sl_balance'],
        ];

        return response()->json([
            'history' => $history,
            'analytics' => $analytics,
            'projections' => $projections,
        ]);
    }

    /**
     * Get detailed analytics data
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $year = $request->input('year', now()->year);

        // Get current balances
        $currentBalances = $this->leaveCardService->getCurrentBalances($employee);

        // Get leave history for calculations
        $leaveHistory = $this->leaveCardService->getLeaveHistory($employee, $year);
        $approvedEntries = $leaveHistory['entries']->where('status', 'approved');

        // Calculate yearly totals
        $yearlyTotals = [
            'vl_used' => $approvedEntries->where('leave_type_code', 'VL')->sum('days'),
            'sl_used' => $approvedEntries->where('leave_type_code', 'SL')->sum('days'),
            'total_used' => $approvedEntries->sum('days'),
        ];

        // Calculate projections
        $projectedVl = $currentBalances['vl_balance'] - $yearlyTotals['vl_used'];
        $projectedSl = $currentBalances['sl_balance'] - $yearlyTotals['sl_used'];

        $projections = [
            'current_vl' => $currentBalances['vl_balance'],
            'projected_vl' => max(0, $projectedVl),
            'current_sl' => $currentBalances['sl_balance'],
            'projected_sl' => max(0, $projectedSl),
        ];

        // Calculate usage patterns
        $totalYears = max(1, Carbon::parse($employee->hire_date)->diffInYears(now()) + 1);
        $analytics = [
            'total_used' => $yearlyTotals['total_used'],
            'vl_used' => $yearlyTotals['vl_used'],
            'sl_used' => $yearlyTotals['sl_used'],
            'average_yearly' => round($yearlyTotals['total_used'] / ($year - Carbon::parse($employee->hire_date)->year + 1), 1),
        ];

        return response()->json([
            'analytics' => $analytics,
            'projections' => $projections,
            'yearly_totals' => $yearlyTotals,
            'current_balances' => $currentBalances,
        ]);
    }

    /**
     * Export leave history to Excel
     */
    public function exportLeaveHistory(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $year = $request->input('year', now()->year);

        // Get leave history data
        $historyResponse = $this->getLeaveHistory($request);
        $historyData = json_decode($historyResponse->getContent(), true);

        // Prepare export data
        $exportData = [];
        $exportData[] = ['Leave Type', 'Start Date', 'End Date', 'Days', 'Status', 'Applied Date', 'Approver', 'Remarks'];

        foreach ($historyData['history'] as $record) {
            $exportData[] = [
                $record['leave_type'],
                $record['start_date'],
                $record['end_date'],
                $record['days_requested'],
                ucfirst($record['status']),
                $record['applied_date'],
                $record['approver'] ?? 'N/A',
                $record['remarks'] ?? ''
            ];
        }

        // Generate filename
        $filename = "leave_history_{$employee->employee_id}_{$year}.csv";

        // Create CSV content
        $csvContent = '';
        foreach ($exportData as $row) {
            $csvContent .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Get printable leave card data
     */
    public function getPrintableLeaveCard(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $year = $request->input('year', now()->year);

        // Get current balances
        $currentBalances = $this->leaveCardService->getCurrentBalances($employee);

        // Get leave history
        $historyResponse = $this->getLeaveHistory($request);
        $historyData = json_decode($historyResponse->getContent(), true);

        // Filter approved leave only
        $approvedLeave = array_filter($historyData['history'], function($record) {
            return $record['status'] === 'approved';
        });

        return response()->json([
            'employee' => [
                'name' => $employee->full_name,
                'employee_id' => $employee->employee_id,
                'position' => $employee->position,
                'department' => $employee->department,
            ],
            'year' => $year,
            'current_balances' => $currentBalances,
            'approved_leave' => $approvedLeave,
            'analytics' => $historyData['analytics'],
            'generated_date' => now()->format('F d, Y'),
        ]);
    }
}
