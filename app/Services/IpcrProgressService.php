<?php

namespace App\Services;

use App\Models\Ipcr;
use App\Models\IpcrProgressUpdate;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class IpcrProgressService
{
    public function recordProgress(Ipcr $ipcr, User $user, array $payload): IpcrProgressUpdate
    {
        return DB::transaction(function () use ($ipcr, $user, $payload) {
            $update = $ipcr->progressUpdates()->create([
                'ipcr_item_id' => Arr::get($payload, 'ipcr_item_id'),
                'reported_by' => $user->id,
                'progress_date' => Arr::get($payload, 'progress_date', now()->toDateString()),
                'status' => Arr::get($payload, 'status', 'on_track'),
                'accomplishments' => Arr::get($payload, 'accomplishments'),
                'challenges' => Arr::get($payload, 'challenges'),
                'next_steps' => Arr::get($payload, 'next_steps'),
            ]);

            $this->updateRiskIndicator($ipcr);

            return $update;
        });
    }

    public function snapshot(Ipcr $ipcr): array
    {
        $updates = $ipcr->progressUpdates()->latest('progress_date')->take(10)->get();

        $statusCounts = $ipcr->progressUpdates()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'recent_updates' => $updates,
            'status_counts' => $statusCounts,
            'risk_level' => data_get($ipcr->metadata, 'risk_level', 'low'),
        ];
    }

    protected function updateRiskIndicator(Ipcr $ipcr): void
    {
        $recent = $ipcr->progressUpdates()->latest('progress_date')->first();

        $risk = 'low';

        if ($recent && in_array($recent->status, ['delayed', 'at_risk'])) {
            $risk = 'medium';
        }

        if ($recent && $recent->status === 'critical') {
            $risk = 'high';
        }

        $ipcr->metadata = array_merge($ipcr->metadata ?? [], ['risk_level' => $risk]);
        $ipcr->save();
    }
}
