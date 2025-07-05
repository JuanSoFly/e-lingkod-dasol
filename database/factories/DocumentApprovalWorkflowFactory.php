<?php

namespace Database\Factories;

use App\Models\DocumentApprovalWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentApprovalWorkflowFactory extends Factory
{
    protected $model = DocumentApprovalWorkflow::class;

    public function definition(): array
    {
        $workflowTypes = [
            'Standard Document Approval',
            'HR Document Approval',
            'Financial Document Approval',
            'Legal Document Approval',
            'Executive Approval',
            'Department Head Approval'
        ];

        return [
            'name' => $this->faker->randomElement($workflowTypes),
            'description' => $this->faker->optional(0.8)->sentence(),
            'is_active' => $this->faker->boolean(90), // 90% active
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            }
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false
        ]);
    }

    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Standard Document Approval',
            'description' => 'Standard workflow for general document approvals'
        ]);
    }

    public function hr(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'HR Document Approval',
            'description' => 'Workflow for HR-related document approvals'
        ]);
    }

    public function executive(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Executive Approval',
            'description' => 'High-level approval workflow for executive documents'
        ]);
    }
}