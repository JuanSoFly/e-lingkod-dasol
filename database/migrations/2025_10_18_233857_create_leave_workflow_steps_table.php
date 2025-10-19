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
        Schema::create('leave_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_workflow_id')->constrained()->onDelete('cascade');
            $table->integer('step_order');
            $table->string('step_type'); // 'individual', 'role_based', 'parallel'
            $table->string('step_name')->nullable(); // Name of this step
            $table->json('approvers'); // Array of approver IDs or role IDs
            $table->boolean('required_all')->default(false); // For parallel approvals
            $table->integer('escalation_hours')->nullable(); // Hours before escalation
            $table->json('escalation_to')->nullable(); // Escalation targets
            $table->timestamps();

            $table->index(['leave_workflow_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_workflow_steps');
    }
};
