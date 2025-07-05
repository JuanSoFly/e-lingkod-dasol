<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Employee;
use App\Models\CivilServiceEligibility;
use App\Models\CareerProgression;
use App\Models\SalaryGrade;
use Carbon\Carbon;

class CivilServiceCareerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create sample salary grades for testing
        SalaryGrade::create([
            'grade_level' => 15,
            'step_increment' => 1,
            'monthly_salary' => 37333.00,
            'daily_rate' => 1697.00,
            'hourly_rate' => 212.13,
            'pera_allowance' => 2000.00,
            'position_level' => 'Second Level',
            'ssl_tranche' => 'Tranche 4',
            'effective_date' => Carbon::parse('2024-01-01'),
            'status' => 'Active'
        ]);

        SalaryGrade::create([
            'grade_level' => 16,
            'step_increment' => 1,
            'monthly_salary' => 39458.00,
            'daily_rate' => 1793.55,
            'hourly_rate' => 224.19,
            'pera_allowance' => 2000.00,
            'position_level' => 'Second Level',
            'ssl_tranche' => 'Tranche 4',
            'effective_date' => Carbon::parse('2024-01-01'),
            'status' => 'Active'
        ]);
    }

    /** @test */
    public function it_can_create_civil_service_eligibility()
    {
        $employee = Employee::factory()->create();

        $eligibility = CivilServiceEligibility::create([
            'employee_id' => $employee->id,
            'eligibility_type' => 'Professional',
            'examination_name' => 'Career Service Professional Examination',
            'date_taken' => Carbon::parse('2023-05-15'),
            'rating' => 85.25,
            'certificate_number' => 'CSC-2023-PRO-12345',
            'valid_until' => Carbon::parse('2030-12-31'),
            'status' => 'Active',
            'is_lifetime_valid' => false,
            'is_verified' => true
        ]);

        $this->assertDatabaseHas('civil_service_eligibilities', [
            'employee_id' => $employee->id,
            'eligibility_type' => 'Professional',
            'certificate_number' => 'CSC-2023-PRO-12345'
        ]);

        $this->assertTrue($eligibility->isValid());
        $this->assertFalse($eligibility->isExpired());
        $this->assertEquals('85.25%', $eligibility->formatted_rating);
    }

    /** @test */
    public function it_can_track_career_progression()
    {
        $employee = Employee::factory()->create([
            'position' => 'Administrative Assistant I',
            'department' => 'General Services',
            'salary_grade' => 15,
            'step_increment' => 1
        ]);

        $progression = CareerProgression::create([
            'employee_id' => $employee->id,
            'from_position' => 'Administrative Assistant I',
            'to_position' => 'Administrative Assistant II',
            'from_department' => 'General Services',
            'to_department' => 'General Services',
            'promotion_date' => Carbon::parse('2024-01-15'),
            'promotion_type' => 'Regular Promotion',
            'salary_grade_from' => 15,
            'salary_grade_to' => 16,
            'step_increment_from' => 1,
            'step_increment_to' => 1,
            'monthly_salary_from' => 37333.00,
            'monthly_salary_to' => 39458.00,
            'appointing_authority' => 'Mayor John Doe',
            'effective_date' => Carbon::parse('2024-02-01'),
            'status' => 'Active',
            'to_employment_status' => 'Regular'
        ]);

        $this->assertDatabaseHas('career_progression', [
            'employee_id' => $employee->id,
            'from_position' => 'Administrative Assistant I',
            'to_position' => 'Administrative Assistant II'
        ]);

        $this->assertTrue($progression->isPromotion());
        $this->assertFalse($progression->isLateralTransfer());
        $this->assertEquals(2125.00, $progression->getSalaryIncrease());
        $this->assertStringContains('₱2,125.00', $progression->formatted_salary_increase);
    }

    /** @test */
    public function it_can_manage_salary_grades()
    {
        $salaryGrade = SalaryGrade::findByGradeAndStep(15, 1);

        $this->assertNotNull($salaryGrade);
        $this->assertEquals(37333.00, $salaryGrade->monthly_salary);
        $this->assertTrue($salaryGrade->isEffective());
        $this->assertEquals('SG15-1', $salaryGrade->identifier);

        $totalCompensation = $salaryGrade->getTotalMonthlyCompensation();
        $this->assertEquals(39333.00, $totalCompensation); // 37333 + 2000 PERA

        $overtimePay = $salaryGrade->calculateOvertimePay(8, 'regular');
        $this->assertGreaterThan(0, $overtimePay);

        $nextStep = $salaryGrade->getNextStep();
        $this->assertNull($nextStep); // We only created step 1 in test data

        $nextGrade = $salaryGrade->getNextGrade();
        $this->assertNotNull($nextGrade);
        $this->assertEquals(16, $nextGrade->grade_level);
    }

    /** @test */
    public function employee_can_have_multiple_eligibilities()
    {
        $employee = Employee::factory()->create();

        CivilServiceEligibility::create([
            'employee_id' => $employee->id,
            'eligibility_type' => 'Professional',
            'examination_name' => 'Career Service Professional Examination',
            'date_taken' => Carbon::parse('2023-05-15'),
            'rating' => 85.25,
            'status' => 'Active'
        ]);

        CivilServiceEligibility::create([
            'employee_id' => $employee->id,
            'eligibility_type' => 'Bar/Board',
            'examination_name' => 'Licensure Examination for Teachers',
            'date_taken' => Carbon::parse('2022-08-20'),
            'rating' => 78.50,
            'status' => 'Active'
        ]);

        $eligibilities = $employee->civilServiceEligibilities;
        $this->assertCount(2, $eligibilities);

        $activeEligibilities = $employee->civilServiceEligibilities()->active()->get();
        $this->assertCount(2, $activeEligibilities);
    }

    /** @test */
    public function employee_can_have_career_progression_history()
    {
        $employee = Employee::factory()->create();

        // First promotion
        CareerProgression::create([
            'employee_id' => $employee->id,
            'from_position' => 'Clerk I',
            'to_position' => 'Clerk II',
            'promotion_date' => Carbon::parse('2023-01-15'),
            'promotion_type' => 'Regular Promotion',
            'salary_grade_from' => 9,
            'salary_grade_to' => 10,
            'effective_date' => Carbon::parse('2023-02-01'),
            'status' => 'Completed'
        ]);

        // Second promotion
        CareerProgression::create([
            'employee_id' => $employee->id,
            'from_position' => 'Clerk II',
            'to_position' => 'Administrative Assistant I',
            'promotion_date' => Carbon::parse('2024-01-15'),
            'promotion_type' => 'Merit Promotion',
            'salary_grade_from' => 10,
            'salary_grade_to' => 15,
            'effective_date' => Carbon::parse('2024-02-01'),
            'status' => 'Active'
        ]);

        $progressions = $employee->careerProgressions;
        $this->assertCount(2, $progressions);

        $promotions = $employee->careerProgressions()->promotions()->get();
        $this->assertCount(2, $promotions);

        $activeProgression = $employee->careerProgressions()->active()->first();
        $this->assertEquals('Administrative Assistant I', $activeProgression->to_position);
    }

    /** @test */
    public function eligibility_expiration_tracking_works()
    {
        $employee = Employee::factory()->create();

        // Create expiring eligibility
        $expiringEligibility = CivilServiceEligibility::create([
            'employee_id' => $employee->id,
            'eligibility_type' => 'Professional',
            'examination_name' => 'Career Service Professional Examination',
            'date_taken' => Carbon::parse('2020-05-15'),
            'rating' => 85.25,
            'valid_until' => now()->addDays(15), // Expires in 15 days
            'status' => 'Active',
            'is_lifetime_valid' => false
        ]);

        // Create lifetime eligibility
        $lifetimeEligibility = CivilServiceEligibility::create([
            'employee_id' => $employee->id,
            'eligibility_type' => 'Bar/Board',
            'examination_name' => 'Bar Examination',
            'date_taken' => Carbon::parse('2019-11-20'),
            'rating' => 75.00,
            'status' => 'Active',
            'is_lifetime_valid' => true
        ]);

        $this->assertTrue($expiringEligibility->isExpiringSoon());
        $this->assertFalse($lifetimeEligibility->isExpiringSoon());
        $this->assertTrue($lifetimeEligibility->isValid());

        $expiring = $employee->civilServiceEligibilities()
            ->where('valid_until', '<=', now()->addDays(30))
            ->where('is_lifetime_valid', false)
            ->get();

        $this->assertCount(1, $expiring);
    }

    /** @test */
    public function salary_grade_calculations_are_accurate()
    {
        $salaryGrade = SalaryGrade::where('grade_level', 15)->where('step_increment', 1)->first();

        // Test automatic calculations
        $expectedDailyRate = round(37333.00 / 22, 2);
        $expectedHourlyRate = round($expectedDailyRate / 8, 2);

        $this->assertEquals($expectedDailyRate, $salaryGrade->daily_rate);
        $this->assertEquals($expectedHourlyRate, $salaryGrade->hourly_rate);

        // Test overtime calculations
        $regularOvertimeRate = round($expectedHourlyRate * 1.25, 2);
        $this->assertEquals($regularOvertimeRate, $salaryGrade->overtime_rate_regular);

        // Test night differential
        $nightDifferentialRate = round($expectedHourlyRate * 0.10, 2);
        $this->assertEquals($nightDifferentialRate, $salaryGrade->night_differential_rate);

        // Test overtime pay calculation
        $overtimePay = $salaryGrade->calculateOvertimePay(4, 'regular');
        $expectedOvertimePay = 4 * $regularOvertimeRate;
        $this->assertEquals($expectedOvertimePay, $overtimePay);
    }
}