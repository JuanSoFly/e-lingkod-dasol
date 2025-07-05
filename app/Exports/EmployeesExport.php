<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Employee::all();
    }

    public function headings(): array
    {
        return [
            'Employee Number',
            'Last Name',
            'First Name',
            'Middle Name',
            'Position',
            'Department',
            'Employment Status',
            'Date Hired',
            'Email',
            'Contact Number',
            'Birth Date',
            'Gender',
            'Civil Status',
            'Address',
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->employee_number,
            $employee->last_name,
            $employee->first_name,
            $employee->middle_name,
            $employee->position,
            $employee->department,
            $employee->employment_status,
            $employee->date_hired->format('Y-m-d'),
            $employee->email,
            $employee->contact_number,
            $employee->birth_date->format('Y-m-d'),
            $employee->gender,
            $employee->civil_status,
            $employee->address,
        ];
    }
}