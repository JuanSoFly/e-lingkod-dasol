<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HolidayService;
use App\Models\Employee;
use Carbon\Carbon;

class DebugLeaveCalculation extends Command
{
    protected $signature = 'leave:debug {employee_id? : Employee ID to test with} {start_date? : Start date (YYYY-MM-DD)} {end_date? : End date (YYYY-MM-DD)}';
    protected $description = 'Debug leave calculation for specific date ranges';

    protected $holidayService;

    public function __construct(HolidayService $holidayService)
    {
        parent::__construct();
        $this->holidayService = $holidayService;
    }

    public function handle()
    {
        $employeeId = $this->argument('employee_id') ?? 1;
        $startDate = $this->argument('start_date') ?? '2025-11-04';
        $endDate = $this->argument('end_date') ?? '2025-11-22';

        $this->info("Testing leave calculation for Employee #{$employeeId}");
        $this->info("Date range: {$startDate} to {$endDate}");
        $this->newLine();

        $employee = Employee::find($employeeId);
        if (!$employee) {
            $this->error("Employee #{$employeeId} not found!");
            return 1;
        }

        $this->info("Employee: {$employee->first_name} {$employee->last_name}");
        $this->info("Work Calendar: {$employee->workCalendar->name}");
        $this->info("Work Week: " . json_encode($employee->workCalendar->work_week));
        $this->newLine();

        // Test the calculation
        try {
            $businessDays = $this->holidayService->businessDaysBetween(
                Carbon::parse($startDate),
                Carbon::parse($endDate),
                $employee,
                $employee->workCalendar->work_week
            );

            $this->info("Business days calculated: {$businessDays}");

            // Let's also manually trace each day
            $this->newLine();
            $this->info("Day-by-day analysis:");
            $current = Carbon::parse($startDate);
            $actualBusinessDays = 0;
            $weekendDays = [];
            $holidayDays = [];

            while ($current->lte(Carbon::parse($endDate))) {
                $dayName = strtolower($current->format('D'));
                $isWorkDay = $employee->workCalendar->work_week[$dayName] ?? 0;
                $isHoliday = $this->holidayService->isHoliday($current);

                $this->info(
                    $current->format('Y-m-d D') .
                    " | Work day: " . ($isWorkDay ? 'YES' : 'NO') .
                    " | Holiday: " . ($isHoliday ? 'YES' : 'NO') .
                    " | Counted: " . (($isWorkDay && !$isHoliday) ? 'YES' : 'NO')
                );

                if ($isWorkDay && !$isHoliday) {
                    $actualBusinessDays++;
                } elseif (!$isWorkDay) {
                    $weekendDays[] = $current->format('Y-m-d');
                } elseif ($isHoliday) {
                    $holidayDays[] = $current->format('Y-m-d');
                }

                $current->addDay();
            }

            $this->newLine();
            $this->info("Summary:");
            $this->info("Total days: " . Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1);
            $this->info("Business days (manual count): {$actualBusinessDays}");
            $this->info("Weekend days: " . implode(', ', $weekendDays));
            $this->info("Holiday days: " . implode(', ', $holidayDays));

            if ($businessDays !== $actualBusinessDays) {
                $this->error("MISMATCH! Service returned {$businessDays}, manual count is {$actualBusinessDays}");
            } else {
                $this->info("Calculation is consistent!");
            }

        } catch (\Exception $e) {
            $this->error("Error during calculation: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }

        return 0;
    }
}