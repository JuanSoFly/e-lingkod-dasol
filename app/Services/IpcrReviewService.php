<?php

namespace App\Services;

use App\Models\Ipcr;
use App\Models\IpcrItem;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class IpcrReviewService
{
    public function supervisorReview(Ipcr $ipcr, User $user, array $payload): Ipcr
    {
        return DB::transaction(function () use ($ipcr, $payload) {
            $items = collect($payload['items']);

            $items->each(function (array $itemData) use ($ipcr) {
                /** @var IpcrItem $item */
                $item = $ipcr->items()->whereKey($itemData['id'])->firstOrFail();

                $item->forceFill([
                    'supervisor_rating' => Arr::get($itemData, 'supervisor_rating'),
                    'supervisor_rating_details' => array_filter([
                        'comments' => Arr::get($itemData, 'supervisor_comments'),
                    ]),
                ])->save();
            });

            if (isset($payload['overall_remarks'])) {
                $ipcr->update([
                    'remarks' => trim($ipcr->remarks . PHP_EOL . 'Supervisor: ' . $payload['overall_remarks']),
                ]);
            }

            return $ipcr->fresh('items');
        });
    }

    public function headOfOfficeReview(Ipcr $ipcr, User $user, array $payload): Ipcr
    {
        return DB::transaction(function () use ($ipcr, $payload) {
            $items = collect($payload['items'] ?? []);

            $items->each(function (array $itemData) use ($ipcr) {
                /** @var IpcrItem $item */
                $item = $ipcr->items()->whereKey($itemData['id'])->firstOrFail();

                $item->forceFill([
                    'head_rating' => Arr::get($itemData, 'head_rating'),
                    'head_rating_details' => array_filter([
                        'comments' => Arr::get($itemData, 'head_comments'),
                    ]),
                ])->save();
            });

            if (isset($payload['remarks'])) {
                $ipcr->update([
                    'remarks' => trim($ipcr->remarks . PHP_EOL . 'Head of Office: ' . $payload['remarks']),
                ]);
            }

            return $ipcr->fresh('items');
        });
    }
}
