<?php

namespace App\Console\Commands;

use App\Models\Ipcr;
use App\Models\IpcrDevelopmentAction;
use App\Models\IpcrProgressUpdate;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckIpcrHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ipcr:health-check {--period= : Filter by performance_period ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Summarise IPCR completion, validations, and monitoring signals for QA';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $periodId = $this->option('period');

        $ipcrQuery = Ipcr::query()
            ->with(['period', 'office'])
            ->when($periodId, fn ($q) => $q->where('period_id', $periodId));

        $total = (clone $ipcrQuery)->count();
        $submitted = (clone $ipcrQuery)->whereNotNull('submitted_at')->count();
        $validated = (clone $ipcrQuery)->whereNotNull('pmt_validated_at')->count();
        $locked = (clone $ipcrQuery)->where('status', 'locked')->count();

        $pending = (clone $ipcrQuery)
            ->where(function ($q) {
                $q->whereNull('submitted_at')
                    ->orWhereNull('pmt_validated_at')
                    ->orWhere('status', '!=', 'locked');
            })
            ->with(['employee', 'period'])
            ->get()
            ->map(function (Ipcr $ipcr) {
                return [
                    'Employee' => optional($ipcr->employee)->full_name ?? optional($ipcr->employee)->first_name,
                    'Office' => optional($ipcr->office)->name,
                    'Period' => optional($ipcr->period)->name,
                    'Status' => $ipcr->status,
                    'Submitted' => optional($ipcr->submitted_at)?->format('Y-m-d') ?? '—',
                    'Validated' => optional($ipcr->pmt_validated_at)?->format('Y-m-d') ?? '—',
                    'Locked' => optional($ipcr->locked_at)?->format('Y-m-d') ?? '—',
                ];
            });

        $staleProgress = IpcrProgressUpdate::query()
            ->whereIn('ipcr_id', $ipcrQuery->pluck('id'))
            ->selectRaw('ipcr_id, MAX(progress_date) as last_update')
            ->groupBy('ipcr_id')
            ->havingRaw('MAX(progress_date) < ?', [Carbon::now()->subDays(30)->toDateString()])
            ->orderBy('last_update')
            ->get();

        $openActions = IpcrDevelopmentAction::query()
            ->whereIn('ipcr_id', $ipcrQuery->pluck('id'))
            ->whereIn('status', ['planned', 'in_progress'])
            ->count();

        $this->components->info('IPCR Health Summary');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total IPCRs', $total],
                ['Submitted', $submitted],
                ['Validated (PMT)', $validated],
                ['Locked', $locked],
                ['Pending (any stage)', $pending->count()],
                ['Stale progress (>30 days)', $staleProgress->count()],
                ['Open development actions', $openActions],
            ]
        );

        if ($pending->isNotEmpty()) {
            $this->components->warn('Pending Items');
            $this->table(array_keys($pending->first()), $pending->toArray());
        }

        if ($staleProgress->isNotEmpty()) {
            $this->components->warn('Stale Progress IPCR IDs');
            $this->table(['IPCR ID', 'Last Update'], $staleProgress->map->only(['ipcr_id', 'last_update'])->toArray());
        }

        $this->components->info('Health check complete.');

        return Command::SUCCESS;
    }
}
