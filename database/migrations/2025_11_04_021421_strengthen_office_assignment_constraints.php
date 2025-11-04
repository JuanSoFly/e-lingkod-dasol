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
        Schema::table('office_assignments', function (Blueprint $table) {
            // Drop the existing weak unique constraint
            $table->dropUnique(['user_id', 'office_id', 'assigned_date']);

            // Add stronger unique constraints to prevent overlapping active assignments
            $table->unique(['employee_id', 'office_id', 'is_active'], 'unique_active_employee_office');
            $table->unique(['employee_id', 'office_id', 'is_active', 'ended_date'], 'unique_employee_office_assignment');

            // Add indexes for better performance
            $table->index(['employee_id', 'is_active']);
            $table->index(['office_id', 'is_active']);
            $table->index(['employee_id', 'office_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('office_assignments', function (Blueprint $table) {
            // Drop the new unique constraints
            $table->dropUnique('unique_active_employee_office');
            $table->dropUnique('unique_employee_office_assignment');

            // Drop the new indexes
            $table->dropIndex(['employee_id', 'is_active']);
            $table->dropIndex(['office_id', 'is_active']);
            $table->dropIndex(['employee_id', 'office_id', 'is_active']);

            // Restore the original weak constraint
            $table->unique(['user_id', 'office_id', 'assigned_date']);
        });
    }
};
