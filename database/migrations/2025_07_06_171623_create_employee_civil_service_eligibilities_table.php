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
        Schema::create('employee_civil_service_eligibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            // PDS Panel 4: Civil Service Eligibility
            $table->string('eligibility_name');
            $table->decimal('rating', 5, 2)->nullable();
            $table->date('date_of_examination')->nullable();
            $table->string('place_of_examination')->nullable();
            $table->string('license_number')->nullable();
            $table->date('date_of_validity')->nullable();
            
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
        Schema::dropIfExists('employee_civil_service_eligibilities');
    }
};
