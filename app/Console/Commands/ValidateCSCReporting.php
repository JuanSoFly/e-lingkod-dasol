<?php

namespace App\Console\Commands;

use App\Services\CSCReportingService;
use App\Models\Employee;
use App\Models\SexualHarassmentCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ValidateCSCReporting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'validate:csc-reports 
                            {--year= : Year to validate (default: current year)}
                            {--month= : Month to validate (default: current month)}
                            {--test=all : Which test to run (accession|separation|dibar|harassment|ighr|all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate CSC reporting functionality and data integrity';

    private CSCReportingService $cscService;

    public function __construct(CSCReportingService $cscService)
    {
        parent::__construct();
        $this->cscService = $cscService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏛️ Validating CSC Reporting Functionality');
        $this->info('==========================================');

        $year = $this->option('year') ?: now()->year;
        $month = $this->option('month') ?: now()->month;
        $test = $this->option('test');

        $this->info("Validation Period: {$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT));
        $this->newLine();

        try {
            switch ($test) {
                case 'accession':
                    return $this->validateAccessionReport($year, $month);
                case 'separation':
                    return $this->validateSeparationReport($year, $month);
                case 'dibar':
                    return $this->validateDIBARReport($year, $month);
                case 'harassment':
                    return $this->validateHarassmentReport($year, $month);
                case 'ighr':
                    return $this->validateIGHRReport($year);
                case 'all':
                default:
                    $this->validateAccessionReport($year, $month);
                    $this->newLine();
                    $this->validateSeparationReport($year, $month);
                    $this->newLine();
                    $this->validateDIBARReport($year, $month);
                    $this->newLine();
                    $this->validateHarassmentReport($year, $month);
                    $this->newLine();
                    $this->validateIGHRReport($year);
                    break;
            }

            $this->newLine();
            $this->info('✅ All CSC reporting validation tests completed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error("Validation failed: {$e->getMessage()}");
            Log::error('CSC report validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Validate Monthly Accession Report
     */
    private function validateAccessionReport(int $year, int $month): int
    {
        $this->info('📈 Validating Monthly Accession Report');
        $this->line('=====================================');

        try {
            // Test report generation
            $this->line('Generating accession report...');
            $report = $this->cscService->generateMonthlyAccessionReport($year, $month);

            // Validate report structure
            $this->validateReportStructure($report, [
                'report_metadata',
                'accession_data',
                'summary_statistics',
                'department_breakdown'
            ], 'Accession Report');

            // Validate data integrity
            $expectedCount = $this->getExpectedAccessionCount($year, $month);
            $actualCount = count($report['accession_data']);
            
            $this->line("Expected records: {$expectedCount}");
            $this->line("Actual records: {$actualCount}");
            
            if ($actualCount === $expectedCount) {
                $this->line('✅ Data count matches expected');
            } else {
                $this->warn("⚠️ Data count mismatch (Expected: {$expectedCount}, Actual: {$actualCount})");
            }

            // Validate CSC field mappings
            if (!empty($report['accession_data'])) {
                $sampleRecord = $report['accession_data'][0];
                $requiredFields = [
                    'employee_number', 'full_name', 'position', 'department',
                    'appointment_type', 'appointment_date', 'employment_status'
                ];
                
                $this->validateRequiredFields($sampleRecord, $requiredFields, 'Accession Record');
            }

            $this->line('✅ Accession report validation completed');

        } catch (\Exception $e) {
            $this->error("❌ Accession report validation failed: {$e->getMessage()}");
            throw $e;
        }

        return 0;
    }

    /**
     * Validate Monthly Separation Report
     */
    private function validateSeparationReport(int $year, int $month): int
    {
        $this->info('📉 Validating Monthly Separation Report');
        $this->line('=====================================');

        try {
            $this->line('Generating separation report...');
            $report = $this->cscService->generateMonthlySeparationReport($year, $month);

            $this->validateReportStructure($report, [
                'report_metadata',
                'separation_data',
                'summary_statistics',
                'separation_reasons'
            ], 'Separation Report');

            $expectedCount = $this->getExpectedSeparationCount($year, $month);
            $actualCount = count($report['separation_data']);
            
            $this->line("Expected records: {$expectedCount}");
            $this->line("Actual records: {$actualCount}");

            if (!empty($report['separation_data'])) {
                $sampleRecord = $report['separation_data'][0];
                $requiredFields = [
                    'employee_number', 'full_name', 'position', 'department',
                    'separation_type', 'separation_date', 'separation_reason'
                ];
                
                $this->validateRequiredFields($sampleRecord, $requiredFields, 'Separation Record');
            }

            $this->line('✅ Separation report validation completed');

        } catch (\Exception $e) {
            $this->error("❌ Separation report validation failed: {$e->getMessage()}");
            throw $e;
        }

        return 0;
    }

    /**
     * Validate Monthly DIBAR Report
     */
    private function validateDIBARReport(int $year, int $month): int
    {
        $this->info('📋 Validating Monthly DIBAR Report');
        $this->line('==================================');

        try {
            $this->line('Generating DIBAR report...');
            $report = $this->cscService->generateMonthlyDIBARReport($year, $month);

            $this->validateReportStructure($report, [
                'report_metadata',
                'dibar_data',
                'summary_statistics'
            ], 'DIBAR Report');

            $expectedCount = $this->getExpectedDIBARCount($year, $month);
            $actualCount = count($report['dibar_data']);
            
            $this->line("Expected AWOL records: {$expectedCount}");
            $this->line("Actual AWOL records: {$actualCount}");

            if (!empty($report['dibar_data'])) {
                $sampleRecord = $report['dibar_data'][0];
                $requiredFields = [
                    'employee_number', 'full_name', 'position', 'department',
                    'last_attendance_date', 'consecutive_absent_days', 'awol_start_date'
                ];
                
                $this->validateRequiredFields($sampleRecord, $requiredFields, 'DIBAR Record');
            }

            $this->line('✅ DIBAR report validation completed');

        } catch (\Exception $e) {
            $this->error("❌ DIBAR report validation failed: {$e->getMessage()}");
            throw $e;
        }

        return 0;
    }

    /**
     * Validate Monthly Sexual Harassment Cases Report
     */
    private function validateHarassmentReport(int $year, int $month): int
    {
        $this->info('🛡️ Validating Sexual Harassment Cases Report');
        $this->line('============================================');

        try {
            $this->line('Generating sexual harassment cases report...');
            $report = $this->cscService->generateMonthlySexualHarassmentReport($year, $month);

            $this->validateReportStructure($report, [
                'report_metadata',
                'harassment_cases',
                'summary_statistics',
                'case_status_breakdown'
            ], 'Sexual Harassment Report');

            $expectedCount = $this->getExpectedHarassmentCount($year, $month);
            $actualCount = count($report['harassment_cases']);
            
            $this->line("Expected cases: {$expectedCount}");
            $this->line("Actual cases: {$actualCount}");

            if (!empty($report['harassment_cases'])) {
                $sampleRecord = $report['harassment_cases'][0];
                $requiredFields = [
                    'case_number', 'date_filed', 'case_status', 'complainant_department',
                    'respondent_department', 'investigating_officer'
                ];
                
                $this->validateRequiredFields($sampleRecord, $requiredFields, 'Harassment Case Record');
            }

            $this->line('✅ Sexual harassment report validation completed');

        } catch (\Exception $e) {
            $this->error("❌ Sexual harassment report validation failed: {$e->getMessage()}");
            throw $e;
        }

        return 0;
    }

    /**
     * Validate Annual IGHR Report
     */
    private function validateIGHRReport(int $year): int
    {
        $this->info('📊 Validating Annual IGHR Report');
        $this->line('================================');

        try {
            $this->line('Generating IGHR report...');
            $report = $this->cscService->generateAnnualIGHRReport($year);

            $this->validateReportStructure($report, [
                'report_metadata',
                'employee_inventory',
                'demographic_analysis',
                'position_classification',
                'performance_summary'
            ], 'IGHR Report');

            $expectedCount = $this->getExpectedEmployeeCount($year);
            $actualCount = count($report['employee_inventory']);
            
            $this->line("Expected employees: {$expectedCount}");
            $this->line("Actual employees: {$actualCount}");

            if (!empty($report['employee_inventory'])) {
                $sampleRecord = $report['employee_inventory'][0];
                $requiredFields = [
                    'employee_number', 'full_name', 'position', 'department',
                    'employment_status', 'date_hired', 'csc_eligibility',
                    'latest_performance_rating'
                ];
                
                $this->validateRequiredFields($sampleRecord, $requiredFields, 'IGHR Employee Record');
            }

            $this->line('✅ IGHR report validation completed');

        } catch (\Exception $e) {
            $this->error("❌ IGHR report validation failed: {$e->getMessage()}");
            throw $e;
        }

        return 0;
    }

    /**
     * Validate report structure
     */
    private function validateReportStructure(array $report, array $requiredSections, string $reportType): void
    {
        foreach ($requiredSections as $section) {
            if (!array_key_exists($section, $report)) {
                throw new \Exception("Missing required section '{$section}' in {$reportType}");
            }
        }
        
        $this->line("✓ Report structure validation passed for {$reportType}");
    }

    /**
     * Validate required fields
     */
    private function validateRequiredFields(array $record, array $requiredFields, string $recordType): void
    {
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $record)) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            throw new \Exception("Missing required fields in {$recordType}: " . implode(', ', $missingFields));
        }
        
        $this->line("✓ Required fields validation passed for {$recordType}");
    }

    /**
     * Get expected accession count (simulated)
     */
    private function getExpectedAccessionCount(int $year, int $month): int
    {
        // In a real scenario, this would query actual data
        // For validation purposes, we simulate expected counts
        return Employee::whereYear('appointment_date', $year)
            ->whereMonth('appointment_date', $month)
            ->count();
    }

    /**
     * Get expected separation count (simulated)
     */
    private function getExpectedSeparationCount(int $year, int $month): int
    {
        return Employee::whereYear('separation_date', $year)
            ->whereMonth('separation_date', $month)
            ->whereNotNull('separation_date')
            ->count();
    }

    /**
     * Get expected DIBAR count (simulated)
     */
    private function getExpectedDIBARCount(int $year, int $month): int
    {
        return Employee::where('is_awol', true)
            ->where('consecutive_absent_days', '>=', 30)
            ->whereYear('awol_start_date', $year)
            ->whereMonth('awol_start_date', $month)
            ->count();
    }

    /**
     * Get expected harassment cases count (simulated)
     */
    private function getExpectedHarassmentCount(int $year, int $month): int
    {
        return SexualHarassmentCase::whereYear('date_filed', $year)
            ->whereMonth('date_filed', $month)
            ->count();
    }

    /**
     * Get expected employee count for IGHR (simulated)
     */
    private function getExpectedEmployeeCount(int $year): int
    {
        return Employee::whereYear('date_hired', '<=', $year)
            ->where(function($query) use ($year) {
                $query->whereNull('separation_date')
                    ->orWhereYear('separation_date', '>', $year);
            })
            ->count();
    }
}