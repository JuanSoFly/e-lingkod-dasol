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
        Schema::create('document_approval_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('document_approval_requests')->cascadeOnDelete();
            $table->foreignId('step_id')->nullable()->constrained('document_approval_steps')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_system_generated')->default(false);
            $table->json('metadata')->nullable(); // Additional comment metadata
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['request_id', 'created_at'], 'da_comments_request_date_idx');
            $table->index(['step_id', 'created_at'], 'da_comments_step_date_idx');
            $table->index(['user_id', 'created_at'], 'da_comments_user_date_idx');
            $table->index('is_internal', 'da_comments_internal_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_approval_comments');
    }
};