<?php

namespace App\Exports;

use App\Exports\Traits\WithExcelFormatting;
use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{
    use WithExcelFormatting;
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Employee::with('user')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Employee Number',
            'First Name',
            'Middle Name',
            'Last Name',
            'Email',
            'Contact Number',
            'Address',
            'Birth Date',
            'Gender',
            'Civil Status',
            'Position',
            'Department',
            'Employment Status',
            'Date Hired',
            'Salary Grade',
            'Step Increment',
            'Basic Salary',
            'Date Created',
        ];
    }

    /**
     * @param Employee $employee
     * @return array
     */
    public function map($employee): array
    {
        return [
            $employee->employee_number,
            $employee->first_name,
            $employee->middle_name,
            $employee->last_name,
            $employee->email,
            $employee->contact_number,
            $employee->address,
            $employee->birth_date?->format('m/d/Y'),
            $employee->gender,
            $employee->civil_status,
            $employee->position,
            $employee->department,
            $employee->employment_status,
            $employee->date_hired?->format('m/d/Y'),
            $employee->salary_grade,
            $employee->step_increment,
            $employee->basic_salary,
            $employee->created_at?->format('m/d/Y'),
        ];
    }

    /**
     * Custom column formatting for employee data.
     */
    protected function getColumnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,        // Employee Number
            'B' => NumberFormat::FORMAT_TEXT,        // First Name
            'C' => NumberFormat::FORMAT_TEXT,        // Middle Name
            'D' => NumberFormat::FORMAT_TEXT,        // Last Name
            'E' => NumberFormat::FORMAT_TEXT,        // Email
            'F' => NumberFormat::FORMAT_TEXT,        // Contact Number
            'G' => NumberFormat::FORMAT_TEXT,        // Address
            'H' => 'mm/dd/yyyy',                     // Birth Date
            'I' => NumberFormat::FORMAT_TEXT,        // Gender
            'J' => NumberFormat::FORMAT_TEXT,        // Civil Status
            'K' => NumberFormat::FORMAT_TEXT,        // Position
            'L' => NumberFormat::FORMAT_TEXT,        // Department
            'M' => NumberFormat::FORMAT_TEXT,        // Employment Status
            'N' => 'mm/dd/yyyy',                     // Date Hired
            'O' => NumberFormat::FORMAT_NUMBER,      // Salary Grade
            'P' => NumberFormat::FORMAT_NUMBER,      // Step Increment
            'Q' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Basic Salary
            'R' => 'mm/dd/yyyy',                     // Date Created
        ];
    }

    /**
     * Custom column widths for employee data.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15, // Employee Number
            'B' => 20, // First Name
            'C' => 20, // Middle Name
            'D' => 20, // Last Name
            'E' => 30, // Email
            'F' => 18, // Contact Number
            'G' => 35, // Address
            'H' => 12, // Birth Date
            'I' => 12, // Gender
            'J' => 15, // Civil Status
            'K' => 25, // Position
            'L' => 25, // Department
            'M' => 20, // Employment Status
            'N' => 12, // Date Hired
            'O' => 12, // Salary Grade
            'P' => 15, // Step Increment
            'Q' => 18, // Basic Salary
            'R' => 12, // Date Created
        ];
    }

    /**
     * Get the number of columns for this export.
     */
    protected function getColumnCount(): int
    {
        return 18; // A to R columns
    }

    /**
     * Custom title for the export.
     */
    public function title(): string
    {
        return 'Employees List';
    }
}