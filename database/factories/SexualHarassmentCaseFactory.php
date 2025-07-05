<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\SexualHarassmentCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SexualHarassmentCase>
 */
class SexualHarassmentCaseFactory extends Factory
{
    protected $model = SexualHarassmentCase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $incidentDate = fake()->dateTimeBetween('-1 year', '-1 month');
        $filedDate = fake()->dateTimeBetween($incidentDate, 'now');
        
        return [
            'case_number' => SexualHarassmentCase::generateCaseNumber(),
            'complainant_name' => fake()->name(),
            'respondent_name' => fake()->name(),
            'incident_date' => $incidentDate,
            'filed_date' => $filedDate,
            'incident_description' => fake()->paragraph(3),
            'complainant_statement' => fake()->paragraph(5),
            'case_status' => fake()->randomElement([
                SexualHarassmentCase::STATUS_FILED,
                SexualHarassmentCase::STATUS_UNDER_INVESTIGATION,
                SexualHarassmentCase::STATUS_DISMISSED,
                SexualHarassmentCase::STATUS_RESOLVED,
            ]),
            'investigation_status' => fake()->randomElement([
                'Pending',
                'In Progress',
                'Completed',
                'On Hold',
            ]),
            'is_confidential' => fake()->boolean(80), // 80% chance of being confidential
            'department_involved' => fake()->randomElement([
                'Human Resources',
                'Finance',
                'IT',
                'Operations',
                'Sales',
                'Marketing',
                'Administration',
            ]),
            'office_location' => fake()->randomElement([
                'Main Office',
                'Branch Office',
                'Field Office',
                'Regional Office',
            ]),
            'remarks' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the case is under investigation.
     */
    public function underInvestigation(): static
    {
        return $this->state(fn (array $attributes) => [
            'case_status' => SexualHarassmentCase::STATUS_UNDER_INVESTIGATION,
            'investigation_status' => 'In Progress',
            'investigation_start_date' => fake()->dateTimeBetween($attributes['filed_date'], 'now'),
        ]);
    }

    /**
     * Indicate that the case is resolved.
     */
    public function resolved(): static
    {
        return $this->state(function (array $attributes) {
            $resolutionDate = fake()->dateTimeBetween($attributes['filed_date'], 'now');
            
            return [
                'case_status' => SexualHarassmentCase::STATUS_RESOLVED,
                'investigation_status' => 'Completed',
                'investigation_start_date' => fake()->dateTimeBetween($attributes['filed_date'], $resolutionDate),
                'investigation_end_date' => fake()->dateTimeBetween($attributes['filed_date'], $resolutionDate),
                'resolution_type' => fake()->randomElement([
                    SexualHarassmentCase::RESOLUTION_ADMINISTRATIVE_SANCTION,
                    SexualHarassmentCase::RESOLUTION_MEDIATION,
                    SexualHarassmentCase::RESOLUTION_NO_VIOLATION,
                ]),
                'resolution_date' => $resolutionDate,
                'resolution_details' => fake()->paragraph(2),
                'investigation_findings' => fake()->paragraph(4),
                'recommendations' => fake()->paragraph(2),
            ];
        });
    }

    /**
     * Indicate that the case is dismissed.
     */
    public function dismissed(): static
    {
        return $this->state(function (array $attributes) {
            $resolutionDate = fake()->dateTimeBetween($attributes['filed_date'], 'now');
            
            return [
                'case_status' => SexualHarassmentCase::STATUS_DISMISSED,
                'investigation_status' => 'Completed',
                'investigation_start_date' => fake()->dateTimeBetween($attributes['filed_date'], $resolutionDate),
                'investigation_end_date' => fake()->dateTimeBetween($attributes['filed_date'], $resolutionDate),
                'resolution_type' => SexualHarassmentCase::RESOLUTION_NO_VIOLATION,
                'resolution_date' => $resolutionDate,
                'resolution_details' => 'Case dismissed due to lack of merit.',
                'investigation_findings' => fake()->paragraph(3),
                'recommendations' => 'Case closed with no further action required.',
            ];
        });
    }

    /**
     * Indicate that the case is forwarded to court.
     */
    public function forwardedToCourt(): static
    {
        return $this->state(function (array $attributes) {
            $courtFilingDate = fake()->dateTimeBetween($attributes['filed_date'], 'now');
            
            return [
                'case_status' => SexualHarassmentCase::STATUS_FORWARDED_TO_COURT,
                'investigation_status' => 'Completed',
                'investigation_start_date' => fake()->dateTimeBetween($attributes['filed_date'], $courtFilingDate),
                'investigation_end_date' => fake()->dateTimeBetween($attributes['filed_date'], $courtFilingDate),
                'forwarded_to_court' => true,
                'court_filing_date' => $courtFilingDate,
                'court_case_number' => fake()->regexify('[A-Z]{2}-[0-9]{4}-[0-9]{4}'),
                'court_status' => fake()->randomElement([
                    'Filed',
                    'Under Trial',
                    'Pending Hearing',
                    'Awaiting Judgment',
                ]),
                'investigation_findings' => fake()->paragraph(4),
                'recommendations' => 'Case forwarded to appropriate court for criminal proceedings.',
            ];
        });
    }

    /**
     * Indicate that the case has an appeal filed.
     */
    public function withAppeal(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'case_status' => SexualHarassmentCase::STATUS_PENDING_APPEAL,
                'appeal_filed' => true,
                'appeal_date' => fake()->dateTimeBetween($attributes['resolution_date'] ?? $attributes['filed_date'], 'now'),
                'appeal_status' => fake()->randomElement([
                    'Filed',
                    'Under Review',
                    'Hearing Scheduled',
                    'Pending Decision',
                ]),
            ];
        });
    }

    /**
     * Indicate that the case is confidential.
     */
    public function confidential(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_confidential' => true,
        ]);
    }

    /**
     * Indicate that the case is not confidential.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_confidential' => false,
        ]);
    }

    /**
     * Set specific complainant employee.
     */
    public function withComplainant(Employee $employee): static
    {
        return $this->state(fn (array $attributes) => [
            'complainant_id' => $employee->id,
            'complainant_name' => $employee->first_name . ' ' . $employee->last_name,
            'department_involved' => $employee->department,
        ]);
    }

    /**
     * Set specific respondent employee.
     */
    public function withRespondent(Employee $employee): static
    {
        return $this->state(fn (array $attributes) => [
            'respondent_id' => $employee->id,
            'respondent_name' => $employee->first_name . ' ' . $employee->last_name,
        ]);
    }

    /**
     * Set specific investigating officer.
     */
    public function withInvestigatingOfficer(Employee $employee): static
    {
        return $this->state(fn (array $attributes) => [
            'investigating_officer_id' => $employee->id,
        ]);
    }

    /**
     * Set witnesses information.
     */
    public function withWitnesses(array $witnesses = null): static
    {
        return $this->state(fn (array $attributes) => [
            'witnesses' => $witnesses ?? [
                [
                    'name' => fake()->name(),
                    'position' => fake()->jobTitle(),
                    'statement' => fake()->paragraph(2),
                ],
                [
                    'name' => fake()->name(),
                    'position' => fake()->jobTitle(),
                    'statement' => fake()->paragraph(2),
                ],
            ],
        ]);
    }

    /**
     * Set evidence files.
     */
    public function withEvidence(array $evidenceFiles = null): static
    {
        return $this->state(fn (array $attributes) => [
            'evidence_files' => $evidenceFiles ?? [
                'incident_report.pdf',
                'witness_statement_1.pdf',
                'email_evidence.pdf',
            ],
        ]);
    }

    /**
     * Set for specific department.
     */
    public function forDepartment(string $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department_involved' => $department,
        ]);
    }

    /**
     * Set for specific time period.
     */
    public function filedInMonth(int $year, int $month): static
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        
        return $this->state(fn (array $attributes) => [
            'filed_date' => fake()->dateTimeBetween($startDate, $endDate),
        ]);
    }

    /**
     * Set for specific resolution period.
     */
    public function resolvedInMonth(int $year, int $month): static
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        
        return $this->state(fn (array $attributes) => [
            'resolution_date' => fake()->dateTimeBetween($startDate, $endDate),
            'case_status' => SexualHarassmentCase::STATUS_RESOLVED,
        ]);
    }
}