<?php

namespace Database\Factories;

use App\Models\LeaveCard;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveCard>
 */
class LeaveCardFactory extends Factory
{
    protected $model = LeaveCard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'year' => $this->faker->year,
            'vl_balance' => $this->faker->randomFloat(1, 0, 15),
            'sl_balance' => $this->faker->randomFloat(1, 0, 15),
            'remarks' => $this->faker->optional()->sentence(),
            'last_updated' => $this->faker->date(),
        ];
    }
}
