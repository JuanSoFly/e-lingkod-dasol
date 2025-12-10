<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_workflow_steps', function (Blueprint $table) {
            $table->unsignedInteger('sla_working_days')->nullable()->after('escalation_to');
            $table->boolean('is_recommendation')->default(false)->after('sla_working_days');
        });
    }

    public function down(): void
    {
        Schema::table('leave_workflow_steps', function (Blueprint $table) {
            $table->dropColumn(['sla_working_days', 'is_recommendation']);
        });
    }
};
