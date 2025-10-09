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
            // Add YES/NO fields for questions 34-40 with conditional textareas
            $table->boolean('field_34_yes_no')->nullable()->comment('Question 34: Relationship to appointing authority (YES=1, NO=0)');
            $table->boolean('field_35a_yes_no')->nullable()->comment('Question 35a: Administrative offense (YES=1, NO=0)');
            $table->boolean('field_35b_yes_no')->nullable()->comment('Question 35b: Criminal charge (YES=1, NO=0)');
            $table->boolean('field_36_yes_no')->nullable()->comment('Question 36: Conviction of any crime (YES=1, NO=0)');
            $table->boolean('field_37_yes_no')->nullable()->comment('Question 37: Separation from service (YES=1, NO=0)');
            $table->boolean('field_38a_yes_no')->nullable()->comment('Question 38a: Election candidacy (YES=1, NO=0)');
            $table->boolean('field_38b_yes_no')->nullable()->comment('Question 38b: Resignation to campaign (YES=1, NO=0)');
            $table->boolean('field_39_yes_no')->nullable()->comment('Question 39: Immigrant status (YES=1, NO=0)');
            $table->boolean('field_40a_yes_no')->nullable()->comment('Question 40a: Indigenous group member (YES=1, NO=0)');
            $table->boolean('field_40b_yes_no')->nullable()->comment('Question 40b: PWD status (YES=1, NO=0)');
            $table->boolean('field_40c_yes_no')->nullable()->comment('Question 40c: Solo parent status (YES=1, NO=0)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_questionnaire', function (Blueprint $table) {
            $table->dropColumn([
                'field_34_yes_no',
                'field_35a_yes_no',
                'field_35b_yes_no',
                'field_36_yes_no',
                'field_37_yes_no',
                'field_38a_yes_no',
                'field_38b_yes_no',
                'field_39_yes_no',
                'field_40a_yes_no',
                'field_40b_yes_no',
                'field_40c_yes_no'
            ]);
        });
    }
};
