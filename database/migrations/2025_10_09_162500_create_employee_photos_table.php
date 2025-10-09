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
        Schema::create('employee_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            // CSC Form No. 212 - Page 4 Photo Section (3.5cm × 4.5cm)
            $table->string('photo_path')->nullable()->comment('Path to employee photo file');
            $table->integer('photo_size')->nullable()->comment('Photo file size in bytes');
            $table->string('photo_format')->nullable()->comment('Photo file format (jpg, png)');
            $table->datetime('photo_taken_date')->nullable()->comment('Date when photo was taken');

            // CSC Form No. 212 - Page 4 Thumbmark Section
            $table->string('thumbmark_path')->nullable()->comment('Path to thumbmark file');
            $table->integer('thumbmark_size')->nullable()->comment('Thumbmark file size in bytes');
            $table->string('thumbmark_format')->nullable()->comment('Thumbmark file format (jpg, png)');
            $table->datetime('thumbmark_taken_date')->nullable()->comment('Date when thumbmark was taken');

            // Status fields
            $table->boolean('is_active')->default(true)->comment('Whether this photo is the current active one');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('employee_id');
            $table->index(['employee_id', 'is_active']);

            // Ensure only one active photo per employee
            $table->unique(['employee_id', 'is_active'], 'unique_employee_active_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_photos');
    }
};