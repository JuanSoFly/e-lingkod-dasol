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
            // CSC Form No. 212 - Field 34: Relationship to appointing authority
            $table->text('field_34_relationship_details')->nullable()->comment('Name and relationship to appointing authority');

            // CSC Form No. 212 - Field 35: Administrative/criminal charges
            $table->text('field_35_charges_details')->nullable()->comment('Full details of administrative/criminal charges');

            // CSC Form No. 212 - Field 36: Candidate in election
            $table->text('field_36_candidate_details')->nullable()->comment('Position and year of candidacy');

            // CSC Form No. 212 - Field 37: Resignation to campaign
            $table->text('field_37_resignation_details')->nullable()->comment('Name of candidate and political party');

            // CSC Form No. 212 - Field 38: Immigrant status
            $table->string('field_38_immigrant_status')->nullable()->comment('Country of immigrant/permanent resident status');

            // CSC Form No. 212 - Field 39: Government ID
            $table->string('field_39_gov_id_number')->nullable()->comment('Government ID number');
            $table->date('field_39_gov_id_date_issued')->nullable()->comment('Date of government ID issuance');
            $table->string('field_39_gov_id_place_issued')->nullable()->comment('Place of government ID issuance');

            // CSC Form No. 212 - Field 41: Indigenous/PWD/Solo Parent status
            $table->string('field_41_indigenous_member')->nullable()->comment('Indigenous group member status');
            $table->string('field_41_indigenous_id_number')->nullable()->comment('IP/ICC Certificate number');
            $table->string('field_41_pwd_member')->nullable()->comment('PWD status and disability');
            $table->string('field_41_pwd_id_number')->nullable()->comment('PWD ID number');
            $table->string('field_41_solo_parent_member')->nullable()->comment('Solo parent status');
            $table->string('field_41_solo_parent_id_number')->nullable()->comment('Solo Parent ID number');

            // Add indexes for better performance
            $table->index(['field_41_indigenous_member']);
            $table->index(['field_41_pwd_member']);
            $table->index(['field_41_solo_parent_member']);

            // Indexes for government ID verification
            $table->index(['field_39_gov_id_number']);
            $table->index(['field_39_gov_id_date_issued']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_questionnaire', function (Blueprint $table) {
            $table->dropColumn([
                'field_34_relationship_details',
                'field_35_charges_details',
                'field_36_candidate_details',
                'field_37_resignation_details',
                'field_38_immigrant_status',
                'field_39_gov_id_number',
                'field_39_gov_id_date_issued',
                'field_39_gov_id_place_issued',
                'field_41_indigenous_member',
                'field_41_indigenous_id_number',
                'field_41_pwd_member',
                'field_41_pwd_id_number',
                'field_41_solo_parent_member',
                'field_41_solo_parent_id_number',
            ]);
        });
    }
};