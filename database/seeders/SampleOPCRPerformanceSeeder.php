<?php

namespace Database\Seeders;

use App\Models\OPCRWorkflow;
use App\Models\PerformanceTarget;
use App\Models\PerformanceRating;
use App\Models\SuccessIndicator;
use App\Models\User;
use App\Models\PerformancePeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleOPCRPerformanceSeeder extends Seeder
{
    /**
     * Seed performance targets and ratings for existing OPCR workflows
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PerformanceTarget::truncate();
        PerformanceRating::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get admin user for created_by field
        $adminUser = User::whereHas('roles', function($query) {
            $query->where('name', 'Super Admin');
        })->first();

        // Get workflows in final_approval state
        $workflows = OPCRWorkflow::where('workflow_state', 'final_approval')
            ->with(['office', 'period'])
            ->get();

        // Get success indicators
        $successIndicators = SuccessIndicator::where('is_active', true)
            ->with('mfo')
            ->get()
            ->groupBy('mfo_id');

        $this->command->info('Creating Performance Targets and Ratings...');

        foreach ($workflows as $workflow) {
            // Get success indicators for this workflow's office
            $officeMfoIds = DB::table('major_final_outputs')
                ->where('office_id', $workflow->office_id)
                ->pluck('id');

            $availableIndicators = collect();
            foreach ($officeMfoIds as $mfoId) {
                if (isset($successIndicators[$mfoId])) {
                    $availableIndicators = $availableIndicators->merge($successIndicators[$mfoId]);
                }
            }

            // If no office-specific indicators, use any available indicators
            if ($availableIndicators->isEmpty()) {
                $availableIndicators = SuccessIndicator::where('is_active', true)
                    ->with('mfo')
                    ->take(3)
                    ->get();
            }

            // Create 2-5 performance targets per workflow
            $targetCount = rand(2, min(5, $availableIndicators->count()));
            $selectedIndicators = $availableIndicators->random($targetCount);

            foreach ($selectedIndicators as $indicator) {
                // Create performance target
                $target = PerformanceTarget::create([
                    'mfo_id' => $indicator->mfo_id,
                    'success_indicator_id' => $indicator->id,
                    'mfo_code' => $indicator->mfo->code ?? '',
                    'si_code' => $indicator->code,
                    'employee_id' => $workflow->committed_by, // Use workflow committer as employee
                    'period_id' => $workflow->period_id,
                    'office_id' => $workflow->office_id,
                    'opcr_workflow_id' => $workflow->id,
                    'objective' => $indicator->mfo->title ?? 'Objective for ' . $workflow->title,
                    'target' => $indicator->description ?? 'Target aligned with OPCR goals',
                    'weight' => round((100 / $targetCount), 2), // Distribute weight evenly
                    'target_quantity' => $indicator->target_quantity ?? rand(80, 95),
                    'target_efficiency' => $indicator->target_efficiency ?? 'High',
                    'target_timeliness' => $indicator->target_timeliness ?? 'On Schedule',
                    'success_indicator' => $indicator->description,
                    'is_target_met' => rand(0, 1),
                    'created_by' => $adminUser?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Create performance rating for this target
                $ratingQuantity = rand(3, 5);
                $ratingEfficiency = rand(3, 5);
                $ratingTimeliness = rand(3, 5);
                $averageRating = ($ratingQuantity + $ratingEfficiency + $ratingTimeliness) / 3;

                $adjectivalRating = $this->getAdjectivalRating($averageRating);

                PerformanceRating::create([
                    'target_id' => $target->id,
                    'office_id' => $workflow->office_id,
                    'self_rating' => $averageRating,
                    'supervisor_rating' => $averageRating,
                    'final_rating' => $averageRating,
                    'average_rating' => $averageRating,
                    'accomplished_quantity' => rand(75, 100),
                    'accomplished_efficiency' => $averageRating >= 4 ? 'Excellent' : ($averageRating >= 3 ? 'Good' : 'Needs Improvement'),
                    'accomplished_timeliness' => $averageRating >= 4 ? 'On Time' : ($averageRating >= 3 ? 'Slightly Delayed' : 'Delayed'),
                    'remarks' => 'Performance target completed with ' . $adjectivalRating . ' rating',
                    'rating_quantity' => $ratingQuantity,
                    'rating_efficiency' => $ratingEfficiency,
                    'rating_timeliness' => $ratingTimeliness,
                    'average_qet_rating' => $averageRating,
                    'adjectival_rating' => $adjectivalRating,
                    'assessed_by' => $workflow->assessed_by,
                    'assessed_at' => $workflow->assessed_at ?: now(),
                    'approved_by' => $workflow->approved_by,
                    'approved_at' => $workflow->approved_at ?: now(),
                    'is_legacy_ipcr' => false,
                    'created_by' => $adminUser?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Update workflow's overall rating based on created targets
            $this->updateWorkflowOverallRating($workflow->id);
        }

        $this->command->info('Performance Targets and Ratings created successfully!');
    }

    /**
     * Get adjectival rating based on numeric score
     */
    private function getAdjectivalRating($score): string
    {
        if ($score >= 4.5) return 'Outstanding';
        if ($score >= 3.5) return 'Very Satisfactory';
        if ($score >= 2.5) return 'Satisfactory';
        if ($score >= 1.5) return 'Fairly Satisfactory';
        if ($score >= 0.5) return 'Poor';
        return 'No Rating';
    }

    /**
     * Update workflow's overall rating based on its targets
     */
    private function updateWorkflowOverallRating($workflowId): void
    {
        $workflow = OPCRWorkflow::find($workflowId);
        if (!$workflow) return;

        $ratings = PerformanceRating::whereHas('target', function($query) use ($workflowId) {
            $query->where('opcr_workflow_id', $workflowId);
        })->pluck('final_rating');

        if ($ratings->isNotEmpty()) {
            $averageRating = $ratings->avg();
            $adjectivalRating = $this->getAdjectivalRating($averageRating);

            $workflow->update([
                'overall_rating' => $averageRating,
                'overall_adjectival_rating' => $adjectivalRating,
            ]);
        }
    }
}