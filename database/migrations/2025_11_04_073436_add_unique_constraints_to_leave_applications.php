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
        Schema::table('leave_applications', function (Blueprint $table) {
            // Add index for efficient queries on employee applications (if it doesn't exist)
            if (!Schema::hasIndex('leave_applications', 'idx_employee_status_created')) {
                $table->index(['employee_id', 'status', 'created_at'], 'idx_employee_status_created');
            }
        });

        // For MySQL, we'll use a simpler approach with a trigger or application-level constraint
        // Since the application already validates this, we'll focus on the index for performance
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            // Drop the index if it exists
            $table->dropIndexIfExists('idx_employee_status_created');
        });
    }
};
