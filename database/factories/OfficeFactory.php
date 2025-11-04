<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Office>
 */
use App\Models\Office;

class OfficeFactory extends Factory
{
    protected $model = Office::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Human Resource Management Office',
            'Municipal Treasurer\'s Office',
            'Municipal Assessor\'s Office',
            'Municipal Planning and Development Office',
            'Office of the Municipal Mayor',
        ]);

        return [
            'code' => strtoupper($this->faker->unique()->lexify('OFF-????')),
            'name' => $name,
            'description' => $this->faker->sentence(),
            'level' => $this->faker->numberBetween(1, 3),
            'head_title' => $this->faker->randomElement(['Department Head', 'Division Chief', 'Supervising Officer']),
            'contact_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->companyEmail(),
            'location' => $this->faker->address(),
            'is_active' => true,
            'metadata' => [
                'created_via_factory' => true,
            ],
        ];
    }
}
