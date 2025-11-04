<?php

namespace Database\Factories;

use App\Models\MajorFinalOutput;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\MajorFinalOutput>
 */
class MajorFinalOutputFactory extends Factory
{
    protected $model = MajorFinalOutput::class;

    public function definition(): array
    {
        return [
            'office_id' => Office::factory(),
            'code' => $this->faker->unique()->regexify('MFO-[A-Z0-9]{8}'),
            'title' => $this->faker->sentence(3),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'is_active' => true,
        ];
    }
}
