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
        Schema::table('employee_questionnaire', function (Blueprint $table) {
            // Make JSON fields nullable to fix constraint error
            // This allows the new individual CSC fields to work without requiring JSON data
            $table->json('questions_answers')->nullable()->change();
            $table->json('question_details')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_questionnaire', function (Blueprint $table) {
            // Revert to required JSON fields (original state)
            $table->json('questions_answers')->nullable(false)->change();
            $table->json('question_details')->nullable(false)->change();
        });
    }
};
