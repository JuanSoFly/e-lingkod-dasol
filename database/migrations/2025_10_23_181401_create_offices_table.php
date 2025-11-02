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
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // Office code
            $table->string('name'); // Office name
            $table->text('description')->nullable(); // Office description
            $table->foreignId('parent_id')->nullable()->constrained('offices')->onDelete('cascade'); // For hierarchy
            $table->integer('level')->default(1); // Hierarchy level
            $table->string('head_title', 100)->nullable(); // Title of office head (e.g., "Department Head", "Chief")
            $table->foreignId('department_head_id')->nullable()->constrained('employees')->onDelete('set null'); // Current department head
            $table->string('contact_number', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('location', 200)->nullable(); // Office location
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional office data
            $table->timestamps();

            // Indexes
            $table->index(['code', 'is_active']);
            $table->index(['parent_id', 'level']);
            $table->index('department_head_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
