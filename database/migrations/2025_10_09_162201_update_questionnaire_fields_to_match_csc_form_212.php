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
            // Remove existing combined field 35
            $table->dropColumn(['field_35_charges_details']);

            // Remove government ID fields (question 39)
            $table->dropColumn([
                'field_39_gov_id_number',
                'field_39_gov_id_date_issued',
                'field_39_gov_id_place_issued'
            ]);

            // Add split question 35 fields
            $table->text('field_35_administrative_offense_details')->nullable()->comment('Full details of administrative offense');
            $table->text('field_36_criminal_charge_details')->nullable()->comment('Full details of criminal charge');

            // Add question 39 dual citizenship field
            $table->text('field_39_dual_citizenship_details')->nullable()->comment('Dual citizenship details');

            // Update question 41 to be question 40 (renumber)
            $table->dropColumn([
                'field_41_indigenous_member',
                'field_41_indigenous_id_number',
                'field_41_pwd_member',
                'field_41_pwd_id_number',
                'field_41_solo_parent_member',
                'field_41_solo_parent_id_number'
            ]);

            // Add question 40 fields with a, b, c sub-parts
            $table->text('field_40_indigenous_details')->nullable()->comment('Question 40a: Indigenous group details and ID number');
            $table->text('field_40_pwd_details')->nullable()->comment('Question 40b: PWD details and ID number');
            $table->text('field_40_solo_parent_details')->nullable()->comment('Question 40c: Solo parent ID number');

            // Note: Cannot index TEXT columns in MySQL without specifying key length
            // Performance is acceptable for these questionnaire fields
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_questionnaire', function (Blueprint $table) {
            // Remove new fields
            $table->dropColumn([
                'field_35_administrative_offense_details',
                'field_36_criminal_charge_details',
                'field_39_dual_citizenship_details',
                'field_40_indigenous_details',
                'field_40_pwd_details',
                'field_40_solo_parent_details'
            ]);

            // Restore original field 35
            $table->text('field_35_charges_details')->nullable()->comment('Full details of administrative/criminal charges');

            // Restore government ID fields
            $table->string('field_39_gov_id_number')->nullable()->comment('Government ID number');
            $table->date('field_39_gov_id_date_issued')->nullable()->comment('Date of government ID issuance');
            $table->string('field_39_gov_id_place_issued')->nullable()->comment('Place of government ID issuance');

            // Restore field 41
            $table->string('field_41_indigenous_member')->nullable()->comment('Indigenous group member status');
            $table->string('field_41_indigenous_id_number')->nullable()->comment('IP/ICC Certificate number');
            $table->string('field_41_pwd_member')->nullable()->comment('PWD status and disability');
            $table->string('field_41_pwd_id_number')->nullable()->comment('PWD ID number');
            $table->string('field_41_solo_parent_member')->nullable()->comment('Solo parent status');
            $table->string('field_41_solo_parent_id_number')->nullable()->comment('Solo Parent ID number');

            // Add back old indexes
            $table->index(['field_41_indigenous_member']);
            $table->index(['field_41_pwd_member']);
            $table->index(['field_41_solo_parent_member']);
            $table->index(['field_39_gov_id_number']);
            $table->index(['field_39_gov_id_date_issued']);
        });
    }
};
