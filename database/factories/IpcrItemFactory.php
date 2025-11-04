<?php

namespace Database\Factories;

use App\Models\Ipcr;
use App\Models\IpcrItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories.Factory<\App\Models\IpcrItem>
 */
class IpcrItemFactory extends Factory
{
    protected $model = IpcrItem::class;

    public function definition(): array
    {
        return [
            'ipcr_id' => Ipcr::factory(),
            'performance_target_id' => null,
            'success_indicator_id' => null,
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(),
            'weight' => $this->faker->randomFloat(2, 5, 20),
            'measure' => $this->faker->randomElement(['Quantity', 'Quality', 'Timeliness']),
            'target_quantity' => $this->faker->randomFloat(2, 10, 100),
            'target_efficiency' => $this->faker->randomElement(['100%', '95%', '90%']),
            'target_timeliness' => $this->faker->randomElement(['Before deadline', 'On or before due date', 'Within 5 days']),
            'accomplished_quantity' => $this->faker->randomFloat(2, 10, 100),
            'accomplished_efficiency' => $this->faker->randomElement(['100%', '98%', '95%']),
            'accomplished_timeliness' => $this->faker->randomElement(['Ahead of time', 'On time']),
            'self_rating' => $this->faker->optional()->randomFloat(2, 3, 5),
            'self_rating_details' => [
                'quality' => $this->faker->randomFloat(2, 3, 5),
                'efficiency' => $this->faker->randomFloat(2, 3, 5),
                'timeliness' => $this->faker->randomFloat(2, 3, 5),
            ],
            'supervisor_rating' => $this->faker->optional()->randomFloat(2, 3, 5),
            'supervisor_rating_details' => [
                'quality' => $this->faker->randomFloat(2, 3, 5),
                'efficiency' => $this->faker->randomFloat(2, 3, 5),
                'timeliness' => $this->faker->randomFloat(2, 3, 5),
            ],
            'head_rating' => $this->faker->optional()->randomFloat(2, 3, 5),
            'head_rating_details' => [
                'quality' => $this->faker->randomFloat(2, 3, 5),
                'efficiency' => $this->faker->randomFloat(2, 3, 5),
                'timeliness' => $this->faker->randomFloat(2, 3, 5),
            ],
            'pmt_rating' => $this->faker->optional()->randomFloat(2, 3, 5),
            'pmt_rating_details' => [
                'quality' => $this->faker->randomFloat(2, 3, 5),
                'efficiency' => $this->faker->randomFloat(2, 3, 5),
                'timeliness' => $this->faker->randomFloat(2, 3, 5),
            ],
            'final_rating' => $this->faker->optional()->randomFloat(2, 3, 5),
            'final_rating_details' => [
                'quality' => $this->faker->randomFloat(2, 3, 5),
                'efficiency' => $this->faker->randomFloat(2, 3, 5),
                'timeliness' => $this->faker->randomFloat(2, 3, 5),
            ],
            'remarks' => $this->faker->sentence(),
            'sequence' => $this->faker->unique()->numberBetween(1, 20),
            'metadata' => [
                'factory_seeded' => true,
            ],
        ];
    }
}
