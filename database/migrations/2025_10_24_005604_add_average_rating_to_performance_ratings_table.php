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
        Schema::table('performance_ratings', function (Blueprint $table) {
            $table->decimal('average_rating', 5, 2)->after('final_rating')->nullable();
        });

        // Calculate average_rating from existing ratings
        // Priority: final_rating > supervisor_rating > self_rating
        \DB::statement('
            UPDATE performance_ratings
            SET average_rating = CASE
                                   WHEN final_rating IS NOT NULL THEN final_rating
                                   WHEN supervisor_rating IS NOT NULL THEN supervisor_rating
                                   WHEN self_rating IS NOT NULL THEN self_rating
                                   ELSE NULL
                               END
            WHERE average_rating IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_ratings', function (Blueprint $table) {
            $table->dropColumn('average_rating');
        });
    }
};
