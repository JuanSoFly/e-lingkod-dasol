<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $workflowService = app(\App\Services\LeaveWorkflowService::class);

        \App\Models\LeaveApplication::where('status', 'pending')
            ->doesntHave('workflowSteps')
            ->chunkById(50, function ($applications) use ($workflowService) {
                foreach ($applications as $application) {
                    try {
                        $workflowService->initializeWorkflow($application);
                    } catch (\Throwable $e) {
                        \Log::error('Failed to backfill workflow steps for leave application', [
                            'application_id' => $application->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // No-op: migration only backfills data.
    }
};
