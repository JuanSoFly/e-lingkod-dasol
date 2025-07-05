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
        Schema::create('performance_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_id')->constrained('performance_targets')->onDelete('cascade');
            $table->integer('self_rating');
            $table->integer('supervisor_rating')->nullable();
            $table->integer('final_rating')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_ratings');
    }
};
