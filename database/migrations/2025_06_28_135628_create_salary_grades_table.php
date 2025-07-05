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
        Schema::create('salary_grades', function (Blueprint $table) {
            $table->id();
            
            // Salary grade structure
            $table->integer('grade_level')->index()->comment('Salary Grade 1-33');
            $table->integer('step_increment')->index()->comment('Step 1-8');
            $table->decimal('monthly_salary', 10, 2)->index();
            
            // Philippine government specific fields
            $table->decimal('daily_rate', 8, 2)->comment('Monthly salary / 22 working days');
            $table->decimal('hourly_rate', 8, 2)->comment('Daily rate / 8 hours');
            
            // Government allowances and benefits
            $table->decimal('pera_allowance', 8, 2)->default(2000.00)->comment('Performance-based Allowance');
            $table->decimal('productivity_allowance', 8, 2)->default(0.00);
            $table->decimal('hazard_allowance', 8, 2)->default(0.00);
            $table->decimal('subsistence_allowance', 8, 2)->default(0.00);
            $table->decimal('laundry_allowance', 8, 2)->default(0.00);
            
            // Overtime rates
            $table->decimal('overtime_rate_regular', 8, 2)->comment('125% of hourly rate');
            $table->decimal('overtime_rate_special', 8, 2)->comment('130% of hourly rate (special holiday)');
            $table->decimal('overtime_rate_legal', 8, 2)->comment('200% of hourly rate (legal holiday)');
            
            // Night shift differential
            $table->decimal('night_differential_rate', 8, 2)->comment('10% of hourly rate');
            
            // Position classifications
            $table->enum('position_level', [
                'First Level',
                'Second Level', 
                'Career Executive Service',
                'Uniformed Personnel',
                'Teaching Position',
                'Medical/Health',
                'Special Position'
            ])->index();
            
            // Salary standardization law reference
            $table->enum('ssl_tranche', [
                'Tranche 1',
                'Tranche 2',
                'Tranche 3',
                'Tranche 4',
                'Tranche 5'
            ])->default('Tranche 4')->comment('SSL (Salary Standardization Law) implementation');
            
            // Effective periods
            $table->date('effective_date')->index();
            $table->date('end_date')->nullable()->index();
            $table->enum('status', [
                'Active',
                'Superseded',
                'Future',
                'Cancelled'
            ])->default('Active')->index();
            
            // Government reference information
            $table->string('dbu_number')->nullable()->comment('DBM Budget Circular/DBU Number');
            $table->string('legal_basis')->nullable()->comment('RA/EO/Administrative Order');
            $table->text('remarks')->nullable();
            
            // Annual adjustment tracking
            $table->decimal('annual_adjustment_percentage', 5, 2)->default(0.00);
            $table->integer('adjustment_year')->nullable();
            
            $table->timestamps();
            
            // Composite indexes for performance
            $table->unique(['grade_level', 'step_increment', 'effective_date'], 'unique_grade_step_effective');
            $table->index(['grade_level', 'step_increment', 'status']);
            $table->index(['effective_date', 'status']);
            $table->index(['position_level', 'grade_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_grades');
    }
};