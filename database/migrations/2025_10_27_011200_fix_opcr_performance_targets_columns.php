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
            // MFO hierarchy columns
            if (!Schema::hasColumn('performance_targets', 'mfo_id')) {
                $table->foreignId('mfo_id')->nullable()->after('id')->constrained('major_final_outputs')->onDelete('set null');
            }
            if (!Schema::hasColumn('performance_targets', 'success_indicator_id')) {
                $table->foreignId('success_indicator_id')->nullable()->after('mfo_id')->constrained('success_indicators')->onDelete('set null');
            }
            if (!Schema::hasColumn('performance_targets', 'mfo_code')) {
                $table->string('mfo_code', 50)->nullable()->after('success_indicator_id'); // Legacy compatibility
            }
            if (!Schema::hasColumn('performance_targets', 'si_code')) {
                $table->string('si_code', 50)->nullable()->after('mfo_code'); // Legacy compatibility
            }

            // Office relationship
            if (!Schema::hasColumn('performance_targets', 'office_id')) {
                $table->foreignId('office_id')->nullable()->after('period_id')->constrained()->onDelete('set null');
            }

            // Enhanced target structure
            if (!Schema::hasColumn('performance_targets', 'target_quality')) {
                $table->decimal('target_quality', 10, 2)->nullable()->after('weight'); // QET target quality
            }
            if (!Schema::hasColumn('performance_targets', 'target_efficiency')) {
                $table->string('target_efficiency', 100)->nullable()->after('target_quality'); // QET target efficiency
            }
            if (!Schema::hasColumn('performance_targets', 'target_timeliness')) {
                $table->string('target_timeliness', 100)->nullable()->after('target_efficiency'); // QET target timeliness
            }

            // Legacy compatibility flag
            if (!Schema::hasColumn('performance_targets', 'is_legacy_ipcr')) {
                $table->boolean('is_legacy_ipcr')->default(false)->after('success_indicator');
            }

            // Indexes
            $table->index(['mfo_id', 'success_indicator_id'], 'pt_mfo_si_index');
            $table->index(['office_id', 'period_id'], 'pt_office_period_index');
            $table->index(['mfo_code', 'si_code'], 'pt_mfo_code_si_index');
            $table->index('is_legacy_ipcr', 'pt_legacy_ipcr_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_targets', function (Blueprint $table) {
            $table->dropForeign(['mfo_id']);
            $table->dropForeign(['success_indicator_id']);
            $table->dropForeign(['office_id']);
            $table->dropColumn([
                'mfo_id',
                'success_indicator_id',
                'mfo_code',
                'si_code',
                'office_id',
                'target_quality',
                'target_efficiency',
                'target_timeliness',
                'is_legacy_ipcr'
            ]);
        });
    }
};
