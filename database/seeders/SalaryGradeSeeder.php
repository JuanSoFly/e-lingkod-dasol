<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SalaryGrade;
use Carbon\Carbon;

class SalaryGradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if salary grades already exist
        if (SalaryGrade::count() > 0) {
            echo "Salary grades already exist. Skipping seeding.\n";
            return;
        }

        // 2024 Philippine Government Salary Standardization (Latest SSL Tranche 4)
        $salaryData = $this->get2024SalaryData();
        
        foreach ($salaryData as $gradeLevel => $steps) {
            foreach ($steps as $stepIncrement => $monthlyAmount) {
                SalaryGrade::create([
                    'grade_level' => $gradeLevel,
                    'step_increment' => $stepIncrement,
                    'monthly_salary' => $monthlyAmount,
                    'daily_rate' => round($monthlyAmount / 22, 2),
                    'hourly_rate' => round($monthlyAmount / 22 / 8, 2),
                    'pera_allowance' => 2000.00,
                    'productivity_allowance' => 0.00,
                    'hazard_allowance' => 0.00,
                    'subsistence_allowance' => 0.00,
                    'laundry_allowance' => 0.00,
                    'overtime_rate_regular' => round(($monthlyAmount / 22 / 8) * 1.25, 2),
                    'overtime_rate_special' => round(($monthlyAmount / 22 / 8) * 1.30, 2),
                    'overtime_rate_legal' => round(($monthlyAmount / 22 / 8) * 2.00, 2),
                    'night_differential_rate' => round(($monthlyAmount / 22 / 8) * 0.10, 2),
                    'position_level' => $this->getPositionLevel($gradeLevel),
                    'ssl_tranche' => 'Tranche 4',
                    'effective_date' => Carbon::parse('2024-01-01'),
                    'end_date' => null,
                    'status' => 'Active',
                    'dbu_number' => 'DBM-SSL-T4-2024',
                    'legal_basis' => 'RA 11466 - Salary Standardization Law (SSL) Tranche 4',
                    'remarks' => '2024 Philippine Government Salary Standardization',
                    'annual_adjustment_percentage' => 5.00,
                    'adjustment_year' => 2024,
                ]);
            }
        }

        // Create sample future salary adjustments
        $this->createFutureSalaryAdjustments();
        
        // Create historical salary data (for reference)
        $this->createHistoricalSalaryData();
    }

    /**
     * Get 2024 Philippine Government Salary Data (SSL Tranche 4)
     * Based on actual government salary schedule
     */
    private function get2024SalaryData(): array
    {
        return [
            // First Level Positions (SG 1-8)
            1 => [
                1 => 16206.00, 2 => 16625.00, 3 => 17044.00, 4 => 17463.00,
                5 => 17882.00, 6 => 18301.00, 7 => 18720.00, 8 => 19139.00
            ],
            2 => [
                1 => 17305.00, 2 => 17752.00, 3 => 18199.00, 4 => 18646.00,
                5 => 19093.00, 6 => 19540.00, 7 => 19987.00, 8 => 20434.00
            ],
            3 => [
                1 => 18451.00, 2 => 18933.00, 3 => 19415.00, 4 => 19897.00,
                5 => 20379.00, 6 => 20861.00, 7 => 21343.00, 8 => 21825.00
            ],
            4 => [
                1 => 19644.00, 2 => 20165.00, 3 => 20686.00, 4 => 21207.00,
                5 => 21728.00, 6 => 22249.00, 7 => 22770.00, 8 => 23291.00
            ],
            5 => [
                1 => 20897.00, 2 => 21462.00, 3 => 22027.00, 4 => 22592.00,
                5 => 23157.00, 6 => 23722.00, 7 => 24287.00, 8 => 24852.00
            ],
            6 => [
                1 => 22210.00, 2 => 22821.00, 3 => 23432.00, 4 => 24043.00,
                5 => 24654.00, 6 => 25265.00, 7 => 25876.00, 8 => 26487.00
            ],
            7 => [
                1 => 23587.00, 2 => 24248.00, 3 => 24909.00, 4 => 25570.00,
                5 => 26231.00, 6 => 26892.00, 7 => 27553.00, 8 => 28214.00
            ],
            8 => [
                1 => 25032.00, 2 => 25747.00, 3 => 26462.00, 4 => 27177.00,
                5 => 27892.00, 6 => 28607.00, 7 => 29322.00, 8 => 30037.00
            ],

            // Second Level Positions (SG 9-18)
            9 => [
                1 => 26548.00, 2 => 27321.00, 3 => 28094.00, 4 => 28867.00,
                5 => 29640.00, 6 => 30413.00, 7 => 31186.00, 8 => 31959.00
            ],
            10 => [
                1 => 28137.00, 2 => 28973.00, 3 => 29809.00, 4 => 30645.00,
                5 => 31481.00, 6 => 32317.00, 7 => 33153.00, 8 => 33989.00
            ],
            11 => [
                1 => 29803.00, 2 => 30708.00, 3 => 31613.00, 4 => 32518.00,
                5 => 33423.00, 6 => 34328.00, 7 => 35233.00, 8 => 36138.00
            ],
            12 => [
                1 => 31551.00, 2 => 32529.00, 3 => 33507.00, 4 => 34485.00,
                5 => 35463.00, 6 => 36441.00, 7 => 37419.00, 8 => 38397.00
            ],
            13 => [
                1 => 33385.00, 2 => 34441.00, 3 => 35497.00, 4 => 36553.00,
                5 => 37609.00, 6 => 38665.00, 7 => 39721.00, 8 => 40777.00
            ],
            14 => [
                1 => 35311.00, 2 => 36449.00, 3 => 37587.00, 4 => 38725.00,
                5 => 39863.00, 6 => 41001.00, 7 => 42139.00, 8 => 43277.00
            ],
            15 => [
                1 => 37333.00, 2 => 38558.00, 3 => 39783.00, 4 => 41008.00,
                5 => 42233.00, 6 => 43458.00, 7 => 44683.00, 8 => 45908.00
            ],
            16 => [
                1 => 39458.00, 2 => 40776.00, 3 => 42094.00, 4 => 43412.00,
                5 => 44730.00, 6 => 46048.00, 7 => 47366.00, 8 => 48684.00
            ],
            17 => [
                1 => 41691.00, 2 => 43108.00, 3 => 44525.00, 4 => 45942.00,
                5 => 47359.00, 6 => 48776.00, 7 => 50193.00, 8 => 51610.00
            ],
            18 => [
                1 => 44040.00, 2 => 45564.00, 3 => 47088.00, 4 => 48612.00,
                5 => 50136.00, 6 => 51660.00, 7 => 53184.00, 8 => 54708.00
            ],

            // Senior Positions (SG 19-24)
            19 => [
                1 => 46509.00, 2 => 48154.00, 3 => 49799.00, 4 => 51444.00,
                5 => 53089.00, 6 => 54734.00, 7 => 56379.00, 8 => 58024.00
            ],
            20 => [
                1 => 49106.00, 2 => 50880.00, 3 => 52654.00, 4 => 54428.00,
                5 => 56202.00, 6 => 57976.00, 7 => 59750.00, 8 => 61524.00
            ],
            21 => [
                1 => 51839.00, 2 => 53757.00, 3 => 55675.00, 4 => 57593.00,
                5 => 59511.00, 6 => 61429.00, 7 => 63347.00, 8 => 65265.00
            ],
            22 => [
                1 => 54717.00, 2 => 56794.00, 3 => 58871.00, 4 => 60948.00,
                5 => 63025.00, 6 => 65102.00, 7 => 67179.00, 8 => 69256.00
            ],
            23 => [
                1 => 57747.00, 2 => 60000.00, 3 => 62253.00, 4 => 64506.00,
                5 => 66759.00, 6 => 69012.00, 7 => 71265.00, 8 => 73518.00
            ],
            24 => [
                1 => 60939.00, 2 => 63384.00, 3 => 65829.00, 4 => 68274.00,
                5 => 70719.00, 6 => 73164.00, 7 => 75609.00, 8 => 78054.00
            ],

            // Executive Positions (SG 25-33)
            25 => [
                1 => 64304.00, 2 => 66957.00, 3 => 69610.00, 4 => 72263.00,
                5 => 74916.00, 6 => 77569.00, 7 => 80222.00, 8 => 82875.00
            ],
            26 => [
                1 => 67851.00, 2 => 70727.00, 3 => 73603.00, 4 => 76479.00,
                5 => 79355.00, 6 => 82231.00, 7 => 85107.00, 8 => 87983.00
            ],
            27 => [
                1 => 71591.00, 2 => 74709.00, 3 => 77827.00, 4 => 80945.00,
                5 => 84063.00, 6 => 87181.00, 7 => 90299.00, 8 => 93417.00
            ],
            28 => [
                1 => 75539.00, 2 => 78923.00, 3 => 82307.00, 4 => 85691.00,
                5 => 89075.00, 6 => 92459.00, 7 => 95843.00, 8 => 99227.00
            ],
            29 => [
                1 => 79707.00, 2 => 83388.00, 3 => 87069.00, 4 => 90750.00,
                5 => 94431.00, 6 => 98112.00, 7 => 101793.00, 8 => 105474.00
            ],
            30 => [
                1 => 84110.00, 2 => 88124.00, 3 => 92138.00, 4 => 96152.00,
                5 => 100166.00, 6 => 104180.00, 7 => 108194.00, 8 => 112208.00
            ],
            31 => [
                1 => 88765.00, 2 => 93142.00, 3 => 97519.00, 4 => 101896.00,
                5 => 106273.00, 6 => 110650.00, 7 => 115027.00, 8 => 119404.00
            ],
            32 => [
                1 => 93687.00, 2 => 98454.00, 3 => 103221.00, 4 => 107988.00,
                5 => 112755.00, 6 => 117522.00, 7 => 122289.00, 8 => 127056.00
            ],
            33 => [
                1 => 98894.00, 2 => 104080.00, 3 => 109266.00, 4 => 114452.00,
                5 => 119638.00, 6 => 124824.00, 7 => 130010.00, 8 => 135196.00
            ],
        ];
    }

    /**
     * Get position level based on salary grade
     */
    private function getPositionLevel(int $gradeLevel): string
    {
        return match (true) {
            $gradeLevel >= 1 && $gradeLevel <= 8 => 'First Level',
            $gradeLevel >= 9 && $gradeLevel <= 18 => 'Second Level',
            $gradeLevel >= 19 && $gradeLevel <= 24 => 'Second Level',
            $gradeLevel >= 25 && $gradeLevel <= 33 => 'Career Executive Service',
            default => 'Special Position'
        };
    }

    /**
     * Create future salary adjustments for 2025
     */
    private function createFutureSalaryAdjustments(): void
    {
        $currentSalaryGrades = SalaryGrade::where('status', 'Active')->get();
        
        foreach ($currentSalaryGrades as $currentGrade) {
            // Create 2025 salary adjustment (projected 4% increase)
            $futureGrade = $currentGrade->replicate();
            $futureGrade->monthly_salary = round($currentGrade->monthly_salary * 1.04, 2);
            $futureGrade->effective_date = Carbon::parse('2025-01-01');
            $futureGrade->status = 'Future';
            $futureGrade->ssl_tranche = 'Tranche 5';
            $futureGrade->dbu_number = 'DBM-SSL-T5-2025';
            $futureGrade->legal_basis = 'Projected SSL Tranche 5 Implementation';
            $futureGrade->remarks = '2025 Projected Salary Adjustment (4% increase)';
            $futureGrade->annual_adjustment_percentage = 4.00;
            $futureGrade->adjustment_year = 2025;
            $futureGrade->save();
        }
    }

    /**
     * Create historical salary data for reference (2023 SSL Tranche 3)
     */
    private function createHistoricalSalaryData(): void
    {
        // Sample historical data for SG 1-5 (2023)
        $historicalData = [
            1 => [
                1 => 15444.00, 2 => 15833.00, 3 => 16222.00, 4 => 16611.00,
                5 => 17000.00, 6 => 17389.00, 7 => 17778.00, 8 => 18167.00
            ],
            2 => [
                1 => 16481.00, 2 => 16906.00, 3 => 17331.00, 4 => 17756.00,
                5 => 18181.00, 6 => 18606.00, 7 => 19031.00, 8 => 19456.00
            ],
            3 => [
                1 => 17572.00, 2 => 18031.00, 3 => 18490.00, 4 => 18949.00,
                5 => 19408.00, 6 => 19867.00, 7 => 20326.00, 8 => 20785.00
            ],
            4 => [
                1 => 18718.00, 2 => 19214.00, 3 => 19710.00, 4 => 20206.00,
                5 => 20702.00, 6 => 21198.00, 7 => 21694.00, 8 => 22190.00
            ],
            5 => [
                1 => 19926.00, 2 => 20464.00, 3 => 21002.00, 4 => 21540.00,
                5 => 22078.00, 6 => 22616.00, 7 => 23154.00, 8 => 23692.00
            ],
        ];

        foreach ($historicalData as $gradeLevel => $steps) {
            foreach ($steps as $stepIncrement => $monthlyAmount) {
                SalaryGrade::create([
                    'grade_level' => $gradeLevel,
                    'step_increment' => $stepIncrement,
                    'monthly_salary' => $monthlyAmount,
                    'daily_rate' => round($monthlyAmount / 22, 2),
                    'hourly_rate' => round($monthlyAmount / 22 / 8, 2),
                    'pera_allowance' => 2000.00,
                    'overtime_rate_regular' => round(($monthlyAmount / 22 / 8) * 1.25, 2),
                    'overtime_rate_special' => round(($monthlyAmount / 22 / 8) * 1.30, 2),
                    'overtime_rate_legal' => round(($monthlyAmount / 22 / 8) * 2.00, 2),
                    'night_differential_rate' => round(($monthlyAmount / 22 / 8) * 0.10, 2),
                    'position_level' => $this->getPositionLevel($gradeLevel),
                    'ssl_tranche' => 'Tranche 3',
                    'effective_date' => Carbon::parse('2023-01-01'),
                    'end_date' => Carbon::parse('2023-12-31'),
                    'status' => 'Superseded',
                    'dbu_number' => 'DBM-SSL-T3-2023',
                    'legal_basis' => 'RA 11466 - Salary Standardization Law (SSL) Tranche 3',
                    'remarks' => '2023 Historical Salary Data',
                    'annual_adjustment_percentage' => 4.50,
                    'adjustment_year' => 2023,
                ]);
            }
        }
    }
}