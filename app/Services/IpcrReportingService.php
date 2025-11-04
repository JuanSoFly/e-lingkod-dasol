<?php

namespace App\Services;

use App\Models\Ipcr;
use App\Models\IpcrDevelopmentAction;
use App\Models\IpcrProgressUpdate;
use App\Models\Office;
use App\Models\PerformancePeriod;
use Illuminate\Support\Arr;

class IpcrReportingService
{
    public function individualReport(int $employeeId, array $filters = []): array
    {
        $query = Ipcr::query()
            ->with(['period', 'items'])
            ->where('employee_id', $employeeId);

        if ($periodId = Arr::get($filters, 'period_id')) {
            $query->where('period_id', $periodId);
        }

        $records = $query->orderByDesc('period_id')->get();

        $itemCollection = $records->flatMap->items;

        $trend = $records->map(function (Ipcr $ipcr) {
            return [
                'period' => optional($ipcr->period)->name,
                'overall_score' => $ipcr->overall_score,
                'status' => $ipcr->status,
                'adjectival_rating' => $ipcr->adjectival_rating,
                'finalized_at' => optional($ipcr->finalized_at)?->format('Y-m-d'),
            ];
        });

        $averages = [
            'self' => $this->roundedAverage($itemCollection->pluck('self_rating')->filter()),
            'supervisor' => $this->roundedAverage($itemCollection->pluck('supervisor_rating')->filter()),
            'pmt' => $this->roundedAverage($itemCollection->pluck('pmt_rating')->filter()),
        ];

        $developmentActions = IpcrDevelopmentAction::query()
            ->whereIn('ipcr_id', $records->pluck('id'))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $progress = IpcrProgressUpdate::query()
            ->whereIn('ipcr_id', $records->pluck('id'))
            ->latest('progress_date')
            ->limit(10)
            ->get();

        return [
            'records' => $records,
            'trend' => $trend,
            'averages' => $averages,
            'development_actions' => $developmentActions,
            'progress_updates' => $progress,
        ];
    }

    public function officeAnalytics(array $filters = []): array
    {
        $query = Ipcr::query()->with(['office', 'period']);

        if ($periodId = Arr::get($filters, 'period_id')) {
            $query->where('period_id', $periodId);
        }

        if ($officeId = Arr::get($filters, 'office_id')) {
            $query->where('office_id', $officeId);
        }

        $records = $query->get();

        $byOffice = $records->groupBy('office_id')->map(function ($group) {
            return [
                'office' => optional($group->first()->office)->name,
                'average_score' => $this->roundedAverage($group->pluck('overall_score')->filter()),
                'locked_count' => $group->where('status', 'locked')->count(),
                'pending_count' => $group->whereIn('status', ['for_supervisor_review', 'for_head_approval', 'for_pmt_validation'])->count(),
            ];
        })->values();

        $statusDistribution = $records->groupBy('status')->map->count();

        return [
            'records' => $records,
            'by_office' => $byOffice,
            'status_distribution' => $statusDistribution,
            'periods' => PerformancePeriod::orderByDesc('start_date')->get(['id', 'name']),
            'offices' => Office::orderBy('name')->get(['id', 'name']),
        ];
    }

    public function complianceSnapshot(array $filters = []): array
    {
        $query = Ipcr::query();

        if ($periodId = Arr::get($filters, 'period_id')) {
            $query->where('period_id', $periodId);
        }

        $total = (clone $query)->count();
        $locked = (clone $query)->where('status', 'locked')->count();
        $validated = (clone $query)->whereNotNull('pmt_validated_at')->count();
        $submitted = (clone $query)->whereNotNull('submitted_at')->count();

        $nonCompliant = (clone $query)
            ->whereNull('submitted_at')
            ->orWhereNull('pmt_validated_at')
            ->orWhere('status', '!=', 'locked')
            ->with(['employee', 'period'])
            ->get();

        $statusCounts = (clone $query)->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => $total,
            'locked' => $locked,
            'validated' => $validated,
            'submitted' => $submitted,
            'status_counts' => $statusCounts,
            'non_compliant' => $nonCompliant,
        ];
    }

    protected function roundedAverage($collection, int $precision = 2): ?float
    {
        if ($collection->isEmpty()) {
            return null;
        }

        return round($collection->avg(), $precision);
    }
}
