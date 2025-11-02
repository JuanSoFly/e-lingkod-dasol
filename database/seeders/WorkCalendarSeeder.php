<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\WorkCalendar;
use Illuminate\Database\Seeder;

class WorkCalendarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $calendars = [
            [
                'name' => 'PH National (Mon–Fri)',
                'timezone' => 'Asia/Manila',
                'work_week' => [
                    'mon' => 1,
                    'tue' => 1,
                    'wed' => 1,
                    'thu' => 1,
                    'fri' => 1,
                    'sat' => 0,
                    'sun' => 0,
                ],
            ],
            [
                'name' => 'Operations (Tue–Sat)',
                'timezone' => 'Asia/Manila',
                'work_week' => [
                    'mon' => 0,
                    'tue' => 1,
                    'wed' => 1,
                    'thu' => 1,
                    'fri' => 1,
                    'sat' => 1,
                    'sun' => 0,
                ],
            ],
        ];

        foreach ($calendars as $calendar) {
            WorkCalendar::updateOrCreate(
                ['name' => $calendar['name']],
                $calendar
            );
        }

        $defaultCalendar = WorkCalendar::where('name', 'PH National (Mon–Fri)')->first();
        if ($defaultCalendar) {
            Employee::whereNull('work_calendar_id')->update([
                'work_calendar_id' => $defaultCalendar->id,
            ]);
        }
    }
}
