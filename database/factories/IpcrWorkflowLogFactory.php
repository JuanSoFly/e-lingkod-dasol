<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Ipcr;
use App\Models\IpcrWorkflowLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories.Factory<\App\Models\IpcrWorkflowLog>
 */
class IpcrWorkflowLogFactory extends Factory
{
    protected $model = IpcrWorkflowLog::class;

    public function definition(): array
    {
        $states = [
            'draft',
            'for_supervisor_review',
            'returned_with_notes',
            'for_head_approval',
            'for_pmt_validation',
            'finalized',
            'locked',
        ];

        $fromState = $this->faker->randomElement($states);
        $toState = $this->faker->randomElement(array_diff($states, [$fromState]));

        return [
            'ipcr_id' => Ipcr::factory(),
            'user_id' => User::factory(),
            'employee_id' => Employee::factory(),
            'from_state' => $fromState,
            'to_state' => $toState,
            'action' => $this->faker->randomElement(['submit', 'review', 'return', 'approve', 'validate', 'lock']),
            'performed_role' => $this->faker->randomElement(['employee', 'supervisor', 'head', 'pmt']),
            'remarks' => $this->faker->sentence(),
            'metadata' => [
                'ip_address' => $this->faker->ipv4(),
            ],
            'performed_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}
