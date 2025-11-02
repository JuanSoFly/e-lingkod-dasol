<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('opcr_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_evaluation_id')->nullable()->constrained()->onDelete('cascade'); // Link to existing evaluation
            $table->foreignId('office_id')->constrained()->onDelete('cascade'); // Office for this OPCR
            $table->foreignId('period_id')->constrained('performance_periods')->onDelete('cascade'); // Performance period
            $table->string('workflow_state', 50)->default('draft'); // 5-stage workflow: draft, committed, in_progress, evaluation, final_approval
            $table->text('title'); // OPCR title
            $table->decimal('overall_rating', 3, 2)->nullable(); // Final overall rating
            $table->string('overall_adjectival_rating', 50)->nullable(); // Overall adjectival rating
            $table->text('summary')->nullable(); // Performance summary
            $table->text('recommendations')->nullable(); // Recommendations
            $table->text('justification')->nullable(); // Rating justification

            // Approval fields
            $table->foreignId('committed_by')->nullable()->constrained('users')->onDelete('set null'); // Department head who committed
            $table->timestamp('committed_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null'); // Who submitted for evaluation
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->onDelete('set null'); // Assessor (PMT)
            $table->timestamp('assessed_at')->nullable();
            $table->text('assessor_remarks')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Final approver (Mayor)
            $table->timestamp('approved_at')->nullable();
            $table->text('approver_remarks')->nullable();

            // Return workflow
            $table->text('return_reason')->nullable(); // Reason for returning
            $table->foreignId('returned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('returned_at')->nullable();

            $table->json('metadata')->nullable(); // Additional workflow data
            $table->timestamps();

            // Indexes
            $table->index(['office_id', 'period_id', 'workflow_state']);
            $table->index('workflow_state');
            $table->index('committed_by');
            $table->index('assessed_by');
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opcr_workflows');
    }
};
