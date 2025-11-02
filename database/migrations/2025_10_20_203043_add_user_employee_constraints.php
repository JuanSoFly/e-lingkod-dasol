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
        Schema::table('users', function (Blueprint $table) {
            // Add index for better performance on employee_id lookups
            $table->index('employee_id', 'users_employee_id_index');
        });

        Schema::table('employees', function (Blueprint $table) {
            // Add index for email field to improve search performance
            $table->index('email', 'employees_email_index');
        });

        // Note: We can't add a true unique constraint that allows NULL values
        // This will be handled at application level through validation
        // The foreign key constraint already provides referential integrity
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_employee_id_index');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_email_index');
        });
    }
};
