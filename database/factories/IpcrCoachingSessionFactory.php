<?php

namespace Database\Factories;

use App\Models\Ipcr;
use App\Models\IpcrCoachingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\IpcrCoachingSession>
 */
class IpcrCoachingSessionFactory extends Factory
{
    protected $model = IpcrCoachingSession::class;

    public function definition(): array
    {
        return [
            'ipcr_id' => Ipcr::factory(),
            'coach_user_id' => User::factory(),
            'participant_user_id' => User::factory(),
            'session_date' => $this->faker->date(),
            'session_type' => $this->faker->randomElement(['coaching', 'mentoring']),
            'focus_area' => $this->faker->words(3, true),
            'discussion_notes' => $this->faker->paragraph(),
            'agreements' => $this->faker->sentence(),
            'follow_up_date' => $this->faker->optional()->date(),
            'metadata' => ['factory_seeded' => true],
        ];
    }
}
