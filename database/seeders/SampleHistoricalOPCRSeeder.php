<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Office;
use App\Models\OPCRWorkflow;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SampleHistoricalOPCRSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the 2024 period ID (should be 2)
        $period2024 = DB::table('performance_periods')->where('year', 2024)->first();
        if (!$period2024) {
            $this->command->error('2024 performance period not found!');
            return;
        }

        // Get all offices
        $offices = Office::all();

        // Get a sample user for workflow actions
        $sampleUser = User::first();

        $this->command->info('Creating sample OPCR workflows for 2024...');

        foreach ($offices as $office) {
            // Create 1-2 workflows per office with varying performance levels
            $workflowCount = rand(1, 2);

            for ($i = 0; $i < $workflowCount; $i++) {
                $workflowState = $this->getWeightedWorkflowState();
                $rating = $this->getRatingForState($workflowState);

                OPCRWorkflow::create([
                    'office_id' => $office->id,
                    'period_id' => $period2024->id,
                    'workflow_state' => $workflowState,
                    'title' => "CY 2024 OPCR - {$office->name}" . ($workflowCount > 1 ? " - Part " . ($i + 1) : ""),
                    'overall_rating' => $rating,
                    'overall_adjectival_rating' => $this->getAdjectivalRating($rating),
                    'summary' => "Sample performance data for {$office->name} covering the 2024 calendar year with focus on core functions and deliverables.",
                    'recommendations' => "Continue maintaining current performance levels and address areas for improvement identified in the evaluation.",
                    'committed_by' => $sampleUser?->id,
                    'committed_at' => now()->subMonths(rand(8, 12)), // 8-12 months ago
                    'submitted_by' => $sampleUser?->id,
                    'submitted_at' => now()->subMonths(rand(6, 10)), // 6-10 months ago
                    'assessed_by' => $sampleUser?->id,
                    'assessed_at' => $workflowState !== 'draft' ? now()->subMonths(rand(4, 8)) : null,
                    'assessor_remarks' => $workflowState !== 'draft' ? "Assessment completed based on documented performance and achievements." : null,
                    'approved_by' => $workflowState === 'final_approval' ? $sampleUser?->id : null,
                    'approved_at' => $workflowState === 'final_approval' ? now()->subMonths(rand(2, 6)) : null,
                    'created_at' => now()->subMonths(rand(10, 14)), // 10-14 months ago
                    'updated_at' => now()->subMonths(rand(2, 6)),
                ]);
            }
        }

        $this->command->info('Created ' . OPCRWorkflow::where('period_id', $period2024->id)->count() . ' historical OPCR workflows for 2024');
    }

    /**
     * Get weighted workflow state (most should be completed for historical data)
     */
    private function getWeightedWorkflowState(): string
    {
        $states = [
            'final_approval' => 40,  // 40% chance - most completed
            'evaluation' => 25,       // 25% chance
            'in_progress' => 20,      // 20% chance
            'committed' => 10,        // 10% chance
            'draft' => 5              // 5% chance
        ];

        $rand = mt_rand(1, 100);
        $cumulative = 0;

        foreach ($states as $state => $probability) {
            $cumulative += $probability;
            if ($rand <= $cumulative) {
                return $state;
            }
        }

        return 'final_approval'; // default
    }

    /**
     * Get realistic rating based on workflow state
     */
    private function getRatingForState(string $state): ?float
    {
        if ($state === 'draft') {
            return null;
        }

        $ratings = [
            'committed' => [2.8, 3.2],    // Lower range for early stage
            'in_progress' => [3.2, 3.6],  // Mid range
            'evaluation' => [3.6, 4.1],   // Higher range
            'final_approval' => [3.8, 4.6] // Highest range
        ];

        $range = $ratings[$state] ?? [3.0, 4.0];
        return round(mt_rand($range[0] * 10, $range[1] * 10) / 10, 1);
    }

    /**
     * Get adjectival rating based on numeric rating
     */
    private function getAdjectivalRating(?float $rating): ?string
    {
        if (!$rating) {
            return null;
        }

        if ($rating >= 4.5) {
            return 'Outstanding';
        } elseif ($rating >= 4.0) {
            return 'Very Satisfactory';
        } elseif ($rating >= 3.5) {
            return 'Satisfactory';
        } elseif ($rating >= 3.0) {
            return 'Moderately Satisfactory';
        } else {
            return 'Unsatisfactory';
        }
    }
}
