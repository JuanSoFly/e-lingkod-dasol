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
        Schema::table('performance_ratings', function (Blueprint $table) {
            // QET rating columns
            $table->integer('rating_quality')->nullable()->after('remarks'); // QET quality rating (1-5)
            $table->integer('rating_efficiency')->nullable()->after('rating_quality'); // EET efficiency rating (1-5)
            $table->integer('rating_timeliness')->nullable()->after('rating_efficiency'); // TET timeliness rating (1-5)
            $table->decimal('average_qet_rating', 3, 2)->nullable()->after('rating_timeliness'); // Average QET rating
            $table->string('adjectival_rating', 50)->nullable()->after('average_qet_rating'); // Adjectival rating

            // Accomplishment tracking
            $table->decimal('accomplished_quality', 10, 2)->nullable()->after('final_rating');
            $table->string('accomplished_efficiency', 100)->nullable()->after('accomplished_quality');
            $table->string('accomplished_timeliness', 100)->nullable()->after('accomplished_efficiency');

            // Rating workflow
            $table->foreignId('assessed_by')->nullable()->after('adjectival_rating')->constrained('users')->onDelete('set null');
            $table->timestamp('assessed_at')->nullable()->after('assessed_by');
            $table->foreignId('approved_by')->nullable()->after('assessed_at')->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            // Office relationship
            $table->foreignId('office_id')->nullable()->after('target_id')->constrained()->onDelete('set null');

            // Legacy compatibility flag
            $table->boolean('is_legacy_ipcr')->default(false)->after('approved_at');

            // Additional fields
            $table->text('assessor_remarks')->nullable()->after('remarks');
            $table->text('approver_remarks')->nullable()->after('assessor_remarks');
            $table->json('evidence_documents')->nullable()->after('approver_remarks'); // Document IDs

            // Indexes
            $table->index(['rating_quality', 'rating_efficiency', 'rating_timeliness'], 'pr_ratings_qet_index');
            $table->index('average_qet_rating');
            $table->index('adjectival_rating');
            $table->index(['office_id', 'assessed_by'], 'pr_ratings_office_assessor_index');
            $table->index('is_legacy_ipcr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_ratings', function (Blueprint $table) {
            $table->dropForeign(['office_id']);
            $table->dropForeign(['assessed_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'rating_quality',
                'rating_efficiency',
                'rating_timeliness',
                'average_qet_rating',
                'adjectival_rating',
                'accomplished_quality',
                'accomplished_efficiency',
                'accomplished_timeliness',
                'assessed_by',
                'assessed_at',
                'approved_by',
                'approved_at',
                'office_id',
                'is_legacy_ipcr',
                'assessor_remarks',
                'approver_remarks',
                'evidence_documents'
            ]);
        });
    }
};
