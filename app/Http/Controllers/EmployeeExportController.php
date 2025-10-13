<?php

namespace App\Http\Controllers;

use App\Exports\EmployeesExport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeExportController extends Controller
{
    use AuthorizesRequests;

    /**
     * Export employees to Excel with creation timestamps
     */
    public function export(Request $request): BinaryFileResponse
    {
        // Only HR Admin and Super Admin can export employees
        $this->authorize('viewAny', \App\Models\Employee::class);

        $filename = 'employees_' . now()->format('Y_m_d_His') . '.xlsx';

        return Excel::download(new EmployeesExport(), $filename);
    }

    /**
     * Export filtered employees to Excel
     */
    public function exportFiltered(Request $request): BinaryFileResponse
    {
        // Only HR Admin and Super Admin can export employees
        $this->authorize('viewAny', \App\Models\Employee::class);

        $filename = 'employees_filtered_' . now()->format('Y_m_d_His') . '.xlsx';

        // We can enhance this later to apply the same filters as the index page
        return Excel::download(new EmployeesExport(), $filename);
    }
}