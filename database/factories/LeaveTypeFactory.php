<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Vacation Leave', 'Sick Leave', 'Emergency Leave', 'Maternity Leave', 'Paternity Leave']),
            'description' => $this->faker->sentence(),
            'days_per_year' => $this->faker->numberBetween(5, 30),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}