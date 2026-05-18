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
        // Update existing employee departments to official Municipality of Dasol offices
        DB::table('employees')->update([
            'department' => DB::raw("CASE
                WHEN department = 'HR' THEN 'Human Resource Management Office'
                WHEN department = 'Finance' THEN 'Municipal Treasurer''s Office'
                WHEN department IN ('IT', 'Engineering') THEN 'Municipal Engineer''s Office'
                WHEN department = 'Accounting' THEN 'Office of the Municipal Accountant'
                WHEN department = 'Budget' THEN 'Office of the Municipal Budget Office'
                WHEN department = 'Planning' THEN 'Municipal Planning and Development Office'
                WHEN department = 'Assessor' THEN 'Municipal Assessor''s Office'
                WHEN department = 'BPL' THEN 'Business Permits and Licensing Office'
                WHEN department = 'Tourism' THEN 'Municipal Tourism and Cultural Affairs Office'
                WHEN department = 'Civil Registry' THEN 'Local Civil Registry Office'
                WHEN department = 'BMO' THEN 'Office of the Building Official'
                WHEN department = 'Agriculture' THEN 'Municipal Agriculturist''s Office'
                WHEN department = 'Social Welfare' THEN 'Municipal Social Welfare and Development Office'
                WHEN department = 'Health' THEN 'Rural Health Unit'
                WHEN department = 'SB' THEN 'Sangguniang Bayan/Secretary to the SB Office'
                WHEN department = 'MDRRMO' THEN 'Municipal Disaster Risk Reduction and Management Office'
                ELSE 'General Services Office'
            END")
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old department names
        DB::table('employees')->update([
            'department' => DB::raw("CASE
                WHEN department = 'Human Resource Management Office' THEN 'HR'
                WHEN department = 'Municipal Treasurer''s Office' THEN 'Finance'
                WHEN department = 'Municipal Engineer''s Office' THEN 'IT'
                WHEN department = 'Office of the Municipal Accountant' THEN 'Accounting'
                WHEN department = 'Office of the Municipal Budget Office' THEN 'Budget'
                WHEN department = 'Municipal Planning and Development Office' THEN 'Planning'
                WHEN department = 'Municipal Assessor''s Office' THEN 'Assessor'
                WHEN department = 'Business Permits and Licensing Office' THEN 'BPL'
                WHEN department = 'Municipal Tourism and Cultural Affairs Office' THEN 'Tourism'
                WHEN department = 'Local Civil Registry Office' THEN 'Civil Registry'
                WHEN department = 'Office of the Building Official' THEN 'BMO'
                WHEN department = 'Municipal Agriculturist''s Office' THEN 'Agriculture'
                WHEN department = 'Municipal Social Welfare and Development Office' THEN 'Social Welfare'
                WHEN department = 'Rural Health Unit' THEN 'Health'
                WHEN department = 'Sangguniang Bayan/Secretary to the SB Office' THEN 'SB'
                WHEN department = 'Municipal Disaster Risk Reduction and Management Office' THEN 'MDRRMO'
                ELSE department
            END")
        ]);
    }
};
