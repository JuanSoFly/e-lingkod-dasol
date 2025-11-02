<?php

namespace Database\Seeders;

use App\Models\MajorFinalOutput;
use App\Models\Office;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MajorFinalOutputSeeder extends Seeder
{
    /**
     * Seed the major_final_outputs table with sample data
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        MajorFinalOutput::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get major offices for MFO assignment
        $offices = Office::where('is_active', true)->get();

        // MFO templates for different office types
        $mfoTemplates = [
            'Office of the Municipal Mayor' => [
                ['code' => 'MAYOR-001', 'title' => 'Executive Leadership and Governance', 'description' => 'Provide overall direction and supervision of municipal operations'],
                ['code' => 'MAYOR-002', 'title' => 'Policy Implementation', 'description' => 'Implement approved municipal policies and programs'],
                ['code' => 'MAYOR-003', 'title' => 'Stakeholder Engagement', 'description' => 'Coordinate with various stakeholders for municipal development'],
            ],
            'Office of the Municipal Administrator' => [
                ['code' => 'ADM-001', 'title' => 'Administrative Supervision', 'description' => 'Supervise administrative operations of municipal offices'],
                ['code' => 'ADM-002', 'title' => 'Records Management', 'description' => 'Maintain and secure municipal records and documents'],
                ['code' => 'ADM-003', 'title' => 'Internal Control Systems', 'description' => 'Implement internal control mechanisms for municipal operations'],
            ],
            'Budget and Treasury Office' => [
                ['code' => 'BTO-001', 'title' => 'Budget Preparation and Execution', 'description' => 'Prepare and implement annual municipal budget'],
                ['code' => 'BTO-002', 'title' => 'Financial Management', 'description' => 'Manage municipal funds and financial resources'],
                ['code' => 'BTO-003', 'title' => 'Revenue Collection', 'description' => 'Collect municipal revenues and taxes'],
                ['code' => 'BTO-004', 'title' => 'Financial Reporting', 'description' => 'Prepare financial statements and reports'],
            ],
            'Human Resource Management Office' => [
                ['code' => 'HRMO-001', 'title' => 'Human Resource Planning', 'description' => 'Develop and implement HR plans and programs'],
                ['code' => 'HRMO-002', 'title' => 'Recruitment and Selection', 'description' => 'Manage recruitment and selection processes'],
                ['code' => 'HRMO-003', 'title' => 'Employee Relations', 'description' => 'Maintain harmonious employee-employer relations'],
                ['code' => 'HRMO-004', 'title' => 'Training and Development', 'description' => 'Conduct training programs for employee development'],
            ],
            'Municipal Planning and Development Office' => [
                ['code' => 'MPDO-001', 'title' => 'Comprehensive Land Use Plan', 'description' => 'Implement and monitor comprehensive land use plan'],
                ['code' => 'MPDO-002', 'title' => 'Development Planning', 'description' => 'Prepare municipal development plans'],
                ['code' => 'MPDO-003', 'title' => 'Project Monitoring', 'description' => 'Monitor implementation of development projects'],
            ],
            'General Services Office' => [
                ['code' => 'GSO-001', 'title' => 'Supply Management', 'description' => 'Manage procurement and supplies'],
                ['code' => 'GSO-002', 'title' => 'Property Management', 'description' => 'Maintain municipal properties and assets'],
                ['code' => 'GSO-003', 'title' => 'General Support Services', 'description' => 'Provide general support services to all offices'],
            ],
            'Municipal Engineer\'s Office' => [
                ['code' => 'MOO-001', 'title' => 'Infrastructure Planning', 'description' => 'Plan municipal infrastructure projects'],
                ['code' => 'MOO-002', 'title' => 'Construction Supervision', 'description' => 'Supervise infrastructure construction projects'],
                ['code' => 'MOO-003', 'title' => 'Building Inspection', 'description' => 'Conduct building inspections and code enforcement'],
            ],
            'Business Permit and Licensing Office' => [
                ['code' => 'BPO-001', 'title' => 'Business Permit Processing', 'description' => 'Process and issue business permits'],
                ['code' => 'BPO-002', 'title' => 'Business Regulation', 'description' => 'Regulate business operations in the municipality'],
                ['code' => 'BPO-003', 'title' => 'Revenue Generation', 'description' => 'Generate revenue from business permits and licenses'],
            ],
            'Municipal Health Office' => [
                ['code' => 'MHO-001', 'title' => 'Health Service Delivery', 'description' => 'Provide primary health care services'],
                ['code' => 'MHO-002', 'title' => 'Public Health Programs', 'description' => 'Implement public health programs'],
                ['code' => 'MHO-003', 'title' => 'Health Education', 'description' => 'Conduct health education activities'],
            ],
            'Municipal Assessor\'s Office' => [
                ['code' => 'ASSO-001', 'title' => 'Property Assessment', 'description' => 'Assess real properties for taxation'],
                ['code' => 'ASSO-002', 'title' => 'Tax Mapping', 'description' => 'Maintain tax maps and property records'],
                ['code' => 'ASSO-003', 'title' => 'Assessment Appeals', 'description' => 'Process assessment appeals and complaints'],
            ],
        ];

        $mfoCount = 0;

        foreach ($offices as $office) {
            // Get MFO templates for this office type
            $officeMfos = $this->getMFOsForOffice($office->name, $mfoTemplates);

            // Create MFOs for this office
            foreach ($officeMfos as $mfoData) {
                MajorFinalOutput::create([
                    'code' => $mfoData['code'],
                    'title' => $mfoData['title'],
                    'description' => $mfoData['description'],
                    'office_id' => $office->id,
                    'level' => 1,
                    'is_active' => true,
                ]);
                $mfoCount++;
            }
        }

        $this->command->info("{$mfoCount} Major Final Outputs created successfully.");
    }

    /**
     * Generate unique MFO code
     */
    private function generateUniqueCode(string $prefix): string
    {
        do {
            $code = $prefix . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        } while (MajorFinalOutput::where('code', $code)->exists());

        return $code;
    }

    /**
     * Get MFOs for a specific office type
     */
    private function getMFOsForOffice(string $officeName, array $mfoTemplates): array
    {
        // Direct match first
        if (isset($mfoTemplates[$officeName])) {
            return $mfoTemplates[$officeName];
        }

        // Fuzzy matching for office variations
        $fuzzyMatches = [
            'Office of the Municipal Mayor' => ['Office of the Municipal Mayor'],
            'Office of the Municipal Administrator' => ['Office of the Municipal Administrator'],
            'Budget and Treasury Office' => ['Budget and Treasury Office'],
            'Human Resource Management Office' => ['Human Resource Management Office'],
            'Municipal Planning and Development Office' => ['Municipal Planning and Development Office'],
            'General Services Office' => ['General Services Office'],
            'Municipal Engineer\'s Office' => ['Municipal Engineer\'s Office'],
            'Business Permit and Licensing Office' => ['Business Permit and Licensing Office'],
            'Municipal Health Office' => ['Municipal Health Office'],
            'Municipal Assessor\'s Office' => ['Municipal Assessor\'s Office'],
        ];

        foreach ($fuzzyMatches as $templateName => $variations) {
            if (in_array($officeName, $variations)) {
                return $mfoTemplates[$templateName] ?? [];
            }
        }

        // Default generic MFOs for other offices
        return [
            [
                'code' => $this->generateUniqueCode('GEN'),
                'title' => 'Office Operations Management',
                'description' => 'Manage daily office operations and administrative functions'
            ],
            [
                'code' => $this->generateUniqueCode('GEN'),
                'title' => 'Service Delivery',
                'description' => 'Provide core services to the public'
            ]
        ];
    }
}