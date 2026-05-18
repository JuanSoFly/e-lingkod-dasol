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
        Schema::table('performance_ratings', function (Blueprint $table) {
            // Add success_indicator_id column after target_id for logical grouping
            $table->unsignedBigInteger('success_indicator_id')->nullable()->after('target_id');

            // Add foreign key constraint
            $table->foreign('success_indicator_id')
                  ->references('id')
                  ->on('success_indicators')
                  ->onDelete('set null');

            // Add index for performance optimization
            $table->index('success_indicator_id', 'idx_performance_ratings_success_indicator_id');
        });

        // Populate success_indicator_id values based on existing target relationships
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('
                UPDATE performance_ratings pr
                SET success_indicator_id = pt.success_indicator_id
                FROM performance_targets pt
                WHERE pr.target_id = pt.id
                  AND pr.success_indicator_id IS NULL
            ');
        } else {
            DB::statement('
                UPDATE performance_ratings pr
                JOIN performance_targets pt ON pr.target_id = pt.id
                SET pr.success_indicator_id = pt.success_indicator_id
                WHERE pr.success_indicator_id IS NULL
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_ratings', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['success_indicator_id']);

            // Drop index
            $table->dropIndex('idx_performance_ratings_success_indicator_id');

            // Drop column
            $table->dropColumn('success_indicator_id');
        });
    }
};
