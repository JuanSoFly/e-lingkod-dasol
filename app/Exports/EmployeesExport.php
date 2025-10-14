<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithStyles
{
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
            $employee->created_at?->format('m/d/Y'),
        ];
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
            'H' => 'mm/dd/yyyy',
            'I' => NumberFormat::FORMAT_TEXT,
            'J' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
            'L' => NumberFormat::FORMAT_TEXT,
            'M' => NumberFormat::FORMAT_TEXT,
            'N' => 'mm/dd/yyyy',
            'O' => NumberFormat::FORMAT_NUMBER,
            'P' => NumberFormat::FORMAT_NUMBER,
            'Q' => 'mm/dd/yyyy',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],

            // Set column widths
            'A' => ['width' => 15],
            'B' => ['width' => 15],
            'C' => ['width' => 15],
            'D' => ['width' => 15],
            'E' => ['width' => 25],
            'F' => ['width' => 15],
            'G' => ['width' => 30],
            'H' => ['width' => 12],
            'I' => ['width' => 10],
            'J' => ['width' => 12],
            'K' => ['width' => 20],
            'L' => ['width' => 15],
            'M' => ['width' => 15],
            'N' => ['width' => 12],
            'O' => ['width' => 10],
            'P' => ['width' => 10],
            'Q' => ['width' => 12],
        ];
    }
}