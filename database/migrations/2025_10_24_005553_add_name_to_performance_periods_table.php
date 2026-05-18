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
        Schema::table('performance_periods', function (Blueprint $table) {
            $table->string('name')->after('semester')->nullable();
        });

        // Migrate existing year + semester combinations to name format
        \DB::statement(<<<'SQL'
            UPDATE performance_periods
            SET name = CONCAT(year, ' - ',
                         CASE
                             WHEN semester = '1st' THEN '1st Semester'
                             WHEN semester = '2nd' THEN '2nd Semester'
                             ELSE semester
                         END)
            WHERE name IS NULL
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_periods', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
