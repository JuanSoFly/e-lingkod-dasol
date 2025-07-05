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
        Schema::create('performance_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('evaluation_period'); // Q1 2025, H1 2025, Annual 2025
            $table->date('evaluation_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('overall_rating', 3, 2); // 1.00 to 5.00
            $table->decimal('quality_rating', 3, 2)->nullable();
            $table->decimal('efficiency_rating', 3, 2)->nullable();
            $table->decimal('timeliness_rating', 3, 2)->nullable();
            $table->decimal('initiative_rating', 3, 2)->nullable();
            $table->decimal('teamwork_rating', 3, 2)->nullable();
            $table->decimal('leadership_rating', 3, 2)->nullable();
            $table->decimal('goal_achievement_percentage', 5, 2)->default(0); // 0.00 to 100.00
            $table->text('achievements')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('goals_next_period')->nullable();
            $table->text('evaluator_comments')->nullable();
            $table->text('employee_comments')->nullable();
            $table->boolean('promotion_readiness')->default(false);
            $table->boolean('leadership_potential')->default(false);
            $table->string('evaluation_status')->default('draft'); // draft, submitted, approved, final
            $table->foreignId('evaluator_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('employees')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('competency_scores')->nullable(); // Store detailed competency ratings
            $table->text('development_plan')->nullable();
            $table->string('performance_level')->nullable(); // Outstanding, Exceeds, Meets, Below, Unsatisfactory
            $table->timestamps();
            
            $table->index(['employee_id', 'evaluation_date']);
            $table->index(['evaluation_period']);
            $table->index(['overall_rating']);
            $table->index(['evaluation_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_evaluations');
    }
};
