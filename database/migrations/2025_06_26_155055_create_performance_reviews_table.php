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
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('period_id')->constrained('performance_periods')->onDelete('cascade');
            $table->integer('overall_rating');
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('recommendations')->nullable();
            $table->foreignId('reviewed_by')->constrained('users')->onDelete('cascade');
            $table->date('review_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
