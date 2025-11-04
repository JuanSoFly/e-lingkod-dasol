<?php

namespace App\Console\Commands;

use App\Models\OfficeAssignment;
use App\Models\Office;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncDepartmentHeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opcr:sync-department-heads {--fix : Apply fixes to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync department head IDs in offices table based on active office assignments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting department head synchronization...');
        $applyFixes = $this->option('fix');

        // Get all offices
        $offices = Office::all();
        $updatesCount = 0;

        foreach ($offices as $office) {
            $activeHead = OfficeAssignment::with('user')
                ->where('office_id', $office->id)
                ->where('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD)
                ->where('is_active', true)
                ->orderByDesc('assigned_date')
                ->first();

            $currentHeadId = $office->department_head_id;
            $newHeadId = $activeHead?->user?->employee_id;

            if ($currentHeadId !== $newHeadId) {
                $this->info(sprintf(
                    'Office %s (%s): Current head_id = %s, New head_id = %s',
                    $office->name,
                    $office->code,
                    $currentHeadId ?? 'NULL',
                    $newHeadId ?? 'NULL'
                ));

                if ($applyFixes && $currentHeadId !== $newHeadId) {
                    $office->update([
                        'department_head_id' => $newHeadId,
                    ]);
                    $updatesCount++;

                    $this->info("✅ Updated office {$office->name} with department head ID: {$newHeadId}");
                }
            } else {
                $this->info(sprintf(
                    'Office %s (%s): Already in sync (head_id = %s)',
                    $office->name,
                    $office->code,
                    $currentHeadId ?? 'NULL'
                ));
            }
        }

        if ($applyFixes) {
            $this->info("🎉 Synchronization complete! Updated {$updatesCount} offices.");
        } else {
            $this->info('Dry run complete. Use --fix option to apply changes.');
        }
    }
}
