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
        // Insert CY 2024 period (completed year for historical comparison)
        DB::table('performance_periods')->insert([
            'office_id' => null,
            'is_opcr_period' => 1,
            'year' => 2024,
            'semester' => 'Annual',
            'name' => 'CY 2024',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'completed',
            'is_active' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Update current CY 2025 period status to active (currently inactive)
        DB::table('performance_periods')
            ->where('name', 'CY 2025')
            ->update([
                'is_active' => 1,
                'status' => 'active',
                'updated_at' => now()
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove CY 2024 period
        DB::table('performance_periods')
            ->where('name', 'CY 2024')
            ->delete();

        // Ensure CY 2025 remains active
        DB::table('performance_periods')
            ->where('name', 'CY 2025')
            ->update(['is_active' => true]);
    }
};
