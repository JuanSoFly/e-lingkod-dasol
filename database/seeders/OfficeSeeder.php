<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OfficeSeeder extends Seeder
{
    /**
     * Seed the offices table with municipal government structure
     */
    public function run(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('TRUNCATE TABLE offices RESTART IDENTITY CASCADE');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Office::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // Create municipal office structure for Dasol, Pangasinan
        $offices = [
            // Level 1 - Municipal Executive Offices
            [
                'code' => 'MAYOR',
                'name' => "Office of the Municipal Mayor",
                'description' => 'Chief Executive Office of the Municipality',
                'level' => 1,
                'head_title' => 'Municipal Mayor',
                'parent_id' => null,
                'is_active' => true,
            ],
            [
                'code' => 'VM',
                'name' => "Office of the Vice Mayor",
                'description' => 'Office of the Municipal Vice Mayor and Legislative Council',
                'level' => 1,
                'head_title' => 'Municipal Vice Mayor',
                'parent_id' => null,
                'is_active' => true,
            ],
            [
                'code' => 'SB',
                'name' => "Sangguniang Bayan",
                'description' => 'Municipal Legislative Council',
                'level' => 1,
                'head_title' => 'Sangguniang Bayan Secretary',
                'parent_id' => null,
                'is_active' => true,
            ],

            // Level 2 - Main Departments
            [
                'code' => 'ADM',
                'name' => "Office of the Municipal Administrator",
                'description' => 'Central Administrative Office',
                'level' => 2,
                'head_title' => 'Municipal Administrator',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'HRMO',
                'name' => "Human Resource Management Office",
                'description' => 'Human Resource Management and Development',
                'level' => 2,
                'head_title' => 'Municipal Human Resource Officer',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'BTO',
                'name' => "Budget and Treasury Office",
                'description' => 'Municipal Budget and Financial Management',
                'level' => 2,
                'head_title' => 'Municipal Treasurer',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'ACCTO',
                'name' => "Accounting Office",
                'description' => 'Municipal Accounting Services',
                'level' => 2,
                'head_title' => 'Municipal Accountant',
                'parent_office_code' => 'BTO',
                'is_active' => true,
            ],
            [
                'code' => 'ASSO',
                'name' => "Assessor's Office",
                'description' => 'Property Assessment and Tax Mapping',
                'level' => 2,
                'head_title' => 'Municipal Assessor',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'TREO',
                'name' => "Treasurer's Office",
                'description' => 'Treasury and Cash Management',
                'level' => 2,
                'head_title' => 'Municipal Treasurer',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'RMO',
                'name' => "Registry of Deeds Office",
                'description' => 'Document Registration and Certification',
                'level' => 2,
                'head_title' => 'Municipal Registrar of Deeds',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'BPO',
                'name' => "Business Permit and Licensing Office",
                'description' => 'Business Permits and Licenses',
                'level' => 2,
                'head_title' => 'Business Permit Officer',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'MENRO',
                'name' => "Municipal Environment and Natural Resources Office",
                'description' => 'Environmental Management and Protection',
                'level' => 2,
                'head_title' => 'MENRO Officer',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'MAO',
                'name' => "Municipal Agriculture Office",
                'description' => 'Agricultural Services and Development',
                'level' => 2,
                'head_title' => 'Municipal Agriculturist',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'MHO',
                'name' => "Municipal Health Office",
                'description' => 'Health Services and Programs',
                'level' => 2,
                'head_title' => 'Municipal Health Officer',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'MSWDO',
                'name' => "Municipal Social Welfare and Development Office",
                'description' => 'Social Welfare Services and Programs',
                'level' => 2,
                'head_title' => 'Municipal Social Welfare Officer',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'MTO',
                'name' => "Municipal Engineering Office",
                'description' => 'Infrastructure and Engineering Services',
                'level' => 2,
                'head_title' => 'Municipal Engineer',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'MOO',
                'name' => "Municipal Engineer's Office",
                'description' => 'Engineering and Public Works',
                'level' => 2,
                'head_title' => 'Municipal Engineer',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'MCHO',
                'name' => "Municipal Civil Registrar's Office",
                'description' => 'Civil Registration Services',
                'level' => 2,
                'head_title' => 'Municipal Civil Registrar',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'MPDO',
                'name' => "Municipal Planning and Development Office",
                'description' => 'Planning and Development Coordination',
                'level' => 2,
                'head_title' => 'Municipal Planning and Development Coordinator',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'GSO',
                'name' => "General Services Office",
                'description' => 'General Services and Property Management',
                'level' => 2,
                'head_title' => 'General Services Officer',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'ITO',
                'name' => "Information Technology Office",
                'description' => 'ICT Services and Management',
                'level' => 2,
                'head_title' => 'Municipal IT Officer',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'PESO',
                'name' => "Public Employment Service Office",
                'description' => 'Employment Services and Local Registry',
                'level' => 2,
                'head_title' => 'PESO Manager',
                'parent_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'CTO',
                'name' => "Cooperatives and Tourist Office",
                'description' => 'Cooperative Development and Tourism Promotion',
                'level' => 2,
                'head_title' => 'Cooperative and Tourism Officer',
                'parent_id' => 1,
                'is_active' => true,
            ],

            // Level 3 - Specialized Units and Divisions
            [
                'code' => 'HRMO-REC',
                'name' => "HRMO - Records Division",
                'description' => 'HR Records and 201 Files Management',
                'level' => 3,
                'head_title' => 'Records Officer',
                'parent_office_code' => 'HRMO',
                'is_active' => true,
            ],
            [
                'code' => 'HRMO-TRG',
                'name' => "HRMO - Training Division",
                'description' => 'Employee Training and Development',
                'level' => 3,
                'head_title' => 'Training Officer',
                'parent_office_code' => 'HRMO',
                'is_active' => true,
            ],
            [
                'code' => 'BTO-COL',
                'name' => "Budget Office - Collection Division",
                'description' => 'Revenue Collection Management',
                'level' => 3,
                'head_title' => 'Collection Officer',
                'parent_office_code' => 'BTO',
                'is_active' => true,
            ],
            [
                'code' => 'BTO-DIS',
                'name' => "Budget Office - Disbursement Division",
                'description' => 'Budget Disbursement and Control',
                'level' => 3,
                'head_title' => 'Disbursement Officer',
                'parent_office_code' => 'BTO',
                'is_active' => true,
            ],
            [
                'code' => 'MTO-MAN',
                'name' => "Engineering Office - Maintenance Division",
                'description' => 'Infrastructure Maintenance',
                'level' => 3,
                'head_title' => 'Maintenance Engineer',
                'parent_office_code' => 'MTO',
                'is_active' => true,
            ],
            [
                'code' => 'MTO-CON',
                'name' => "Engineering Office - Construction Division",
                'description' => 'Infrastructure Construction and Development',
                'level' => 3,
                'head_title' => 'Construction Engineer',
                'parent_office_code' => 'MTO',
                'is_active' => true,
            ],
        ];

        // Create office code to ID mapping
        $officeCodeMap = [];
        $tempOffices = [];

        // First pass: create offices with parent_office_code as temp data
        foreach ($offices as $officeData) {
            $tempOffices[] = $officeData;
        }

        // Second pass: map parent_office_code to parent_id
        $processedOffices = [];
        foreach ($tempOffices as $officeData) {
            if (isset($officeData['parent_office_code'])) {
                $parentCode = $officeData['parent_office_code'];
                $parentId = null;

                // Find parent office by code
                foreach ($tempOffices as $parentOffice) {
                    if ($parentOffice['code'] === $parentCode) {
                        // Get the index + 1 as the ID (since they're inserted in order)
                        $parentId = array_search($parentOffice, $tempOffices) + 1;
                        break;
                    }
                }

                $officeData['parent_id'] = $parentId;
                unset($officeData['parent_office_code']);
            }
            $processedOffices[] = $officeData;
        }

        // Insert offices in batches
        foreach ($processedOffices as $officeData) {
            Office::create($officeData);
        }

        $this->command->info('Municipal offices structure created successfully.');
    }
}
