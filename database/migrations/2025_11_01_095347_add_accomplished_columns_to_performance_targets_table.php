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
        Schema::table('performance_targets', function (Blueprint $table) {
            // Add accomplished columns for OPCR evaluation
            if (!Schema::hasColumn('performance_targets', 'accomplished_quantity')) {
                $table->decimal('accomplished_quantity', 10, 2)->nullable()->after('is_target_met');
            }
            if (!Schema::hasColumn('performance_targets', 'accomplished_efficiency')) {
                $table->string('accomplished_efficiency', 100)->nullable()->after('accomplished_quantity');
            }
            if (!Schema::hasColumn('performance_targets', 'accomplished_timeliness')) {
                $table->string('accomplished_timeliness', 100)->nullable()->after('accomplished_efficiency');
            }
            if (!Schema::hasColumn('performance_targets', 'performance_percentage')) {
                $table->decimal('performance_percentage', 5, 2)->nullable()->after('accomplished_timeliness');
            }

            // Add index for performance queries
            $table->index('performance_percentage', 'pt_performance_percentage_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_targets', function (Blueprint $table) {
            $table->dropColumn([
                'accomplished_quantity',
                'accomplished_efficiency',
                'accomplished_timeliness',
                'performance_percentage'
            ]);
        });
    }
};
