<?php

namespace Database\Seeders;

use App\Models\MajorFinalOutput;
use App\Models\SuccessIndicator;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuccessIndicatorsSeeder extends Seeder
{
    /**
     * Seed the success_indicators table with sample data
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        SuccessIndicator::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get first admin user for created_by field
        $adminUser = User::whereHas('roles', function($query) {
            $query->where('name', 'Super Admin');
        })->first();

        // Get all MFOs to create success indicators for
        $mfos = MajorFinalOutput::where('is_active', true)->get();

        // Success indicator templates based on MFO types
        $siTemplates = [
            'Executive Leadership and Governance' => [
                ['code' => 'ELG-001', 'title' => 'Leadership Efficiency', 'description' => 'Percentage of executive directives implemented within timeframe'],
                ['code' => 'ELG-002', 'title' => 'Governance Compliance', 'description' => 'Compliance rate with governance standards and regulations'],
            ],
            'Policy Implementation' => [
                ['code' => 'PI-001', 'title' => 'Policy Implementation Rate', 'description' => 'Percentage of approved policies implemented'],
                ['code' => 'PI-002', 'title' => 'Stakeholder Adoption', 'description' => 'Rate of stakeholder compliance with new policies'],
            ],
            'Stakeholder Engagement' => [
                ['code' => 'SE-001', 'title' => 'Engagement Meetings', 'description' => 'Number of stakeholder meetings conducted'],
                ['code' => 'SE-002', 'title' => 'Satisfaction Rating', 'description' => 'Stakeholder satisfaction rating on engagement activities'],
            ],
            'Administrative Supervision' => [
                ['code' => 'AS-001', 'title' => 'Supervision Coverage', 'description' => 'Percentage of offices supervised quarterly'],
                ['code' => 'AS-002', 'title' => 'Compliance Rate', 'description' => 'Administrative compliance rate across supervised offices'],
            ],
            'Records Management' => [
                ['code' => 'RM-001', 'title' => 'Record Organization', 'description' => 'Percentage of records properly organized and accessible'],
                ['code' => 'RM-002', 'title' => 'Digitalization Rate', 'description' => 'Percentage of records digitized and stored electronically'],
            ],
            'Internal Control Systems' => [
                ['code' => 'ICS-001', 'title' => 'Control Implementation', 'description' => 'Percentage of internal controls implemented effectively'],
                ['code' => 'ICS-002', 'title' => 'Audit Compliance', 'description' => 'Rate of compliance with internal audit recommendations'],
            ],
            'Budget Preparation and Execution' => [
                ['code' => 'BPE-001', 'title' => 'Budget Timeliness', 'description' => 'Percentage of budget prepared and submitted on time'],
                ['code' => 'BPE-002', 'title' => 'Budget Execution Rate', 'description' => 'Percentage of budget utilized according to plan'],
            ],
            'Financial Management' => [
                ['code' => 'FM-001', 'title' => 'Financial Accuracy', 'description' => 'Accuracy rate of financial reports and records'],
                ['code' => 'FM-002', 'title' => 'Cost Efficiency', 'description' => 'Percentage reduction in unnecessary expenditures'],
            ],
            'Revenue Collection' => [
                ['code' => 'RC-001', 'title' => 'Collection Efficiency', 'description' => 'Percentage of target revenues collected'],
                ['code' => 'RC-002', 'title' => 'Collection Timeliness', 'description' => 'Timeliness of revenue collection compared to targets'],
            ],
            'Financial Reporting' => [
                ['code' => 'FR-001', 'title' => 'Report Accuracy', 'description' => 'Accuracy rate of financial reports'],
                ['code' => 'FR-002', 'title' => 'Report Timeliness', 'description' => 'Percentage of financial reports submitted on time'],
            ],
            'Human Resource Management' => [
                ['code' => 'HRM-001', 'title' => 'Recruitment Efficiency', 'description' => 'Time to fill vacant positions'],
                ['code' => 'HRM-002', 'title' => 'Training Coverage', 'description' => 'Percentage of staff receiving training annually'],
            ],
            'Employee Relations' => [
                ['code' => 'ER-001', 'title' => 'Grievance Resolution', 'description' => 'Average time to resolve employee grievances'],
                ['code' => 'ER-002', 'title' => 'Satisfaction Rate', 'description' => 'Employee satisfaction rate with HR services'],
            ],
            'Payroll Management' => [
                ['code' => 'PM-001', 'title' => 'Payroll Accuracy', 'description' => 'Accuracy rate of payroll processing'],
                ['code' => 'PM-002', 'title' => 'Payroll Timeliness', 'description' => 'Percentage of payrolls processed on time'],
            ],
            'Service Delivery' => [
                ['code' => 'SD-001', 'title' => 'Service Completion Rate', 'description' => 'Percentage of services completed within target timeframe'],
                ['code' => 'SD-002', 'title' => 'Service Quality', 'description' => 'Customer satisfaction rating on service quality'],
            ],
            'Office Operations Management' => [
                ['code' => 'OOM-001', 'title' => 'Operational Efficiency', 'description' => 'Percentage of processes optimized for efficiency'],
                ['code' => 'OOM-002', 'title' => 'Resource Utilization', 'description' => 'Efficiency rate of resource utilization'],
            ],
            ' Legislative Services' => [
                ['code' => 'LS-001', 'title' => 'Legislation Quality', 'description' => 'Quality rating of legislative instruments produced'],
                ['code' => 'LS-002', 'title' => 'Session Attendance', 'description' => 'Attendance rate in legislative sessions'],
            ],
            'Planning and Development' => [
                ['code' => 'PD-001', 'title' => 'Plan Completion', 'description' => 'Percentage of development plans completed on time'],
                ['code' => 'PD-002', 'title' => 'Project Implementation', 'description' => 'Percentage of planned projects implemented'],
            ],
            'Engineering Services' => [
                ['code' => 'ES-001', 'title' => 'Project Completion', 'description' => 'Percentage of infrastructure projects completed'],
                ['code' => 'ES-002', 'title' => 'Quality Compliance', 'description' => 'Rate of compliance with engineering quality standards'],
            ],
            'Social Welfare Services' => [
                ['code' => 'SWS-001', 'title' => 'Assistance Coverage', 'description' => 'Percentage of target beneficiaries reached'],
                ['code' => 'SWS-002', 'title' => 'Service Effectiveness', 'description' => 'Effectiveness rating of social welfare programs'],
            ],
            'Health Services' => [
                ['code' => 'HS-001', 'title' => 'Service Accessibility', 'description' => 'Percentage of target population with access to health services'],
                ['code' => 'HS-002', 'title' => 'Health Outcome Improvement', 'description' => 'Improvement rate in key health indicators'],
            ],
            'Agricultural Services' => [
                ['code' => 'AGR-001', 'title' => 'Farmer Assistance', 'description' => 'Number of farmers receiving agricultural assistance'],
                ['code' => 'AGR-002', 'title' => 'Productivity Increase', 'description' => 'Percentage increase in agricultural productivity'],
            ],
            'Environmental Management' => [
                ['code' => 'ENV-001', 'title' => 'Compliance Rate', 'description' => 'Environmental compliance rate of regulated establishments'],
                ['code' => 'ENV-002', 'title' => 'Conservation Activities', 'description' => 'Number of environmental conservation activities implemented'],
            ],
        ];

        $this->command->info('Creating Success Indicators...');

        foreach ($mfos as $mfo) {
            // Find matching template based on MFO title
            $templateKey = $this->findTemplateKey($mfo->title, $siTemplates);

            if ($templateKey && isset($siTemplates[$templateKey])) {
                foreach ($siTemplates[$templateKey] as $template) {
                    SuccessIndicator::create([
                        'mfo_id' => $mfo->id,
                        'code' => $template['code'],
                        'title' => $template['title'],
                        'description' => $template['description'],
                        'target_quantity' => rand(80, 95),
                        'target_efficiency' => 'High',
                        'target_timeliness' => 'On Schedule',
                        'rating_quantity' => 4,
                        'rating_efficiency' => 4,
                        'rating_timeliness' => 4,
                        'average_rating' => 4.0,
                        'adjectival_rating' => 'Very Satisfactory',
                        'is_active' => true,
                        'created_by' => $adminUser?->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                // Create default success indicators for MFOs without specific templates
                SuccessIndicator::create([
                    'mfo_id' => $mfo->id,
                    'code' => 'DEFAULT-001',
                    'title' => 'Performance Quality',
                    'description' => 'Overall quality of performance for ' . $mfo->title,
                    'target_quantity' => 85,
                    'target_efficiency' => 'High',
                    'target_timeliness' => 'On Schedule',
                    'rating_quantity' => 4,
                    'rating_efficiency' => 4,
                    'rating_timeliness' => 4,
                    'average_rating' => 4.0,
                    'adjectival_rating' => 'Very Satisfactory',
                    'is_active' => true,
                    'created_by' => $adminUser?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Success Indicators created successfully!');
    }

    /**
     * Find the appropriate template key based on MFO title
     */
    private function findTemplateKey($mfoTitle, $templates): ?string
    {
        foreach ($templates as $key => $template) {
            if (stripos($mfoTitle, $key) !== false || stripos($key, $mfoTitle) !== false) {
                return $key;
            }
        }

        // Try partial matching
        foreach ($templates as $key => $template) {
            $titleWords = explode(' ', $mfoTitle);
            $keyWords = explode(' ', $key);

            foreach ($titleWords as $titleWord) {
                if (strlen($titleWord) > 3) {
                    foreach ($keyWords as $keyWord) {
                        if (strlen($keyWord) > 3 && stripos($titleWord, $keyWord) !== false) {
                            return $key;
                        }
                    }
                }
            }
        }

        return null;
    }
}