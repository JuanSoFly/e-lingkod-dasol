<?php

namespace Database\Seeders;

use App\Models\CalibrationSession;
use App\Models\CalibrationSessionItem;
use App\Models\Employee;
use App\Models\FinalRating;
use App\Models\Ipcr;
use App\Models\IpcrItem;
use App\Models\IpcrRating;
use App\Models\IpcrWorkflowLog;
use App\Models\IpcrProgressUpdate;
use App\Models\IpcrCoachingSession;
use App\Models\IpcrDevelopmentAction;
use App\Models\MidPeriodAdjustment;
use App\Models\Office;
use App\Models\OpcrIpcrMapping;
use App\Models\OPCRWorkflow;
use App\Models\PerformanceLink;
use App\Models\PerformancePeriod;
use App\Models\PmtValidation;
use App\Models\WeightDistributionRule;
use App\Models\WorkflowAttachment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SampleIPCRSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Verify required roles exist before seeding
            $this->verifyRequiredRoles();

            $period = PerformancePeriod::firstOrCreate(
            ['name' => 'CY 2025'],
            [
                'year' => 2025,
                'semester' => 'Annual',
                'start_date' => Carbon::create(2025, 1, 1),
                'end_date' => Carbon::create(2025, 12, 31),
                'status' => 'active',
            ]
        );

        $office = Office::firstOrCreate(
            ['code' => 'HRMO'],
            [
                'name' => 'Human Resource Management Office',
                'description' => 'Responsible for municipal HR operations',
                'level' => 2,
                'head_title' => 'HRMO Head',
                'contact_number' => '075-123-4567',
                'email' => 'hrmo@dasol.gov.ph',
                'location' => 'Municipal Hall, Dasol, Pangasinan',
                'is_active' => true,
            ]
        );

        $employee = Employee::factory()
            ->withUser()
            ->create([
                'first_name' => 'Angela',
                'last_name' => 'Soriano',
                'email' => 'angela.soriano@dasol.gov.ph',
                'position' => 'HR Officer II',
                'department' => $office->name,
                'office_id' => $office->id,
                'office_code' => $office->code,
            ]);
        $this->safeAssignRole($employee, ['Employee', 'IPCR Employee']);

        $supervisor = Employee::factory()
            ->withUser()
            ->create([
                'first_name' => 'Mario',
                'last_name' => 'Buenaventura',
                'email' => 'mario.buenaventura@dasol.gov.ph',
                'position' => 'Supervising HR Officer',
                'department' => $office->name,
                'office_id' => $office->id,
                'office_code' => $office->code,
            ]);
        $this->safeAssignRole($supervisor, 'IPCR Supervisor');

        $headOfOffice = Employee::factory()
            ->withUser()
            ->create([
                'first_name' => 'Clarissa',
                'last_name' => 'Villanueva',
                'email' => 'clarissa.villanueva@dasol.gov.ph',
                'position' => 'HRMO Department Head',
                'department' => $office->name,
                'office_id' => $office->id,
                'office_code' => $office->code,
            ]);
        $this->safeAssignRole($headOfOffice, ['Department Head', 'Head of Office']);

        $pmtMember = Employee::factory()
            ->withUser()
            ->create([
                'first_name' => 'Rodel',
                'last_name' => 'Aquino',
                'email' => 'rodel.aquino@dasol.gov.ph',
                'position' => 'PMT Member',
                'department' => 'Performance Management Team',
                'office_id' => $office->id,
                'office_code' => $office->code,
            ]);
        $this->safeAssignRole($pmtMember, ['Assessor', 'PMT Member']);

        $finalApprover = Employee::factory()
            ->withUser()
            ->create([
                'first_name' => 'Lucia',
                'last_name' => 'Garcia',
                'email' => 'lucia.garcia@dasol.gov.ph',
                'position' => 'Municipal Administrator',
                'department' => 'Office of the Municipal Administrator',
                'office_id' => $office->id,
                'office_code' => $office->code,
            ]);
        $this->safeAssignRole($finalApprover, 'Final Approver');

        $ipcr = Ipcr::factory()
            ->has(
                IpcrItem::factory()
                    ->count(3)
                    ->state(function () {
                        return [
                            'weight' => 33.33,
                        ];
                    })
            )
            ->create([
                'employee_id' => $employee->id,
                'office_id' => $office->id,
                'period_id' => $period->id,
                'supervisor_id' => $supervisor->id,
                'head_of_office_id' => $headOfOffice->id,
                'pmt_validator_id' => $pmtMember->id,
                'final_approver_id' => $finalApprover->id,
                'status' => 'finalized',
                'total_weight' => 100,
                'overall_score' => 4.5,
                'adjectival_rating' => 'Outstanding',
                'is_auto_generated' => true,
                'submitted_at' => now()->subWeeks(3),
                'supervisor_reviewed_at' => now()->subWeeks(2),
                'head_reviewed_at' => now()->subWeek(),
                'pmt_validated_at' => now()->subDays(5),
                'finalized_at' => now()->subDays(3),
                'locked_at' => now()->subDay(),
                'remarks' => 'Sample finalized IPCR for demonstration.',
            ]);

        $opcrWorkflow = OPCRWorkflow::first();

        WeightDistributionRule::updateOrCreate(
            [
                'office_id' => $office->id,
                'rule_name' => 'Standard HR Staff Distribution',
            ],
            [
                'applies_to_role' => 'IPCR Employee',
                'allocation_method' => 'proportional',
                'default_weight' => 33.33,
                'minimum_weight' => 10,
                'maximum_weight' => 60,
                'priority' => 1,
                'effective_start_date' => now()->startOfYear(),
                'is_active' => true,
                'conditions' => [
                    'department' => $office->name,
                    'employment_status' => 'Regular',
                ],
            ]
        );

        WorkflowAttachment::firstOrCreate(
            [
                'attachable_type' => Ipcr::class,
                'attachable_id' => $ipcr->id,
                'file_path' => 'ipcr/evidence/' . Str::uuid() . '.pdf',
            ],
            [
                'uploaded_by' => $employee->user->id,
                'attachment_type' => 'evidence',
                'file_name' => 'accomplishment-summary.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 120_000,
                'checksum' => Str::uuid()->toString(),
                'uploaded_at' => now()->subWeeks(2),
                'metadata' => ['note' => 'Sample attachment generated by seeder'],
            ]
        );

        $session = CalibrationSession::firstOrCreate(
            [
                'performance_period_id' => $period->id,
                'session_title' => 'CY 2025 HRMO Calibration',
            ],
            [
                'office_id' => $office->id,
                'status' => 'completed',
                'scheduled_at' => now()->subDays(10),
                'started_at' => now()->subDays(9),
                'completed_at' => now()->subDays(7),
                'participants' => [
                    $headOfOffice->user->email,
                    $pmtMember->user->email,
                    $finalApprover->user->email,
                ],
                'rating_statistics' => [
                    'mean' => 4.5,
                    'median' => 4.5,
                    'std_dev' => 0.1,
                ],
                'action_items' => [
                    ['task' => 'Document coaching notes', 'owner' => $supervisor->user->email],
                ],
            ]
        );

        $ipcr->items->each(function (IpcrItem $item) use ($ipcr, $employee, $supervisor, $headOfOffice, $pmtMember, $opcrWorkflow, $session) {
            IpcrRating::factory()
                ->for($ipcr, 'ipcr')
                ->for($item, 'item')
                ->for($employee->user, 'raterUser')
                ->for($employee, 'raterEmployee')
                ->state([
                    'rater_role' => 'employee',
                    'rating_type' => 'self',
                    'overall_rating' => 4.5,
                    'rated_at' => now()->subWeeks(3),
                ])
                ->create();

            IpcrRating::factory()
                ->for($ipcr, 'ipcr')
                ->for($item, 'item')
                ->for($supervisor->user, 'raterUser')
                ->for($supervisor, 'raterEmployee')
                ->state([
                    'rater_role' => 'supervisor',
                    'rating_type' => 'supervisor',
                    'overall_rating' => 4.6,
                    'rated_at' => now()->subWeeks(2),
                ])
                ->create();

            IpcrRating::factory()
                ->for($ipcr, 'ipcr')
                ->for($item, 'item')
                ->for($headOfOffice->user, 'raterUser')
                ->for($headOfOffice, 'raterEmployee')
                ->state([
                    'rater_role' => 'head',
                    'rating_type' => 'head',
                    'overall_rating' => 4.6,
                    'rated_at' => now()->subWeek(),
                ])
                ->create();

            IpcrRating::factory()
                ->for($ipcr, 'ipcr')
                ->for($item, 'item')
                ->for($pmtMember->user, 'raterUser')
                ->for($pmtMember, 'raterEmployee')
                ->state([
                    'rater_role' => 'pmt',
                    'rating_type' => 'pmt',
                    'overall_rating' => 4.7,
                    'rated_at' => now()->subDays(5),
                ])
                ->create();

            OpcrIpcrMapping::firstOrCreate([
                'opcr_workflow_id' => $opcrWorkflow?->id,
                'performance_target_id' => $opcrWorkflow?->targets()->first()->id ?? null,
                'ipcr_id' => $ipcr->id,
                'ipcr_item_id' => $item->id,
            ], [
                'allocation_strategy' => 'proportional',
                'weight_percentage' => $item->weight,
                'cascade_level' => 'office-to-individual',
            ]);

            PerformanceLink::firstOrCreate([
                'ipcr_id' => $ipcr->id,
                'ipcr_item_id' => $item->id,
                'linked_type' => OPCRWorkflow::class,
                'linked_id' => $opcrWorkflow?->id ?? 0,
            ], [
                'link_category' => 'cascaded-target',
                'description' => 'Aligned with OPCR strategic objective',
                'created_by' => $supervisor->user->id,
            ]);

            WorkflowAttachment::firstOrCreate([
                'attachable_type' => IpcrItem::class,
                'attachable_id' => $item->id,
                'file_path' => 'ipcr/item-support/' . Str::uuid() . '.pdf',
            ], [
                'uploaded_by' => $employee->user->id,
                'attachment_type' => 'supporting-document',
                'file_name' => $item->title . '.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 86_000,
                'checksum' => Str::uuid()->toString(),
                'uploaded_at' => now()->subWeeks(2),
            ]);

            if ($item->sequence === 1) {
                MidPeriodAdjustment::firstOrCreate([
                    'ipcr_id' => $ipcr->id,
                    'ipcr_item_id' => $item->id,
                    'requested_by' => $employee->user->id,
                ], [
                    'requested_employee_id' => $employee->id,
                    'requested_at' => now()->subWeeks(1),
                    'approved_by' => $supervisor->user->id,
                    'approved_at' => now()->subDays(6),
                    'status' => 'approved',
                    'adjustment_type' => 'target_update',
                    'original_values' => ['target_quantity' => 50],
                    'proposed_values' => ['target_quantity' => 60],
                    'justification' => 'Expanded HRIS onboarding coverage.',
                    'decision_notes' => 'Approved with monitoring checkpoints.',
                ]);
            }

            CalibrationSessionItem::firstOrCreate([
                'calibration_session_id' => $session->id,
                'ipcr_id' => $ipcr->id,
                'employee_id' => $ipcr->employee_id,
            ], [
                'initial_rating' => 4.5,
                'recommended_rating' => 4.6,
                'final_rating' => 4.5,
                'discussion_points' => ['alignment_with_targets' => true],
                'notes' => 'Validated during HRMO calibration.',
            ]);
        });

        IpcrProgressUpdate::create([
            'ipcr_id' => $ipcr->id,
            'ipcr_item_id' => $ipcr->items->first()?->id,
            'reported_by' => $employee->user->id,
            'progress_date' => now()->subWeeks(2),
            'status' => 'on_track',
            'accomplishments' => 'Completed 80% of deliverables with ahead-of-time submissions.',
            'challenges' => 'Coordinating with external offices.',
            'next_steps' => 'Finish remaining items and gather supporting documents.',
        ]);

        IpcrCoachingSession::create([
            'ipcr_id' => $ipcr->id,
            'coach_user_id' => $supervisor->user->id,
            'participant_user_id' => $employee->user->id,
            'session_date' => now()->subWeek(),
            'session_type' => 'coaching',
            'focus_area' => 'Timeliness of document submissions',
            'discussion_notes' => 'Aligned expectations and provided checklist template.',
            'agreements' => 'Employee to adopt new checklist and send weekly summaries.',
            'follow_up_date' => now()->addWeek(),
        ]);

        IpcrDevelopmentAction::create([
            'ipcr_id' => $ipcr->id,
            'focus_area' => 'Process Optimization',
            'action_item' => 'Attend LGU digital workflow training',
            'target_date' => now()->addMonth(),
            'status' => 'planned',
            'support_needed' => 'Training enrollment from HR',
            'created_by' => $supervisor->user->id,
        ]);

        PmtValidation::firstOrCreate([
            'ipcr_id' => $ipcr->id,
            'validator_id' => $pmtMember->id,
        ], [
            'office_id' => $office->id,
            'validator_user_id' => $pmtMember->user->id,
            'validation_stage' => 'initial',
            'status' => 'validated',
            'recommended_rating' => 4.6,
            'rating_breakdown' => ['quality' => 4.7, 'efficiency' => 4.5, 'timeliness' => 4.6],
            'remarks' => 'Performance exceeds quality metrics.',
            'validated_at' => now()->subDays(4),
        ]);

        FinalRating::firstOrCreate([
            'ipcr_id' => $ipcr->id,
            'employee_id' => $ipcr->employee_id,
        ], [
            'validated_by' => $finalApprover->user->id,
            'final_score' => 4.5,
            'adjectival_rating' => 'Outstanding',
            'performance_level' => 'Level 5',
            'is_locked' => true,
            'locked_at' => now()->subDay(),
            'remarks' => 'Eligible for PBB incentives.',
        ]);

        collect([
            ['draft', 'for_supervisor_review', $employee->user, $employee, 'employee'],
            ['for_supervisor_review', 'for_head_approval', $supervisor->user, $supervisor, 'supervisor'],
            ['for_head_approval', 'for_pmt_validation', $headOfOffice->user, $headOfOffice, 'head'],
            ['for_pmt_validation', 'finalized', $pmtMember->user, $pmtMember, 'pmt'],
            ['finalized', 'locked', $finalApprover->user, $finalApprover, 'final_approver'],
        ])->each(function (array $step) use ($ipcr) {
            [$from, $to, $user, $employee, $role] = $step;

            IpcrWorkflowLog::factory()
                ->for($ipcr, 'ipcr')
                ->for($user, 'user')
                ->for($employee, 'employee')
                ->state([
                    'from_state' => $from,
                    'to_state' => $to,
                    'performed_role' => $role,
                    'performed_at' => now()->subDays(rand(1, 21)),
                ])
                ->create();
        });

            if ($this->command) {
                $this->command->info('SampleIPCRSeeder completed successfully!');
            }
        } catch (\Exception $e) {
            if ($this->command) {
                $this->command->error('SampleIPCRSeeder failed: ' . $e->getMessage());
                $this->command->error('Stack trace: ' . $e->getTraceAsString());
            }
            throw $e;
        }
    }

    /**
     * Verify that all required roles exist before seeding
     */
    private function verifyRequiredRoles(): void
    {
        $requiredRoles = ['Employee', 'IPCR Employee', 'IPCR Supervisor', 'Department Head', 'Head of Office', 'Assessor', 'PMT Member', 'Final Approver'];

        foreach ($requiredRoles as $role) {
            if (!\Spatie\Permission\Models\Role::where('name', $role)->exists()) {
                throw new \RuntimeException("Required role '{$role}' does not exist. Please run RoleAndPermissionSeeder first.");
            }
        }
    }

    /**
     * Validate employee has a user account
     */
    private function validateEmployeeUser(Employee $employee, string $roleName): void
    {
        if (!$employee->user) {
            throw new \RuntimeException("Employee {$employee->full_name} has no user account. Cannot assign role: {$roleName}");
        }
    }

    /**
     * Safely assign roles to an employee's user account with validation
     */
    private function safeAssignRole(Employee $employee, string|array $roles): void
    {
        $roles = is_array($roles) ? $roles : [$roles];

        foreach ($roles as $role) {
            $this->validateEmployeeUser($employee, $role);

            if (!\Spatie\Permission\Models\Role::where('name', $role)->exists()) {
                throw new \RuntimeException("Role '{$role}' does not exist in the system");
            }

            $employee->user->assignRole($role);
        }
    }
}
