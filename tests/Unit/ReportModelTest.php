<?php

namespace Tests\Unit;

use App\Models\Report;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ReportModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run the role and permission seeder for proper user setup
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    /** @test */
    public function it_can_create_a_report()
    {
        $user = User::factory()->create();
        
        $report = Report::factory()->create([
            'report_type' => Report::TYPE_ACCESSION,
            'report_year' => 2025,
            'report_month' => 6,
            'generated_by' => $user->id,
        ]);
        
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'report_type' => Report::TYPE_ACCESSION,
            'report_year' => 2025,
            'report_month' => 6,
        ]);
    }

    /** @test */
    public function it_generates_unique_report_numbers()
    {
        $user = User::factory()->create();
        
        $report1 = Report::factory()->create([
            'report_type' => Report::TYPE_ACCESSION,
            'report_year' => 2025,
            'generated_by' => $user->id,
        ]);
        
        $report2 = Report::factory()->create([
            'report_type' => Report::TYPE_ACCESSION,
            'report_year' => 2025,
            'generated_by' => $user->id,
        ]);
        
        $this->assertNotEquals($report1->report_number, $report2->report_number);
        $this->assertStringStartsWith('CSC-ACC-2025-', $report1->report_number);
        $this->assertStringStartsWith('CSC-ACC-2025-', $report2->report_number);
    }

    /** @test */
    public function it_formats_report_period_correctly()
    {
        $user = User::factory()->create();
        
        // Monthly report
        $monthlyReport = Report::factory()->create([
            'report_year' => 2025,
            'report_month' => 6,
            'generated_by' => $user->id,
        ]);
        
        $this->assertEquals('June 2025', $monthlyReport->formatted_period);
        
        // Annual report
        $annualReport = Report::factory()->create([
            'report_type' => Report::TYPE_IGHR,
            'report_year' => 2025,
            'report_month' => null,
            'generated_by' => $user->id,
        ]);
        
        $this->assertEquals('2025', $annualReport->formatted_period);
    }

    /** @test */
    public function it_formats_report_type_correctly()
    {
        $user = User::factory()->create();
        
        $testCases = [
            Report::TYPE_ACCESSION => 'Monthly Accession Report',
            Report::TYPE_SEPARATION => 'Monthly Separation Report',
            Report::TYPE_DIBAR => 'Monthly DIBAR Report',
            Report::TYPE_HARASSMENT => 'Monthly Sexual Harassment Cases Report',
            Report::TYPE_IGHR => 'Annual IGHR Report',
        ];
        
        foreach ($testCases as $type => $expectedTitle) {
            $report = Report::factory()->create([
                'report_type' => $type,
                'generated_by' => $user->id,
            ]);
            
            $this->assertEquals($expectedTitle, $report->formatted_type);
        }
    }

    /** @test */
    public function it_determines_editability_correctly()
    {
        $user = User::factory()->create();
        
        $editableStatuses = [
            Report::STATUS_GENERATING,
            Report::STATUS_GENERATED,
            Report::STATUS_FAILED,
        ];
        
        $nonEditableStatuses = [
            Report::STATUS_REVIEWED,
            Report::STATUS_APPROVED,
            Report::STATUS_SUBMITTED,
            Report::STATUS_ACKNOWLEDGED,
            Report::STATUS_CANCELLED,
        ];
        
        foreach ($editableStatuses as $status) {
            $report = Report::factory()->create([
                'status' => $status,
                'generated_by' => $user->id,
            ]);
            
            $this->assertTrue($report->isEditable(), "Report with status {$status} should be editable");
        }
        
        foreach ($nonEditableStatuses as $status) {
            $report = Report::factory()->create([
                'status' => $status,
                'generated_by' => $user->id,
            ]);
            
            $this->assertFalse($report->isEditable(), "Report with status {$status} should not be editable");
        }
    }

    /** @test */
    public function it_determines_submission_eligibility_correctly()
    {
        $user = User::factory()->create();
        
        $submittableStatuses = [
            Report::STATUS_GENERATED,
            Report::STATUS_REVIEWED,
            Report::STATUS_APPROVED,
        ];
        
        $nonSubmittableStatuses = [
            Report::STATUS_GENERATING,
            Report::STATUS_SUBMITTED,
            Report::STATUS_ACKNOWLEDGED,
            Report::STATUS_FAILED,
            Report::STATUS_CANCELLED,
        ];
        
        foreach ($submittableStatuses as $status) {
            $report = Report::factory()->create([
                'status' => $status,
                'generated_by' => $user->id,
            ]);
            
            $this->assertTrue($report->canBeSubmitted(), "Report with status {$status} should be submittable");
        }
        
        foreach ($nonSubmittableStatuses as $status) {
            $report = Report::factory()->create([
                'status' => $status,
                'generated_by' => $user->id,
            ]);
            
            $this->assertFalse($report->canBeSubmitted(), "Report with status {$status} should not be submittable");
        }
    }

    /** @test */
    public function it_can_manage_report_versions()
    {
        $user = User::factory()->create();
        
        // Create original report
        $originalReport = Report::factory()->create([
            'report_type' => Report::TYPE_ACCESSION,
            'report_year' => 2025,
            'report_month' => 6,
            'version' => 1,
            'is_current_version' => true,
            'generated_by' => $user->id,
        ]);
        
        // Create new version
        $newVersion = Report::factory()->create([
            'report_type' => Report::TYPE_ACCESSION,
            'report_year' => 2025,
            'report_month' => 6,
            'version' => 2,
            'parent_report_id' => $originalReport->id,
            'is_current_version' => false,
            'generated_by' => $user->id,
        ]);
        
        // Mark new version as current
        $newVersion->markAsCurrentVersion();
        
        // Refresh models
        $originalReport->refresh();
        $newVersion->refresh();
        
        $this->assertFalse($originalReport->is_current_version);
        $this->assertTrue($newVersion->is_current_version);
    }

    /** @test */
    public function it_calculates_generation_duration()
    {
        $user = User::factory()->create();
        
        $startTime = Carbon::now()->subMinutes(5);
        $endTime = Carbon::now();
        
        $report = Report::factory()->create([
            'generation_started_at' => $startTime,
            'generation_completed_at' => $endTime,
            'generated_by' => $user->id,
        ]);
        
        $expectedDuration = $endTime->diffInSeconds($startTime);
        $this->assertEquals($expectedDuration, $report->calculateGenerationDuration());
    }

    /** @test */
    public function it_has_proper_relationships()
    {
        $generator = User::factory()->create();
        $reviewer = User::factory()->create();
        $approver = User::factory()->create();
        
        $report = Report::factory()->create([
            'generated_by' => $generator->id,
            'reviewed_by' => $reviewer->id,
            'approved_by' => $approver->id,
        ]);
        
        $this->assertEquals($generator->id, $report->generatedBy->id);
        $this->assertEquals($reviewer->id, $report->reviewedBy->id);
        $this->assertEquals($approver->id, $report->approvedBy->id);
    }

    /** @test */
    public function it_can_scope_queries_correctly()
    {
        $user = User::factory()->create();
        
        // Create reports with different attributes
        Report::factory()->create([
            'report_type' => Report::TYPE_ACCESSION,
            'status' => Report::STATUS_GENERATED,
            'report_year' => 2025,
            'report_month' => 6,
            'department' => 'HR',
            'is_current_version' => true,
            'generated_by' => $user->id,
        ]);
        
        Report::factory()->create([
            'report_type' => Report::TYPE_SEPARATION,
            'status' => Report::STATUS_SUBMITTED,
            'report_year' => 2024,
            'report_month' => 12,
            'department' => 'Finance',
            'is_current_version' => false,
            'generated_by' => $user->id,
        ]);
        
        // Test scopes
        $this->assertEquals(1, Report::ofType(Report::TYPE_ACCESSION)->count());
        $this->assertEquals(1, Report::withStatus(Report::STATUS_GENERATED)->count());
        $this->assertEquals(1, Report::forPeriod(2025, 6)->count());
        $this->assertEquals(1, Report::forDepartment('HR')->count());
        $this->assertEquals(1, Report::currentVersions()->count());
        $this->assertEquals(1, Report::submitted()->count());
    }

    /** @test */
    public function it_handles_confidential_data_properly()
    {
        $user = User::factory()->create();
        
        $confidentialReport = Report::factory()->confidential()->create([
            'generated_by' => $user->id,
        ]);
        
        $this->assertTrue($confidentialReport->contains_confidential_data);
        $this->assertContains($confidentialReport->confidentiality_level, [
            Report::CONFIDENTIALITY_CONFIDENTIAL,
            Report::CONFIDENTIALITY_RESTRICTED,
        ]);
    }

    /** @test */
    public function it_auto_generates_report_number_on_creation()
    {
        $user = User::factory()->create();
        
        $report = Report::factory()->create([
            'report_type' => Report::TYPE_ACCESSION,
            'report_year' => 2025,
            'generated_by' => $user->id,
            'report_number' => null, // Explicitly set to null to test auto-generation
        ]);
        
        $this->assertNotNull($report->report_number);
        $this->assertStringStartsWith('CSC-ACC-2025-', $report->report_number);
    }
}