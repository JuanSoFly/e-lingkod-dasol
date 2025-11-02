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
        Schema::create('major_final_outputs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // MFO code like MFO-001
            $table->string('title'); // MFO title
            $table->text('description')->nullable(); // MFO description
            $table->foreignId('parent_id')->nullable()->constrained('major_final_outputs')->onDelete('cascade'); // For hierarchy
            $table->integer('level')->default(1); // Hierarchy level
            $table->boolean('is_active')->default(true); // Active status
            $table->foreignId('office_id')->nullable()->constrained()->onDelete('set null'); // Office assignment
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();

            // Indexes
            $table->index(['code', 'is_active']);
            $table->index(['parent_id', 'level']);
            $table->index('office_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('major_final_outputs');
    }
};
