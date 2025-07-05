<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reportType = $this->faker->randomElement([
            Report::TYPE_ACCESSION,
            Report::TYPE_SEPARATION,
            Report::TYPE_DIBAR,
            Report::TYPE_HARASSMENT,
            Report::TYPE_IGHR,
        ]);
        
        $year = $this->faker->numberBetween(2020, 2025);
        $month = $reportType === Report::TYPE_IGHR ? null : $this->faker->numberBetween(1, 12);
        
        $departments = [
            'Human Resources',
            'Finance',
            'Information Technology',
            'Administration',
            'Public Affairs',
            'Legal Affairs',
            'Engineering',
            'Health Services',
        ];
        
        $department = $this->faker->randomElement(array_merge($departments, [null]));
        
        $generationStarted = $this->faker->dateTimeBetween('-6 months', 'now');
        $generationCompleted = $this->faker->optional(0.9)->dateTimeBetween($generationStarted, 'now');
        
        $status = $this->faker->randomElement([
            Report::STATUS_GENERATING,
            Report::STATUS_GENERATED,
            Report::STATUS_REVIEWED,
            Report::STATUS_APPROVED,
            Report::STATUS_SUBMITTED,
            Report::STATUS_ACKNOWLEDGED,
            Report::STATUS_FAILED,
        ]);
        
        // Adjust fields based on status
        $submittedAt = null;
        $acknowledgedAt = null;
        
        if (in_array($status, [Report::STATUS_SUBMITTED, Report::STATUS_ACKNOWLEDGED])) {
            $submittedAt = $this->faker->dateTimeBetween($generationCompleted ?? $generationStarted, 'now');
            
            if ($status === Report::STATUS_ACKNOWLEDGED) {
                $acknowledgedAt = $this->faker->dateTimeBetween($submittedAt, 'now');
            }
        }
        
        return [
            'report_number' => Report::generateReportNumber($reportType, $year),
            'report_type' => $reportType,
            'title' => $this->generateTitle($reportType, $year, $month, $department),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'report_year' => $year,
            'report_month' => $month,
            'period_start' => $month ? Carbon::create($year, $month, 1)->startOfMonth() : Carbon::create($year, 1, 1),
            'period_end' => $month ? Carbon::create($year, $month, 1)->endOfMonth() : Carbon::create($year, 12, 31),
            'department' => $department,
            'filters' => $this->faker->optional(0.5)->randomElement([
                ['employment_status' => 'permanent'],
                ['salary_grade_min' => 15],
                ['position_type' => 'executive'],
                null,
            ]),
            'status' => $status,
            'file_format' => $this->faker->randomElement([
                Report::FORMAT_PDF,
                Report::FORMAT_EXCEL,
                Report::FORMAT_BOTH,
            ]),
            'pdf_file_path' => $this->faker->optional(0.8)->filePath(),
            'excel_file_path' => $this->faker->optional(0.6)->filePath(),
            'file_size' => $this->faker->numberBetween(50000, 5000000), // 50KB to 5MB
            'file_hash' => $this->faker->optional(0.8)->sha256(),
            'report_data' => $this->generateReportData($reportType),
            'summary_statistics' => $this->generateSummaryStatistics($reportType),
            'total_records' => $this->faker->numberBetween(0, 500),
            'submitted_at' => $submittedAt,
            'submission_method' => $submittedAt ? $this->faker->randomElement([
                Report::SUBMISSION_ONLINE,
                Report::SUBMISSION_EMAIL,
                Report::SUBMISSION_PHYSICAL,
            ]) : null,
            'submission_reference' => $submittedAt ? 'CSC-REF-' . $this->faker->numerify('####-####') : null,
            'submission_notes' => $submittedAt ? $this->faker->optional(0.6)->sentence() : null,
            'acknowledged_at' => $acknowledgedAt,
            'generated_by' => User::factory(),
            'reviewed_by' => $this->faker->optional(0.7)->randomElement([null, User::factory()]),
            'approved_by' => in_array($status, [Report::STATUS_APPROVED, Report::STATUS_SUBMITTED, Report::STATUS_ACKNOWLEDGED]) 
                ? User::factory() : null,
            'submitted_by' => $submittedAt ? User::factory() : null,
            'generation_started_at' => $generationStarted,
            'generation_completed_at' => $generationCompleted,
            'generation_duration_seconds' => $generationCompleted 
                ? $this->faker->numberBetween(30, 600) : null,
            'generation_log' => $this->faker->optional(0.5)->paragraph(),
            'error_message' => $status === Report::STATUS_FAILED 
                ? $this->faker->sentence() : null,
            'version' => $this->faker->numberBetween(1, 5),
            'parent_report_id' => null, // Can be set externally for versioning
            'is_current_version' => true,
            'contains_confidential_data' => $reportType === Report::TYPE_HARASSMENT 
                ? $this->faker->boolean(80) : $this->faker->boolean(20),
            'confidentiality_level' => $reportType === Report::TYPE_HARASSMENT 
                ? $this->faker->randomElement([Report::CONFIDENTIALITY_CONFIDENTIAL, Report::CONFIDENTIALITY_RESTRICTED])
                : $this->faker->randomElement([Report::CONFIDENTIALITY_PUBLIC, Report::CONFIDENTIALITY_INTERNAL]),
            'retention_until' => $this->faker->optional(0.3)->dateTimeBetween('+2 years', '+10 years'),
            'legal_notes' => $this->faker->optional(0.2)->paragraph(),
        ];
    }

    /**
     * Generate a contextual title based on report type and parameters.
     */
    private function generateTitle(string $reportType, int $year, ?int $month, ?string $department): string
    {
        $baseTitle = match ($reportType) {
            Report::TYPE_ACCESSION => 'Monthly Accession Report',
            Report::TYPE_SEPARATION => 'Monthly Separation Report',
            Report::TYPE_DIBAR => 'Monthly DIBAR Report',
            Report::TYPE_HARASSMENT => 'Monthly Sexual Harassment Cases Report',
            Report::TYPE_IGHR => 'Annual IGHR Report',
            default => 'CSC Report',
        };
        
        $period = $month ? Carbon::create($year, $month, 1)->format('F Y') : $year;
        $dept = $department ? " - {$department}" : " - All Departments";
        
        return "{$baseTitle} - {$period}{$dept}";
    }

    /**
     * Generate sample report data based on report type.
     */
    private function generateReportData(string $reportType): array
    {
        return match ($reportType) {
            Report::TYPE_ACCESSION => $this->generateAccessionData(),
            Report::TYPE_SEPARATION => $this->generateSeparationData(),
            Report::TYPE_DIBAR => $this->generateDibarData(),
            Report::TYPE_HARASSMENT => $this->generateHarassmentData(),
            Report::TYPE_IGHR => $this->generateIghrData(),
            default => ['sample' => 'data'],
        };
    }

    /**
     * Generate sample summary statistics.
     */
    private function generateSummaryStatistics(string $reportType): array
    {
        return [
            'total_entries' => $this->faker->numberBetween(0, 100),
            'by_department' => [
                'HR' => $this->faker->numberBetween(0, 20),
                'Finance' => $this->faker->numberBetween(0, 15),
                'IT' => $this->faker->numberBetween(0, 10),
            ],
            'by_employment_status' => [
                'permanent' => $this->faker->numberBetween(0, 50),
                'temporary' => $this->faker->numberBetween(0, 20),
                'contractual' => $this->faker->numberBetween(0, 15),
            ],
        ];
    }

    private function generateAccessionData(): array
    {
        return [
            'new_appointments' => $this->faker->numberBetween(0, 20),
            'promotions' => $this->faker->numberBetween(0, 10),
            'transfers' => $this->faker->numberBetween(0, 5),
            'total_accessions' => $this->faker->numberBetween(5, 35),
        ];
    }

    private function generateSeparationData(): array
    {
        return [
            'resignations' => $this->faker->numberBetween(0, 15),
            'retirements' => $this->faker->numberBetween(0, 8),
            'terminations' => $this->faker->numberBetween(0, 3),
            'total_separations' => $this->faker->numberBetween(3, 26),
        ];
    }

    private function generateDibarData(): array
    {
        return [
            'potential_awol_cases' => $this->faker->numberBetween(0, 5),
            'cases_under_review' => $this->faker->numberBetween(0, 3),
            'cases_ready_for_dibar' => $this->faker->numberBetween(0, 2),
        ];
    }

    private function generateHarassmentData(): array
    {
        return [
            'new_cases_filed' => $this->faker->numberBetween(0, 3),
            'cases_under_investigation' => $this->faker->numberBetween(0, 5),
            'cases_resolved' => $this->faker->numberBetween(0, 4),
            'cases_dismissed' => $this->faker->numberBetween(0, 2),
        ];
    }

    private function generateIghrData(): array
    {
        return [
            'total_employees' => $this->faker->numberBetween(100, 1000),
            'by_employment_status' => [
                'permanent' => $this->faker->numberBetween(50, 600),
                'temporary' => $this->faker->numberBetween(20, 200),
                'contractual' => $this->faker->numberBetween(10, 150),
            ],
            'average_age' => $this->faker->randomFloat(1, 30, 50),
            'turnover_rate' => $this->faker->randomFloat(2, 2, 15),
        ];
    }

    /**
     * Indicate that the report is for accession.
     */
    public function accession(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_type' => Report::TYPE_ACCESSION,
        ]);
    }

    /**
     * Indicate that the report is for separation.
     */
    public function separation(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_type' => Report::TYPE_SEPARATION,
        ]);
    }

    /**
     * Indicate that the report is for DIBAR.
     */
    public function dibar(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_type' => Report::TYPE_DIBAR,
        ]);
    }

    /**
     * Indicate that the report is for sexual harassment.
     */
    public function harassment(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_type' => Report::TYPE_HARASSMENT,
            'contains_confidential_data' => true,
            'confidentiality_level' => Report::CONFIDENTIALITY_CONFIDENTIAL,
        ]);
    }

    /**
     * Indicate that the report is for IGHR.
     */
    public function ighr(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_type' => Report::TYPE_IGHR,
            'report_month' => null,
        ]);
    }

    /**
     * Indicate that the report has been submitted.
     */
    public function submitted(): static
    {
        $submittedAt = $this->faker->dateTimeBetween('-3 months', 'now');
        
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_SUBMITTED,
            'submitted_at' => $submittedAt,
            'submission_method' => $this->faker->randomElement([
                Report::SUBMISSION_ONLINE,
                Report::SUBMISSION_EMAIL,
                Report::SUBMISSION_PHYSICAL,
            ]),
            'submission_reference' => 'CSC-REF-' . $this->faker->numerify('####-####'),
        ]);
    }

    /**
     * Indicate that the report has failed generation.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_FAILED,
            'error_message' => $this->faker->sentence(),
            'pdf_file_path' => null,
            'excel_file_path' => null,
        ]);
    }

    /**
     * Indicate that the report contains confidential data.
     */
    public function confidential(): static
    {
        return $this->state(fn (array $attributes) => [
            'contains_confidential_data' => true,
            'confidentiality_level' => $this->faker->randomElement([
                Report::CONFIDENTIALITY_CONFIDENTIAL,
                Report::CONFIDENTIALITY_RESTRICTED,
            ]),
        ]);
    }
}