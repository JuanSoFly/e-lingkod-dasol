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
        Schema::table('employee_references', function (Blueprint $table) {
            // CSC Form No. 212 - Reference ordering (3 references required)
            $table->tinyInteger('reference_order')->default(1)->comment('Reference order (1-3) for CSC Form compliance');

            // Add unique constraint to prevent duplicate orders per employee
            $table->unique(['employee_id', 'reference_order'], 'unique_employee_reference_order');

            // Add index for better performance
            $table->index(['employee_id', 'reference_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_references', function (Blueprint $table) {
            $table->dropUnique('unique_employee_reference_order');
            $table->dropIndex(['employee_id', 'reference_order']);
            $table->dropColumn('reference_order');
        });
    }
};