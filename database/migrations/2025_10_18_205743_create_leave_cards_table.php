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
        Schema::create('leave_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->year('year');
            $table->decimal('vl_balance', 5, 2)->default(0);
            $table->decimal('sl_balance', 5, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->date('last_updated');
            $table->timestamps();

            $table->unique(['employee_id', 'year']);
            $table->index(['employee_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_cards');
    }
};
