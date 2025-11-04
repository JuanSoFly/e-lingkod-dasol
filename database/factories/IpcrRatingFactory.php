<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Ipcr;
use App\Models\IpcrItem;
use App\Models\IpcrRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories.Factory<\App\Models\IpcrRating>
 */
class IpcrRatingFactory extends Factory
{
    protected $model = IpcrRating::class;

    public function definition(): array
    {
        return [
            'ipcr_id' => null,
            'ipcr_item_id' => IpcrItem::factory(),
            'rater_user_id' => User::factory(),
            'rater_employee_id' => Employee::factory(),
            'rater_role' => $this->faker->randomElement(['employee', 'supervisor', 'head', 'pmt', 'final_approver']),
            'rating_type' => $this->faker->randomElement(['self', 'supervisor', 'head', 'pmt', 'final']),
            'quality_rating' => $this->faker->randomFloat(2, 3, 5),
            'efficiency_rating' => $this->faker->randomFloat(2, 3, 5),
            'timeliness_rating' => $this->faker->randomFloat(2, 3, 5),
            'overall_rating' => $this->faker->randomFloat(2, 3, 5),
            'rating_details' => [
                'notes' => $this->faker->sentence(),
            ],
            'comments' => $this->faker->paragraph(),
            'rated_at' => $this->faker->dateTimeThisYear(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (IpcrRating $rating) {
            $rating->loadMissing('item.ipcr');

            if (!$rating->ipcr_id && $rating->item?->ipcr) {
                $rating->ipcr()->associate($rating->item->ipcr);
                $rating->save();
            }
        });
    }
}
