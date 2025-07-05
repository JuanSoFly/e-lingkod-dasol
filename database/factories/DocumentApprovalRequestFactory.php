<?php

namespace Database\Factories;

use App\Models\DocumentApprovalRequest;
use App\Models\DocumentApprovalWorkflow;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentApprovalRequestFactory extends Factory
{
    protected $model = DocumentApprovalRequest::class;

    public function definition(): array
    {
        $statuses = ['draft', 'submitted', 'under_review', 'approved', 'rejected'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $documentTypes = [
            'Certificate of Employment',
            'Certificate of Service',
            'Salary Certificate',
            'Training Certificate',
            'Performance Evaluation',
            'Leave Clearance',
            'Tax Declaration',
            'Character Reference'
        ];

        $submittedAt = $this->faker->dateTimeBetween('-6 months', 'now');
        $status = $this->faker->randomElement($statuses);
        $documentType = $this->faker->randomElement($documentTypes);

        return [
            'reference_number' => 'DA-' . date('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'document_type' => $documentType,
            'title' => $documentType . ' Request',
            'description' => $this->faker->optional(0.7)->paragraph(),
            'requester_id' => function () {
                return \App\Models\User::whereHas('roles', function($q) {
                    $q->whereIn('name', ['Employee', 'HR Admin', 'Super Admin']);
                })->inRandomOrder()->first()?->id ?? 1;
            },
            'employee_id' => Employee::factory(),
            'priority' => $this->faker->randomElement($priorities),
            'status' => $status,
            'workflow_config' => [
                'steps' => [
                    ['name' => 'Supervisor Review', 'step_order' => 1, 'is_required' => true],
                    ['name' => 'HR Approval', 'step_order' => 2, 'is_required' => true]
                ]
            ],
            'submitted_at' => $status !== 'draft' ? $submittedAt : null,
            'approved_at' => $status === 'approved' ? $this->faker->dateTimeBetween($submittedAt, 'now') : null,
            'rejected_at' => $status === 'rejected' ? $this->faker->dateTimeBetween($submittedAt, 'now') : null,
            'deadline' => $this->faker->optional(0.6)->dateTimeBetween('now', '+30 days'),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            }
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'submitted_at' => null,
            'approved_at' => null,
            'rejected_at' => null
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'approved_at' => null,
            'rejected_at' => null
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_review',
            'submitted_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'approved_at' => null,
            'rejected_at' => null
        ]);
    }

    public function approved(): static
    {
        $submittedAt = $this->faker->dateTimeBetween('-60 days', '-7 days');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'submitted_at' => $submittedAt,
            'approved_at' => $this->faker->dateTimeBetween($submittedAt, 'now'),
            'rejected_at' => null
        ]);
    }

    public function rejected(): static
    {
        $submittedAt = $this->faker->dateTimeBetween('-60 days', '-7 days');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'submitted_at' => $submittedAt,
            'approved_at' => null,
            'rejected_at' => $this->faker->dateTimeBetween($submittedAt, 'now')
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
            'deadline' => $this->faker->dateTimeBetween('now', '+7 days')
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => $this->faker->dateTimeBetween('-7 days', '-1 day'),
            'status' => $this->faker->randomElement(['submitted', 'under_review'])
        ]);
    }
}