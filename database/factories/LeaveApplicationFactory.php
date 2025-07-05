<?php

namespace Database\Factories;

use App\Models\LeaveApplication;
use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveApplicationFactory extends Factory
{
    protected $model = LeaveApplication::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('+1 day', '+30 days');
        $endDate = $this->faker->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +7 days');
        
        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $this->faker->numberBetween(1, 7),
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected', 'cancelled']),
            'applied_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'supervisor_comments' => $this->faker->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'supervisor_comments' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'supervisor_comments' => 'Leave approved.',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'supervisor_comments' => 'Leave rejected due to business requirements.',
        ]);
    }
}