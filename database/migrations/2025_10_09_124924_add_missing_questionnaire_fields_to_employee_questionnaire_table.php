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
            // Question 34b: Fourth degree relationship for LGU employees
            $table->boolean('field_34b_yes_no')->nullable()->comment('Question 34b: Relationship within fourth degree for LGU');
            $table->text('field_34b_relationship_details')->nullable()->comment('Question 34b: Details of fourth degree relationship');

            // Question 36: Conviction details
            $table->text('field_36_conviction_details')->nullable()->comment('Question 36: Details of criminal conviction');

            // Question 37: Separation from service details
            $table->text('field_37_separation_details')->nullable()->comment('Question 37: Details of separation from service');

            // Question 39: Immigrant status details
            $table->text('field_39_immigrant_details')->nullable()->comment('Question 39: Details of immigrant/permanent resident status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_questionnaire', function (Blueprint $table) {
            $table->dropColumn([
                'field_34b_yes_no',
                'field_34b_relationship_details',
                'field_36_conviction_details',
                'field_37_separation_details',
                'field_39_immigrant_details'
            ]);
        });
    }
};
