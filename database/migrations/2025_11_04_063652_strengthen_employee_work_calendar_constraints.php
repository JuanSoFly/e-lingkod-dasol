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
        // First, update any existing NULL work_calendar_id to default (Mon-Fri)
        DB::statement('UPDATE employees SET work_calendar_id = 1 WHERE work_calendar_id IS NULL');

        Schema::table('employees', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign('employees_work_calendar_id_foreign');

            // Make the column NOT NULL
            $table->unsignedBigInteger('work_calendar_id')->nullable(false)->change();

            // Re-add foreign key constraint without SET NULL
            $table->foreign('work_calendar_id')->references('id')->on('work_calendars')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign('work_calendar_id');

            // Make work_calendar_id nullable again
            $table->unsignedBigInteger('work_calendar_id')->nullable()->change();

            // Re-add foreign key constraint with SET NULL
            $table->foreign('work_calendar_id')->references('id')->on('work_calendars')->onDelete('set null');
        });
    }
};
