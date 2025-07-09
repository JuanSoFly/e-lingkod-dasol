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
        Schema::table('employee_work_experience', function (Blueprint $table) {
            // PDS Panel 5: Work Experience - Additional fields
            $table->date('inclusive_date_from')->nullable()->after('from_date');
            $table->date('inclusive_date_to')->nullable()->after('to_date');
            $table->string('position_title')->nullable()->after('position');
            $table->string('department_agency_office')->nullable()->after('company');
            $table->decimal('monthly_salary', 10, 2)->nullable()->after('salary');
            $table->string('salary_grade_step')->nullable()->after('monthly_salary');
            $table->string('status_of_appointment')->nullable()->after('status');
            $table->boolean('is_government_service')->default(false)->after('status_of_appointment');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_work_experience', function (Blueprint $table) {
            $table->dropColumn([
                'inclusive_date_from',
                'inclusive_date_to',
                'position_title',
                'department_agency_office',
                'monthly_salary',
                'salary_grade_step',
                'status_of_appointment',
                'is_government_service'
            ]);
            $table->dropSoftDeletes();
        });
    }
};
