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
            $table->foreignId('mfo_id')->nullable()->after('id')->constrained('major_final_outputs')->onDelete('set null');
            $table->foreignId('success_indicator_id')->nullable()->after('mfo_id')->constrained('success_indicators')->onDelete('set null');
            $table->string('mfo_code', 50)->nullable()->after('success_indicator_id'); // Legacy compatibility
            $table->string('si_code', 50)->nullable()->after('mfo_code'); // Legacy compatibility

            // Office relationship
            $table->foreignId('office_id')->nullable()->after('period_id')->constrained()->onDelete('set null');

            // Enhanced target structure
            $table->decimal('target_quantity', 10, 2)->nullable()->after('weight'); // QET target quantity
            $table->string('target_efficiency', 100)->nullable()->after('target_quantity'); // QET target efficiency
            $table->string('target_timeliness', 100)->nullable()->after('target_efficiency'); // QET target timeliness

            // Legacy compatibility flag
            $table->boolean('is_legacy_ipcr')->default(false)->after('success_indicator');

            // Indexes
            $table->index(['mfo_id', 'success_indicator_id']);
            $table->index(['office_id', 'period_id']);
            $table->index(['mfo_code', 'si_code']);
            $table->index('is_legacy_ipcr');
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
                'target_quantity',
                'target_efficiency',
                'target_timeliness',
                'is_legacy_ipcr'
            ]);
        });
    }
};
