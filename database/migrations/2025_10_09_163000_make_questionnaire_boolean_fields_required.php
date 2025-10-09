<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_questionnaire', function (Blueprint $table) {
            // First, update any NULL values to false (0) as default
            DB::table('employee_questionnaire')
                ->whereNull('field_34_yes_no')
                ->update(['field_34_yes_no' => false]);

            DB::table('employee_questionnaire')
                ->whereNull('field_34b_yes_no')
                ->update(['field_34b_yes_no' => false]);

            DB::table('employee_questionnaire')
                ->whereNull('field_35a_yes_no')
                ->update(['field_35a_yes_no' => false]);

            DB::table('employee_questionnaire')
                ->whereNull('field_35b_yes_no')
                ->update(['field_35b_yes_no' => false]);

            DB::table('employee_questionnaire')
                ->whereNull('field_36_yes_no')
                ->update(['field_36_yes_no' => false]);

            DB::table('employee_questionnaire')
                ->whereNull('field_37_yes_no')
                ->update(['field_37_yes_no' => false]);

            DB::table('employee_questionnaire')
                ->whereNull('field_38a_yes_no')
                ->update(['field_38a_yes_no' => false]);

            DB::table('employee_questionnaire')
                ->whereNull('field_38b_yes_no')
                ->update(['field_38b_yes_no' => false]);

            DB::table('employee_questionnaire')
                ->whereNull('field_39_yes_no')
                ->update(['field_39_yes_no' => false]);

            DB::table('employee_questionnaire')
                ->whereNull('field_40a_yes_no')
                ->update(['field_40a_yes_no' => false]);

            DB::table('employee_questionnaire')
                ->whereNull('field_40b_yes_no')
                ->update(['field_40b_yes_no' => false]);

            DB::table('employee_questionnaire')
                ->whereNull('field_40c_yes_no')
                ->update(['field_40c_yes_no' => false]);

            // Now change the columns to be NOT NULL
            $table->boolean('field_34_yes_no')->nullable(false)->default(false)->change();
            $table->boolean('field_34b_yes_no')->nullable(false)->default(false)->change();
            $table->boolean('field_35a_yes_no')->nullable(false)->default(false)->change();
            $table->boolean('field_35b_yes_no')->nullable(false)->default(false)->change();
            $table->boolean('field_36_yes_no')->nullable(false)->default(false)->change();
            $table->boolean('field_37_yes_no')->nullable(false)->default(false)->change();
            $table->boolean('field_38a_yes_no')->nullable(false)->default(false)->change();
            $table->boolean('field_38b_yes_no')->nullable(false)->default(false)->change();
            $table->boolean('field_39_yes_no')->nullable(false)->default(false)->change();
            $table->boolean('field_40a_yes_no')->nullable(false)->default(false)->change();
            $table->boolean('field_40b_yes_no')->nullable(false)->default(false)->change();
            $table->boolean('field_40c_yes_no')->nullable(false)->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_questionnaire', function (Blueprint $table) {
            // Change columns back to nullable
            $table->boolean('field_34_yes_no')->nullable()->change();
            $table->boolean('field_34b_yes_no')->nullable()->change();
            $table->boolean('field_35a_yes_no')->nullable()->change();
            $table->boolean('field_35b_yes_no')->nullable()->change();
            $table->boolean('field_36_yes_no')->nullable()->change();
            $table->boolean('field_37_yes_no')->nullable()->change();
            $table->boolean('field_38a_yes_no')->nullable()->change();
            $table->boolean('field_38b_yes_no')->nullable()->change();
            $table->boolean('field_39_yes_no')->nullable()->change();
            $table->boolean('field_40a_yes_no')->nullable()->change();
            $table->boolean('field_40b_yes_no')->nullable()->change();
            $table->boolean('field_40c_yes_no')->nullable()->change();
        });
    }
};