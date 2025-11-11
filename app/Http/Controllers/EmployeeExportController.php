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

        $filters = $this->extractEmployeeFilters($request);

        return Excel::download(new EmployeesExport($filters), $filename);
    }

    /**
     * Export filtered employees to Excel
     */
    public function exportFiltered(Request $request): BinaryFileResponse
    {
        // Only HR Admin and Super Admin can export employees
        $this->authorize('viewAny', \App\Models\Employee::class);

        $filename = 'employees_filtered_' . now()->format('Y_m_d_His') . '.xlsx';

        $filters = $this->extractEmployeeFilters($request);

        return Excel::download(new EmployeesExport($filters), $filename);
    }

    /**
     * Mirror the employee list filters when exporting.
     */
    protected function extractEmployeeFilters(Request $request): array
    {
        $filters = $request->only([
            'search',
            'department',
            'position',
            'employment_status',
            'office_id',
            'is_department_head',
        ]);

        if ($request->has('is_department_head')) {
            $filters['is_department_head'] = $request->boolean('is_department_head');
        }

        return $filters;
    }
}
