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
        Schema::table('employees', function (Blueprint $table) {
            // Index for name searches
            $table->index(['first_name', 'last_name']);
            // Index for PDS common lookups
            $table->index(['id', 'email']);
        });
        
        // Add more indexes as needed based on specific slow queries found
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['first_name', 'last_name']);
            $table->dropIndex(['id', 'email']);
        });
    }
};
