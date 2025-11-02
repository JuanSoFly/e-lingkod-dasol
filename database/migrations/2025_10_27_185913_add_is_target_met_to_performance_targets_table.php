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
            $table->boolean('is_target_met')->nullable()->after('is_legacy_ipcr');
            $table->index('is_target_met');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_targets', function (Blueprint $table) {
            $table->dropIndex(['is_target_met']);
            $table->dropColumn('is_target_met');
        });
    }
};
