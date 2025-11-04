<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;

class HolidayService
{
    public function isHoliday(Carbon $date, ?Employee $employee = null): ?Holiday
    {
        $cacheKey = 'holidays:' . $date->year;

        $holidays = Cache::remember($cacheKey, now()->addDay(), function () use ($date) {
            return Holiday::where('year', $date->year)->get();
        });

        return $holidays->first(function (Holiday $holiday) use ($date, $employee) {
            $targetDate = $holiday->observed_date
                ? $holiday->observed_date->toDateString()
                : $holiday->date->toDateString();

            if ($targetDate !== $date->toDateString()) {
                return false;
            }

            if ($holiday->scope === 'national') {
                return true;
            }

            if (!$employee) {
                return false;
            }

            $employeeScopeCode = $employee->office_code ?? optional($employee->office)->code ?? null;

            if (!$employeeScopeCode) {
                return false;
            }

            return $employeeScopeCode === $holiday->scope_code;
        });
    }

    public function isNonWorking(Carbon $date, ?Employee $employee = null): bool
    {
        $holiday = $this->isHoliday($date, $employee);

        return $holiday ? (bool) $holiday->is_non_working : false;
    }

    public function isWorkingWeekday(Carbon $date, ?array $workWeek = null): bool
    {
        // Use the same validation logic
        $workWeek = $this->validateAndSanitizeWorkWeek($workWeek);

        $map = [
            'Mon' => 'mon',
            'Tue' => 'tue',
            'Wed' => 'wed',
            'Thu' => 'thu',
            'Fri' => 'fri',
            'Sat' => 'sat',
            'Sun' => 'sun',
        ];

        return (bool) ($workWeek[$map[$date->format('D')] ?? ''] ?? 0);
    }

    /**
     * Validate and sanitize workWeek input with proper fallbacks
     */
    private function validateAndSanitizeWorkWeek(?array $workWeek, ?Employee $employee = null): array
    {
        // Default Mon-Fri schedule
        $defaultWorkWeek = [
            'mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 0, 'sun' => 0
        ];

        // If workWeek is provided, validate and sanitize it
        if ($workWeek !== null) {
            return array_merge($defaultWorkWeek, array_intersect_key($workWeek, $defaultWorkWeek));
        }

        // Try to get from employee's work calendar
        if ($employee && $employee->workCalendar) {
            $employeeWorkWeek = $employee->workCalendar->work_week;
            if (is_array($employeeWorkWeek) || is_object($employeeWorkWeek)) {
                $employeeWorkWeek = (array) $employeeWorkWeek;
                return array_merge($defaultWorkWeek, array_intersect_key($employeeWorkWeek, $defaultWorkWeek));
            }
        }

        // Log warning if we couldn't get proper work week data
        if ($employee) {
            \Log::warning('No valid work calendar found for employee', [
                'employee_id' => $employee->id,
                'work_calendar_id' => $employee->work_calendar_id,
                'fallback_used' => 'Mon-Fri default'
            ]);
        }

        return $defaultWorkWeek;
    }

    public function businessDaysBetween(Carbon $start, Carbon $end, ?Employee $employee = null, ?array $workWeek = null): int
    {
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        // Validate and sanitize workWeek input
        $workWeek = $this->validateAndSanitizeWorkWeek($workWeek, $employee);

        $count = 0;
        foreach (CarbonPeriod::create($start, $end) as $day) {
            if ($this->isWorkingWeekday($day, $workWeek) && !$this->isNonWorking($day, $employee)) {
                $count++;
            }
        }

        return $count;
    }

    public function getHolidaySummaries(Carbon $start, Carbon $end, ?Employee $employee = null): array
    {
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $summaries = [];

        foreach (CarbonPeriod::create($start, $end) as $day) {
            $holiday = $this->isHoliday($day, $employee);

            if (!$holiday) {
                continue;
            }

            $dateKey = $day->toDateString();
            $summaries[$dateKey] = [
                'date' => $dateKey,
                'name' => $holiday->name,
                'class' => $holiday->class,
                'scope' => $holiday->scope,
                'is_non_working' => (bool) $holiday->is_non_working,
                'source' => $holiday->source,
            ];
        }

        return array_values($summaries);
    }

    public function getNonWorkingDates(Carbon $start, Carbon $end, ?Employee $employee = null, ?array $workWeek = null): array
    {
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $nonWorking = [];

        foreach (CarbonPeriod::create($start, $end) as $day) {
            $dateKey = $day->toDateString();
            $isWorkingWeekday = $this->isWorkingWeekday($day, $workWeek);
            $holiday = $this->isHoliday($day, $employee);

            if (!$isWorkingWeekday || ($holiday && $holiday->is_non_working)) {
                $nonWorking[$dateKey] = $dateKey;
            }
        }

        return array_values($nonWorking);
    }
}
