<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'employee_id' => Employee::factory(),
        ];
    }

    /**
     * Create a user without an employee (for Super Admin cases)
     */
    public function withoutEmployee(): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_id' => null,
        ]);
    }

    /**
     * Create a Super Admin user
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'employee_id' => null,
        ]);
    }

    /**
     * Create a Department Head for Office of the Municipal Mayor
     */
    public function mayorDepartmentHead(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Department Head - Mayor Office',
            'email' => 'depthead.mayor@dasol.gov.ph',
        ]);
    }

    /**
     * Create an Assessor user (Performance Management Team)
     */
    public function assessor(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Assessor - PMT',
            'email' => 'assessor.pmt@dasol.gov.ph',
        ]);
    }

    /**
     * Create a Final Approver user (Senior Management)
     */
    public function finalApprover(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Final Approver - Administrator',
            'email' => 'administrator@dasol.gov.ph',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}