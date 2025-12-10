<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opcr_workflows', function (Blueprint $table) {
            // Planning review
            $table->foreignId('planning_reviewer_id')->nullable()->after('submitted_at')->constrained('users');
            $table->timestamp('planning_reviewed_at')->nullable()->after('planning_reviewer_id');
            $table->text('planning_remarks')->nullable()->after('planning_reviewed_at');

            // PMT review
            $table->foreignId('pmt_recommender_id')->nullable()->after('planning_remarks')->constrained('users');
            $table->timestamp('pmt_recommended_at')->nullable()->after('pmt_recommender_id');
            $table->text('pmt_remarks')->nullable()->after('pmt_recommended_at');

            // Source-aware returns and HRMO override
            $table->string('return_source', 50)->nullable()->after('return_reason');
            $table->boolean('hrmo_override')->default(false)->after('return_source');
            $table->text('hrmo_override_reason')->nullable()->after('hrmo_override');
        });

        Schema::table('performance_periods', function (Blueprint $table) {
            $table->date('planning_deadline')->nullable()->after('is_active');
            $table->date('pmt_deadline')->nullable()->after('planning_deadline');
            $table->date('lce_deadline')->nullable()->after('pmt_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('opcr_workflows', function (Blueprint $table) {
            $table->dropForeign(['planning_reviewer_id']);
            $table->dropForeign(['pmt_recommender_id']);

            $table->dropColumn([
                'planning_reviewer_id',
                'planning_reviewed_at',
                'planning_remarks',
                'pmt_recommender_id',
                'pmt_recommended_at',
                'pmt_remarks',
                'return_source',
                'hrmo_override',
                'hrmo_override_reason',
            ]);
        });

        Schema::table('performance_periods', function (Blueprint $table) {
            $table->dropColumn([
                'planning_deadline',
                'pmt_deadline',
                'lce_deadline',
            ]);
        });
    }
};
