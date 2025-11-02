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
        // Populate approval_status for existing workflows based on their current state
        Log::info('Populating approval_status for existing OPCR workflows');

        // For workflows that are already in final_approval state, set approval_status to 'approved'
        $approvedWorkflows = DB::table('opcr_workflows')
            ->where('workflow_state', 'final_approval')
            ->whereNotNull('approved_at')
            ->whereNull('approval_status')
            ->update([
                'approval_status' => 'approved',
                'updated_at' => now()
            ]);

        Log::info("Set approval_status='approved' for {$approvedWorkflows} existing approved workflows");

        // For workflows that are in returned state, set approval_status to 'returned'
        $returnedWorkflows = DB::table('opcr_workflows')
            ->where('workflow_state', 'returned')
            ->whereNotNull('returned_at')
            ->whereNull('approval_status')
            ->update([
                'approval_status' => 'returned',
                'updated_at' => now()
            ]);

        Log::info("Set approval_status='returned' for {$returnedWorkflows} existing returned workflows");

        // For workflows with ratings but no approval_status, set to approved by default
        $ratedWorkflows = DB::table('opcr_workflows')
            ->whereNotNull('overall_rating')
            ->whereNull('approval_status')
            ->update([
                'approval_status' => 'approved',
                'updated_at' => now()
            ]);

        Log::info("Set approval_status='approved' for {$ratedWorkflows} workflows with existing ratings");

        // Log summary
        $totalUpdated = $approvedWorkflows + $returnedWorkflows + $ratedWorkflows;
        Log::info("Approval status population completed. Updated {$totalUpdated} workflows.");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the approval_status column for workflows that were populated
        DB::table('opcr_workflows')
            ->update([
                'approval_status' => null,
                'final_rating_override' => null,
                'performance_level' => null,
                'rating_override_justification' => null,
                'updated_at' => now()
            ]);

        Log::info('Cleared approval enhancement columns for all workflows');
    }
};
