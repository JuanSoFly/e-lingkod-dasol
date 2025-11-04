<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Ipcr;
use App\Models\Office;
use App\Models\PerformancePeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ipcr>
 */
class IpcrFactory extends Factory
{
    protected $model = Ipcr::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory()->for(Office::factory(), 'office'),
            'office_id' => null,
            'period_id' => PerformancePeriod::factory(),
            'status' => $this->faker->randomElement([
                'draft',
                'for_supervisor_review',
                'for_head_approval',
                'for_pmt_validation',
                'finalized',
            ]),
            'total_weight' => $this->faker->randomFloat(2, 0, 100),
            'overall_score' => $this->faker->randomFloat(2, 0, 5),
            'adjectival_rating' => $this->faker->randomElement([
                'Outstanding',
                'Very Satisfactory',
                'Satisfactory',
                'Unsatisfactory',
            ]),
            'is_auto_generated' => $this->faker->boolean(),
            'submitted_at' => $this->faker->optional()->dateTimeThisYear(),
            'supervisor_reviewed_at' => $this->faker->optional()->dateTimeThisYear(),
            'head_reviewed_at' => $this->faker->optional()->dateTimeThisYear(),
            'pmt_validated_at' => $this->faker->optional()->dateTimeThisYear(),
            'finalized_at' => $this->faker->optional()->dateTimeThisYear(),
            'locked_at' => $this->faker->optional()->dateTimeThisYear(),
            'remarks' => $this->faker->sentence(),
            'metadata' => [
                'factory_seeded' => true,
            ],
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Ipcr $ipcr) {
            $ipcr->loadMissing('employee', 'office');

            if (!$ipcr->office_id && $ipcr->employee?->office) {
                $ipcr->office()->associate($ipcr->employee->office);
                $ipcr->save();
            }

            if ($ipcr->employee && $ipcr->office_id && $ipcr->employee->office_id !== $ipcr->office_id) {
                $ipcr->employee->forceFill([
                    'office_id' => $ipcr->office_id,
                    'office_code' => optional($ipcr->office)->code,
                ])->save();
            }
        });
    }
}
