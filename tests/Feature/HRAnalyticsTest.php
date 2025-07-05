<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use App\Models\PerformanceEvaluation;
use App\Models\AnalyticsSnapshot;
use App\Services\HRAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class HRAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user with appropriate permissions
        $this->user = User::factory()->create();
        $this->user->assignRole('hr_admin');
        
        $this->analyticsService = new HRAnalyticsService();
    }

    /** @test */
    public function test_analytics_dashboard_loads_successfully()
    {
        $response = $this->actingAs($this->user)
            ->get(route('hr-analytics.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('hr-analytics.dashboard');
    }

    /** @test */
    public function test_workforce_analytics_returns_data()
    {
        // Create test employees
        $employees = Employee::factory()->count(10)->create([
            'employment_status' => 'active',
            'department' => 'IT Department',
            'birth_date' => Carbon::now()->subYears(30),
            'date_hired' => Carbon::now()->subYears(5)
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('hr-analytics.api.workforce'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'employee_demographics' => [
                    'total_employees',
                    'new_hires_this_month',
                    'average_age',
                    'average_tenure'
                ],
                'department_distribution',
                'employment_status_breakdown',
                'age_distribution',
                'tenure_analysis',
                'gender_distribution'
            ]
        ]);

        $data = $response->json('data');
        $this->assertEquals(10, $data['employee_demographics']['total_employees']);
    }

    /** @test */
    public function test_performance_analytics_calculates_correctly()
    {
        // Create employees with performance evaluations
        $employees = Employee::factory()->count(5)->create(['employment_status' => 'active']);
        
        foreach ($employees as $employee) {
            PerformanceEvaluation::factory()->create([
                'employee_id' => $employee->id,
                'overall_rating' => rand(300, 500) / 100, // 3.0 to 5.0
                'evaluation_date' => Carbon::now()->subMonths(rand(1, 6)),
                'evaluation_status' => 'final'
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('hr-analytics.api.performance'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'performance_distribution',
                'performance_trends',
                'top_performers',
                'improvement_needed'
            ]
        ]);
    }

    /** @test */
    public function test_turnover_analytics_processes_correctly()
    {
        // Create active employees
        Employee::factory()->count(8)->create(['employment_status' => 'active']);
        
        // Create terminated employees
        Employee::factory()->count(2)->create([
            'employment_status' => 'terminated',
            'termination_date' => Carbon::now()->subMonths(2)
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('hr-analytics.api.turnover'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'monthly_turnover_rate',
                'annual_turnover_rate',
                'turnover_by_department',
                'retention_rate'
            ]
        ]);
    }

    /** @test */
    public function test_analytics_summary_api_works()
    {
        // Create some test data
        Employee::factory()->count(15)->create(['employment_status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('hr-analytics.summary'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'workforce_summary',
                'turnover_summary',
                'performance_summary',
                'compliance_summary'
            ]
        ]);
    }

    /** @test */
    public function test_analytics_cache_clearing_works()
    {
        $response = $this->actingAs($this->user)
            ->post(route('hr-analytics.clear-cache'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Analytics cache cleared successfully'
        ]);
    }

    /** @test */
    public function test_analytics_insights_generation()
    {
        // Create scenario with high turnover
        Employee::factory()->count(5)->create(['employment_status' => 'active']);
        Employee::factory()->count(3)->create([
            'employment_status' => 'terminated',
            'termination_date' => Carbon::now()->subMonths(1)
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('hr-analytics.insights'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'insights',
                'total_insights',
                'high_priority'
            ]
        ]);
    }

    /** @test */
    public function test_workforce_analytics_service_methods()
    {
        // Create test data
        Employee::factory()->count(20)->create([
            'employment_status' => 'active',
            'department' => 'Engineering',
            'birth_date' => Carbon::now()->subYears(35),
            'date_hired' => Carbon::now()->subYears(8)
        ]);

        Employee::factory()->count(10)->create([
            'employment_status' => 'active',
            'department' => 'HR',
            'birth_date' => Carbon::now()->subYears(28),
            'date_hired' => Carbon::now()->subYears(3)
        ]);

        $analytics = $this->analyticsService->getWorkforceAnalytics();

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('employee_demographics', $analytics);
        $this->assertArrayHasKey('department_distribution', $analytics);
        $this->assertEquals(30, $analytics['employee_demographics']['total_employees']);
    }

    /** @test */
    public function test_analytics_snapshot_creation()
    {
        $testData = [
            'total_employees' => 100,
            'turnover_rate' => 12.5,
            'average_performance' => 4.2,
            'compliance_rate' => 95.0
        ];

        $snapshot = AnalyticsSnapshot::createSnapshot(
            'workforce',
            Carbon::today(),
            $testData,
            'daily'
        );

        $this->assertInstanceOf(AnalyticsSnapshot::class, $snapshot);
        $this->assertEquals('workforce', $snapshot->snapshot_type);
        $this->assertEquals(100, $snapshot->total_employees);
        $this->assertEquals('12.50', $snapshot->turnover_rate);
    }

    /** @test */
    public function test_analytics_chart_data_retrieval()
    {
        // Create multiple snapshots for chart data
        for ($i = 0; $i < 6; $i++) {
            AnalyticsSnapshot::createSnapshot(
                'turnover',
                Carbon::now()->subMonths($i),
                ['turnover_rate' => 10 + $i],
                'monthly'
            );
        }

        $chartData = AnalyticsSnapshot::getChartData('turnover', 'monthly', 6);

        $this->assertCount(6, $chartData);
        $this->assertEquals('turnover', $chartData->first()->snapshot_type);
    }

    /** @test */
    public function test_unauthorized_access_denied()
    {
        $unauthorizedUser = User::factory()->create();
        // Don't assign any roles

        $response = $this->actingAs($unauthorizedUser)
            ->get(route('hr-analytics.dashboard'));

        $response->assertStatus(403);
    }

    /** @test */
    public function test_export_functionality()
    {
        $response = $this->actingAs($this->user)
            ->post(route('hr-analytics.export'), [
                'type' => 'workforce',
                'format' => 'excel'
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'file_path',
                'download_url'
            ]
        ]);
    }

    /** @test */
    public function test_department_specific_analytics()
    {
        // Create employees in specific department
        Employee::factory()->count(5)->create([
            'employment_status' => 'active',
            'department' => 'Finance'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('hr-analytics.department', ['department' => 'Finance']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'department',
                'workforce',
                'performance',
                'training'
            ]
        ]);

        $this->assertEquals('Finance', $response->json('data.department'));
    }

    /** @test */
    public function test_analytics_trends_calculation()
    {
        $response = $this->actingAs($this->user)
            ->get(route('hr-analytics.trends'), [
                'metric' => 'turnover',
                'period' => 'monthly'
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'metric',
                'period',
                'trends'
            ]
        ]);
    }

    /** @test */
    public function test_predictive_analytics_data_structure()
    {
        // Create some employees for predictions
        Employee::factory()->count(15)->create(['employment_status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('hr-analytics.api.predictive'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'turnover_predictions',
                'performance_forecasts',
                'training_needs_prediction',
                'succession_risks'
            ]
        ]);
    }
}