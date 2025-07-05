<?php

namespace App\Http\Controllers;

use App\Exports\EmployeesExport;
use App\Exports\LeaveBalanceExport; // Add this
use App\Exports\PerformanceSummaryExport; // Add this
use App\Models\Employee;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        return view('reports.index');
    }

    // --- Employee Reports ---
    public function exportEmployeesExcel()
    {
        $this->authorize('reports.export');
        return Excel::download(new EmployeesExport, 'employee-masterlist-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportEmployeesPdf()
    {
        $this->authorize('reports.export');
        $employees = Employee::with('user')->get();
        $pdf = Pdf::loadView('reports.pdf.employees', compact('employees'));
        return $pdf->setPaper('a4', 'landscape')->download('employee-masterlist-' . now()->format('Y-m-d') . '.pdf');
    }

    // --- Leave Reports (New) ---
    public function exportLeaveBalancesExcel()
    {
        $this->authorize('reports.export');
        return Excel::download(new LeaveBalanceExport, 'leave-balance-summary-' . now()->format('Y-m-d') . '.xlsx');
    }
    
    // --- Performance Reports (New) ---
    public function exportPerformanceSummaryExcel()
    {
        $this->authorize('reports.export');
        return Excel::download(new PerformanceSummaryExport, 'performance-summary-' . now()->format('Y-m-d') . '.xlsx');
    }
}