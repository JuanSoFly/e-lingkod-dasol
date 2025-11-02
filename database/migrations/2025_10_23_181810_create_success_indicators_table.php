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
        Schema::create('success_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mfo_id')->constrained('major_final_outputs')->onDelete('cascade'); // Parent MFO
            $table->string('code', 50); // SI code like SI-001
            $table->string('title'); // Success Indicator title
            $table->text('description')->nullable(); // Description
            $table->decimal('target_quantity', 10, 2)->nullable(); // Target quantity
            $table->string('target_efficiency', 100)->nullable(); // Target efficiency (e.g., "100%", "Excellent")
            $table->string('target_timeliness', 100)->nullable(); // Target timeliness (e.g., "On time", "Within deadline")
            $table->decimal('accomplished_quantity', 10, 2)->nullable(); // Accomplished quantity
            $table->string('accomplished_efficiency', 100)->nullable(); // Accomplished efficiency
            $table->string('accomplished_timeliness', 100)->nullable(); // Accomplished timeliness
            $table->integer('rating_quantity')->nullable(); // QET rating (1-5)
            $table->integer('rating_efficiency')->nullable(); // EET rating (1-5)
            $table->integer('rating_timeliness')->nullable(); // TET rating (1-5)
            $table->decimal('average_rating', 3, 2)->nullable(); // Average QET rating
            $table->string('adjectival_rating', 50)->nullable(); // Adjectival rating (Outstanding, Very Satisfactory, etc.)
            $table->text('remarks')->nullable(); // Remarks/justification
            $table->json('evidence_documents')->nullable(); // Evidence document IDs
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->unique(['mfo_id', 'code']); // Unique within MFO
            $table->index(['mfo_id', 'is_active']);
            $table->index('average_rating');
            $table->index('adjectival_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('success_indicators');
    }
};
