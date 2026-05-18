<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing reports to avoid duplicates during seeding
        $this->command->info('Clearing existing reports...');
        Report::truncate();
        

        // Get some users for assignment
        $users = User::limit(5)->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Creating a default user for report assignments.');
            $defaultUser = User::factory()->create([
                'name' => 'CSC Report Administrator',
                'email' => 'csc.admin@example.com',
            ]);
            $users = collect([$defaultUser]);
        }

        // Create sample reports for the last 2 years
        $currentYear = Carbon::now()->year;
        $years = [$currentYear - 1, $currentYear];
        
        foreach ($years as $year) {
            $this->createYearlyReports($year, $users);
        }

        $this->command->info('Sample reports created successfully.');
    }

    /**
     * Create reports for a specific year.
     */
    private function createYearlyReports(int $year, $users): void
    {
        // Create monthly reports for each type (except IGHR which is annual)
        $monthlyTypes = [
            Report::TYPE_ACCESSION,
            Report::TYPE_SEPARATION,
            Report::TYPE_DIBAR,
            Report::TYPE_HARASSMENT,
        ];

        foreach ($monthlyTypes as $reportType) {
            for ($month = 1; $month <= 12; $month++) {
                // Skip future months for current year
                if ($year === Carbon::now()->year && $month > Carbon::now()->month) {
                    continue;
                }

                $this->createMonthlyReport($reportType, $year, $month, $users);
                
                // Create some department-specific reports
                if (rand(1, 4) === 1) { // 25% chance
                    $departments = ['Human Resources', 'Finance', 'Information Technology'];
                    $department = $departments[array_rand($departments)];
                    $this->createMonthlyReport($reportType, $year, $month, $users, $department);
                }
            }
        }

        // Create annual IGHR report
        $this->createAnnualReport(Report::TYPE_IGHR, $year, $users);
        
        // Create some department-specific IGHR reports
        $departments = ['Human Resources', 'Finance', 'Information Technology', 'Administration'];
        foreach ($departments as $department) {
            if (rand(1, 3) === 1) { // 33% chance
                $this->createAnnualReport(Report::TYPE_IGHR, $year, $users, $department);
            }
        }
    }

    /**
     * Create a monthly report.
     */
    private function createMonthlyReport(string $type, int $year, int $month, $users, ?string $department = null): void
    {
        $user = $users->random();
        $status = $this->getRandomStatus($year, $month);

        // Generate report number first to avoid conflicts
        $reportNumber = Report::generateReportNumber($type, $year);

        $report = Report::factory()
            ->state([
                'report_number' => $reportNumber,
                'report_type' => $type,
                'report_year' => $year,
                'report_month' => $month,
                'department' => $department,
                'generated_by' => $user->id,
                'status' => $status,
            ])
            ->create();

        // Add workflow users based on status
        $this->addWorkflowUsers($report, $users, $status);

        // Create some report versions for demonstration
        if (rand(1, 10) === 1) { // 10% chance of having versions
            $this->createReportVersions($report, $users);
        }
    }

    /**
     * Create an annual report.
     */
    private function createAnnualReport(string $type, int $year, $users, ?string $department = null): void
    {
        $user = $users->random();
        $status = $this->getRandomStatus($year, 12); // Use December as reference for annual reports

        // Generate report number first to avoid conflicts
        $reportNumber = Report::generateReportNumber($type, $year);

        $report = Report::factory()
            ->state([
                'report_number' => $reportNumber,
                'report_type' => $type,
                'report_year' => $year,
                'report_month' => null,
                'department' => $department,
                'generated_by' => $user->id,
                'status' => $status,
            ])
            ->create();

        // Add workflow users based on status
        $this->addWorkflowUsers($report, $users, $status);
    }

    /**
     * Get a random status based on the report age.
     */
    private function getRandomStatus(int $year, int $month): string
    {
        $reportDate = Carbon::create($year, $month, 1);
        $monthsOld = Carbon::now()->diffInMonths($reportDate);
        
        // Older reports are more likely to be completed
        if ($monthsOld > 6) {
            return collect([
                Report::STATUS_SUBMITTED,
                Report::STATUS_ACKNOWLEDGED,
                Report::STATUS_APPROVED,
            ])->random();
        } elseif ($monthsOld > 3) {
            return collect([
                Report::STATUS_GENERATED,
                Report::STATUS_REVIEWED,
                Report::STATUS_APPROVED,
                Report::STATUS_SUBMITTED,
            ])->random();
        } else {
            return collect([
                Report::STATUS_GENERATING,
                Report::STATUS_GENERATED,
                Report::STATUS_REVIEWED,
                Report::STATUS_FAILED,
            ])->random();
        }
    }

    /**
     * Add workflow users based on report status.
     */
    private function addWorkflowUsers(Report $report, $users, string $status): void
    {
        $updates = [];
        $availableUsers = $users->where('id', '!=', $report->generated_by)->shuffle()->values();
        $stepIndex = 0;
        
        // Add reviewer for reviewed+ statuses
        if (in_array($status, [
            Report::STATUS_REVIEWED,
            Report::STATUS_APPROVED,
            Report::STATUS_SUBMITTED,
            Report::STATUS_ACKNOWLEDGED,
        ])) {
            if (isset($availableUsers[$stepIndex])) {
                $updates['reviewed_by'] = $availableUsers[$stepIndex++]->id;
            }
        }
        
        // Add approver for approved+ statuses
        if (in_array($status, [
            Report::STATUS_APPROVED,
            Report::STATUS_SUBMITTED,
            Report::STATUS_ACKNOWLEDGED,
        ])) {
            if (isset($availableUsers[$stepIndex])) {
                $updates['approved_by'] = $availableUsers[$stepIndex++]->id;
            }
        }
        
        // Add submitter for submitted+ statuses
        if (in_array($status, [
            Report::STATUS_SUBMITTED,
            Report::STATUS_ACKNOWLEDGED,
        ])) {
            if (isset($availableUsers[$stepIndex])) {
                $updates['submitted_by'] = $availableUsers[$stepIndex++]->id;
            }
            $updates['submitted_at'] = Carbon::now()->subDays(rand(1, 30));
            $updates['submission_method'] = collect([
                Report::SUBMISSION_ONLINE,
                Report::SUBMISSION_EMAIL,
                Report::SUBMISSION_PHYSICAL,
            ])->random();
            $updates['submission_reference'] = 'CSC-REF-' . rand(1000, 9999) . '-' . rand(1000, 9999);
        }
        
        // Add acknowledgment for acknowledged status
        if ($status === Report::STATUS_ACKNOWLEDGED) {
            $updates['acknowledged_at'] = Carbon::parse($updates['submitted_at'] ?? $report->submitted_at)
                ->addDays(rand(1, 14));
        }
        
        if (!empty($updates)) {
            $report->update($updates);
        }
    }

    /**
     * Create report versions for demonstration.
     */
    private function createReportVersions(Report $originalReport, $users): void
    {
        $versionsToCreate = rand(1, 3);
        $parentId = $originalReport->id;
        
        // Mark original as not current
        $originalReport->update(['is_current_version' => false]);
        
        for ($version = 2; $version <= $versionsToCreate + 1; $version++) {
            // Generate report number for version
            $versionReportNumber = Report::generateReportNumber($originalReport->report_type, $originalReport->report_year);

            $newVersion = Report::factory()
                ->state([
                    'report_number' => $versionReportNumber,
                    'report_type' => $originalReport->report_type,
                    'report_year' => $originalReport->report_year,
                    'report_month' => $originalReport->report_month,
                    'department' => $originalReport->department,
                    'generated_by' => $users->random()->id,
                    'parent_report_id' => $parentId,
                    'version' => $version,
                    'is_current_version' => $version === $versionsToCreate + 1, // Last version is current
                    'status' => $version === $versionsToCreate + 1
                        ? $this->getRandomStatus($originalReport->report_year, $originalReport->report_month ?? 12)
                        : Report::STATUS_GENERATED,
                ])
                ->create();
                
            // Add workflow users for current version
            if ($newVersion->is_current_version) {
                $this->addWorkflowUsers($newVersion, $users, $newVersion->status);
            }
        }
    }
}