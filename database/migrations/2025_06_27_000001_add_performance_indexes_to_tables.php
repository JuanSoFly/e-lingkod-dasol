<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add indexes to employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->index('employee_number', 'idx_employee_number');
            $table->index('email', 'idx_employee_email');
            $table->index('department', 'idx_department');
            $table->index('position', 'idx_position');
            $table->index('employment_status', 'idx_employment_status');
            $table->index('date_hired', 'idx_date_hired');
        });

        // Add indexes to leave_applications table
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->index(['employee_id', 'status'], 'idx_employee_status');
            $table->index(['status', 'applied_date'], 'idx_status_date');
            $table->index(['start_date', 'end_date'], 'idx_date_range');
            $table->index('leave_type_id', 'idx_leave_type');
        });

        // Add indexes to leave_credits table
        Schema::table('leave_credits', function (Blueprint $table) {
            $table->index(['employee_id', 'year', 'leave_type_id'], 'idx_employee_year_type');
        });

        // Add indexes to performance_targets table
        Schema::table('performance_targets', function (Blueprint $table) {
            $table->index(['employee_id', 'period_id'], 'idx_employee_period');
        });

        // Add indexes to performance_periods table
        Schema::table('performance_periods', function (Blueprint $table) {
            $table->index(['year', 'semester'], 'idx_year_semester');
            $table->index('status', 'idx_period_status');
        });

        // Add indexes to employee_documents table
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->index(['employee_id', 'document_type'], 'idx_employee_doc_type');
            $table->index('upload_date', 'idx_upload_date');
        });

        // Add indexes to performance_ratings table
        Schema::table('performance_ratings', function (Blueprint $table) {
            $table->index(['target_id', 'final_rating'], 'idx_target_ratings');
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_employee_number');
            $table->dropIndex('idx_employee_email');
            $table->dropIndex('idx_department');
            $table->dropIndex('idx_position');
            $table->dropIndex('idx_employment_status');
            $table->dropIndex('idx_date_hired');
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropIndex('idx_employee_status');
            $table->dropIndex('idx_status_date');
            $table->dropIndex('idx_date_range');
            $table->dropIndex('idx_leave_type');
        });

        Schema::table('leave_credits', function (Blueprint $table) {
            $table->dropIndex('idx_employee_year_type');
        });

        Schema::table('performance_targets', function (Blueprint $table) {
            $table->dropIndex('idx_employee_period');
        });

        Schema::table('performance_periods', function (Blueprint $table) {
            $table->dropIndex('idx_year_semester');
            $table->dropIndex('idx_period_status');
        });

        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropIndex('idx_employee_doc_type');
            $table->dropIndex('idx_upload_date');
        });

        Schema::table('performance_ratings', function (Blueprint $table) {
            $table->dropIndex('idx_target_ratings');
        });
    }
};