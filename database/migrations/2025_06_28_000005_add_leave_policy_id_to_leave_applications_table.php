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
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->foreignId('leave_policy_id')->nullable()->after('leave_type_id')->constrained()->onDelete('set null');
            $table->index('leave_policy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropForeign(['leave_policy_id']);
            $table->dropIndex(['leave_policy_id']);
            $table->dropColumn('leave_policy_id');
        });
    }
};