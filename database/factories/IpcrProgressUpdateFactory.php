<?php

namespace Database\Factories;

use App\Models\Ipcr;
use App\Models\IpcrItem;
use App\Models\IpcrProgressUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\IpcrProgressUpdate>
 */
class IpcrProgressUpdateFactory extends Factory
{
    protected $model = IpcrProgressUpdate::class;

    public function definition(): array
    {
        return [
            'ipcr_id' => Ipcr::factory(),
            'ipcr_item_id' => IpcrItem::factory(),
            'reported_by' => User::factory(),
            'progress_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['on_track', 'at_risk', 'delayed']),
            'accomplishments' => $this->faker->sentence(),
            'challenges' => $this->faker->sentence(),
            'next_steps' => $this->faker->sentence(),
            'metadata' => ['factory_seeded' => true],
        ];
    }
}
