<?php

namespace Database\Factories;

use App\Models\MajorFinalOutput;
use App\Models\Office;
use App\Models\SuccessIndicator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\SuccessIndicator>
 */
class SuccessIndicatorFactory extends Factory
{
    protected $model = SuccessIndicator::class;

    public function definition(): array
    {
        return [
            'mfo_id' => MajorFinalOutput::factory(),
            'code' => $this->faker->unique()->regexify('SI-[A-Z0-9]{8}'),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
