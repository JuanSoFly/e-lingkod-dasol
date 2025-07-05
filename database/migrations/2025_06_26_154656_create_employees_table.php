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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('birth_date');
            $table->string('gender');
            $table->string('civil_status');
            $table->text('address');
            $table->string('contact_number');
            $table->string('email')->unique();
            $table->string('position');
            $table->string('department');
            $table->string('employment_status');
            $table->date('date_hired');
            $table->integer('salary_grade');
            $table->integer('step_increment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
