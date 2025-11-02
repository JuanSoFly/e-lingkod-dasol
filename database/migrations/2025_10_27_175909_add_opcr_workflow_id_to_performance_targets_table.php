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
            $table->foreignId('opcr_workflow_id')->nullable()->after('office_id')->constrained('opcr_workflows')->onDelete('set null');
            $table->index('opcr_workflow_id', 'pt_opcr_workflow_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_targets', function (Blueprint $table) {
            $table->dropForeign(['opcr_workflow_id']);
            $table->dropIndex('pt_opcr_workflow_index');
            $table->dropColumn('opcr_workflow_id');
        });
    }
};
