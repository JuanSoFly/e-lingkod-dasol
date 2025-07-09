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
        Schema::create('employee_questionnaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            // PDS Panel 10: Questionnaire - Legal/Ethical Questions
            $table->json('questions_answers'); // Store Q34a, Q34b, Q35a, etc. as JSON
            $table->json('question_details')->nullable(); // Details for "Yes" answers
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_questionnaire');
    }
};
