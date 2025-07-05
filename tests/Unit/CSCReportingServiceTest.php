<?php

namespace Tests\Unit;

use App\Services\CSCReportingService;
use App\Models\Employee;
use App\Models\SexualHarassmentCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Carbon\Carbon;

class CSCReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    private CSCReportingService $cscService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cscService = new CSCReportingService();
    }

    public function test_can_generate_monthly_accession_report()
    {
        // Create test employees hired in June 2025
        Employee::factory()->create([
            'date_hired' => Carbon::create(2025, 6, 15),
            'employment_status' => 'permanent',
            'department' => 'Human Resources',
        ]);

        Employee::factory()->create([
            'date_hired' => Carbon::create(2025, 6, 20),
            'employment_status' => 'temporary',
            'department' => 'Finance',
        ]);

        $report = $this->cscService->generateMonthlyAccessionReport(2025, 6);

        $this->assertIsArray($report);
        $this->assertEquals('Monthly Accession Report', $report['report_type']);
        $this->assertEquals('June 2025', $report['period']);
        $this->assertEquals(2, $report['total_accessions']);
        $this->assertArrayHasKey('data', $report);
        $this->assertArrayHasKey('summary', $report);
    }

    public function test_can_generate_monthly_separation_report()
    {
        // Create and soft delete an employee in June 2025
        $employee = Employee::factory()->create([
            'date_hired' => Carbon::create(2024, 1, 1),
            'employment_status' => 'permanent',
            'department' => 'IT',
        ]);

        // Simulate separation by soft deleting
        $employee->delete();
        $employee->update(['deleted_at' => Carbon::create(2025, 6, 10)]);

        $report = $this->cscService->generateMonthlySeparationReport(2025, 6);

        $this->assertIsArray($report);
        $this->assertEquals('Monthly Separation Report', $report['report_type']);
        $this->assertEquals('June 2025', $report['period']);
        $this->assertEquals(1, $report['total_separations']);
        $this->assertArrayHasKey('data', $report);
        $this->assertArrayHasKey('summary', $report);
    }

    public function test_can_generate_monthly_dibar_report()
    {
        // Create active employees
        Employee::factory()->create([
            'employment_status' => 'active',
            'department' => 'Operations',
            'date_hired' => Carbon::create(2024, 1, 1),
        ]);

        $report = $this->cscService->generateMonthlyDIBARReport(2025, 6);

        $this->assertIsArray($report);
        $this->assertEquals('Monthly DIBAR Report (Dropped from the Rolls)', $report['report_type']);
        $this->assertEquals('June 2025', $report['period']);
        $this->assertArrayHasKey('total_cases', $report);
        $this->assertArrayHasKey('awol_threshold_days', $report);
        $this->assertEquals(30, $report['awol_threshold_days']);
        $this->assertArrayHasKey('data', $report);
        $this->assertArrayHasKey('summary', $report);
    }

    public function test_can_generate_monthly_sexual_harassment_report()
    {
        // Create employees for harassment case
        $complainant = Employee::factory()->create(['department' => 'Sales']);
        $respondent = Employee::factory()->create(['department' => 'Sales']);

        // Create sexual harassment case filed in June 2025
        SexualHarassmentCase::factory()->create([
            'complainant_id' => $complainant->id,
            'respondent_id' => $respondent->id,
            'filed_date' => Carbon::create(2025, 6, 5),
            'case_status' => SexualHarassmentCase::STATUS_UNDER_INVESTIGATION,
            'department_involved' => 'Sales',
        ]);

        $report = $this->cscService->generateMonthlySexualHarassmentReport(2025, 6);

        $this->assertIsArray($report);
        $this->assertEquals('Monthly Sexual Harassment Cases Report', $report['report_type']);
        $this->assertEquals('June 2025', $report['period']);
        $this->assertArrayHasKey('total_cases', $report);
        $this->assertArrayHasKey('data', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('statistics', $report);
    }

    public function test_can_generate_annual_ighr_report()
    {
        // Create employees with various statuses
        Employee::factory()->create([
            'employment_status' => 'permanent',
            'department' => 'HR',
            'salary_grade' => 15,
            'gender' => 'Male',
            'date_hired' => Carbon::create(2024, 3, 1),
        ]);

        Employee::factory()->create([
            'employment_status' => 'temporary',
            'department' => 'Finance',
            'salary_grade' => 12,
            'gender' => 'Female',
            'date_hired' => Carbon::create(2024, 8, 15),
        ]);

        $report = $this->cscService->generateAnnualIGHRReport(2024);

        $this->assertIsArray($report);
        $this->assertEquals('Annual IGHR Report (Inventory of Government Human Resources)', $report['report_type']);
        $this->assertEquals(2024, $report['year']);
        $this->assertEquals(2, $report['total_employees']);
        $this->assertArrayHasKey('demographics', $report);
        $this->assertArrayHasKey('workforce_metrics', $report);
        $this->assertArrayHasKey('performance_metrics', $report);
        $this->assertArrayHasKey('recommendations', $report);
    }

    public function test_validates_report_parameters_correctly()
    {
        // Valid parameters
        $validParams = [
            'year' => 2025,
            'month' => 6,
            'department' => 'HR'
        ];

        $errors = $this->cscService->validateReportParameters($validParams);
        $this->assertEmpty($errors);

        // Invalid parameters
        $invalidParams = [
            'year' => 1999,  // Too old
            'month' => 13,   // Invalid month
        ];

        $errors = $this->cscService->validateReportParameters($invalidParams);
        $this->assertNotEmpty($errors);
        $this->assertContains('Year must be between 2000 and ' . (now()->year + 1), $errors);
        $this->assertContains('Month must be between 1 and 12', $errors);
    }

    public function test_can_filter_reports_by_department()
    {
        // Create employees in different departments
        Employee::factory()->create([
            'date_hired' => Carbon::create(2025, 6, 15),
            'department' => 'HR',
        ]);

        Employee::factory()->create([
            'date_hired' => Carbon::create(2025, 6, 20),
            'department' => 'Finance',
        ]);

        // Get report for HR department only
        $hrReport = $this->cscService->generateMonthlyAccessionReport(2025, 6, 'HR');
        $this->assertEquals('HR', $hrReport['department']);
        $this->assertEquals(1, $hrReport['total_accessions']);

        // Get report for all departments
        $allReport = $this->cscService->generateMonthlyAccessionReport(2025, 6);
        $this->assertEquals('All Departments', $allReport['department']);
        $this->assertEquals(2, $allReport['total_accessions']);
    }

    public function test_cache_is_used_for_reports()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([
                'report_type' => 'Monthly Accession Report',
                'total_accessions' => 0,
                'data' => [],
            ]);

        $report = $this->cscService->generateMonthlyAccessionReport(2025, 6);
        $this->assertIsArray($report);
    }

    public function test_can_clear_report_cache()
    {
        Cache::shouldReceive('forget')->once();
        Cache::shouldReceive('flush')->once();

        $this->cscService->clearReportCache();
        $this->cscService->clearReportCache('accession');

        // No exceptions thrown, method executed successfully
        $this->assertTrue(true);
    }

    public function test_handles_empty_data_gracefully()
    {
        // Test with no employees
        $report = $this->cscService->generateMonthlyAccessionReport(2025, 6);

        $this->assertIsArray($report);
        $this->assertEquals(0, $report['total_accessions']);
        $this->assertIsArray($report['data']);
        $this->assertIsArray($report['summary']);
    }

    public function test_calculates_length_of_service_correctly()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->cscService);
        $method = $reflection->getMethod('calculateLengthOfService');
        $method->setAccessible(true);

        $startDate = Carbon::create(2020, 1, 1);
        $endDate = Carbon::create(2025, 6, 15);

        $result = $method->invoke($this->cscService, $startDate, $endDate);

        $this->assertIsString($result);
        $this->assertStringContains('5 years', $result);
        $this->assertStringContains('5 months', $result);
    }

    public function test_generates_unique_filenames()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->cscService);
        $method = $reflection->getMethod('generateFilename');
        $method->setAccessible(true);

        $reportData = ['period' => 'June 2025'];
        $result = $method->invoke($this->cscService, 'Monthly Accession Report', $reportData);

        $this->assertIsString($result);
        $this->assertStringContains('csc_monthly_accession_report', $result);
        $this->assertStringContains('june_2025', $result);
    }
}