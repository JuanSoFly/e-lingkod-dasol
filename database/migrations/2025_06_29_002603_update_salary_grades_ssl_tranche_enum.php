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
        // Update the ssl_tranche enum to include Tranche 5
        DB::statement("ALTER TABLE salary_grades MODIFY COLUMN ssl_tranche ENUM('Tranche 1', 'Tranche 2', 'Tranche 3', 'Tranche 4', 'Tranche 5') DEFAULT 'Tranche 4' COMMENT 'SSL (Salary Standardization Law) implementation'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum (only if no Tranche 5 records exist)
        DB::statement("ALTER TABLE salary_grades MODIFY COLUMN ssl_tranche ENUM('Tranche 1', 'Tranche 2', 'Tranche 3', 'Tranche 4') DEFAULT 'Tranche 4' COMMENT 'SSL (Salary Standardization Law) implementation'");
    }
};