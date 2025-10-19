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
        Schema::create('leave_card_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_card_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_application_id')->nullable()->constrained()->onDelete('set null');
            $table->string('leave_type_code'); // VL, SL, SPL, FL, etc.
            $table->date('date');
            $table->decimal('days', 3, 1)->default(0);
            $table->decimal('vl_balance_after', 5, 2)->nullable();
            $table->decimal('sl_balance_after', 5, 2)->nullable();
            $table->text('remarks');
            $table->string('entry_type'); // 'deduction', 'credit', 'adjustment'
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['leave_card_id', 'date']);
            $table->index('leave_application_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_card_entries');
    }
};
