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
        Schema::table('employee_work_experience', function (Blueprint $table) {
            // Make original fields nullable to accommodate PDS system
            $table->string('position')->nullable()->change();
            $table->string('company')->nullable()->change();
            $table->date('from_date')->nullable()->change();
            $table->date('to_date')->nullable()->change();
            $table->decimal('salary', 10, 2)->nullable()->change();
            $table->string('status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_work_experience', function (Blueprint $table) {
            // Revert to NOT NULL (note: this may fail if there are existing NULL values)
            $table->string('position')->nullable(false)->change();
            $table->string('company')->nullable(false)->change();
            $table->date('from_date')->nullable(false)->change();
            $table->date('to_date')->nullable(false)->change();
            $table->decimal('salary', 10, 2)->nullable(false)->change();
            $table->string('status')->nullable(false)->change();
        });
    }
};
