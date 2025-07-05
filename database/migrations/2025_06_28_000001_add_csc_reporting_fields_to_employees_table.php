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
            // CSC Reporting fields
            $table->string('appointment_type')->nullable()->after('employment_status');
            $table->date('appointment_date')->nullable()->after('appointment_type');
            $table->string('separation_type')->nullable()->after('appointment_date');
            $table->date('separation_date')->nullable()->after('separation_type');
            $table->text('separation_reason')->nullable()->after('separation_date');
            $table->string('csc_eligibility')->nullable()->after('separation_reason');
            $table->date('csc_eligibility_date')->nullable()->after('csc_eligibility');
            $table->string('place_of_birth')->nullable()->after('csc_eligibility_date');
            $table->string('citizenship')->nullable()->after('place_of_birth');
            $table->string('religion')->nullable()->after('citizenship');
            $table->string('height')->nullable()->after('religion');
            $table->string('weight')->nullable()->after('height');
            $table->string('blood_type')->nullable()->after('weight');
            $table->string('tin_number')->nullable()->after('blood_type');
            $table->string('sss_number')->nullable()->after('tin_number');
            $table->string('pagibig_number')->nullable()->after('sss_number');
            $table->string('philhealth_number')->nullable()->after('pagibig_number');
            $table->string('gsis_number')->nullable()->after('philhealth_number');
            
            // Performance tracking for IGHR
            $table->decimal('latest_performance_rating', 3, 2)->nullable()->after('gsis_number');
            $table->date('latest_performance_date')->nullable()->after('latest_performance_rating');
            
            // Training and development
            $table->integer('training_hours_ytd')->default(0)->after('latest_performance_date');
            $table->date('last_promotion_date')->nullable()->after('training_hours_ytd');
            $table->string('previous_position')->nullable()->after('last_promotion_date');
            
            // Leave and attendance tracking
            $table->date('last_attendance_date')->nullable()->after('previous_position');
            $table->integer('consecutive_absent_days')->default(0)->after('last_attendance_date');
            $table->boolean('is_awol')->default(false)->after('consecutive_absent_days');
            $table->date('awol_start_date')->nullable()->after('is_awol');
            
            // Additional CSC required fields
            $table->string('spouse_name')->nullable()->after('awol_start_date');
            $table->string('spouse_occupation')->nullable()->after('spouse_name');
            $table->string('emergency_contact_name')->nullable()->after('spouse_occupation');
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_number')->nullable()->after('emergency_contact_relationship');
            $table->text('emergency_contact_address')->nullable()->after('emergency_contact_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'appointment_type',
                'appointment_date',
                'separation_type',
                'separation_date',
                'separation_reason',
                'csc_eligibility',
                'csc_eligibility_date',
                'place_of_birth',
                'citizenship',
                'religion',
                'height',
                'weight',
                'blood_type',
                'tin_number',
                'sss_number',
                'pagibig_number',
                'philhealth_number',
                'gsis_number',
                'latest_performance_rating',
                'latest_performance_date',
                'training_hours_ytd',
                'last_promotion_date',
                'previous_position',
                'last_attendance_date',
                'consecutive_absent_days',
                'is_awol',
                'awol_start_date',
                'spouse_name',
                'spouse_occupation',
                'emergency_contact_name',
                'emergency_contact_relationship',
                'emergency_contact_number',
                'emergency_contact_address',
            ]);
        });
    }
};