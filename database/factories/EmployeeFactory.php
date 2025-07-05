<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_number' => $this->faker->unique()->numerify('EMP-#####'),
            'first_name' => $this->faker->firstName(),
            'middle_name' => $this->faker->lastName(),
            'last_name' => $this->faker->lastName(),
            'birth_date' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'civil_status' => $this->faker->randomElement(['Single', 'Married', 'Widowed']),
            'address' => $this->faker->address(),
            'contact_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'position' => $this->faker->jobTitle(),
            'department' => $this->faker->randomElement(['IT', 'HR', 'Finance', 'Engineering']),
            'employment_status' => 'Regular',
            'date_hired' => $this->faker->date(),
            'salary_grade' => $this->faker->numberBetween(1, 20),
            'step_increment' => $this->faker->numberBetween(1, 8),
        ];
    }
}