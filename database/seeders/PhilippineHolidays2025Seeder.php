<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PhilippineHolidays2025Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $years = config('philippine_holidays.years', []);

        foreach ($years as $year => $entries) {
            $payload = collect($entries)
                ->map(function (array $holiday) use ($year) {
                    return [
                        'date' => $holiday['date'],
                        'observed_date' => $holiday['observed_date'] ?? null,
                        'name' => $holiday['name'],
                        'class' => $holiday['class'],
                        'scope' => $holiday['scope'] ?? 'national',
                        'scope_code' => $holiday['scope_code'] ?? null,
                        'is_non_working' => (bool) ($holiday['is_non_working'] ?? true),
                        'year' => $year,
                        'source' => $holiday['source'] ?? null,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                })
                ->all();

            if (!empty($payload)) {
                DB::table('holidays')->upsert(
                    $payload,
                    ['date', 'scope', 'scope_code'],
                    ['observed_date', 'class', 'is_non_working', 'year', 'source', 'updated_at']
                );

                Cache::forget('holidays:' . $year);
            }
        }
    }
}
