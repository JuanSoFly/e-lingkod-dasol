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
        Schema::create('employee_family_background', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            // Spouse Information
            $table->string('spouse_surname')->nullable();
            $table->string('spouse_first_name')->nullable();
            $table->string('spouse_middle_name')->nullable();
            $table->string('spouse_occupation')->nullable();
            $table->string('spouse_employer')->nullable();
            $table->text('spouse_business_address')->nullable();
            $table->string('spouse_telephone_no')->nullable();
            
            // Father Information
            $table->string('father_surname')->nullable();
            $table->string('father_first_name')->nullable();
            $table->string('father_middle_name')->nullable();
            
            // Mother Information
            $table->string('mother_maiden_name')->nullable();
            $table->string('mother_surname')->nullable();
            $table->string('mother_first_name')->nullable();
            $table->string('mother_middle_name')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_family_background');
    }
};
