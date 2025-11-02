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
        // Add soft deletes to leave-related tables that don't have them
        Schema::table('leave_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_applications', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('leave_credits', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_credits', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('leave_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_cards', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('leave_card_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_card_entries', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('leave_application_workflow_steps', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_application_workflow_steps', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove soft deletes columns from leave-related tables
        Schema::table('leave_applications', function (Blueprint $table) {
            if (Schema::hasColumn('leave_applications', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('leave_credits', function (Blueprint $table) {
            if (Schema::hasColumn('leave_credits', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('leave_cards', function (Blueprint $table) {
            if (Schema::hasColumn('leave_cards', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('leave_card_entries', function (Blueprint $table) {
            if (Schema::hasColumn('leave_card_entries', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('leave_application_workflow_steps', function (Blueprint $table) {
            if (Schema::hasColumn('leave_application_workflow_steps', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
