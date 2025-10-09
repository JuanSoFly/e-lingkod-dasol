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
            // CSC Form No. 212 - Page 4 Government ID Section
            $table->string('gov_id_type')->nullable()->comment('Type of government ID (PSA, GSIS, PNP, etc.)');
            $table->string('gov_id_number')->nullable()->comment('Government ID number');
            $table->date('gov_id_date_issued')->nullable()->comment('Date of government ID issuance');
            $table->string('gov_id_place_issued')->nullable()->comment('Place where government ID was issued');

            // Add indexes for better performance
            $table->index(['gov_id_type']);
            $table->index(['gov_id_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'gov_id_type',
                'gov_id_number',
                'gov_id_date_issued',
                'gov_id_place_issued',
            ]);
        });
    }
};