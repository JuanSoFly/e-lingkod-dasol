<?php

namespace App\Services;

use App\Models\Ipcr;
use App\Models\IpcrItem;
use Illuminate\Support\Collection;

class IpcrCalibrationService
{
    public function buildSummary(Collection $ipcrs): array
    {
        $items = $ipcrs->flatMap->items;

        if ($items->isEmpty()) {
            return [
                'count' => 0,
                'average' => null,
                'variance' => null,
                'outliers' => collect(),
            ];
        }

        $ratings = $items->pluck('supervisor_rating')->filter()->values();
        $average = $ratings->avg();
        $variance = $ratings->map(fn ($score) => pow($score - $average, 2))->avg();

        return [
            'count' => $ratings->count(),
            'average' => round($average, 2),
            'variance' => round($variance, 4),
            'outliers' => $items->filter(function (IpcrItem $item) use ($average) {
                if (!$item->supervisor_rating || !$item->self_rating) {
                    return false;
                }

                return abs($item->supervisor_rating - $item->self_rating) >= 1.5;
            })->take(10),
        ];
    }

    public function buildIpcrStatistics(Ipcr $ipcr): array
    {
        $items = $ipcr->items;

        $self = $items->pluck('self_rating')->filter()->avg();
        $supervisor = $items->pluck('supervisor_rating')->filter()->avg();
        $pmt = $items->pluck('pmt_rating')->filter()->avg();

        return [
            'self_average' => $self ? round($self, 2) : null,
            'supervisor_average' => $supervisor ? round($supervisor, 2) : null,
            'pmt_average' => $pmt ? round($pmt, 2) : null,
            'rating_spread' => $this->ratingSpread($items),
        ];
    }

    protected function ratingSpread(Collection $items): array
    {
        return $items->map(function (IpcrItem $item) {
            return [
                'title' => $item->title,
                'self' => $item->self_rating,
                'supervisor' => $item->supervisor_rating,
                'pmt' => $item->pmt_rating,
            ];
        })->all();
    }
}
