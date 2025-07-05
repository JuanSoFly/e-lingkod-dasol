<?php

namespace App\Exports;

use App\Models\LeaveCredit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeaveBalanceExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return LeaveCredit::with(['employee', 'leaveType'])->get();
    }

    public function headings(): array
    {
        return [
            'Employee Number',
            'Employee Name',
            'Leave Type',
            'Year',
            'Earned',
            'Used',
            'Remaining',
        ];
    }

    public function map($credit): array
    {
        return [
            $credit->employee->employee_number,
            $credit->employee->first_name . ' ' . $credit->employee->last_name,
            $credit->leaveType->name,
            $credit->year,
            $credit->earned_credits,
            $credit->used_credits,
            $credit->remaining_credits,
        ];
    }
}