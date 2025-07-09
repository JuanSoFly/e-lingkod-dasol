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
        Schema::table('employee_trainings', function (Blueprint $table) {
            // PDS Panel 7 specific fields
            $table->string('training_title')->nullable()->after('learning_analytics');
            $table->date('inclusive_date_from')->nullable()->after('training_title');
            $table->date('inclusive_date_to')->nullable()->after('inclusive_date_from');
            $table->decimal('number_of_hours', 8, 2)->nullable()->after('inclusive_date_to');
            $table->enum('type_of_ld', ['Managerial', 'Supervisory', 'Technical', 'Professional', 'Foundational', 'Other'])->nullable()->after('number_of_hours');
            $table->string('conducted_sponsored_by')->nullable()->after('type_of_ld');
            $table->string('attachment_id')->nullable()->after('conducted_sponsored_by');
            
            // Add indexes for better performance
            $table->index('inclusive_date_from');
            $table->index('inclusive_date_to');
            $table->index('type_of_ld');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_trainings', function (Blueprint $table) {
            // Drop PDS Panel 7 specific fields
            $table->dropColumn([
                'training_title',
                'inclusive_date_from',
                'inclusive_date_to',
                'number_of_hours',
                'type_of_ld',
                'conducted_sponsored_by',
                'attachment_id'
            ]);
        });
    }
};
