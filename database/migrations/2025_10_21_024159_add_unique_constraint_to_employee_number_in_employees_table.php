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
        // First, handle any existing duplicates by renaming them
        $duplicates = DB::select('
            SELECT employee_number, COUNT(*) as count
            FROM employees
            GROUP BY employee_number
            HAVING COUNT(*) > 1
        ');

        foreach ($duplicates as $duplicate) {
            $employees = DB::table('employees')
                ->where('employee_number', $duplicate->employee_number)
                ->orderBy('id')
                ->get();

            // Keep the first one as is, rename the rest
            $first = true;
            foreach ($employees as $employee) {
                if (!$first) {
                    $newNumber = $duplicate->employee_number . '-' . $employee->id;
                    DB::table('employees')
                        ->where('id', $employee->id)
                        ->update(['employee_number' => $newNumber]);
                }
                $first = false;
            }
        }

        // Now add the unique constraint
        Schema::table('employees', function (Blueprint $table) {
            $table->unique('employee_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['employee_number']);
        });
    }
};
