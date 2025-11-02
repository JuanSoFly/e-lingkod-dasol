<?php

namespace Database\Seeders;

use App\Models\OPCRWorkflow;
use App\Models\Office;
use App\Models\PerformancePeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OPCRWorkflowSeeder extends Seeder
{
    /**
     * Seed the opcr_workflows table with sample data
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        OPCRWorkflow::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get current performance period or create a default one
        $period = PerformancePeriod::where('is_active', true)->first();
        if (!$period) {
            $period = PerformancePeriod::create([
                'name' => 'CY 2025',
                'year' => 2025,
                'semester' => 'Annual',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'status' => 'active',
                'is_active' => true,
                'is_opcr_period' => true,
            ]);
        }

        // Get major offices for workflow assignment
        $offices = Office::where('is_active', true)->get();

        // Workflow states
        $workflowStates = ['draft', 'committed', 'in_progress', 'evaluation', 'final_approval'];

        // Sample titles and ratings
        $workflowTitles = [
            'Annual Performance Commitment Review',
            'Office Performance Assessment',
            'Strategic Implementation Review',
            'Service Delivery Evaluation',
            'Operational Efficiency Assessment'
        ];

        $adjectivalRatings = ['Outstanding', 'Very Satisfactory', 'Satisfactory', 'Unsatisfactory', 'Poor'];

        $workflowCount = 0;

        foreach ($offices as $office) {
            // Create 1-3 workflows per office with different states
            $numWorkflows = rand(1, 3);

            for ($i = 0; $i < $numWorkflows; $i++) {
                $state = $workflowStates[array_rand($workflowStates)];
                $title = $workflowTitles[array_rand($workflowTitles)];
                $overallRating = null;
                $adjectivalRating = null;

                // Assign ratings only for workflows in final states
                if ($state === 'final_approval') {
                    $overallRating = round((rand(35, 50) / 10), 2); // 3.5 to 5.0
                    $adjectivalRating = $adjectivalRatings[array_rand($adjectivalRatings)];
                }

                $workflowData = [
                    'office_id' => $office->id,
                    'period_id' => $period->id,
                    'workflow_state' => $state,
                    'title' => "{$title} - {$office->name}",
                    'overall_rating' => $overallRating,
                    'overall_adjectival_rating' => $adjectivalRating,
                    'summary' => $this->generateSummary($state),
                    'recommendations' => $this->generateRecommendations($state),
                    'created_at' => now()->subDays(rand(1, 60)),
                    'updated_at' => now()->subDays(rand(0, 30)),
                ];

                // Add timestamps based on workflow state
                switch ($state) {
                    case 'committed':
                        $workflowData['committed_by'] = 1;
                        $workflowData['committed_at'] = now()->subDays(rand(1, 10));
                        break;
                    case 'in_progress':
                        $workflowData['committed_by'] = 1;
                        $workflowData['committed_at'] = now()->subDays(rand(20, 30));
                        $workflowData['submitted_by'] = 1;
                        $workflowData['submitted_at'] = now()->subDays(rand(10, 20));
                        break;
                    case 'evaluation':
                        $workflowData['committed_by'] = 1;
                        $workflowData['committed_at'] = now()->subDays(rand(30, 40));
                        $workflowData['submitted_by'] = 1;
                        $workflowData['submitted_at'] = now()->subDays(rand(20, 30));
                        $workflowData['assessed_by'] = 1;
                        $workflowData['assessed_at'] = now()->subDays(rand(10, 20));
                        $workflowData['assessor_remarks'] = $this->generateAssessorRemarks();
                        break;
                    case 'final_approval':
                        $workflowData['committed_by'] = 1;
                        $workflowData['committed_at'] = now()->subDays(rand(40, 50));
                        $workflowData['submitted_by'] = 1;
                        $workflowData['submitted_at'] = now()->subDays(rand(30, 40));
                        $workflowData['assessed_by'] = 1;
                        $workflowData['assessed_at'] = now()->subDays(rand(20, 30));
                        $workflowData['approved_by'] = 1;
                        $workflowData['approved_at'] = now()->subDays(rand(5, 15));
                        $workflowData['assessor_remarks'] = $this->generateAssessorRemarks();
                        $workflowData['approver_remarks'] = $this->generateApproverRemarks();
                        break;
                }

                OPCRWorkflow::create($workflowData);
                $workflowCount++;
            }
        }

        $this->command->info("{$workflowCount} OPCR workflows created successfully.");

        // Display workflow distribution
        $distribution = OPCRWorkflow::selectRaw('workflow_state, COUNT(*) as count')
            ->groupBy('workflow_state')
            ->orderBy('workflow_state')
            ->get();

        $this->command->info('Workflow Distribution:');
        foreach ($distribution as $item) {
            $this->command->info("- {$item->workflow_state}: {$item->count}");
        }
    }

    /**
     * Generate summary based on workflow state
     */
    private function generateSummary(string $state): string
    {
        $summaries = [
            'draft' => 'Performance commitment review is currently being prepared for the office.',
            'committed' => 'Performance targets have been identified and committed by the department head.',
            'in_progress' => 'Performance review is currently in progress with ongoing data collection and analysis.',
            'evaluation' => 'Performance assessment is being conducted by designated evaluators.',
            'final_approval' => 'Performance review has been completed and is awaiting final approval.'
        ];

        return $summaries[$state] ?? $summaries['draft'];
    }

    /**
     * Generate recommendations based on workflow state
     */
    private function generateRecommendations(string $state): string
    {
        if ($state !== 'final_approval') {
            return 'Recommendations will be provided after performance evaluation is completed.';
        }

        $recommendations = [
            'Continue strengthening service delivery mechanisms and public engagement.',
            'Focus on capacity building and professional development for staff.',
            'Implement enhanced monitoring and evaluation systems.',
            'Improve inter-office coordination and communication protocols.',
            'Adopt innovative approaches to increase operational efficiency.'
        ];

        return implode(' ', array_slice($recommendations, 0, rand(2, 3)));
    }

    /**
     * Generate assessor remarks
     */
    private function generateAssessorRemarks(): string
    {
        $remarks = [
            'Office has demonstrated significant improvement in service delivery.',
            'Performance targets were achieved with notable excellence in key areas.',
            'Recommend continuation of current effective practices.',
            'Areas for improvement have been identified and should be addressed.',
            'Overall performance meets expected standards.'
        ];

        return $remarks[array_rand($remarks)];
    }

    /**
     * Generate approver remarks
     */
    private function generateApproverRemarks(): string
    {
        $remarks = [
            'Performance review approved. Commendable work by the office team.',
            'Approved with recommendation for implementation of best practices.',
            'Performance targets successfully met. Continue excellent work.',
            'Approved. Office shows consistent improvement and dedication.',
            'Final approval granted. Outstanding performance achievement.'
        ];

        return $remarks[array_rand($remarks)];
    }
}