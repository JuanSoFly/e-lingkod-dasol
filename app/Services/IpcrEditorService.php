<?php

namespace App\Services;

use App\Models\Ipcr;
use App\Models\IpcrItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IpcrEditorService
{
    public function updateEmployeeSubmission(Ipcr $ipcr, array $payload): Ipcr
    {
        return DB::transaction(function () use ($ipcr, $payload) {
            $items = collect($payload['items']);

            $this->validateWeights($items);

            $items->each(function (array $itemData) use ($ipcr) {
                /** @var IpcrItem $item */
                $item = $ipcr->items()->whereKey($itemData['id'])->firstOrFail();

                $item->fill([
                    'self_rating' => Arr::get($itemData, 'self_rating'),
                    'remarks' => Arr::get($itemData, 'remarks', $item->remarks),
                    'weight' => Arr::get($itemData, 'weight', $item->weight),
                ])->save();
            });

            $ipcr->update([
                'remarks' => Arr::get($payload, 'remarks', $ipcr->remarks),
                'total_weight' => $ipcr->items()->sum('weight'),
            ]);

            return $ipcr->fresh('items');
        });
    }

    protected function validateWeights($items): void
    {
        $weightsProvided = $items->filter(fn ($item) => Arr::has($item, 'weight'));

        if ($weightsProvided->isEmpty()) {
            return;
        }

        $total = round($weightsProvided->sum(fn ($item) => (float) Arr::get($item, 'weight')), 2);

        if (abs($total - 100.0) > 0.01) {
            throw ValidationException::withMessages([
                'items' => ['Combined weight must equal 100%. Currently totals ' . $total . '%.'],
            ]);
        }
    }
}
