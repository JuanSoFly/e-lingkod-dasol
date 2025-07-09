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
        Schema::table('employee_education', function (Blueprint $table) {
            // PDS Panel 3: Educational Background - Additional fields
            $table->string('degree_course')->nullable()->after('course');
            $table->year('period_from')->nullable()->after('degree_course');
            $table->year('period_to')->nullable()->after('period_from');
            $table->string('highest_level_units_earned')->nullable()->after('period_to');
            $table->year('year_graduated_pds')->nullable()->after('highest_level_units_earned');
            $table->text('scholarship_honors_received')->nullable()->after('year_graduated_pds');
            $table->string('attachment_id')->nullable()->after('scholarship_honors_received');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_education', function (Blueprint $table) {
            $table->dropColumn([
                'degree_course',
                'period_from',
                'period_to',
                'highest_level_units_earned',
                'year_graduated_pds',
                'scholarship_honors_received',
                'attachment_id'
            ]);
            $table->dropSoftDeletes();
        });
    }
};
