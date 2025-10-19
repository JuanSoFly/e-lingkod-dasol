<?php

namespace Database\Factories;

use App\Models\LeaveCardEntry;
use App\Models\LeaveCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveCardEntry>
 */
class LeaveCardEntryFactory extends Factory
{
    protected $model = LeaveCardEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leave_card_id' => LeaveCard::factory(),
            'leave_application_id' => null,
            'leave_type_code' => $this->faker->randomElement(['VL', 'SL', 'ML', 'PL', 'SPL']),
            'date' => $this->faker->date(),
            'days' => $this->faker->randomFloat(1, 0.5, 5),
            'vl_balance_after' => $this->faker->randomFloat(1, 0, 15),
            'sl_balance_after' => $this->faker->randomFloat(1, 0, 15),
            'remarks' => $this->faker->sentence(),
            'entry_type' => $this->faker->randomElement(['deduction', 'credit', 'adjustment']),
            'created_by' => 1,
        ];
    }
}