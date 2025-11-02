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
            $table->boolean('is_active')->default(true)->after('status');
        });

        // Set existing records as active by default
        DB::statement('UPDATE performance_periods SET is_active = 1 WHERE is_active IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_periods', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
