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
        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('snapshot_type'); // workforce, turnover, performance, etc.
            $table->date('snapshot_date');
            $table->json('data'); // Store analytics data as JSON
            $table->string('period_type')->default('daily'); // daily, weekly, monthly, quarterly, yearly
            $table->integer('total_employees')->nullable();
            $table->decimal('turnover_rate', 5, 2)->nullable();
            $table->decimal('average_performance', 3, 2)->nullable();
            $table->decimal('compliance_rate', 5, 2)->nullable();
            $table->decimal('training_completion_rate', 5, 2)->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['snapshot_type', 'snapshot_date']);
            $table->index(['snapshot_type', 'period_type']);
            $table->index('snapshot_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_snapshots');
    }
};
