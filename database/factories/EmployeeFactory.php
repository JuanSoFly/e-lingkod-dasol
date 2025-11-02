<?php

namespace Database\Factories;

use App\Models\User;
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
            'employee_number' => $this->faker->unique()->numerify('DAS-' . date('Y') . '-####'),
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
            'department' => $this->faker->randomElement([
                'Office of the Municipal Mayor',
                'Office of the Municipal Accountant',
                'Office of the Municipal Budget Office',
                'Municipal Planning and Development Office',
                'General Services Office',
                'Municipal Treasurer\'s Office',
                'Municipal Assessor\'s Office',
                'Business Permits and Licensing Office',
                'Municipal Tourism and Cultural Affairs Office',
                'Local Civil Registry Office',
                'Human Resource Management Office',
                'Office of the Building Official',
                'Municipal Engineer\'s Office',
                'Municipal Agriculturist\'s Office',
                'Municipal Social Welfare and Development Office',
                'Rural Health Unit',
                'Sangguniang Bayan/Secretary to the SB Office',
                'Municipal Disaster Risk Reduction and Management Office'
            ]),
            'employment_status' => 'Regular',
            'date_hired' => $this->faker->date(),
            'salary_grade' => $this->faker->numberBetween(1, 20),
            'step_increment' => $this->faker->numberBetween(1, 8),
        ];
    }

    /**
     * Create an employee with an associated user account
     */
    public function withUser(): static
    {
        return $this->has(User::factory()->state(function (array $attributes, \App\Models\Employee $employee) {
            return [
                'name' => trim($employee->first_name . ' ' . $employee->last_name),
                'email' => $employee->email,
                'employee_id' => $employee->id,
            ];
        }));
    }

    /**
     * Create an employee without an associated user account
     */
    public function withoutUser(): static
    {
        return $this->state(fn () => [
            'email' => null, // No email to avoid conflicts with user emails
        ]);
    }
}