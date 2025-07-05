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
        Schema::create('document_approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document_type');
            $table->text('description')->nullable();
            $table->json('steps'); // Array of approval steps with roles/users
            $table->integer('default_deadline_days')->default(7);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_all_approvers')->default(false);
            $table->json('escalation_rules')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['document_type', 'is_active']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_approval_workflows');
    }
};