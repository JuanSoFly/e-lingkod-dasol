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
            // Add composite unique index to prevent duplicate active assignments by user_id
            // This prevents the same user from having multiple active assignments for the same office and role
            $table->unique(['user_id', 'office_id', 'role', 'is_active', 'ended_date'], 'office_assignments_unique_user_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('office_assignments', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('office_assignments_unique_user_active');
        });
    }
};
