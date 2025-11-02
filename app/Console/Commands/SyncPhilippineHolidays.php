<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncPhilippineHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holidays:sync {year? : Year to sync (defaults to all configured years)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync configured Philippine holidays into the holidays table for the leave system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $configured = config('philippine_holidays.years', []);

        if (empty($configured)) {
            $this->warn('No holiday definitions found in config/philippine_holidays.php.');
            return self::FAILURE;
        }

        $year = $this->argument('year');

        if ($year) {
            if (!array_key_exists((int) $year, $configured)) {
                $this->error("No holiday definitions found for year {$year}.");
                return self::FAILURE;
            }

            config()->set('philippine_holidays.years', [(int) $year => $configured[(int) $year]]);
        }

        app(\Database\Seeders\PhilippineHolidays2025Seeder::class)->run();

        $this->info('Philippine holidays synced for ' . ($year ? $year : 'all configured years') . '.');

        return self::SUCCESS;
    }
}
