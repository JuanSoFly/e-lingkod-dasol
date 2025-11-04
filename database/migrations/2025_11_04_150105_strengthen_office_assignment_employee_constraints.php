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
            // Add constraint to prevent duplicate active user assignments per office
            // This ensures each user can only have one active assignment per office
            $table->unique(['user_id', 'office_id', 'is_active'], 'office_assignments_unique_user_office_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('office_assignments', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('office_assignments_unique_user_office_active');
        });
    }
};
