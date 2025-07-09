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
        Schema::table('employee_education', function (Blueprint $table) {
            // Make the legacy year_graduated field nullable to avoid conflicts
            $table->string('year_graduated')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_education', function (Blueprint $table) {
            // Revert the change
            $table->string('year_graduated')->nullable(false)->change();
        });
    }
};
