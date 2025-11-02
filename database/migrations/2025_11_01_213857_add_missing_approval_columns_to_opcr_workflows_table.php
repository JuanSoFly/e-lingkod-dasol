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
        Schema::table('opcr_workflows', function (Blueprint $table) {
            $table->enum('approval_status', ['approved', 'returned', 'rejected'])->nullable()->after('overall_adjectival_rating');
            $table->decimal('final_rating_override', 3, 2)->nullable()->after('approval_status');
            $table->string('performance_level', 50)->nullable()->after('final_rating_override');
            $table->text('rating_override_justification')->nullable()->after('performance_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opcr_workflows', function (Blueprint $table) {
            $table->dropColumn([
                'approval_status',
                'final_rating_override',
                'performance_level',
                'rating_override_justification'
            ]);
        });
    }
};
