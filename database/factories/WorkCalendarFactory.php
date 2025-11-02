<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkCalendar>
 */
class WorkCalendarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true) . ' Calendar',
            'timezone' => 'Asia/Manila',
            'work_week' => [
                'mon' => 1,
                'tue' => 1,
                'wed' => 1,
                'thu' => 1,
                'fri' => 1,
                'sat' => 0,
                'sun' => 0,
            ],
        ];
    }
}
