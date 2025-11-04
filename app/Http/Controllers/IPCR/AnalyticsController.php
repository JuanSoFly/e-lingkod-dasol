<?php

namespace App\Http\Controllers\IPCR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\IpcrReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(protected IpcrReportingService $reportingService)
    {
        $this->middleware(['auth', 'verified']);
    }

    public function individual(Request $request): View
    {
        $employeeId = $request->integer('employee_id', Auth::user()->employee_id);

        abort_unless($employeeId, 403);

        if ($employeeId !== Auth::user()->employee_id) {
            abort_unless(Auth::user()->can('ipcr.analytics'), 403);
        }

        $report = $this->reportingService->individualReport($employeeId, $request->only('period_id'));

        $employees = Employee::orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return view('ipcr.analytics.individual', [
            'report' => $report,
            'selected_employee_id' => $employeeId,
            'period_id' => $request->input('period_id'),
            'employees' => $employees,
        ]);
    }

    public function office(Request $request): View
    {
        abort_unless(Auth::user()->can('ipcr.analytics'), 403);

        $analytics = $this->reportingService->officeAnalytics($request->only('period_id', 'office_id'));

        return view('ipcr.analytics.office', [
            'analytics' => $analytics,
            'filters' => $request->only('period_id', 'office_id'),
        ]);
    }

    public function compliance(Request $request): View
    {
        abort_unless(Auth::user()->can('ipcr.analytics'), 403);

        $snapshot = $this->reportingService->complianceSnapshot($request->only('period_id'));

        return view('ipcr.analytics.compliance', [
            'snapshot' => $snapshot,
            'period_id' => $request->input('period_id'),
            'periods' => \App\Models\PerformancePeriod::orderByDesc('start_date')->get(['id', 'name']),
        ]);
    }
}
