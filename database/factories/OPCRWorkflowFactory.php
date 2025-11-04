<?php

namespace Database\Factories;

use App\Models\OPCRWorkflow;
use App\Models\Office;
use App\Models\PerformancePeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\OPCRWorkflow>
 */
class OPCRWorkflowFactory extends Factory
{
    protected $model = OPCRWorkflow::class;

    public function definition(): array
    {
        return [
            'office_id' => Office::factory(),
            'period_id' => PerformancePeriod::factory(),
            'workflow_state' => OPCRWorkflow::STATE_DRAFT,
            'title' => $this->faker->sentence(4),
            'summary' => $this->faker->sentence(),
            'metadata' => ['factory_seeded' => true],
        ];
    }
}
