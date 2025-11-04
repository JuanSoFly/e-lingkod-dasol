<?php

namespace App\Services;

use App\Models\FinalRating;
use App\Models\Ipcr;
use App\Models\IpcrItem;
use App\Models\PmtValidation;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class IpcrValidationService
{
    public function recordValidation(Ipcr $ipcr, User $validator, array $payload): Ipcr
    {
        return DB::transaction(function () use ($ipcr, $validator, $payload) {
            $items = collect($payload['items'] ?? []);

            $items->each(function (array $values) use ($ipcr) {
                /** @var IpcrItem $item */
                $item = $ipcr->items()->whereKey($values['id'])->firstOrFail();

                $item->forceFill([
                    'pmt_rating' => Arr::get($values, 'pmt_rating'),
                    'pmt_rating_details' => array_filter([
                        'comments' => Arr::get($values, 'pmt_comments'),
                    ]),
                ])->save();
            });

            PmtValidation::updateOrCreate(
                [
                    'ipcr_id' => $ipcr->id,
                    'validator_user_id' => $validator->id,
                ],
                [
                    'office_id' => $ipcr->office_id,
                    'validator_id' => $validator->employee_id,
                    'validation_stage' => Arr::get($payload, 'validation_stage', 'initial'),
                    'status' => 'validated',
                    'recommended_rating' => Arr::get($payload, 'recommended_rating'),
                    'rating_breakdown' => Arr::only($payload, ['quality', 'efficiency', 'timeliness']),
                    'remarks' => Arr::get($payload, 'remarks'),
                    'validated_at' => now(),
                ]
            );

            return $ipcr->fresh('items');
        });
    }

    public function finalizeValidation(Ipcr $ipcr, User $validator, array $payload): Ipcr
    {
        return DB::transaction(function () use ($ipcr, $validator, $payload) {
            $averages = $this->calculateAverages($ipcr);

            $ipcr->forceFill([
                'overall_score' => Arr::get($payload, 'overall_score', $averages['pmt_average']),
                'adjectival_rating' => $this->mapAdjectivalRating(Arr::get($payload, 'overall_score', $averages['pmt_average'])),
            ])->save();

            PmtValidation::updateOrCreate(
                [
                    'ipcr_id' => $ipcr->id,
                    'validator_user_id' => $validator->id,
                ],
                [
                    'status' => 'endorsed',
                    'recommended_rating' => Arr::get($payload, 'overall_score', $averages['pmt_average']),
                    'remarks' => Arr::get($payload, 'remarks'),
                    'validated_at' => now(),
                ]
            );

            return $ipcr->fresh(['items', 'pmtValidations']);
        });
    }

    public function recordFinalRating(Ipcr $ipcr, User $user, array $payload): Ipcr
    {
        return DB::transaction(function () use ($ipcr, $user, $payload) {
            $score = Arr::get($payload, 'final_score');

            $ipcr->forceFill([
                'overall_score' => $score,
                'adjectival_rating' => $this->mapAdjectivalRating($score),
                'finalized_at' => $ipcr->finalized_at ?? now(),
            ])->save();

            FinalRating::updateOrCreate(
                [
                    'ipcr_id' => $ipcr->id,
                    'employee_id' => $ipcr->employee_id,
                ],
                [
                    'validated_by' => $user->id,
                    'final_score' => $score,
                    'adjectival_rating' => $this->mapAdjectivalRating($score),
                    'performance_level' => Arr::get($payload, 'performance_level'),
                    'is_locked' => true,
                    'locked_at' => now(),
                    'remarks' => Arr::get($payload, 'remarks'),
                ]
            );

            return $ipcr->fresh(['finalRating']);
        });
    }

    protected function calculateAverages(Ipcr $ipcr): array
    {
        $items = $ipcr->items;

        return [
            'self_average' => $items->pluck('self_rating')->filter()->avg(),
            'supervisor_average' => $items->pluck('supervisor_rating')->filter()->avg(),
            'pmt_average' => $items->pluck('pmt_rating')->filter()->avg(),
        ];
    }

    protected function mapAdjectivalRating(?float $score): ?string
    {
        if (is_null($score)) {
            return null;
        }

        return match (true) {
            $score >= 4.5 => 'Outstanding',
            $score >= 4.0 => 'Very Satisfactory',
            $score >= 3.0 => 'Satisfactory',
            $score >= 2.0 => 'Below Satisfactory',
            default => 'Poor',
        };
    }
}
