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
        Schema::create('ipcr_progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipcr_id')->constrained('ipcrs')->cascadeOnDelete();
            $table->foreignId('ipcr_item_id')->nullable()->constrained('ipcr_items')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->date('progress_date');
            $table->string('status', 50)->default('on_track');
            $table->text('accomplishments')->nullable();
            $table->text('challenges')->nullable();
            $table->text('next_steps')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['ipcr_id', 'progress_date']);
            $table->index(['status', 'progress_date']);
        });

        Schema::create('ipcr_coaching_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipcr_id')->constrained('ipcrs')->cascadeOnDelete();
            $table->foreignId('coach_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('participant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('session_date');
            $table->string('session_type', 100)->default('coaching');
            $table->string('focus_area')->nullable();
            $table->text('discussion_notes')->nullable();
            $table->text('agreements')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['ipcr_id', 'session_date']);
            $table->index(['coach_user_id', 'session_date']);
        });

        Schema::create('ipcr_development_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipcr_id')->constrained('ipcrs')->cascadeOnDelete();
            $table->string('focus_area');
            $table->string('action_item');
            $table->date('target_date')->nullable();
            $table->string('status', 50)->default('planned');
            $table->text('support_needed')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['ipcr_id', 'status']);
            $table->index(['target_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ipcr_development_actions');
        Schema::dropIfExists('ipcr_coaching_sessions');
        Schema::dropIfExists('ipcr_progress_updates');
    }
};
