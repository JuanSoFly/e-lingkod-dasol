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
        Schema::create('office_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User assigned to office
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('set null'); // Employee record if applicable
            $table->foreignId('office_id')->constrained()->onDelete('cascade'); // Office assigned to
            $table->string('role', 50); // Role in office (Department Head, Assessor, Final Approver, Staff, etc.)
            $table->date('assigned_date'); // When assignment started
            $table->date('ended_date')->nullable(); // When assignment ended
            $table->boolean('is_active')->default(true); // Current active assignment
            $table->text('remarks')->nullable(); // Assignment remarks
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null'); // Who made the assignment
            $table->timestamps();

            // Indexes
            $table->unique(['user_id', 'office_id', 'assigned_date']); // One assignment per user per office per date
            $table->index(['user_id', 'is_active']);
            $table->index(['office_id', 'role', 'is_active']);
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_assignments');
    }
};
