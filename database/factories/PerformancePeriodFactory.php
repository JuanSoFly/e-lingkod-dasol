<?php

namespace Database\Factories;

use App\Models\PerformancePeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerformancePeriodFactory extends Factory
{
    protected $model = PerformancePeriod::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Q1 2024', 'Q2 2024', 'H1 2024', 'Annual 2024']),
            'year' => $this->faker->numberBetween(2023, 2025),
            'semester' => $this->faker->numberBetween(1, 2),
            'start_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'status' => $this->faker->randomElement(['draft', 'active', 'completed']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}