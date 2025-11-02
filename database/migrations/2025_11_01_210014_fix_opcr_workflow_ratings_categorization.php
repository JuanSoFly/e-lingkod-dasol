<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix existing OPCR workflow ratings that have incorrect categorization
        // This migration recategorizes ratings using the standardized backend logic

        Log::info('Starting OPCR workflow ratings categorization fix');

        // Get all workflows that need fixing
        $workflowsToFix = DB::table('opcr_workflows')
            ->whereNotNull('overall_rating')
            ->get(['id', 'overall_rating', 'overall_adjectival_rating']);

        $fixedCount = 0;

        foreach ($workflowsToFix as $workflow) {
            $correctCategory = $this->getRatingCategory($workflow->overall_rating);

            if ($workflow->overall_adjectival_rating !== $correctCategory) {
                Log::info('Fixing workflow rating categorization', [
                    'workflow_id' => $workflow->id,
                    'rating' => $workflow->overall_rating,
                    'old_category' => $workflow->overall_adjectival_rating,
                    'new_category' => $correctCategory
                ]);

                DB::table('opcr_workflows')
                    ->where('id', $workflow->id)
                    ->update([
                        'overall_adjectival_rating' => $correctCategory,
                        'updated_at' => now()
                    ]);

                $fixedCount++;
            }
        }

        Log::info("OPCR workflow ratings categorization fix completed. Fixed {$fixedCount} workflows.");

        // Backfill missing ratings for workflows that have corresponding performance evaluations
        $this->backfillMissingRatings();
    }

    /**
     * Backfill missing ratings from performance evaluations
     */
    private function backfillMissingRatings(): void
    {
        Log::info('Starting backfill of missing OPCR workflow ratings');

        // Get workflows with NULL ratings but have performance evaluations
        $workflowsToBackfill = DB::table('opcr_workflows as ow')
            ->join('performance_evaluations as pe', 'ow.id', '=', 'pe.opcr_workflow_id')
            ->whereNull('ow.overall_rating')
            ->select('ow.id', 'pe.overall_rating as pe_rating', 'pe.overall_adjectival_rating as pe_category')
            ->get();

        $backfilledCount = 0;

        foreach ($workflowsToBackfill as $workflow) {
            $correctCategory = $this->getRatingCategory($workflow->pe_rating);

            Log::info('Backfilling workflow rating', [
                'workflow_id' => $workflow->id,
                'pe_rating' => $workflow->pe_rating,
                'pe_category' => $workflow->pe_category,
                'new_category' => $correctCategory
            ]);

            DB::table('opcr_workflows')
                ->where('id', $workflow->id)
                ->update([
                    'overall_rating' => $workflow->pe_rating,
                    'overall_adjectival_rating' => $correctCategory,
                    'updated_at' => now()
                ]);

            $backfilledCount++;
        }

        Log::info("OPCR workflow ratings backfill completed. Backfilled {$backfilledCount} workflows.");
    }

    /**
     * Get rating category based on numerical rating
     * This matches the backend getRatingCategory method in OPCRController
     */
    private function getRatingCategory(float $rating): string
    {
        if ($rating >= 4.51) return 'Outstanding';
        if ($rating >= 3.76) return 'Very Satisfactory';
        if ($rating >= 3.01) return 'Satisfactory';
        if ($rating >= 2.51) return 'Fairly Satisfactory';
        if ($rating >= 1.51) return 'Poor';
        return 'Very Poor';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is data recovery, so we can't reliably reverse it
        // The down migration is left empty as the original data is not preserved
        Log::warning('OPCR workflow ratings categorization fix migration cannot be reversed automatically');
    }
};
