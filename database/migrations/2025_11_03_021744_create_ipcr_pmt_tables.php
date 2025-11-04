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
        Schema::create('pmt_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipcr_id')->constrained('ipcrs')->cascadeOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('validator_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('validator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('validation_stage', 100)->default('initial');
            $table->string('status', 50)->default('pending');
            $table->decimal('recommended_rating', 4, 2)->nullable();
            $table->json('rating_breakdown')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['ipcr_id', 'status']);
            $table->index(['office_id', 'validation_stage']);
            $table->index(['validator_id', 'validated_at']);
        });

        Schema::create('calibration_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_period_id')->constrained('performance_periods')->cascadeOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_title');
            $table->text('agenda')->nullable();
            $table->string('status', 50)->default('scheduled');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('participants')->nullable();
            $table->json('rating_statistics')->nullable();
            $table->json('action_items')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['performance_period_id', 'status']);
            $table->index(['office_id', 'scheduled_at']);
        });

        Schema::create('calibration_session_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calibration_session_id')->constrained('calibration_sessions')->cascadeOnDelete();
            $table->foreignId('ipcr_id')->constrained('ipcrs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('initial_rating', 4, 2)->nullable();
            $table->decimal('recommended_rating', 4, 2)->nullable();
            $table->decimal('final_rating', 4, 2)->nullable();
            $table->json('discussion_points')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['calibration_session_id', 'ipcr_id'], 'calibration_session_ipcr_unique');
            $table->index(['employee_id', 'final_rating']);
        });

        Schema::create('final_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipcr_id')->constrained('ipcrs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('final_score', 4, 2);
            $table->string('adjectival_rating', 100);
            $table->string('performance_level', 100)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['ipcr_id', 'employee_id'], 'final_ratings_unique_employee');
            $table->index(['employee_id', 'final_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_ratings');
        Schema::dropIfExists('calibration_session_items');
        Schema::dropIfExists('calibration_sessions');
        Schema::dropIfExists('pmt_validations');
    }
};
