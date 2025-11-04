<?php

namespace Database\Factories;

use App\Models\Ipcr;
use App\Models\IpcrDevelopmentAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\IpcrDevelopmentAction>
 */
class IpcrDevelopmentActionFactory extends Factory
{
    protected $model = IpcrDevelopmentAction::class;

    public function definition(): array
    {
        return [
            'ipcr_id' => Ipcr::factory(),
            'focus_area' => $this->faker->words(3, true),
            'action_item' => $this->faker->sentence(),
            'target_date' => $this->faker->optional()->dateTimeBetween('+1 week', '+2 months'),
            'status' => $this->faker->randomElement(['planned', 'in_progress', 'completed']),
            'support_needed' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'metadata' => ['factory_seeded' => true],
        ];
    }
}
