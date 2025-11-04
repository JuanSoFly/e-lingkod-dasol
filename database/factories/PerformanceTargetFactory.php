<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\MajorFinalOutput;
use App\Models\OPCRWorkflow;
use App\Models\PerformanceTarget;
use App\Models\PerformancePeriod;
use App\Models\Office;
use App\Models\SuccessIndicator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PerformanceTarget>
 */
class PerformanceTargetFactory extends Factory
{
    protected $model = PerformanceTarget::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period_id' => PerformancePeriod::factory(),
            'office_id' => Office::factory(),
            'opcr_workflow_id' => OPCRWorkflow::factory(),
            'mfo_id' => MajorFinalOutput::factory(),
            'success_indicator_id' => null,
            'objective' => $this->faker->sentence(),
            'target' => $this->faker->sentence(),
            'weight' => $this->faker->randomElement([25, 30, 40]),
            'target_quantity' => $this->faker->randomFloat(2, 10, 100),
            'target_efficiency' => '100%',
            'target_timeliness' => 'Within period',
            'success_indicator' => $this->faker->sentence(),
        ];
    }
}
