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
        Schema::create('employee_other_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            // PDS Panel 8: Other Information (3 types)
            $table->enum('information_type', ['special_skills', 'distinctions', 'memberships']);
            $table->text('description');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('employee_id');
            $table->index('information_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_other_information');
    }
};
