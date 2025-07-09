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
        Schema::create('employee_voluntary_work', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            // PDS Panel 6: Voluntary Work
            $table->text('organization_name_address');
            $table->date('inclusive_date_from');
            $table->date('inclusive_date_to')->nullable();
            $table->integer('number_of_hours');
            $table->text('position_nature_of_work');
            
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
        Schema::dropIfExists('employee_voluntary_work');
    }
};
