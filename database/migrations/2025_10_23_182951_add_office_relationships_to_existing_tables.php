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
        // Add office relationship to employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('office_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->string('office_code', 50)->nullable()->after('office_id'); // Legacy compatibility
            $table->boolean('is_department_head')->default(false)->after('office_code');
            $table->index(['office_id', 'is_department_head']);
            $table->index('office_code');
        });

        // Add office relationship to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('office_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->string('office_role', 50)->nullable()->after('office_id'); // Role in office context
            $table->index(['office_id', 'office_role']);
        });

        // Add office relationship to performance_periods table
        Schema::table('performance_periods', function (Blueprint $table) {
            $table->foreignId('office_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->boolean('is_opcr_period')->default(false)->after('office_id'); // OPCR-specific period
            $table->index(['office_id', 'is_opcr_period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['office_id']);
            $table->dropColumn(['office_id', 'office_code', 'is_department_head']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['office_id']);
            $table->dropColumn(['office_id', 'office_role']);
        });

        Schema::table('performance_periods', function (Blueprint $table) {
            $table->dropForeign(['office_id']);
            $table->dropColumn(['office_id', 'is_opcr_period']);
        });
    }
};
